<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\File as BookFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\QueryException;

class CoversController extends Controller
{
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
            return response()->json(['message' => 'Temp file missing'], 422);
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
        return response()->json(['file_id' => $file->id], 201);
    }

    // 通过 URL 抓取图片作为封面
    public function fromUrl(Request $request, int $bookId)
    {
        $book = Book::findOrFail($bookId);
        $data = $request->validate(['url' => ['required','url']]);
        $url = $data['url'];
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

        // 设置常用请求头
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
        ];
        if (str_contains($url, 'doubanio.com') || str_contains($url, 'book.douban.com')) {
            $headers['Referer'] = 'https://book.douban.com/';
        }

        try {
            $resp = Http::withHeaders($headers)->get($url);
            if (!$resp->ok()) {
                return response()->json(['message' => '下载图片失败'], 422);
            }
            $bin = $resp->body();
            if (!$bin) return response()->json(['message' => '图片内容为空'], 422);
            $contentType = strtolower($resp->header('Content-Type', 'image/jpeg'));
        } catch (\Throwable $e) {
            return response()->json(['message' => '下载图片失败'], 422);
        }

        $sha = hash('sha256', $bin);
        // 如果已有相同 sha 的封面文件，直接复用
        $existing = BookFile::where('sha256', $sha)->where('format', 'cover')->first();

        $ext = 'jpg';
        if (str_contains($contentType ?? '', 'png') || str_contains(strtolower($url), '.png')) $ext = 'png';
        elseif (str_contains($contentType ?? '', 'webp') || str_contains(strtolower($url), '.webp')) $ext = 'webp';

        if ($existing) {
            $file = $existing;
        } else {
            $a = Str::substr($sha, 0, 2);
            $b = Str::substr($sha, 2, 2);
            $safe = $sha.'.'.$ext;
            $relPath = "covers/{$a}/{$b}/{$sha}/{$safe}";
            $disk = Storage::disk('library');
            if (!$disk->exists($relPath)) {
                $disk->put($relPath, $bin);
            }

            try {
                $file = BookFile::create([
                    'book_id' => $book->id,
                    'format' => 'cover',
                    'size' => strlen($bin),
                    'mime' => match ($ext) {
                        'png' => 'image/png',
                        'webp' => 'image/webp',
                        default => 'image/jpeg'
                    },
                    'sha256' => $sha,
                    'path' => $relPath,
                    'storage' => 'library',
                    'pages' => null,
                ]);
            } catch (QueryException $qe) {
                // 并发唯一约束冲突：复用已存在记录
                if ((int) $qe->getCode() === 23000) {
                    $file = BookFile::where('sha256', $sha)->first();
                } else {
                    throw $qe;
                }
            }
        }

        if (!$file) {
            return response()->json(['message' => '保存失败'], 500);
        }

        $book->cover_file_id = $file->id;
        $book->save();
        return response()->json(['file_id' => $file->id], 201);
    }
}
