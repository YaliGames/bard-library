<?php

namespace App\Http\Controllers;

use App\Services\Metadata\DoubanMetadataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MetadataController extends Controller
{
    public function __construct(private DoubanMetadataService $douban) {}

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', DoubanMetadataService::SEARCH_CONCURRENCY);
        if ($q === '') {
            return response()->json(['error' => 'Missing q'], 422);
        }
        $urls = $this->douban->searchBooks($q, $limit);
        // Optionally prefetch details concurrently
        $books = $this->douban->fetchMany($urls);

        return response()->json(['query' => $q, 'count' => count($books), 'items' => $books]);
    }

    public function book(Request $request)
    {
        $url = (string) $request->query('url', '');
        $id = (string) $request->query('id', '');
        if ($id && ! $url) {
            $url = rtrim(DoubanMetadataService::BASE_URL, '/').'/subject/'.urlencode($id).'/';
        }
        if ($url === '') {
            return response()->json(['error' => 'Missing url or id'], 422);
        }
        $book = $this->douban->fetchBookByUrl($url);
        if (! $book) {
            return response()->json(['error' => 'Not found or parse failed'], 404);
        }

        return response()->json($book);
    }

    public function cover(Request $request)
    {
        $cover = (string) $request->query('cover', '');
        if ($cover === '') {
            return response()->json(['error' => 'Missing cover'], 422);
        }
        $resp = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            'Referer' => DoubanMetadataService::BASE_URL,
        ])->get($cover);

        if (! $resp->ok()) {
            return response()->json(['error' => 'Failed to fetch cover'], 502);
        }

        $contentType = $resp->header('Content-Type', 'image/jpeg');

        return response($resp->body(), 200, ['Content-Type' => $contentType]);
    }
}
