<?php

namespace App\Http\Controllers;

use App\Models\ReadingProgress;
use Illuminate\Http\Request;
use App\Services\OptionalUserResolver;

class ProgressController extends Controller
{
    public function __construct(private OptionalUserResolver $userResolver)
    {
    }
    public function show(Request $request, int $bookId)
    {
        $userId = $this->userResolver->id($request);
        if ($userId <= 0) {
            return response()->json(['code' => 0, 'message' => '未登录无法加载数据', 'data' => null]);
        }
        $p = ReadingProgress::where('user_id', $userId)->where('book_id', $bookId)->first();
        if ($p) return $p;
        return response()->json([
            'user_id' => $userId,
            'book_id' => $bookId,
            'file_id' => null,
            'progress' => 0,
            'location' => null,
        ]);
    }

    public function showWithFile(Request $request, int $bookId, int $fileId)
    {
        $userId = $this->userResolver->id($request);
        if ($userId <= 0) {
            return response()->json(['code' => 0, 'message' => '未登录无法加载数据', 'data' => null]);
        }
        $p = ReadingProgress::where('user_id', $userId)->where('book_id', $bookId)->where('file_id', $fileId)->first();
        if ($p) return $p;
        return response()->json([
            'user_id' => $userId,
            'book_id' => $bookId,
            'file_id' => $fileId,
            'progress' => 0,
            'location' => null,
        ]);
    }

    public function upsert(Request $request, int $bookId)
    {
        $userId = $this->userResolver->id($request);
        if ($userId <= 0) {
            return response()->json(['code' => 0, 'message' => '未登录无法加载数据', 'data' => null]);
        }
        $data = $request->validate([
            'file_id' => ['nullable','integer'],
            'progress' => ['nullable','numeric','min:0','max:1'],
            'location' => ['nullable','string','max:1024'],
        ]);
        $p = ReadingProgress::updateOrCreate(
            ['user_id' => $userId, 'book_id' => $bookId],
            [
                'file_id' => $data['file_id'] ?? null,
                'progress' => isset($data['progress']) ? (float)$data['progress'] : null,
                'location' => $data['location'] ?? null,
            ]
        );
        return $p;
    }

    public function upsertWithFile(Request $request, int $bookId, int $fileId)
    {
        $userId = $this->userResolver->id($request);
        if ($userId <= 0) {
            return response()->json(['code' => 0, 'message' => '未登录无法加载数据', 'data' => null]);
        }
        $data = $request->validate([
            'file_id' => ['nullable','integer'],
            'progress' => ['nullable','numeric','min:0','max:1'],
            'location' => ['nullable','string','max:1024'],
        ]);
        $useFileId = $fileId ?? ($data['file_id'] ?? null);
        $p = ReadingProgress::updateOrCreate(
            ['user_id' => $userId, 'book_id' => $bookId, 'file_id' => $useFileId],
            [
                'file_id' => $useFileId,
                'progress' => isset($data['progress']) ? (float)$data['progress'] : null,
                'location' => $data['location'] ?? null,
            ]
        );
        return $p;
    }
}
