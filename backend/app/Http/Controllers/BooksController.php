<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Shelf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\OptionalUserResolver;
use App\Services\BookCreationService;

class BooksController extends Controller
{
    public function __construct(
        private OptionalUserResolver $userResolver,
        private BookCreationService $bookService
    ) {}
    public function index(Request $request)
    {
        $q = Book::query()->with(['authors', 'tags', 'files', 'series']);
        $q->select('books.*');
        $userId = $this->userResolver->id($request);
        $user = $this->userResolver->user($request);
        $isAdmin = $user && (($user->role ?? 'user') === 'admin');
        // is_reading（当前用户是否存在阅读进度）与 is_read_mark（用户手动标注已读）
        if ($userId > 0) {
            $q->selectRaw('EXISTS(SELECT 1 FROM user_reading_progress rp WHERE rp.book_id = books.id AND rp.user_id = ? AND (rp.progress IS NULL OR rp.progress < 1)) as is_reading', [$userId])
                ->selectRaw('EXISTS(SELECT 1 FROM user_read_marks ubm WHERE ubm.book_id = books.id AND ubm.user_id = ? AND ubm.is_read = 1) as is_read_mark', [$userId]);
        } else {
            $q->selectRaw('0 as is_reading')
                ->selectRaw('0 as is_read_mark');
        }
        if ($search = trim((string)$request->query('q'))) {
            $q->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%");
            });
        }
        // 作者筛选：author_id 多值逗号分隔
        if ($authorIds = trim((string)$request->query('author_id', ''))) {
            $ids = array_filter(array_map('intval', explode(',', $authorIds)));
            if ($ids) {
                $q->whereHas('authors', fn($qa) => $qa->whereIn('authors.id', $ids));
            }
        }
        // 标签筛选：tag_id 多值，匹配包含全部已选择的标签
        if ($tagIds = trim((string)$request->query('tag_id', ''))) {
            $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $tagIds)))));
            if ($ids) {
                $q->whereHas('tags', function ($qt) use ($ids) {
                    $qt->whereIn('tags.id', $ids);
                }, '=', count($ids));
            }
        }
        // 书架筛选：shelf_id 多值（校验权限：非管理员不可筛选他人私有书架）
        if ($shelfIds = trim((string)$request->query('shelf_id', ''))) {
            $ids = array_filter(array_map('intval', explode(',', $shelfIds)));
            if ($ids) {
                if (!$isAdmin) {
                    // 仅允许筛选 公开 或 自己的 书架
                    $allowed = Shelf::query()
                        ->whereIn('id', $ids)
                        ->where(function($w) use ($userId) {
                            $w->where('is_public', true);
                            if ($userId > 0) {
                                $w->orWhere('user_id', $userId);
                            }
                        })
                        ->pluck('id')->all();
                    sort($allowed);
                    $sortedIds = $ids; sort($sortedIds);
                    if (count($allowed) !== count($sortedIds) || array_values($allowed) !== array_values($sortedIds)) {
                        return response()->json(['message' => '包含无权限的书架筛选'], 403);
                    }
                }
                $q->whereHas('shelves', fn($qs) => $qs->whereIn('shelves.id', $ids));
            }
        }
        // 已读/未读/在读：
        // - read: 用户手动标记已读（user_read_marks.is_read = 1）
        // - unread: 未手动标记且不在读（不存在进行中的阅读进度）
        // - reading: 存在阅读进度，且 progress 为空或 < 1
        $readState = $request->query('read_state'); // 'read' | 'unread' | 'reading' | null
        if ($readState === 'read') {
            if ($userId > 0) {
                $q->whereExists(function ($sub) use ($userId) {
                    $sub->select(DB::raw(1))
                        ->from('user_read_marks as ubm')
                        ->whereColumn('ubm.book_id', 'books.id')
                        ->where('ubm.user_id', $userId)
                        ->where('ubm.is_read', 1);
                });
            } else {
                // 未登录无法判断用户手动标记，直接返回空集
                $q->whereRaw('1=0');
            }
        } elseif ($readState === 'unread') {
            if ($userId > 0) {
                $q->whereNotExists(function ($sub) use ($userId) {
                    $sub->select(DB::raw(1))
                        ->from('user_read_marks as ubm')
                        ->whereColumn('ubm.book_id', 'books.id')
                        ->where('ubm.user_id', $userId)
                        ->where('ubm.is_read', 1);
                })
                    ->whereNotExists(function ($sub) use ($userId) {
                        $sub->select(DB::raw(1))
                            ->from('user_reading_progress as rp')
                            ->whereColumn('rp.book_id', 'books.id')
                            ->where('rp.user_id', $userId)
                            ->where(function ($w) {
                                $w->whereNull('rp.progress')->orWhere('rp.progress', '<', 1);
                            });
                    });
            } else {
                // 未登录时，"未读"按不在读处理
                $q->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('user_reading_progress as rp')
                        ->whereColumn('rp.book_id', 'books.id')
                        ->where(function ($w) {
                            $w->whereNull('rp.progress')->orWhere('rp.progress', '<', 1);
                        });
                });
            }
        } elseif ($readState === 'reading') {
            if ($userId > 0) {
                $q->whereExists(function ($sub) use ($userId) {
                    $sub->select(DB::raw(1))
                        ->from('user_reading_progress as rp')
                        ->whereColumn('rp.book_id', 'books.id')
                        ->where('rp.user_id', $userId)
                        ->where(function ($w) {
                            $w->whereNull('rp.progress')->orWhere('rp.progress', '<', 1);
                        });
                });
            } else {
                // 未登录无法判断“在读”，直接返回空集
                $q->whereRaw('1=0');
            }
        }
        // 评分筛选：min_rating/max_rating
        if ($request->query('min_rating') !== null || $request->query('max_rating') !== null) {
            $minRaw = $request->query('min_rating');
            $maxRaw = $request->query('max_rating');
            $min = $minRaw !== null ? (float)$minRaw : null;
            $max = $maxRaw !== null ? (float)$maxRaw : null;

            $includeNull = false;
            // 只要 0 在用户指定的区间内，就把 NULL 一并包含
            if ((is_null($min) || $min <= 0.0) && (is_null($max) || $max >= 0.0)) {
                $includeNull = true;
            }

            $q->where(function ($w) use ($min, $max, $includeNull) {
                if ($min !== null) {
                    $w->where('rating', '>=', $min);
                }
                if ($max !== null) {
                    $w->where('rating', '<=', $max);
                }
                if ($includeNull) {
                    $w->orWhereNull('rating');
                }
            });
        }
        // 出版社筛选：publisher
        if ($publisher = trim((string)$request->query('publisher', ''))) {
            $q->where('publisher', 'like', "%{$publisher}%");
        }
        // 出版日期范围：published_from / published_to（YYYY-MM-DD）
        if ($from = (string)$request->query('published_from')) {
            $q->whereDate('published_at', '>=', $from);
        }
        if ($to = (string)$request->query('published_to')) {
            $q->whereDate('published_at', '<=', $to);
        }
        // 语言：language
        if ($lang = trim((string)$request->query('language', ''))) {
            $q->where('language', 'like', "%{$lang}%");
        }
        // 丛书：ID 或 名称
        $seriesValue = $request->query('series_value');
        if ($seriesValue !== null && $seriesValue !== '') {
            if (is_numeric($seriesValue)) {
                $q->where('series_id', (int)$seriesValue);
            } else {
                $name = trim((string)$seriesValue);
                if ($name !== '') {
                    $q->whereHas('series', fn($qs) => $qs->where('name', 'like', "%{$name}%"));
                }
            }
        }
        // ISBN 10 或 13 模糊匹配
        if ($isbn = trim((string)$request->query('isbn', ''))) {
            $q->where(function($w) use ($isbn) {
                $w->where('isbn10', 'like', "%{$isbn}%")
                  ->orWhere('isbn13', 'like', "%{$isbn}%");
            });
        }
        // 排序：sort=modified|created|rating，order=asc|desc，默认 id desc
        $sort = (string)$request->query('sort', '');
        $order = strtolower((string)$request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortable = [
            'modified' => 'updated_at',
            'created' => 'created_at',
            'rating' => 'rating',
            'id' => 'id',
        ];
        $col = $sortable[$sort] ?? 'id';
        $q->orderBy($col, $order);

        $perPage = min(max((int)$request->query('per_page', 12), 1), 100);
        return $q->paginate($perPage);
    }

    public function show(Request $request, int $id)
    {
        $userId = $this->userResolver->id($request);
        $q = Book::query()
            ->with([
                'authors',
                'tags',
                'files',
                'series',
                'shelves' => function ($query) use ($userId) {
                    $query->where('is_public', true);
                    if ($userId > 0) {
                        $query->orWhere('user_id', $userId);
                    }
                }
            ])
            ->select('books.*')
            ->selectRaw(
                $userId > 0
                    ? 'EXISTS(SELECT 1 FROM user_reading_progress rp WHERE rp.book_id = books.id AND rp.user_id = ? AND (rp.progress IS NULL OR rp.progress < 1)) as is_reading'
                    : '0 as is_reading',
                $userId > 0 ? [$userId] : []
            );
        if ($userId > 0) {
            $q->selectRaw('EXISTS(SELECT 1 FROM user_read_marks ubm WHERE ubm.book_id = books.id AND ubm.user_id = ? AND ubm.is_read = 1) as is_read_mark', [$userId]);
        } else {
            $q->selectRaw('0 as is_read_mark');
        }

        $book = $q->where('books.id', $id)->firstOrFail();
        return $book;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'language' => ['nullable', 'string', 'max:16'],
            'publisher' => ['nullable', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'isbn10' => ['nullable', 'string', 'max:10'],
            'isbn13' => ['nullable', 'string', 'max:13'],
            // 丛书改为 series_value（int|string|null），由后端解析
            'series_value' => ['sometimes', function($attr,$value,$fail){
                if ($value === null) return;
                if (is_int($value)) return;
                if (is_string($value) && trim($value) !== '') return;
                $fail('The '.$attr.' field must be an integer id or a non-empty string.');
            }],
            // 丛书编号：整数 >=1 或 null
            'series_index' => ['sometimes','nullable','integer','min:1'],
            'author_values' => ['sometimes', 'array'],
            'author_values.*' => [function($attr,$value,$fail){
                if (is_int($value)) return; // 允许数字ID
                if (is_string($value)) { if (trim($value) !== '') return; }
                $fail('The '.$attr.' field must be an integer id or a non-empty string.');
            }],
            'tag_values' => ['sometimes', 'array'],
            'tag_values.*' => [function($attr,$value,$fail){
                if (is_int($value)) return; // 允许数字ID
                if (is_string($value)) { if (trim($value) !== '') return; }
                $fail('The '.$attr.' field must be an integer id or a non-empty string.');
            }],
        ]);
        $book = Book::create($data);
        
        // 使用 BookCreationService 处理作者/标签/丛书
        $this->syncAuthorsAndTags($book, $request);
        $this->syncSeriesFromRequest($book, $request);
        
        return response()->json($book->load(['authors','tags','series']), 201);
    }

    public function update(Request $request, int $id)
    {
        $book = Book::findOrFail($id);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'rating' => ['sometimes', 'nullable', 'numeric', 'between:0,5'],
            'language' => ['sometimes', 'nullable', 'string', 'max:16'],
            'publisher' => ['sometimes', 'nullable', 'string', 'max:255'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'isbn10' => ['sometimes', 'nullable', 'string', 'max:10'],
            'isbn13' => ['sometimes', 'nullable', 'string', 'max:13'],
            'series_value' => ['sometimes', function($attr,$value,$fail){
                if ($value === null) return;
                if (is_int($value)) return;
                if (is_string($value) && trim($value) !== '') return;
                $fail('The '.$attr.' field must be an integer id or a non-empty string.');
            }],
            'series_index' => ['sometimes','nullable','integer','min:1'],
            'author_values' => ['sometimes', 'array'],
            'author_values.*' => [function($attr,$value,$fail){
                if (is_int($value)) return; // 允许数字ID
                if (is_string($value)) { if (trim($value) !== '') return; }
                $fail('The '.$attr.' field must be an integer id or a non-empty string.');
            }],
            'tag_values' => ['sometimes', 'array'],
            'tag_values.*' => [function($attr,$value,$fail){
                if (is_int($value)) return; // 允许数字ID
                if (is_string($value)) { if (trim($value) !== '') return; }
                $fail('The '.$attr.' field must be an integer id or a non-empty string.');
            }],
        ]);
        
        $book->fill($data)->save();
        
        // 使用 BookCreationService 处理作者/标签/丛书
        $this->syncAuthorsAndTags($book, $request);
        $this->syncSeriesFromRequest($book, $request);
        
        return $book->refresh()->load(['authors','tags','series']);
    }

    /**
     * 同步作者和标签（使用 BookCreationService）
     */
    private function syncAuthorsAndTags(Book $book, Request $request): void
    {
        $authorValues = $request->input('author_values', null);
        $tagValues = $request->input('tag_values', null);
        
        // 使用 BookCreationService 处理作者
        if (is_array($authorValues)) {
            $filteredAuthors = array_filter($authorValues, function($v) {
                $v = is_string($v) ? trim($v) : $v;
                return $v !== '' && $v !== null;
            });
            
            if (!empty($filteredAuthors)) {
                $this->bookService->syncAuthors($book, $filteredAuthors);
            } else {
                $book->authors()->detach();
            }
        }
        
        // 使用 BookCreationService 处理标签
        if (is_array($tagValues)) {
            $filteredTags = array_filter($tagValues, function($v) {
                $v = is_string($v) ? trim($v) : $v;
                return $v !== '' && $v !== null;
            });
            
            if (!empty($filteredTags)) {
                $this->bookService->syncTags($book, $filteredTags);
            } else {
                $book->tags()->detach();
            }
        }
    }
    
    /**
     * 从请求中同步丛书信息（使用 BookCreationService）
     */
    private function syncSeriesFromRequest(Book $book, Request $request): void
    {
        $seriesValue = $request->input('series_value', null);
        $seriesIndex = $request->input('series_index', null);
        
        if ($seriesValue !== null) {
            $this->bookService->syncSeries($book, $seriesValue, $seriesIndex);
        }
    }

    public function setAuthors(Request $request, int $id)
    {
        $book = Book::findOrFail($id);
        $payload = $request->validate([
            'author_ids' => ['required', 'array'],
            'author_ids.*' => ['integer'],
        ]);
        $book->authors()->sync($payload['author_ids']);
        return $book->load(['authors', 'tags']);
    }

    public function setTags(Request $request, int $id)
    {
        $book = Book::findOrFail($id);
        $payload = $request->validate([
            'tag_ids' => ['required', 'array'],
            'tag_ids.*' => ['integer'],
        ]);
        $book->tags()->sync($payload['tag_ids']);
        return $book->load(['authors', 'tags']);
    }

    public function destroy(Request $request, int $id)
    {
        $withFiles = filter_var((string) $request->query('with_files', 'false'), FILTER_VALIDATE_BOOLEAN);

        DB::transaction(function () use ($id, $withFiles) {
            $b = Book::with('files')->findOrFail($id);

            // 先解除作者/标签关联
            $b->authors()->detach();
            $b->tags()->detach();

            if ($withFiles) {
                // 处理封面：若未被其他书籍引用，则删除物理文件与记录
                $coverId = $b->cover_file_id;
                if ($coverId) {
                    $cover = \App\Models\File::find($coverId);
                    if ($cover) {
                        $otherUsed = Book::where('cover_file_id', $cover->id)->where('id', '!=', $b->id)->exists();
                        if (! $otherUsed) {
                            // 先清空引用，避免外键/一致性问题
                            $b->cover_file_id = null;
                            $b->save();
                            $disk = \Illuminate\Support\Facades\Storage::disk($cover->storage ?: config('filesystems.default'));
                            if ($disk->exists($cover->path)) { $disk->delete($cover->path); }
                            $cover->delete();
                        }
                    }
                }

                // 删除本书关联的附件文件（不含已在上面处理的封面文件）
                foreach ($b->files as $f) {
                    if ($coverId && $f->id === $coverId) { continue; }
                    $disk = \Illuminate\Support\Facades\Storage::disk($f->storage ?: config('filesystems.default'));
                    if ($disk->exists($f->path)) { $disk->delete($f->path); }
                    $f->delete();
                }
            } else {
                // 仅删除记录：不动物理文件
                $b->files()->delete();
            }

            // 最后删除图书本体记录
            $b->delete();
        });
        return response()->noContent();
    }
}
