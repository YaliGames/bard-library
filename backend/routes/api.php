<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BooksController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\BookmarksController;
use App\Http\Controllers\AuthorsController;
use App\Http\Controllers\TagsController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\ShelvesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TxtController;
use App\Http\Controllers\MarkController;
use App\Http\Controllers\CoversController;
use App\Http\Controllers\MetadataController;
use App\Http\Controllers\FilesAdminController;

// API v1
Route::prefix('v1')->group(function () {
    // ============================
    // Auth 认证
    // ============================
    Route::prefix('auth')->group(function () {
        // 登录（公开）
        Route::post('/login', [AuthController::class, 'login']);
        // 注册（公开）
        Route::post('/register', [AuthController::class, 'register']);
        // 忘记密码与重置（公开触发、重置无需登录）
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        // 重新发送验证邮件（公开触发）
        Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
        // 退出登录（需登录）
        Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    });
    // 当前用户信息（需登录）
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/me', [AuthController::class, 'updateMe']);
        Route::post('/me/change-password', [AuthController::class, 'changePassword']);
        Route::post('/me/request-delete', [AuthController::class, 'requestDelete']);
        Route::get('/me/settings', [AuthController::class, 'getSettings']);
        Route::post('/me/settings', [AuthController::class, 'updateSettings']);
    });

    // ============================
    // Books 图书（浏览 & 公开接口）
    // ============================
    Route::prefix('books')->group(function () {
        // 列表/详情/文件（公开）
        Route::get('/', [BooksController::class, 'index']);
        Route::get('/{id}', [BooksController::class, 'show']);
        Route::get('/{id}/files', [FilesController::class, 'listByBook']);

        // 导入（需登录 + 管理员）
        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::post('/import', [ImportController::class, 'upload']);
        });

        // 阅读进度（公开） 支持可选 fileId：/books/{id}/progress 和 /books/{id}/{fileId}/progress
        Route::get('/{id}/progress', [ProgressController::class, 'show']);
        Route::post('/{id}/progress', [ProgressController::class, 'upsert']);
        Route::get('/{id}/{fileId}/progress', [ProgressController::class, 'showWithFile']);
        Route::post('/{id}/{fileId}/progress', [ProgressController::class, 'upsertWithFile']);

        // 书签（需登录） 支持可选 fileId：/books/{id}/bookmarks 和 /books/{id}/{fileId}/bookmarks
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/{id}/bookmarks', [BookmarksController::class, 'list']);
            Route::post('/{id}/bookmarks', [BookmarksController::class, 'create']);
            Route::get('/{id}/{fileId}/bookmarks', [BookmarksController::class, 'listByFile']);
            Route::post('/{id}/{fileId}/bookmarks', [BookmarksController::class, 'createByFile']);
            Route::patch('/{id}/bookmarks/{bookmarkId}', [BookmarksController::class, 'update']);
            Route::delete('/{id}/bookmarks/{bookmarkId}', [BookmarksController::class, 'delete']);
        });

        // 用户标记“已读”（需登录）
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/{id}/mark-read', [MarkController::class, 'setRead']);
        });

        // Admin：图书管理（需登录 + 管理员）
        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::post('/', [BooksController::class, 'store']);
            Route::patch('/{id}', [BooksController::class, 'update']);
            Route::delete('/{id}', [BooksController::class, 'destroy']);
            Route::post('/{id}/authors', [BooksController::class, 'setAuthors']);
            Route::post('/{id}/tags', [BooksController::class, 'setTags']);
            // 为书籍设置书架
            Route::post('/{id}/shelves', [ShelvesController::class, 'setShelvesForBook']);
            // 封面：上传与通过 URL 导入
            Route::post('/{id}/cover/upload', [CoversController::class, 'upload']);
            Route::post('/{id}/cover/from-url', [CoversController::class, 'fromUrl']);
            Route::delete('/{id}/cover', [CoversController::class, 'clear']);
        });
    });

    // ============================
    // Files 文件
    // ============================
    Route::prefix('files')->group(function () {
        // 下载/预览（公开）
        Route::get('/{id}/download', [FilesController::class, 'download']);
        Route::get('/{id}/preview', [FilesController::class, 'preview']);
        // 删除（需管理员）
        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::delete('/{id}', [FilesController::class, 'destroy']);
        });
    });

    // ============================
    // Admin Files 管理（需管理员）
    // ============================
    Route::prefix('admin/files')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/', [FilesAdminController::class, 'index']);
        Route::delete('/{id}', [FilesAdminController::class, 'destroy']);
        Route::post('/cleanup', [FilesAdminController::class, 'cleanup']);
    });

    // ============================
    // TXT 阅读（公开）
    // ============================
    Route::prefix('txt')->group(function () {
        // 支持 ?pattern=...&dry=true 预览
        Route::get('/{fileId}/chapters', [TxtController::class, 'chapters']);
        Route::get('/{fileId}/chapters/{index}', [TxtController::class, 'chapterContent']);

        // 保存目录：pattern 或 chapters[]，需管理员
        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::post('/{fileId}/chapters', [TxtController::class, 'commit']);
            // 章节重命名
            Route::patch('/{fileId}/chapters/{index}', [TxtController::class, 'renameChapter']);
            // 删除并与相邻章节合并（?merge=prev|next 或 JSON { merge: 'prev'|'next' }）
            Route::delete('/{fileId}/chapters/{index}', [TxtController::class, 'deleteWithMerge']);
        });
    });

    // ============================
    // Shelves 书架
    // ============================
    Route::prefix('shelves')->group(function () {
        // 分页
        Route::get('/', [ShelvesController::class, 'index']);
        // 不分页
        Route::get('/all', [ShelvesController::class, 'all']);

        // Admin：书架管理（需管理员）
        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::post('/', [ShelvesController::class, 'store']);
            Route::patch('/{id}', [ShelvesController::class, 'update']);
            Route::delete('/{id}', [ShelvesController::class, 'destroy']);
            Route::post('/{id}/books', [ShelvesController::class, 'setBooks']);
        });
    });

    // ============================
    // Authors 作者
    // ============================
    Route::prefix('authors')->group(function () {
        Route::get('/', [AuthorsController::class, 'index']);

        // Admin：作者管理（需管理员）
        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::post('/', [AuthorsController::class, 'store']);
            Route::patch('/{id}', [AuthorsController::class, 'update']);
            Route::delete('/{id}', [AuthorsController::class, 'destroy']);
        });
    });

    // ============================
    // Tags 标签
    // ============================
    Route::prefix('tags')->group(function () {
        Route::get('/', [TagsController::class, 'index']);

        // Admin：标签管理（需管理员）
        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::post('/', [TagsController::class, 'store']);
            Route::patch('/{id}', [TagsController::class, 'update']);
            Route::delete('/{id}', [TagsController::class, 'destroy']);
        });
    });

    // ============================
    // Series 丛书
    // ============================
    Route::prefix('series')->group(function () {
        Route::get('/', [SeriesController::class, 'index']);

        // Admin：丛书管理（需管理员）
        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::post('/', [SeriesController::class, 'store']);
            // 若后续需要：Route::patch('/{id}', ...); Route::delete('/{id}', ...);
        });
    });

    // ============================
    // Metadata 元数据抓取（公开）
    // ============================
    Route::prefix('metadata')->group(function () {
        // 搜索并返回若干图书元数据项（默认最多5条）
        Route::get('/douban/search', [MetadataController::class, 'search']);
        // 根据 id 或 url 返回单本详情
        Route::get('/douban/book', [MetadataController::class, 'book']);
        // 代理封面（避免防盗链）
        Route::get('/douban/cover', [MetadataController::class, 'cover']);
    });
});
