<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilesController extends Controller
{
    public function listByBook(Request $request, int $bookId)
    {
        $includeCover = filter_var((string)$request->query('include_cover', 'false'), FILTER_VALIDATE_BOOLEAN);
        $q = File::where('book_id', $bookId)->orderBy('id');
        if (!$includeCover) {
            $q->where('format', '!=', 'cover');
        }
        return $q->get();
    }

    public function download(int $id)
    {
    $file = File::findOrFail($id);
    $diskName = $file->storage ?: config('filesystems.default');
    $disk = Storage::disk($diskName);
        if (!$disk->exists($file->path)) {
            abort(404, 'File missing');
        }
        $mime = $file->mime ?: 'application/octet-stream';
        $filename = basename($file->path);
        return new StreamedResponse(function() use ($disk, $file) {
            $stream = $disk->readStream($file->path);
            if ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"'
        ]);
    }

    public function destroy(int $id)
    {
        $file = File::findOrFail($id);
        // 仅删除记录，不删除物理文件，后续提供清理任务
        $file->delete();
        return response()->noContent();
    }

    // 预览（用于图片/封面等内联展示）
    public function preview(int $id)
    {
        $file = File::findOrFail($id);
        $diskName = $file->storage ?: config('filesystems.default');
        $disk = Storage::disk($diskName);
        if (!$disk->exists($file->path)) {
            abort(404, 'File missing');
        }
        $mime = $file->mime ?: 'application/octet-stream';
        $filename = basename($file->path);
        return new StreamedResponse(function() use ($disk, $file) {
            $stream = $disk->readStream($file->path);
            if ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"'
        ]);
    }
}
