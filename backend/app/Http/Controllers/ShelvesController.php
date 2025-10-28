<?php

namespace App\Http\Controllers;

use App\Models\Shelf;
use App\Models\Book;
use Illuminate\Http\Request;

class ShelvesController extends Controller
{
    public function index(Request $request)
    {
        $q = Shelf::query();
        if ($kw = trim((string)$request->query('q'))) {
            $q->where('name', 'like', "%{$kw}%");
        }
        return $q->orderBy('name')->paginate(min(max((int)$request->query('per_page', 20),1),100));
    }

    // 返回全部书架（不分页），用于前端筛选列表等场景
    public function all(Request $request)
    {
        $q = Shelf::query();
        if ($kw = trim((string)$request->query('q'))) {
            $q->where('name', 'like', "%{$kw}%");
        }
        return $q->orderBy('name')->get();
    }

    /**
     * 返回所有书架并附带每个书架的若干书籍摘要，用于书架导览页面
     * 可通过 ?limit=5 控制每个书架包含的书籍数量
     */
    public function summaryAll(Request $request)
    {
        $limit = min(max((int)$request->query('limit', 5), 1), 50);
        $q = Shelf::query();
        if ($kw = trim((string)$request->query('q'))) {
            $q->where('name', 'like', "%{$kw}%");
        }
        $shelves = $q->orderBy('name')->get();
        $result = $shelves->map(function ($s) use ($limit) {
            $books = $s->books()->select('books.id', 'books.title')->orderBy('books.id', 'desc')->limit($limit)->get();
            return [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'books' => $books,
            ];
        });
        return $result;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:190'],
            'description' => ['nullable','string','max:500'],
        ]);
        $s = Shelf::create($data);
        return response()->json($s, 201);
    }

    public function update(Request $request, int $id)
    {
        $s = Shelf::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes','string','max:190'],
            'description' => ['sometimes','nullable','string','max:500'],
        ]);
        $s->fill($data)->save();
        return $s->refresh();
    }

    public function destroy(int $id)
    {
        $s = Shelf::findOrFail($id);
        $s->books()->detach();
        $s->delete();
        return response()->noContent();
    }

    public function setBooks(Request $request, int $id)
    {
        $s = Shelf::findOrFail($id);
        $payload = $request->validate([
            'book_ids' => ['required','array'],
            'book_ids.*' => ['integer'],
        ]);
        $s->books()->sync($payload['book_ids']);
        return $s->load('books');
    }

    public function setShelvesForBook(Request $request, int $bookId)
    {
        $b = Book::findOrFail($bookId);
        $payload = $request->validate([
            'shelf_ids' => ['required','array'],
            'shelf_ids.*' => ['integer'],
        ]);
        $b->shelves()->sync($payload['shelf_ids']);
        return $b->load('shelves');
    }
}
