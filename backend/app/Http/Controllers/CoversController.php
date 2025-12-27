<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\File as BookFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\QueryException;
use App\Services\BookCreationService;
use App\Support\ApiHelpers;

class CoversController extends Controller
{
    public function __construct(private BookCreationService $bookService) {}
    // 上传图片作为封面
    public function upload(Request $request, int $bookId)
    {
        $book = Book::findOrFail($bookId);
        $data = $request->validate([
            'file' => ['required','image','max:10240'], // 10MB
        ]);
        $uploaded = $data['file'];
        $tmpPath = $uploaded->getRealPath();
        if (!$tmpPath || !file_exists($tmpPath)) {
            return ApiHelpers::error('Temp file missing', 422);
        }

        $sha = hash_file('sha256', $tmpPath);
        $a = Str::substr($sha, 0, 2);
        $b = Str::substr($sha, 2, 2);
        $ext = strtolower($uploaded->extension() ?: 'jpg');
        $safe = $sha.'.'.$ext;
        $relPath = "covers/{$a}/{$b}/{$sha}/{$safe}";
        $disk = Storage::disk('library');
        $stream = fopen($tmpPath, 'r');
        $disk->put($relPath, $stream);
        if (is_resource($stream)) fclose($stream);

        $file = BookFile::create([
            'book_id' => $book->id,
            'format' => 'cover',
            'size' => (int) $uploaded->getSize(),
            'mime' => $uploaded->getClientMimeType() ?: 'image/jpeg',
            'sha256' => $sha,
            'path' => $relPath,
            'storage' => 'library',
            'pages' => null,
        ]);

        $book->cover_file_id = $file->id;
        $book->save();
        return ApiHelpers::success(['file_id' => $file->id], 'Cover uploaded', 201);
    }

    // 通过 URL 抓取图片作为封面
    public function fromUrl(Request $request, int $bookId)
    {
        $book = Book::findOrFail($bookId);
        $data = $request->validate(['url' => ['required','url']]);
        $url = $data['url'];
        
        // 处理元数据 API 代理 URL（解析实际封面 URL）
        try {
            $appHost = parse_url(config('app.url') ?: $request->getSchemeAndHttpHost(), PHP_URL_HOST);
            $target = parse_url($url);
            $targetHost = $target['host'] ?? null;
            $targetPath = $target['path'] ?? '';
            if ($targetHost && $appHost && strcasecmp($targetHost, $appHost) === 0 && str_contains($targetPath, '/api/v1/metadata/')) {
                parse_str($target['query'] ?? '', $qs);
                if (!empty($qs['cover']) && filter_var($qs['cover'], FILTER_VALIDATE_URL)) {
                    $url = $qs['cover'];
                }
            }
        } catch (\Throwable $e) {}

        // 使用 BookCreationService 下载并附加封面
        $success = $this->bookService->downloadAndAttachCover($book, $url);
        
        if (!$success) {
            return ApiHelpers::error('下载或保存封面失败', 422);
        }

        // 刷新图书以获取最新的 cover_file_id
        $book->refresh();
        
        return ApiHelpers::success(['file_id' => $book->cover_file_id], 'Cover set from URL', 201);
    }

    // 清除书籍封面关联（不删除底层文件记录，避免影响其他书籍复用）
    public function clear(int $bookId)
    {
        $book = Book::findOrFail($bookId);
        $book->cover_file_id = null;
        $book->save();
        return ApiHelpers::success(null, 'Cover cleared', 200);
    }
}
