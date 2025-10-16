<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagsController extends Controller
{
    public function index(Request $request)
    {
        $q = Tag::query();
        if ($kw = trim((string)$request->query('q'))) {
            $q->where('name', 'like', "%{$kw}%");
        }
        if ($type = $request->query('type')) {
            $q->where('type', $type);
        }
        return $q->orderBy('name')->limit(50)->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:64'],
            'type' => ['nullable','string','max:32'],
        ]);
        $t = Tag::firstOrCreate(['name' => $data['name'], 'type' => $data['type'] ?? null]);
        return response()->json($t, 201);
    }

    public function update(Request $request, int $id)
    {
        $t = Tag::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes','string','max:64'],
            'type' => ['sometimes','nullable','string','max:32'],
        ]);
        $t->fill($data)->save();
        return $t->refresh();
    }

    public function destroy(int $id)
    {
        $t = Tag::findOrFail($id);
        // 解除关联
        $t->books()->detach();
        $t->delete();
        return response()->noContent();
    }
}
