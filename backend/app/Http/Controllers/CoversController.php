<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\File as BookFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            $bin = file_get_contents($url);
        } catch (\Throwable $e) {
            return response()->json(['message' => '下载图片失败'], 422);
        }
        if (!$bin) return response()->json(['message' => '图片内容为空'], 422);

        $sha = hash('sha256', $bin);
        $a = Str::substr($sha, 0, 2);
        $b = Str::substr($sha, 2, 2);
        $ext = 'jpg';
        if (str_contains(strtolower($url), '.png')) $ext = 'png';
        elseif (str_contains(strtolower($url), '.webp')) $ext = 'webp';
        $safe = $sha.'.'.$ext;
        $relPath = "covers/{$a}/{$b}/{$sha}/{$safe}";
        $disk = Storage::disk('library');
        $disk->put($relPath, $bin);

        $file = BookFile::create([
            'book_id' => $book->id,
            'format' => 'cover',
            'size' => strlen($bin),
            'mime' => match($ext){
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'image/jpeg'
            },
            'sha256' => $sha,
            'path' => $relPath,
            'storage' => 'library',
            'pages' => null,
        ]);
        $book->cover_file_id = $file->id;
        $book->save();
        return response()->json(['file_id' => $file->id], 201);
    }
}
