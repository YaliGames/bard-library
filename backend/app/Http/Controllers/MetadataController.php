<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\Metadata\MetadataConfigLoader;
use App\Services\Metadata\MetadataScraper;

class MetadataController extends Controller
{
    /**
     * 列出支持的平台
     */
    public function providers(Request $request)
    {
        $loader = new MetadataConfigLoader();
        $ids = $loader->listPlatforms();
        $out = [];
        foreach ($ids as $id) {
            $cfg = $loader->load($id) ?? [];
            $out[] = [
                'id' => $cfg['id'] ?? $id,
                'name' => $cfg['name'] ?? $id,
                'description' => $cfg['description'] ?? '',
            ];
        }
        return response()->json($out);
    }

    /**
     * 搜索（返回若干候选）
     * @param Request $request 请求对象，支持参数 full（是否立即获取完整详情，默认false）
     * @param string $provider 提供商ID
     */
    public function search(Request $request, string $provider)
    {
        $q = trim((string)$request->query('q', ''));
        $limit = (int)$request->query('limit', 10);
        $full = filter_var($request->query('full', false), FILTER_VALIDATE_BOOLEAN);
        
        if ($q === '') return response()->json(['error' => 'Missing q'], 422);

        $loader = new MetadataConfigLoader();
        $cfg = $loader->load($provider);
        if (!$cfg) return response()->json(['error' => 'Provider not found'], 404);

        $scraper = new MetadataScraper($cfg);
        
        if ($full) {
            // 完整模式：立即获取详情页数据（向后兼容）
            $items = $scraper->searchItems($q, $limit);
        } else {
            // 预览模式：仅返回搜索页信息（推荐，减少请求）
            $items = $scraper->searchItemsWithPreview($q, $limit);
        }
        
        return response()->json([
            'query' => $q,
            'count' => count($items),
            'items' => $items,
            'preview_only' => !$full,
        ]);
    }

    /**
     * 批量获取详情页数据
     */
    public function batchDetails(Request $request, string $provider)
    {
        $validated = $request->validate([
            'urls' => 'required|array|min:1|max:50',
            'urls.*' => 'required|url',
        ]);

        $loader = new MetadataConfigLoader();
        $cfg = $loader->load($provider);
        if (!$cfg) return response()->json(['error' => 'Provider not found'], 404);

        $scraper = new MetadataScraper($cfg);
        $results = [];

        foreach ($validated['urls'] as $url) {
            try {
                $detail = $scraper->fetchDetail($url);
                $results[] = [
                    'url' => $url,
                    'success' => true,
                    'data' => $detail,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'url' => $url,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'total' => count($validated['urls']),
            'results' => $results,
        ]);
    }

    /**
     * 单本详情
     */
    public function book(Request $request, string $provider)
    {
        $loader = new MetadataConfigLoader();
        $cfg = $loader->load($provider);
        if (!$cfg) return response()->json(['error' => 'Provider not found'], 404);

        $url = (string)$request->query('url', '');
        $id = (string)$request->query('id', '');
        if ($id && !$url && !empty($cfg['base_url'])) {
            $url = rtrim($cfg['base_url'], '/') . '/' . ltrim($id, '/');
        }
        if ($url === '') return response()->json(['error' => 'Missing url or id'], 422);

        $scraper = new MetadataScraper($cfg);
        $book = $scraper->fetchDetail($url);
        if (!$book) return response()->json(['error' => 'Not found or parse failed'], 404);
        return response()->json($book);
    }

    /**
     * 代理封面
     */
    public function cover(Request $request, string $provider)
    {
        $cover = (string)$request->query('cover', '');
        if ($cover === '') return response()->json(['error' => 'Missing cover'], 422);
        $loader = new MetadataConfigLoader();
        $cfg = $loader->load($provider) ?? [];
        $referer = $cfg['base_url'] ?? null;

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
        ];
        if ($referer) $headers['Referer'] = $referer;

        $resp = Http::withHeaders($headers)->get($cover);
        if (!$resp->ok()) return response()->json(['error' => 'Failed to fetch cover'], 502);
        $contentType = $resp->header('Content-Type', 'image/jpeg');
        return response($resp->body(), 200, ['Content-Type' => $contentType]);
    }
}
