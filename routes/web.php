<?php

use App\Http\Controllers\GoogleAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'login')->name('login');
Route::get('/login/google', [GoogleAuthController::class, 'redirect'])->name('login.google');
Route::get('/login/google/callback', [GoogleAuthController::class, 'callback']);

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');

Route::view('/', 'board')->middleware('auth')->name('board');
Route::view('/colleghi', 'colleagues')->middleware('auth')->name('colleagues');
Route::view('/classifica', 'leaderboard')->middleware('auth')->name('leaderboard');
Route::view('/admin', 'admin')->middleware(['auth', 'admin'])->name('admin');
