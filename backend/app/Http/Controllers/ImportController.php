<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\File as BookFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function upload(Request $request)
    {
        $data = $request->validate([
            'file' => ['required','file','max:524288'], // 512 MB 上限，可按需调整
            'book_id' => ['nullable','integer'],
            'parse_txt' => ['nullable','boolean'],
        ]);

        $uploaded = $data['file'];
        $tmpPath = $uploaded->getRealPath();
        if (!$tmpPath || !file_exists($tmpPath)) {
            return response()->json(['message' => 'Temp file missing'], 422);
        }

        $sha = hash_file('sha256', $tmpPath);
        // 去重：已存在则直接返回
        $existing = BookFile::where('sha256', $sha)->first();
        if ($existing) {
            $book = Book::find($existing->book_id);
            return response()->json([
                'created' => false,
                'book' => $book,
                'file' => $existing,
                'duplicate' => true,
            ]);
        }

        $origName = $uploaded->getClientOriginalName() ?: ($sha . '.' . ($uploaded->extension() ?: 'bin'));
        $safeName = $this->sanitizeFilename($origName);
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $format = in_array($ext, ['epub','pdf','txt']) ? $ext : $ext;
        $mime = $uploaded->getClientMimeType() ?: 'application/octet-stream';
        $size = (int) $uploaded->getSize();

        // 目录分层：books/a1/b2/sha/filename.ext
        $a = Str::substr($sha, 0, 2);
        $b = Str::substr($sha, 2, 2);
        $relPath = "books/{$a}/{$b}/{$sha}/{$safeName}";

    // 强制写入到 library 盘，避免默认盘配置导致落到 storage/app
    $disk = Storage::disk('library');
        // 确保目录存在，使用 putStream 写入
        $stream = fopen($tmpPath, 'r');
        $disk->put($relPath, $stream);
        if (is_resource($stream)) fclose($stream);

        // 归属到指定书籍或新建
        if (!empty($data['book_id'])) {
            $book = Book::find((int)$data['book_id']);
            if (!$book) {
                return response()->json(['message' => 'book_id not found'], 422);
            }
        } else {
            // 简单创建 Book（标题来源于文件名去掉扩展名）
            $title = trim(Str::beforeLast($safeName, '.' . $ext)) ?: $safeName;
            $book = Book::create(['title' => $title]);
        }

        // 创建 File 记录
        $file = BookFile::create([
            'book_id' => $book->id,
            'format' => $format ?: 'bin',
            'size' => $size,
            'mime' => $mime,
            'sha256' => $sha,
            'path' => $relPath,
            // 记录存储磁盘，后续下载/处理将以此选择磁盘
            'storage' => 'library',
            'pages' => null,
        ]);

        $response = [
            'created' => true,
            'book' => $book,
            'file' => $file,
        ];

        // 可选：立即解析 TXT 章节并返回章节数量
        if (strtolower($format) === 'txt' && ($request->boolean('parse_txt'))) {
            try {
                $chapters = app(\App\Http\Controllers\TxtController::class)->chapters($request, $file->id);
                if ($chapters instanceof \Illuminate\Http\JsonResponse) {
                    // 忽略错误
                } else {
                    $response['txt_chapter_count'] = is_countable($chapters) ? count($chapters) : null;
                }
            } catch (\Throwable $e) {
                // 忽略解析错误，前端可后续单独触发
            }
        }

        return response()->json($response, 201);
    }

    private function sanitizeFilename(string $name): string
    {
        // 替换不安全字符，保留常见文件名字符
        $name = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name) ?: 'file';
        return trim($name);
    }
}
