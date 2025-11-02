<?php

namespace App\Http\Controllers;

use App\Models\Shelf;
use App\Models\Book;
use Illuminate\Http\Request;
use App\Services\OptionalUserResolver;

class ShelvesController extends Controller
{
    public function __construct(private OptionalUserResolver $userResolver)
    {
    }
    public function show(Request $request, int $id)
    {
        $s = Shelf::with(['books.authors'])->findOrFail($id);
        $user = $this->userResolver->user($request);
        $isAdmin = $user && (($user->role ?? 'user') === 'admin');
        $isOwner = $user && ((int)$s->user_id === (int)$user->id);
        if (!$isAdmin && !$isOwner && !$s->is_public) {
            return response()->json(['message' => 'Permission denied'], 403);
        }
        return $s;
    }
    public function index(Request $request)
    {
        $q = Shelf::query();
        $user = $this->userResolver->user($request);
        $isAdmin = $user && (($user->role ?? 'user') === 'admin');
        if (!$isAdmin) {
            if ($user) {
                $q->where(function($qq) use ($user) {
                    $qq->where('is_public', true)->orWhere('user_id', $user->id);
                });
            } else {
                $q->where('is_public', true);
            }
        }
        if ($kw = trim((string)$request->query('q'))) {
            $q->where('name', 'like', "%{$kw}%");
        }
        // 过滤：owner=me
        $owner = (string)$request->query('owner', '');
        if ($owner === 'me' && $user) {
            $q->where('user_id', $user->id);
        }
        // 过滤：visibility=public|private
        $visibility = (string)$request->query('visibility', '');
        if ($visibility === 'public') {
            $q->where('is_public', true);
        } elseif ($visibility === 'private') {
            $q->where('is_public', false);
            if (!$isAdmin && $user) {
                // 非管理员仅能看到自己的私有
                $q->where('user_id', $user->id);
            }
        }
        $perPage = min(max((int)$request->query('per_page', 20),1),100);
        $bookLimit = (int)$request->query('book_limit', 0);
        $page = $q->orderBy('name')->paginate($perPage);
        if ($bookLimit > 0) {
            $limit = min(max($bookLimit, 1), 50);
            $page->getCollection()->transform(function (Shelf $s) use ($limit) {
                $books = $s->books()->select('books.id','books.title','books.cover_file_id')->orderBy('books.id','desc')->limit($limit)->get();
                $s->setRelation('books', $books);
                return $s;
            });
        }
        return $page;
    }

    // 返回全部书架（不分页），用于前端筛选列表等场景
    public function all(Request $request)
    {
        $q = Shelf::query();
        $user = $this->userResolver->user($request);
        $isAdmin = $user && (($user->role ?? 'user') === 'admin');
        if (!$isAdmin) {
            if ($user) {
                $q->where(function($qq) use ($user) {
                    $qq->where('is_public', true)->orWhere('user_id', $user->id);
                });
            } else {
                $q->where('is_public', true);
            }
        }
        if ($kw = trim((string)$request->query('q'))) {
            $q->where('name', 'like', "%{$kw}%");
        }
        return $q->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $user = $this->userResolver->user($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $isAdmin = ($user->role ?? 'user') === 'admin';

        $rules = [
            'name' => ['required','string','max:190'],
            'description' => ['nullable','string','max:500'],
        ];
        if ($isAdmin) {
            $rules['is_public'] = ['sometimes','boolean'];
            $rules['user_id'] = ['sometimes','nullable','integer'];
        }
        $data = $request->validate($rules);

        if ($isAdmin) {
            $data['is_public'] = (bool)($data['is_public'] ?? false);
            // allow admin to optionally assign owner; if omitted, it's a global/admin shelf (user_id = null)
            $data['user_id'] = $data['user_id'] ?? null;
        } else {
            $data['user_id'] = $user->id;
            $data['is_public'] = false;
        }

        $s = Shelf::create($data);
        return response()->json($s, 201);
    }

    public function update(Request $request, int $id)
    {
        $s = Shelf::findOrFail($id);
        $user = $this->userResolver->user($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $isAdmin = ($user->role ?? 'user') === 'admin';
        if (!$isAdmin && (int)$s->user_id !== (int)$user->id) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $rules = [
            'name' => ['sometimes','string','max:190'],
            'description' => ['sometimes','nullable','string','max:500'],
        ];
        if ($isAdmin) {
            $rules['is_public'] = ['sometimes','boolean'];
            $rules['user_id'] = ['sometimes','nullable','integer'];
        }
        $data = $request->validate($rules);

        if (!$isAdmin) {
            // prevent non-admin from altering owner or visibility
            unset($data['user_id'], $data['is_public']);
        }

        $s->fill($data)->save();
        return $s->refresh();
    }

    public function destroy(int $id)
    {
        $s = Shelf::findOrFail($id);
        $user = request()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $isAdmin = ($user->role ?? 'user') === 'admin';
        if (!$isAdmin && (int)$s->user_id !== (int)$user->id) {
            return response()->json(['message' => 'Permission denied'], 403);
        }
        $s->books()->detach();
        $s->delete();
        return response()->noContent();
    }

    public function setBooks(Request $request, int $id)
    {
        $s = Shelf::findOrFail($id);
        $user = $this->userResolver->user($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $isAdmin = ($user->role ?? 'user') === 'admin';
        if (!$isAdmin && (int)$s->user_id !== (int)$user->id) {
            return response()->json(['message' => 'Permission denied'], 403);
        }
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
        $user = $this->userResolver->user($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $isAdmin = ($user->role ?? 'user') === 'admin';
        $payload = $request->validate([
            'shelf_ids' => ['required','array'],
            'shelf_ids.*' => ['integer'],
        ]);
        $ids = $payload['shelf_ids'];

        if ($isAdmin) {
            $b->shelves()->sync($ids);
            return $b->load('shelves');
        }

        // 普通用户：仅能操作自己的书架，且仅影响自己的关联，不影响他人的书架
        $userShelfIds = Shelf::query()->where('user_id', $user->id)->pluck('id')->all();
        // 校验传入 ID 均为用户自己的书架
        $invalid = array_diff($ids, $userShelfIds);
        if (!empty($invalid)) {
            return response()->json(['message' => '包含无权限的书架'], 403);
        }
        // 计算需要附加和移除的（仅限用户自己的书架范围内）
        $currentUserShelfIds = $b->shelves()->whereIn('shelf_id', $userShelfIds)->pluck('shelf_id')->all();
        $toAttach = array_values(array_diff($ids, $currentUserShelfIds));
        $toDetach = array_values(array_diff($currentUserShelfIds, $ids));
        if ($toDetach) {
            $b->shelves()->detach($toDetach);
        }
        if ($toAttach) {
            $b->shelves()->syncWithoutDetaching($toAttach);
        }
        return $b->load('shelves');
    }
}
