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
        $s = Shelf::with(['books.authors', 'user:id,name,email'])->findOrFail($id);
        $user = $this->userResolver->user($request);
        
        // 权限检查:公开书架任何人可见,私有书架只有所有者或有管理权限的人可见
        $hasManageAll = $user && $user->can('shelves.manage_all');
        $isOwner = $user && ((int)$s->user_id === (int)$user->id);
        
        if (!$s->is_public && !$isOwner && !$hasManageAll) {
            return response()->json(['message' => 'Permission denied'], 403);
        }
        return $s;
    }
    public function index(Request $request)
    {
        $q = Shelf::query()->with(['user:id,name,email']);
        $user = $this->userResolver->user($request);
        $hasManageAll = $user && $user->can('shelves.manage_all');

        // owner=admin 时（且当前用户有 manage_all 权限）返回所有书架
        $owner = (string)$request->query('owner', '');
        if (!($hasManageAll && $owner === 'admin')) {
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
        // 过滤：owner=me（仅在用户存在时生效）
        if ($owner === 'me' && $user) {
            $q->where('user_id', $user->id);
        }
        // 过滤：visibility=public|private
        $visibility = (string)$request->query('visibility', '');
        if ($visibility === 'public') {
            $q->where('is_public', true);
        } elseif ($visibility === 'private') {
            $q->where('is_public', false);
            if (!$hasManageAll && $user) {
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
        $q = Shelf::query()->with(['user:id,name,email']);
        $user = $this->userResolver->user($request);
        $hasManageAll = $user && $user->can('shelves.manage_all');
        
        // 与 index 一致的默认权限：除非明确 owner=admin，否则默认返回公开+自己的
        $owner = (string)$request->query('owner', '');
        if (!($hasManageAll && $owner === 'admin')) {
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

        // 权限检查
        $canCreatePublic = $user->can('shelves.create_public');
        $canCreateGlobal = $user->can('shelves.create_global');
        $hasManageAll = $user->can('shelves.manage_all');

        $rules = [
            'name' => ['required','string','max:190'],
            'description' => ['nullable','string','max:500'],
        ];
        
        // 只有特定权限才能设置这些字段
        if ($canCreatePublic || $hasManageAll) {
            $rules['is_public'] = ['sometimes','boolean'];
        }
        if ($canCreateGlobal || $hasManageAll) {
            $rules['global'] = ['sometimes','boolean'];
        }
        if ($hasManageAll) {
            $rules['user_id'] = ['sometimes','nullable','integer'];
        }
        
        $data = $request->validate($rules);

        // 处理 is_public 字段
        if (isset($data['is_public']) && ($canCreatePublic || $hasManageAll)) {
            $data['is_public'] = (bool)$data['is_public'];
        } else {
            $data['is_public'] = false; // 默认私有
        }

        // 处理全局书架
        $isGlobal = isset($data['global']) && (bool)$data['global'] && ($canCreateGlobal || $hasManageAll);
        if ($isGlobal) {
            $data['user_id'] = null;
        } else {
            // 只有 manage_all 权限才能指定其他用户
            if ($hasManageAll && isset($data['user_id'])) {
                $data['user_id'] = $data['user_id'];
            } else {
                $data['user_id'] = $user->id;
            }
        }
        unset($data['global']);

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
        
        // 权限检查:所有者可以编辑(需要 shelves.edit),或有 manage_all 权限
        $hasManageAll = $user->can('shelves.manage_all');
        $isOwner = (int)$s->user_id === (int)$user->id;
        
        if (!$hasManageAll && !$isOwner) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $rules = [
            'name' => ['sometimes','string','max:190'],
            'description' => ['sometimes','nullable','string','max:500'],
        ];
        
        // 权限字段检查
        $canSetPublic = $user->can('shelves.create_public') || $hasManageAll;
        $canSetGlobal = $user->can('shelves.create_global') || $hasManageAll;
        
        if ($canSetPublic) {
            $rules['is_public'] = ['sometimes','boolean'];
        }
        if ($canSetGlobal) {
            $rules['global'] = ['sometimes','boolean'];
        }
        if ($hasManageAll) {
            $rules['user_id'] = ['sometimes','nullable','integer'];
        }
        
        $data = $request->validate($rules);

        // 普通用户不能修改所有者和可见性(除非有特定权限)
        if (!$canSetPublic) {
            unset($data['is_public']);
        }
        if (!$hasManageAll) {
            unset($data['user_id']);
        }
        
        // 处理全局书架
        if (isset($data['global'])) {
            if ($canSetGlobal) {
                $isGlobal = (bool)$data['global'];
                if ($isGlobal) {
                    $data['user_id'] = null;
                } else {
                    if (!isset($data['user_id'])) {
                        $data['user_id'] = $s->user_id ?? $user->id;
                    }
                }
            }
            unset($data['global']);
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
        
        // 权限检查:所有者可以删除(需要 shelves.delete),或有 manage_all 权限
        $hasManageAll = $user->can('shelves.manage_all');
        $isOwner = (int)$s->user_id === (int)$user->id;
        
        if (!$hasManageAll && !$isOwner) {
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
        
        // 权限检查:所有者可以编辑(需要 shelves.edit),或有 manage_all 权限
        $hasManageAll = $user->can('shelves.manage_all');
        $isOwner = (int)$s->user_id === (int)$user->id;
        
        if (!$hasManageAll && !$isOwner) {
            return response()->json(['message' => 'Permission denied'], 403);
        }
        $payload = $request->validate([
            'book_ids' => ['present','array'],
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
        
        $hasManageAll = $user->can('shelves.manage_all');
        
        $payload = $request->validate([
            'shelf_ids' => ['present','array'],
            'shelf_ids.*' => ['integer'],
        ]);
        $ids = $payload['shelf_ids'];

        // 有 manage_all 权限可以操作所有书架
        if ($hasManageAll) {
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
