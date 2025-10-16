<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;

class AuthorsController extends Controller
{
    public function index(Request $request)
    {
        $q = Author::query();
        if ($kw = trim((string)$request->query('q'))) {
            $q->where('name', 'like', "%{$kw}%");
        }
        return $q->orderBy('name')->limit(50)->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:190'],
            'sort_name' => ['nullable','string','max:190'],
        ]);
        $a = Author::create($data);
        return response()->json($a, 201);
    }

    public function update(Request $request, int $id)
    {
        $a = Author::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes','string','max:190'],
            'sort_name' => ['sometimes','nullable','string','max:190'],
        ]);
        $a->fill($data)->save();
        return $a->refresh();
    }

    public function destroy(int $id)
    {
        $a = Author::findOrFail($id);
        // 解除关联
        $a->books()->detach();
        $a->delete();
        return response()->noContent();
    }
}
