<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Book;
use Illuminate\Http\Request;

class BookmarksController extends Controller
{
    public function list(Request $request, int $bookId)
    {
        $userId = (int)($request->user()->id ?? $request->query('user_id', 1));
        $bookId = (int)$bookId;
        $book = Book::find($bookId);
        $bm = Bookmark::where('user_id', $userId)->where('book_id', $bookId)->orderBy('id')->get();
        $response = ['book' => $book, 'bookmarks' => $bm];
        return $response;
    }

    public function listByFile(Request $request, int $bookId, int $fileId)
    {
        $userId = (int)($request->user()->id ?? $request->query('user_id', 1));
        $bookId = (int)$bookId;
        $book = Book::find($bookId);
        $bm = Bookmark::where('user_id', $userId)->where('book_id', $bookId)->where('file_id', $fileId)->orderBy('id')->get();
        $response = ['book' => $book, 'bookmarks' => $bm];
        return $response;
    }

    public function create(Request $request, int $bookId)
    {
        $userId = (int)($request->user()->id ?? $request->input('user_id', 1));
        $data = $request->validate([
            'file_id' => ['nullable','integer'],
            'location' => ['required','string','max:1024'],
            'note' => ['nullable','string'],
        ]);
        $bm = Bookmark::create([
            'user_id' => $userId,
            'book_id' => $bookId,
            'file_id' => $data['file_id'] ?? null,
            'location' => $data['location'],
            'note' => $data['note'] ?? null,
        ]);
        return response()->json($bm, 201);
    }

    public function createByFile(Request $request, int $bookId, int $fileId)
    {
        $userId = (int)($request->user()->id ?? $request->input('user_id', 1));
        $data = $request->validate([
            'location' => ['required','string','max:1024'],
            'note' => ['nullable','string'],
        ]);
        $bm = Bookmark::create([
            'user_id' => $userId,
            'book_id' => $bookId,
            'file_id' => $fileId,
            'location' => $data['location'],
            'note' => $data['note'] ?? null,
        ]);
        return response()->json($bm, 201);
    }

    public function update(Request $request, int $bookId, int $bookmarkId)
    {
        $userId = (int)($request->user()->id ?? $request->input('user_id', 1));
        $data = $request->validate([
            'note' => ['nullable','string'],
            'color' => ['nullable','string','max:16'],
        ]);
        $bm = Bookmark::where('id', $bookmarkId)
            ->where('book_id', $bookId)
            ->where('user_id', $userId)
            ->firstOrFail();
        if (array_key_exists('note', $data)) $bm->note = $data['note'];
        if (array_key_exists('color', $data)) $bm->color = $data['color'];
        $bm->save();
        return response()->json($bm);
    }

    public function delete(int $id, int $bookmarkId)
    {
        $bm = Bookmark::where('id', $bookmarkId)->where('book_id', $id)->firstOrFail();
        $bm->delete();
        return response()->noContent();
    }
}
