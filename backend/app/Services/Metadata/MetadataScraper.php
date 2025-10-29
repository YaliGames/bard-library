<?php

namespace App\Services\Metadata;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class MetadataScraper
{
    protected array $config;
    protected Client $client;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'headers' => $config['headers'] ?? [],
            'http_errors' => false,
            'timeout' => 10,
        ]);
    }

    /**
     * 兼容旧用法：仅返回首条详情
     */
    public function getMetadata(string $query): ?array
    {
        $urls = $this->searchDetailUrls($query, 1);
        if (empty($urls)) return null;
        return $this->fetchDetail($urls[0]);
    }

    /**
     * 搜索并返回前 N 条详情记录
     */
    public function searchItems(string $query, int $limit = 5): array
    {
        $urls = $this->searchDetailUrls($query, $limit);
        $items = [];
        foreach ($urls as $u) {
            try {
                $items[] = $this->fetchDetail($u);
            } catch (\Throwable $e) {
                // 跳过失败项
            }
        }
        return $items;
    }

    /**
     * 解析搜索页，提取前 N 个详情 URL
     */
    public function searchDetailUrls(string $query, int $limit = 5): array
    {
        $searchCfg = $this->config['search'] ?? null;
        if (!$searchCfg) return [];
        $params = $searchCfg['params'] ?? [];
        foreach ($params as $k => &$v) { $v = str_replace('$query', $query, (string)$v); }
        $enc = strtolower($this->config['encoding'] ?? 'utf-8');
        if (in_array($enc, ['gbk','gb2312'])) {
            foreach ($params as $k => &$v) {
                if (is_string($v) && function_exists('mb_convert_encoding')) {
                    $v = @mb_convert_encoding($v, 'GBK', 'UTF-8');
                }
            }
        }
        $method = strtoupper($searchCfg['method'] ?? 'GET');
        $url = $searchCfg['url'] ?? '';
        if (!$url) return [];
        $res = $this->client->request($method, $url, [ 'query' => $params ]);
        $html = (string)$res->getBody();
        if ($html !== '' && $enc !== 'utf-8' && function_exists('mb_convert_encoding')) {
            $to = 'UTF-8'; $from = strtoupper($enc);
            $html = @mb_convert_encoding($html, $to, $from);
        }
        $crawler = new Crawler($html);
        $listXpath = $searchCfg['result_list_xpath'] ?? '';
        if (!$listXpath) return [];
        $nodes = $crawler->filterXPath($listXpath);
        if ($nodes->count() === 0) return [];
        $attr = $searchCfg['detail_url_attr'] ?? 'href';
        $urls = [];
        foreach ($nodes as $n) {
            $node = new Crawler($n);
            $u = $node->attr($attr);
            if (!empty($searchCfg['detail_url_parse']) && $searchCfg['detail_url_parse'] === 'extract_redirect_target') {
                $u = $this->extractRedirectTarget($u);
            }
            // 规范化为绝对 URL
            if ($u) {
                if (str_starts_with($u, '//')) { $u = 'https:' . $u; }
                elseif (!preg_match('#^https?://#i', $u)) {
                    $base = rtrim((string)($this->config['base_url'] ?? ''), '/');
                    if ($base) { $u = $base . '/' . ltrim($u, '/'); }
                }
            }
            if ($u) {
                $urls[] = $u;
                if (count($urls) >= $limit) break;
            }
        }
        // 去重
        $urls = array_values(array_unique($urls));
        return $urls;
    }

    public function fetchDetail(string $url): array
    {
        $res = $this->client->request('GET', $url);
        $html = (string)$res->getBody();
        $enc = strtolower($this->config['encoding'] ?? 'utf-8');
        if ($html !== '' && $enc !== 'utf-8' && function_exists('mb_convert_encoding')) {
            $to = 'UTF-8'; $from = strtoupper($enc);
            $html = @mb_convert_encoding($html, $to, $from);
        }
        $crawler = new Crawler($html);
        $fields = $this->config['detail']['fields'] ?? [];
        $result = [];
        foreach ($fields as $field => $rule) {
            $value = null;
            $xpath = $rule['xpath'] ?? null;
            if ($xpath) {
                $nodes = $crawler->filterXPath($xpath);
                $isMulti = !empty($rule['multi']);
                if ($nodes->count() > 0) {
                    if ($isMulti) {
                        $vals = [];
                        foreach ($nodes as $n) {
                            $node = new Crawler($n);
                            $attr = $rule['attr'] ?? 'text';
                            $vals[] = ($attr !== 'text') ? $node->attr($attr) : trim($node->text());
                        }
                        $vals = array_values(array_unique(array_filter($vals, fn($v) => ($v !== null && $v !== ''))));
                        // 允许 pick=last 选取最后一个
                        $pick = $rule['pick'] ?? null; // 'last' | 'first'
                        if ($pick === 'last' && count($vals) > 0) {
                            $value = $vals[count($vals) - 1];
                        } else {
                            // 允许 join 指定分隔符拼接
                            $join = $rule['join'] ?? null;
                            $value = $join !== null ? implode((string)$join, $vals) : $vals;
                        }
                    } else {
                        $attr = $rule['attr'] ?? 'text';
                        $value = ($attr !== 'text') ? $nodes->first()->attr($attr) : trim($nodes->first()->text());
                    }
                }
            }
            if (isset($rule['filter'])) {
                $value = $this->applyFilter($rule['filter'], $value);
            }
            $result[$field] = $value;
        }
        // 通用补充和规范化
        $result['url'] = $url;
        if (!isset($result['description']) && isset($result['desc'])) { $result['description'] = $result['desc']; }
        if (!isset($result['publishedDate']) && isset($result['published_date'])) { $result['publishedDate'] = $result['published_date']; }
        if (isset($result['authors']) && !is_array($result['authors'])) {
            $a = trim((string)$result['authors']);
            $result['authors'] = $a === '' ? [] : preg_split('/[\s\/，,]+/u', $a);
        }
        // 如果 description 为空，尝试从 intro 最后一个，按段落 <p> 拼接兜底
        if (empty($result['description'])) {
            try {
                $introNodes = $crawler->filterXPath("//div[@id='link-report']//div[contains(@class,'intro')]");
                if ($introNodes->count() > 0) {
                    $lastNode = $introNodes->last();
                    // 优先取段落集合
                    $paras = $lastNode->filter('p');
                    if ($paras->count() > 0) {
                        $parts = [];
                        foreach ($paras as $p) {
                            $t = trim((new Crawler($p))->text());
                            if ($t !== '') $parts[] = $t;
                        }
                        if (!empty($parts)) {
                            $result['description'] = implode("\n\n", $parts);
                        }
                    }
                    // 否则退回整块文本
                    if (empty($result['description'])) {
                        $last = trim($lastNode->text());
                        if ($last !== '') $result['description'] = $last;
                    }
                }
            } catch (\Throwable $e) {}
        }
        // 如果 tags 为空，尝试 class=tag 的锚点兜底；再不行，尝试 criteria 变量解析
        if (empty($result['tags']) || (is_array($result['tags']) && count($result['tags']) === 0)) {
            try {
                $tagNodes = $crawler->filterXPath("//a[contains(concat(' ', normalize-space(@class), ' '), ' tag ')]");
                $tags = [];
                foreach ($tagNodes as $n) {
                    $t = trim((new Crawler($n))->text());
                    if ($t !== '') $tags[] = $t;
                }
                $tags = array_values(array_unique($tags));
                if (!empty($tags)) $result['tags'] = $tags;
            } catch (\Throwable $e) {}
            // criteria 变量回退
            if (empty($result['tags']) || (is_array($result['tags']) && count($result['tags']) === 0)) {
                if (preg_match("/criteria\s*=\s*'([^']+)'/", $html, $m)) {
                    $parts = explode('|', $m[1]);
                    $tags = [];
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if ($p !== '' && str_starts_with($p, '7:')) {
                            $tags[] = substr($p, 2);
                        }
                    }
                    if (!empty($tags)) $result['tags'] = array_values(array_unique($tags));
                }
            }
        }
        if (empty($result['id'])) {
            if (preg_match('/(\d{5,})/', $url, $m)) { $result['id'] = $m[1]; }
            else { $result['id'] = substr(md5($url), 0, 12); }
        }
        $result['source'] = [
            'id' => $this->config['id'] ?? 'unknown',
            'description' => $this->config['name'] ?? '',
            'link' => $url,
        ];
        return $result;
    }

    protected function extractRedirectTarget(?string $url): ?string
    {
        if (!$url) return $url;
        if (preg_match('/\?url=([^&]+)/', $url, $m)) {
            return urldecode($m[1]);
        }
        return $url;
    }

    protected function applyFilter(string $filter, $value)
    {
        if ($value === null) return $value;
        if (strpos($filter, 'floatval') !== false) { $value = floatval($value); }
        if (strpos($filter, '/2') !== false && is_numeric($value)) { $value = $value / 2; }
        if (strpos($filter, 'trim') !== false && is_string($value)) { $value = trim($value); }
        if (strpos($filter, 'normalizePublishDate') !== false && is_string($value)) { $value = $this->normalizePublishDate($value); }
        return $value;
    }

    protected function normalizePublishDate(string $v): string
    {
        return preg_replace('/[^0-9\-]/', '', $v);
    }
}
