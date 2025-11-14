<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function download(Request $request, int $id)
    {
        $file = File::findOrFail($id);
        
        // Laravel 标准认证
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return response()->json([
                'code' => 401,
                'data' => null,
                'message' => '需要登录才能下载文件'
            ], 401);
        }
        
        $user = $request->user();
        
        // 检查是否有下载权限
        if (!$user->hasPermission('books.download')) {
            return response()->json([
                'code' => 403,
                'data' => null,
                'message' => '没有权限下载文件'
            ], 403);
        }
        
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
    public function preview(Request $request, int $id)
    {
        $file = File::findOrFail($id);
        
        // 如果是封面图片,允许公开访问
        $isCover = $file->type === 'cover' || 
                   str_starts_with($file->path, 'covers/') ||
                   str_contains($file->mime ?? '', 'image/');
        
        // 非封面文件需要权限检查
        if (!$isCover) {
            // Laravel 标准认证
            if (!\Illuminate\Support\Facades\Auth::check()) {
                return response()->json([
                    'code' => 401,
                    'data' => null,
                    'message' => '需要登录才能访问此文件'
                ], 401);
            }
            
            $user = $request->user();
            
            // 检查是否有下载权限
            if (!$user->hasPermission('books.download')) {
                return response()->json([
                    'code' => 403,
                    'data' => null,
                    'message' => '没有权限访问此文件'
                ], 403);
            }
        }
        
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

    // 全局资源访问令牌（可用于所有预览/下载/代理封面等资源访问），默认 10 分钟
    public function accessToken(Request $request)
    {
        $minutes = max(1, min(60, intval($request->query('ttl', 10))));
        $exp = time() + ($minutes * 60);
        $payload = json_encode(['exp' => $exp], JSON_UNESCAPED_SLASHES);
        $p64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $sig = $this->hmac($p64);
        $token = $p64 . '.' . $sig;
        return response()->json(['token' => $token, 'expires_in' => $minutes * 60]);
    }

    protected function hmac(string $data): string
    {
        $key = config('app.key');
        if (is_string($key) && Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }
        $key = is_string($key) ? $key : '';
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $data, $key, true)), '+/', '-_'), '=');
    }
}
