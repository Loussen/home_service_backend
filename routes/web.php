<?php

use App\Http\Controllers\Web\WebAppController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebAppController::class, 'index'])->name('web.app');
Route::get('/login', [WebAppController::class, 'login'])->name('web.login');
Route::get('/onboarding', [WebAppController::class, 'onboarding'])->name('web.onboarding');
Route::get('/categories', [WebAppController::class, 'categories'])->name('web.categories');
Route::get('/profile', [WebAppController::class, 'profile'])->name('web.profile');
Route::get('/request', [WebAppController::class, 'request'])->name('web.request');
Route::get('/chat', [WebAppController::class, 'chat'])->name('web.chat');
Route::get('/chat/{id}', [WebAppController::class, 'chatShow'])->whereNumber('id')->name('web.chat.show');
Route::get('/jobs', [WebAppController::class, 'jobs'])->name('web.jobs');

// Legacy /web/* → root (köhnə bookmark / deploy)
Route::redirect('/web', '/', 301);
Route::redirect('/web/login', '/login', 301);
Route::redirect('/web/onboarding', '/onboarding', 301);
Route::redirect('/web/categories', '/categories', 301);
Route::redirect('/web/profile', '/profile', 301);
Route::redirect('/web/request', '/request', 301);
Route::redirect('/web/chat', '/chat', 301);
Route::get('/web/chat/{id}', function (int $id) {
    return redirect('/chat/'.$id, 301);
})->whereNumber('id');
Route::redirect('/web/jobs', '/jobs', 301);
