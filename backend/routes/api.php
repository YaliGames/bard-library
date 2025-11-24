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
use App\Http\Controllers\ScrapingTaskController;

// API v1
Route::prefix('v1')->group(function () {
    // ============================
    // Auth 认证
    // ============================
    Route::prefix('auth')->group(function () {
        // 登录 - 需要 session
        Route::middleware('session')->post('/login', [AuthController::class, 'login']);
        // 注册（公开）
        Route::post('/register', [AuthController::class, 'register']);
        // 忘记密码与重置（公开触发、重置无需登录）
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        // 重新发送验证邮件（公开触发）
        Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
        // 退出登录 - 需要 session
        Route::middleware(['session', 'auth'])->post('/logout', [AuthController::class, 'logout']);
    });
    // 当前用户信息（需登录）- 需要 session 中间件来读取 Cookie
    Route::middleware(['session', 'auth'])->group(function () {
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

        // 导入（需登录 + files.upload 权限）
        Route::middleware(['session', 'auth', 'permission:files.upload'])->group(function () {
            Route::post('/import', [ImportController::class, 'upload']);
        });

        // 阅读进度（公开） 支持可选 fileId：/books/{id}/progress 和 /books/{id}/{fileId}/progress
        Route::get('/{id}/progress', [ProgressController::class, 'show']);
        Route::post('/{id}/progress', [ProgressController::class, 'upsert']);
        Route::get('/{id}/{fileId}/progress', [ProgressController::class, 'showWithFile']);
        Route::post('/{id}/{fileId}/progress', [ProgressController::class, 'upsertWithFile']);

        // 书签（需登录） 支持可选 fileId：/books/{id}/bookmarks 和 /books/{id}/{fileId}/bookmarks
        Route::middleware(['session', 'auth'])->group(function () {
            Route::get('/{id}/bookmarks', [BookmarksController::class, 'list']);
            Route::post('/{id}/bookmarks', [BookmarksController::class, 'create']);
            Route::get('/{id}/{fileId}/bookmarks', [BookmarksController::class, 'listByFile']);
            Route::post('/{id}/{fileId}/bookmarks', [BookmarksController::class, 'createByFile']);
            Route::patch('/{id}/bookmarks/{bookmarkId}', [BookmarksController::class, 'update']);
            Route::delete('/{id}/bookmarks/{bookmarkId}', [BookmarksController::class, 'delete']);
        });

        // 用户标记"已读"（需登录）
        Route::middleware(['session', 'auth'])->group(function () {
            Route::post('/{id}/mark-read', [MarkController::class, 'setRead']);
        });

        // Admin：图书管理（需登录 + 相应权限）
        Route::middleware(['session', 'auth'])->group(function () {
            Route::post('/', [BooksController::class, 'store'])
                ->middleware('permission:books.create');
            Route::patch('/{id}', [BooksController::class, 'update'])
                ->middleware('permission:books.edit');
            Route::delete('/{id}', [BooksController::class, 'destroy'])
                ->middleware('permission:books.delete');
            Route::post('/{id}/authors', [BooksController::class, 'setAuthors'])
                ->middleware('permission:books.edit');
            Route::post('/{id}/tags', [BooksController::class, 'setTags'])
                ->middleware('permission:books.edit');
            // 封面：上传与通过 URL 导入
            Route::post('/{id}/cover/upload', [CoversController::class, 'upload'])
                ->middleware('permission:books.edit');
            Route::post('/{id}/cover/from-url', [CoversController::class, 'fromUrl'])
                ->middleware('permission:books.edit');
            Route::delete('/{id}/cover', [CoversController::class, 'clear'])
                ->middleware('permission:books.edit');
        });

        // 用户为书籍设置书架（需登录）：管理员可设置任意书架，普通用户仅能设置自己的书架
        Route::middleware(['session', 'auth'])->group(function () {
            Route::post('/{id}/shelves', [ShelvesController::class, 'setShelvesForBook']);
        });
    });
    
    // 管理：系统设置（需相应权限）
    Route::middleware(['session', 'auth', 'permission:settings.view'])->group(function () {
        Route::get('/admin/settings', [\App\Http\Controllers\SystemSettingsController::class, 'get']);
        Route::post('/admin/settings', [\App\Http\Controllers\SystemSettingsController::class, 'update'])
            ->middleware('permission:settings.edit');
        Route::post('/admin/settings/reset', [\App\Http\Controllers\SystemSettingsController::class, 'reset'])
            ->middleware('permission:settings.edit');
    });
    
    // 管理：安全管理（需相应权限）
    Route::middleware(['session', 'auth', 'permission:settings.view'])->group(function () {
        Route::get('/admin/security/login-attempts', [\App\Http\Controllers\SecurityController::class, 'getLoginAttempts']);
        Route::get('/admin/security/login-stats', [\App\Http\Controllers\SecurityController::class, 'getLoginStats']);
        Route::delete('/admin/security/login-attempts', [\App\Http\Controllers\SecurityController::class, 'clearLoginAttempts'])
            ->middleware('permission:settings.edit');
        Route::post('/admin/security/unlock-account', [\App\Http\Controllers\SecurityController::class, 'unlockAccount'])
            ->middleware('permission:settings.edit');
        Route::get('/admin/security/password-policy', [\App\Http\Controllers\SecurityController::class, 'getPasswordPolicy']);
    });
    
    // 公开：密码验证（注册时前端验证用）
    Route::post('/security/validate-password', [\App\Http\Controllers\SecurityController::class, 'validatePassword']);

    // ============================
    // RBAC 权限管理系统
    // ============================
    // 角色管理
    Route::prefix('admin/roles')->middleware(['session', 'auth', 'permission:roles.view'])->group(function () {
        Route::get('/', [\App\Http\Controllers\RolesController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\RolesController::class, 'show']);
        Route::get('/{id}/permissions', [\App\Http\Controllers\RolesController::class, 'permissions']);
        
        Route::post('/', [\App\Http\Controllers\RolesController::class, 'store'])
            ->middleware('permission:roles.create');
        Route::patch('/{id}', [\App\Http\Controllers\RolesController::class, 'update'])
            ->middleware('permission:roles.edit');
        Route::delete('/{id}', [\App\Http\Controllers\RolesController::class, 'destroy'])
            ->middleware('permission:roles.delete');
        Route::post('/{id}/permissions', [\App\Http\Controllers\RolesController::class, 'syncPermissions'])
            ->middleware('permission:roles.assign_permissions');
    });

    // 权限列表
    Route::prefix('admin/permissions')->middleware(['session', 'auth', 'permission:roles.view'])->group(function () {
        Route::get('/', [\App\Http\Controllers\PermissionsController::class, 'index']);
        Route::get('/grouped', [\App\Http\Controllers\PermissionsController::class, 'grouped']);
        Route::get('/groups', [\App\Http\Controllers\PermissionsController::class, 'groups']);
    });

    // 用户管理
    Route::prefix('admin/users')->middleware(['session', 'auth', 'permission:users.view'])->group(function () {
        Route::get('/', [\App\Http\Controllers\UsersController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\UsersController::class, 'show']);
        Route::get('/{id}/roles', [\App\Http\Controllers\UsersController::class, 'roles']);
        Route::get('/{id}/permissions', [\App\Http\Controllers\UsersController::class, 'permissions']);
        
        Route::post('/', [\App\Http\Controllers\UsersController::class, 'store'])
            ->middleware('permission:users.create');
        Route::patch('/{id}', [\App\Http\Controllers\UsersController::class, 'update'])
            ->middleware('permission:users.edit');
        Route::delete('/{id}', [\App\Http\Controllers\UsersController::class, 'destroy'])
            ->middleware('permission:users.delete');
        Route::post('/{id}/roles', [\App\Http\Controllers\UsersController::class, 'syncRoles'])
            ->middleware('permission:users.assign_roles');
    });

    // 公开：权限相关的公开设置，供前端路由守卫使用
    Route::get('/settings/public', [\App\Http\Controllers\SystemSettingsController::class, 'public']);

    // ============================
    // Files 文件
    // ============================
    Route::prefix('files')->group(function () {
        // 预览 - 公开访问，控制器内部根据文件类型进行权限控制
        Route::get('/{id}/preview', [FilesController::class, 'preview']);
        
        // 下载 - 需要登录和 books.download 权限
        Route::middleware(['session', 'auth', 'permission:books.download'])->group(function () {
            Route::get('/{id}/download', [FilesController::class, 'download']);
        });
        
        // 删除（需 files.delete 权限）
        Route::middleware(['session', 'auth', 'permission:files.delete'])->group(function () {
            Route::delete('/{id}', [FilesController::class, 'destroy']);
        });
        
        // 全局资源访问令牌（需登录）
        Route::middleware(['session', 'auth'])->group(function(){
            Route::get('/access-token', [FilesController::class, 'accessToken']);
        });
    });

    // ============================
    // Admin Files 管理（需相应权限）
    // ============================
    Route::prefix('admin/files')->middleware(['session', 'auth', 'permission:files.view'])->group(function () {
        Route::get('/', [FilesAdminController::class, 'index']);
        Route::delete('/{id}', [FilesAdminController::class, 'destroy'])
            ->middleware('permission:files.delete');
        Route::post('/cleanup', [FilesAdminController::class, 'cleanup'])
            ->middleware('permission:files.cleanup');
    });

    // ============================
    // TXT 阅读（公开）
    // ============================
    Route::prefix('txt')->group(function () {
        // 支持 ?pattern=...&dry=true 预览
        Route::get('/{fileId}/chapters', [TxtController::class, 'chapters']);
        Route::get('/{fileId}/chapters/{index}', [TxtController::class, 'chapterContent']);
        // 获取整本书的所有章节内容
        Route::get('/{fileId}/full-content', [TxtController::class, 'fullContent']);

        // 保存目录：pattern 或 chapters[]，需 books.edit 权限
        Route::middleware(['session', 'auth', 'permission:books.edit'])->group(function () {
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
        // 分页列表 - 公开访问或根据权限查看
        Route::get('/', [ShelvesController::class, 'index']);
        // 不分页
        Route::get('/all', [ShelvesController::class, 'all']);
        // 详情 - 公开书架或自己的书架
        Route::get('/{id}', [ShelvesController::class, 'show']);

        // 书架管理（需登录+权限）
        Route::middleware(['session', 'auth'])->group(function () {
            // 创建书架 - 需要 shelves.create 权限
            Route::post('/', [ShelvesController::class, 'store'])
                ->middleware('permission:shelves.create');
            // 编辑书架 - 需要 shelves.edit 权限(自己的)或 shelves.manage_all(所有)
            Route::patch('/{id}', [ShelvesController::class, 'update'])
                ->middleware('permission:shelves.edit');
            // 删除书架 - 需要 shelves.delete 权限(自己的)或 shelves.manage_all(所有)
            Route::delete('/{id}', [ShelvesController::class, 'destroy'])
                ->middleware('permission:shelves.delete');
            // 设置书架书籍 - 需要 shelves.edit 权限
            Route::post('/{id}/books', [ShelvesController::class, 'setBooks'])
                ->middleware('permission:shelves.edit');
        });
    });

    // ============================
    // Authors 作者
    // ============================
    Route::prefix('authors')->group(function () {
        Route::get('/', [AuthorsController::class, 'index']);

        // Admin：作者管理（需相应权限）
        Route::middleware(['session', 'auth'])->group(function () {
            Route::post('/', [AuthorsController::class, 'store'])
                ->middleware('permission:authors.create');
            Route::patch('/{id}', [AuthorsController::class, 'update'])
                ->middleware('permission:authors.edit');
            Route::delete('/{id}', [AuthorsController::class, 'destroy'])
                ->middleware('permission:authors.delete');
        });
    });

    // ============================
    // Tags 标签
    // ============================
    Route::prefix('tags')->group(function () {
        Route::get('/', [TagsController::class, 'index']);

        // Admin：标签管理（需相应权限）
        Route::middleware(['session', 'auth'])->group(function () {
            Route::post('/', [TagsController::class, 'store'])
                ->middleware('permission:tags.create');
            Route::patch('/{id}', [TagsController::class, 'update'])
                ->middleware('permission:tags.edit');
            Route::delete('/{id}', [TagsController::class, 'destroy'])
                ->middleware('permission:tags.delete');
        });
    });

    // ============================
    // Series 丛书
    // ============================
    Route::prefix('series')->group(function () {
        Route::get('/', [SeriesController::class, 'index']);

        // Admin：丛书管理（需相应权限）
        Route::middleware(['session', 'auth'])->group(function () {
            Route::post('/', [SeriesController::class, 'store'])
                ->middleware('permission:series.create');
            Route::patch('/{id}', [SeriesController::class, 'update'])
                ->middleware('permission:series.edit');
            Route::delete('/{id}', [SeriesController::class, 'destroy'])
                ->middleware('permission:series.delete');
        });
    });

    // ============================
    // Metadata 元数据抓取（公开）
    // ============================
    Route::prefix('metadata')->group(function () {
        Route::get('/providers', [MetadataController::class, 'providers']);
        Route::get('/{provider}/search', [MetadataController::class, 'search']);
        Route::post('/{provider}/batch-details', [MetadataController::class, 'batchDetails']);
        Route::get('/{provider}/book', [MetadataController::class, 'book']);
        Route::get('/{provider}/cover', [MetadataController::class, 'cover']);
    });

    // ============================
    // Scraping Tasks 批量刮削任务
    // ============================
    Route::middleware(['session', 'auth'])->prefix('admin/scraping-tasks')->group(function () {
        Route::get('/', [ScrapingTaskController::class, 'index'])
            ->middleware('permission:metadata.batch_scrape');
        Route::post('/', [ScrapingTaskController::class, 'store'])
            ->middleware('permission:metadata.batch_scrape');
        Route::get('/{id}', [ScrapingTaskController::class, 'show'])
            ->middleware('permission:metadata.batch_scrape');
        Route::post('/{id}/cancel', [ScrapingTaskController::class, 'cancel'])
            ->middleware('permission:metadata.batch_scrape');
        Route::delete('/{id}', [ScrapingTaskController::class, 'destroy'])
            ->middleware('permission:metadata.batch_scrape');
        Route::get('/{id}/results', [ScrapingTaskController::class, 'results'])
            ->middleware('permission:metadata.batch_scrape');
    });
});
