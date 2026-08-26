<?php

use App\Http\Controllers\Web\WebAppController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/web', [WebAppController::class, 'index'])->name('web.app');
Route::get('/web/login', [WebAppController::class, 'login'])->name('web.login');
Route::get('/web/onboarding', [WebAppController::class, 'onboarding'])->name('web.onboarding');
Route::get('/web/categories', [WebAppController::class, 'categories'])->name('web.categories');
Route::get('/web/profile', [WebAppController::class, 'profile'])->name('web.profile');
Route::get('/web/request', [WebAppController::class, 'request'])->name('web.request');
Route::get('/web/chat', [WebAppController::class, 'chat'])->name('web.chat');
Route::get('/web/chat/{id}', [WebAppController::class, 'chatShow'])->whereNumber('id')->name('web.chat.show');
Route::get('/web/jobs', [WebAppController::class, 'jobs'])->name('web.jobs');
