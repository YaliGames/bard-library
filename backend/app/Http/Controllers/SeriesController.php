<?php

namespace App\Http\Controllers;

use App\Models\Series;
use Illuminate\Http\Request;
use App\Support\ApiHelpers;

class SeriesController extends Controller
{
    public function index(Request $request)
    {
        $q = Series::query();
        if ($kw = trim((string)$request->query('q'))) {
            $q->where('name', 'like', "%{$kw}%");
        }
        return ApiHelpers::success($q->orderBy('name')->limit(50)->get(), '', 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:190']
        ]);
        $s = Series::firstOrCreate(['name' => $data['name']]);
        return ApiHelpers::success($s, '', 201);
    }
}
