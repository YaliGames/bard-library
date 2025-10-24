<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FilesAdminController extends Controller
{
    // 列表：支持关键字、格式、缺失物理文件、是否被作为封面使用、排序
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $format = (string) $request->query('format', '');
        $missing = filter_var((string) $request->query('missing_physical', 'false'), FILTER_VALIDATE_BOOLEAN);
        $onlyUnusedCovers = filter_var((string) $request->query('unused_covers', 'false'), FILTER_VALIDATE_BOOLEAN);
        $sortKey = (string) $request->query('sortKey', 'id');
        $sortOrder = strtolower((string) $request->query('sortOrder', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSort = ['id','format','size','mime','sha256','path','storage','book_id','created_at'];
        if (! in_array($sortKey, $allowedSort, true)) { $sortKey = 'id'; }

        $files = File::query();

        if ($q !== '') {
            $files->where(function($w) use ($q) {
                $w->where('path','like','%'.$q.'%')
                  ->orWhere('mime','like','%'.$q.'%')
                  ->orWhere('sha256','like','%'.$q.'%');
            });
        }
        if ($format !== '') {
            $files->where('format', $format);
        }

        if ($onlyUnusedCovers) {
            // 仅未被任何书籍引用为封面的 cover 文件
            $files->where('format','cover')
                  ->whereNotIn('id', Book::query()->whereNotNull('cover_file_id')->select('cover_file_id'));
        }

        $files->orderBy($sortKey, $sortOrder);

        // 载入关联书籍与是否被引用为封面
        $items = $files->get()->map(function(File $f) use ($missing) {
            $book = $f->book_id ? Book::find($f->book_id, ['id','title','cover_file_id']) : null;
            $disk = Storage::disk($f->storage ?: config('filesystems.default'));
            $exists = $disk->exists($f->path);
            return [
                'id' => $f->id,
                'book_id' => $f->book_id,
                'book' => $book ? [ 'id' => $book->id, 'title' => $book->title ] : null,
                'used_as_cover' => $book ? ($book->cover_file_id === $f->id) : false,
                'format' => $f->format,
                'size' => $f->size,
                'mime' => $f->mime,
                'sha256' => $f->sha256,
                'filename' => basename($f->path),
                'path' => $f->path,
                'storage' => $f->storage,
                'created_at' => $f->created_at,
                'physical_exists' => $exists,
            ];
        });

        if ($missing) {
            $items = $items->filter(fn($it) => $it['physical_exists'] === false)->values();
        }

        return response()->json([ 'count' => $items->count(), 'items' => $items ]);
    }

    // 删除：支持 ?physical=true 同时删除物理文件
    public function destroy(Request $request, int $id)
    {
        $physical = filter_var((string) $request->query('physical','false'), FILTER_VALIDATE_BOOLEAN);
        $file = File::findOrFail($id);
        // 若被某书作为封面使用，则先解除引用
        Book::where('cover_file_id', $file->id)->update(['cover_file_id' => null]);
        if ($physical) {
            $disk = Storage::disk($file->storage ?: config('filesystems.default'));
            if ($disk->exists($file->path)) { $disk->delete($file->path); }
        }
        $file->delete();
        return response()->noContent();
    }

    // 清理：unused covers / dangling records / 物理缺失
    public function cleanup(Request $request)
    {
        $kind = (string) $request->input('kind', 'covers'); // covers|all|orphans (兼容)
        $kinds = $request->input('kinds'); // 可选数组：['covers','dangling','missing','orphans']
        $dry = filter_var((string) $request->input('dry', 'true'), FILTER_VALIDATE_BOOLEAN);
        $removePhysical = filter_var((string) $request->input('removePhysical','false'), FILTER_VALIDATE_BOOLEAN);

        $removed = ['covers' => [], 'dangling' => [], 'missing_physical' => [], 'orphans_physical' => []];

        // 未被引用为封面的 cover 文件
        $unusedCovers = File::where('format','cover')
            ->whereNotIn('id', Book::query()->whereNotNull('cover_file_id')->select('cover_file_id'))
            ->get();

        // 悬挂记录：文件记录关联到不存在的书籍
        $dangling = File::leftJoin('books','files.book_id','=','books.id')
            ->whereNull('books.id')
            ->select('files.*')
            ->get();

        // 物理缺失
        $missing = collect();
        foreach (File::all() as $f) {
            $disk = Storage::disk($f->storage ?: config('filesystems.default'));
            if (! $disk->exists($f->path)) { $missing->push($f); }
        }

        // 磁盘孤儿文件：磁盘存在，但 files 表里没有对应 path 的记录
        $disk = Storage::disk(config('filesystems.default'));
        // 仅扫描 library 盘（与本项目一致），且限定在 books/ 与 covers/ 目录
        $libDisk = Storage::disk('library');
        $diskFiles = collect($libDisk->allFiles('books'))
            ->merge($libDisk->allFiles('covers'))
            ->values();
        $dbPaths = File::pluck('path')->all();
        $dbSet = array_fill_keys($dbPaths, true);
        $orphans = $diskFiles->filter(function($p) use ($dbSet) {
            return !isset($dbSet[$p]);
        })->values();

        // 允许按复选组合进行处理
        $useKinds = [];
        if (is_array($kinds)) {
            $kinds = array_map('strval', $kinds);
            $useKinds = array_fill_keys($kinds, true);
        } else {
            // 兼容旧的 kind 单选
            if ($kind === 'all') { $useKinds = ['covers'=>true,'dangling'=>true,'missing'=>true]; }
            elseif ($kind === 'covers') { $useKinds = ['covers'=>true]; }
            elseif ($kind === 'orphans') { $useKinds = ['orphans'=>true]; }
            else { $useKinds = ['covers'=>true]; }
        }

        $summary = [
            'unused_covers' => $unusedCovers->count(),
            'dangling_records' => $dangling->count(),
            'missing_physical' => $missing->count(),
            'orphans_physical' => $orphans->count(),
        ];

        if ($dry) {
            // 预览列表：返回匹配清理项的详细条目
            $preview = [];
            if (!empty($useKinds['covers'])) {
                $preview['covers'] = $unusedCovers->map(fn($f) => [
                    'id' => $f->id,
                    'book_id' => $f->book_id,
                    'path' => $f->path,
                    'storage' => $f->storage,
                    'format' => $f->format,
                    'size' => $f->size,
                    'mime' => $f->mime,
                ])->values();
            }
            if (!empty($useKinds['dangling'])) {
                $preview['dangling'] = $dangling->map(fn($f) => [
                    'id' => $f->id,
                    'book_id' => $f->book_id,
                    'path' => $f->path,
                    'storage' => $f->storage,
                    'format' => $f->format,
                    'size' => $f->size,
                    'mime' => $f->mime,
                ])->values();
            }
            if (!empty($useKinds['missing'])) {
                $preview['missing_physical'] = $missing->map(fn($f) => [
                    'id' => $f->id,
                    'book_id' => $f->book_id,
                    'path' => $f->path,
                    'storage' => $f->storage,
                    'format' => $f->format,
                    'size' => $f->size,
                    'mime' => $f->mime,
                ])->values();
            }
            if (!empty($useKinds['orphans'])) {
                $preview['orphans_physical'] = $orphans->map(fn($p) => [
                    'path' => $p,
                    'storage' => 'library',
                ])->values();
            }
            return response()->json(['dry' => true, 'summary' => $summary, 'preview' => $preview]);
        }

        // 执行删除
        $doDelete = function($collection) use ($removePhysical, &$removed) {
            foreach ($collection as $f) {
                /** @var File $f */
                if ($removePhysical) {
                    $disk = Storage::disk($f->storage ?: config('filesystems.default'));
                    if ($disk->exists($f->path)) { $disk->delete($f->path); }
                }
                Book::where('cover_file_id', $f->id)->update(['cover_file_id' => null]);
                $removed[] = $f->id;
                $f->delete();
            }
        };

        if (!empty($useKinds['covers'])) {
            $list = $unusedCovers;
            $tmp = [];
            foreach ($list as $f) { $tmp[] = $f; }
            $removed['covers'] = [];
            foreach ($tmp as $f) {
                /** @var File $f */
                if ($removePhysical) {
                    $disk = Storage::disk($f->storage ?: config('filesystems.default'));
                    if ($disk->exists($f->path)) { $disk->delete($f->path); }
                }
                $removed['covers'][] = $f->id;
                $f->delete();
            }
        }

        if (!empty($useKinds['dangling']) || !empty($useKinds['missing'])) {
            $removed['dangling'] = [];
            if (!empty($useKinds['dangling'])) foreach ($dangling as $f) {
                /** @var File $f */
                if ($removePhysical) {
                    $disk = Storage::disk($f->storage ?: config('filesystems.default'));
                    if ($disk->exists($f->path)) { $disk->delete($f->path); }
                }
                $removed['dangling'][] = $f->id;
                $f->delete();
            }

            $removed['missing_physical'] = [];
            if (!empty($useKinds['missing'])) foreach ($missing as $f) {
                /** @var File $f */
                // 物理缺失只需要删除记录
                $removed['missing_physical'][] = $f->id;
                $f->delete();
            }
        }

        if (!empty($useKinds['orphans'])) {
            $removed['orphans_physical'] = [];
            foreach ($orphans as $path) {
                if ($removePhysical) {
                    if ($libDisk->exists($path)) { $libDisk->delete($path); }
                    $removed['orphans_physical'][] = $path;
                }
            }
        }

        return response()->json(['dry' => false, 'summary' => $summary, 'removed' => $removed]);
    }
}
