<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RssController;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;

Route::get('/rss.xml', [RssController::class, 'feed']);

Route::get('/', function () {
    return view('welcome');
});

// 公开邮箱验证链接（签名保护），无需登录
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmailPublic'])
    ->middleware('signed')
    ->name('verification.verify');
