<?php

namespace App\Services\Metadata;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DoubanMetadataService
{
    const SEARCH_URL = 'https://www.douban.com/search';

    const BASE_URL = 'https://book.douban.com/';

    const COVER_DOMAIN = 'doubanio.com';

    const BOOK_CAT = '1001';

    const SEARCH_CONCURRENCY = 5;

    const CACHE_TTL_MINUTES = 60 * 12; // 12h

    protected array $headers = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
        'Accept-Encoding' => 'gzip, deflate',
        'Referer' => self::BASE_URL,
    ];

    protected bool $verify;

    public function __construct()
    {
        $this->verify = (bool) (env('DOUBAN_VERIFY_SSL', true));
    }

    public function searchBooks(string $query, int $limit = self::SEARCH_CONCURRENCY): array
    {
        $resp = $this->request()
            ->get(self::SEARCH_URL, ['cat' => self::BOOK_CAT, 'q' => $query]);

        if (! $resp->ok()) {
            return [];
        }

        $html = $resp->body();
        $urls = $this->extractBookUrlsFromSearchHtml($html, $limit);

        return array_values(array_unique($urls));
    }

    public function fetchBookByUrl(string $url): ?array
    {
        $id = $this->extractIdFromUrl($url);
        if (! $id) {
            return null;
        }
        $cacheKey = 'douban:book:'.$id;

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($url) {
            $resp = $this->request()->get($url);
            if (! $resp->ok()) {
                return null;
            }

            return $this->parseBookHtml($url, $resp->body());
        });
    }

    public function fetchMany(array $urls): array
    {
        $urls = array_values(array_filter($urls));
        if (empty($urls)) {
            return [];
        }

        // Simple concurrency using pools
        $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($urls) {
            $requests = [];
            foreach ($urls as $i => $url) {
                $requests[] = $pool->as((string) $i)->withHeaders($this->headers)->withOptions(['verify' => $this->verify])->get($url);
            }

            return $requests;
        });

        $books = [];
        foreach ($responses as $i => $resp) {
            $url = $urls[(int) $i] ?? null;
            if ($url && optional($resp)->ok()) {
                $books[] = $this->parseBookHtml($url, $resp->body());
            }
        }

        return array_values(array_filter($books));
    }

    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders($this->headers)->withOptions(['verify' => $this->verify]);
    }

    protected function extractBookUrlsFromSearchHtml(string $html, int $limit): array
    {
        $urls = [];
        // Find anchors with class="nbg" and parse their href ?url= parameter
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " nbg ")]') as $a) {
            /** @var \DOMElement $a */
            $href = $a->getAttribute('href');
            if (! $href) {
                continue;
            }
            $parsed = $this->extractRedirectTargetUrl($href);
            if ($parsed && $this->isSubjectUrl($parsed)) {
                $urls[] = $parsed;
                if (count($urls) >= $limit) {
                    break;
                }
            }
        }

        return $urls;
    }

    protected function extractRedirectTargetUrl(string $href): ?string
    {
        $parts = parse_url($href);
        if (! isset($parts['query'])) {
            return null;
        }
        parse_str($parts['query'] ?? '', $q);
        $url = $q['url'] ?? null;
        if ($url) {
            $url = urldecode($url);
        }

        return $url ?: null;
    }

    protected function isSubjectUrl(string $url): bool
    {
        return (bool) preg_match('#.*/subject/(\d+)/?#', $url);
    }

    protected function extractIdFromUrl(string $url): ?string
    {
        if (preg_match('#.*/subject/(\d+)/?#', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    protected function text(?\DOMNodeList $list, string $default = ''): string
    {
        if (! $list || $list->length === 0) {
            return $default;
        }
        $node = $list->item(0);
        $t = trim($node->textContent ?? '');

        return $t !== '' ? $t : $default;
    }

    protected function parseBookHtml(string $url, string $html): ?array
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xp = new \DOMXPath($dom);

        $title = $this->text($xp->query("//span[@property='v:itemreviewed']"));
        if ($title === '') {
            // Not a valid book page
            return null;
        }

        $share = $xp->query('//a[@data-url]');
        $shareUrl = $url;
        if ($share->length) {
            $node = $share->item(0);
            if ($node instanceof \DOMElement) {
                $shareUrl = $node->getAttribute('data-url') ?: $url;
            }
        }

        // Cover
        $cover = '';
        $imgA = $xp->query("//a[@class='nbg']");
        if ($imgA->length) {
            $node = $imgA->item(0);
            if ($node instanceof \DOMElement) {
                $cover = $node->getAttribute('href') ?: '';
            }
            if (! $cover || str_ends_with($cover, 'update_image')) {
                $cover = '';
            }
        }

        // Rating (0-10); normalize to 0-5 for convenience
        $ratingRaw = $this->text($xp->query("//strong[@property='v:average']"), '0');
        $rating10 = is_numeric($ratingRaw) ? (float) $ratingRaw : 0.0;
        $rating5 = $rating10 / 2.0;

        $authors = [];
        $publisher = '';
        $series = '';
        $publishedDate = '';
        $identifiers = [];

        foreach ($xp->query("//span[@class='pl']") as $el) {
            /** @var \DOMElement $el */
            $label = trim($el->textContent ?? '');
            if (str_starts_with($label, '作者') || str_starts_with($label, '译者')) {
                // Find following sibling links
                $parent = $el->parentNode; // dt or span container
                if ($parent instanceof \DOMElement) {
                    foreach ($parent->getElementsByTagName('a') as $a) {
                        $href = $a->getAttribute('href');
                        if (str_contains($href, '/author') || str_contains($href, '/search')) {
                            $name = trim($a->textContent ?? '');
                            if ($name !== '') {
                                $authors[] = $name;
                            }
                        }
                    }
                }
            } elseif (str_starts_with($label, '出版社')) {
                $publisher = $this->tailText($el) ?: $publisher;
            } elseif (str_starts_with($label, '副标题')) {
                $sub = $this->tailText($el);
                if ($sub !== '') {
                    $title .= ':'.$sub;
                }
            } elseif (str_starts_with($label, '出版年')) {
                $publishedDate = $this->normalizePublishDate($this->tailText($el));
            } elseif (str_starts_with($label, '丛书')) {
                $next = $el->nextSibling;
                $series = trim(($next instanceof \DOMElement ? $next->textContent : '') ?? '') ?: $series;
            } elseif (str_starts_with($label, 'ISBN')) {
                $isbn = $this->tailText($el);
                if ($isbn !== '') {
                    $identifiers['isbn'] = $isbn;
                }
            }
        }

        // Summary
        $desc = '';
        $introDivs = $xp->query("//div[@id='link-report']//div[@class='intro']");
        if ($introDivs->length) {
            $desc = trim($introDivs->item($introDivs->length - 1)->textContent ?? '');
        }

        // Tags
        $tags = [];
        foreach ($xp->query("//a[contains(@class, 'tag')]") as $a) {
            $t = trim($a->textContent ?? '');
            if ($t !== '') {
                $tags[] = $t;
            }
        }
        if (empty($tags)) {
            // Fallback parse from embedded criteria variable
            if (preg_match("/criteria\s*=\s*'([^']+)'/", $html, $m)) {
                $parts = explode('|', $m[1]);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p !== '' && str_starts_with($p, '7:')) {
                        $tags[] = substr($p, 2);
                    }
                }
            }
        }

        $id = $this->extractIdFromUrl($shareUrl ?: $url) ?? $this->extractIdFromUrl($url);

        return [
            'id' => $id,
            'title' => $title,
            'authors' => array_values(array_unique($authors)),
            'publisher' => $publisher,
            'description' => $desc,
            'url' => $shareUrl ?: $url,
            'cover' => $cover,
            'rating' => $rating5,
            'publishedDate' => $publishedDate,
            'series' => $series,
            'tags' => $tags,
            'identifiers' => $identifiers,
            'source' => [
                'id' => 'new_douban',
                'description' => 'New Douban Books',
                'link' => self::BASE_URL,
            ],
        ];
    }

    protected function tailText(\DOMElement $el, string $default = ''): string
    {
        $text = trim($el->nextSibling?->textContent ?? '');

        return $text !== '' ? $text : $default;
    }

    protected function normalizePublishDate(?string $date): string
    {
        $date = trim($date ?? '');
        if ($date === '') {
            return '';
        }
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $date, $m)) {
            return sprintf('%04d-%02d-01', (int) $m[1], (int) $m[2]);
        }

        return $date;
    }
}
