<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarkController extends Controller
{
    public function setRead(Request $request, int $bookId)
    {
        $userId = (int)($request->user()->id ?? 0);
        if (!$userId) return response()->json(['message' => 'Unauthorized'], 401);
        $data = $request->validate([
            'is_read' => ['required', 'boolean']
        ]);
        DB::table('user_read_marks')->updateOrInsert(
            ['user_id' => $userId, 'book_id' => $bookId],
            ['is_read' => $data['is_read'], 'updated_at' => now(), 'created_at' => now()]
        );
        return ['ok' => true];
    }
}
