<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use App\Support\ApiHelpers;

class AuthorsController extends Controller
{
    public function index(Request $request)
    {
        $q = Author::query();
        if ($kw = trim((string)$request->query('q'))) {
            $q->where('name', 'like', "%{$kw}%");
        }
        return ApiHelpers::success($q->orderBy('name')->limit(50)->get(), '', 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:190'],
            'sort_name' => ['nullable','string','max:190'],
        ]);
        $a = Author::create($data);
        return ApiHelpers::success($a, 'Author created', 201);
    }

    public function update(Request $request, int $id)
    {
        $a = Author::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes','string','max:190'],
            'sort_name' => ['sometimes','nullable','string','max:190'],
        ]);
        $a->fill($data)->save();
        return ApiHelpers::success($a->refresh(), 'Author updated', 200);
    }

    public function destroy(int $id)
    {
        $a = Author::findOrFail($id);
        // 解除关联
        $a->books()->detach();
        $a->delete();
        return ApiHelpers::success(null, 'Author deleted', 200);
    }
}
