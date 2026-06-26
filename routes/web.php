<?php

use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;

use App\Livewire\LptkPublic;

Route::redirect('/', '/app')->name('home');

Route::get('/lptk', LptkPublic::class)->name('lptk.public');

Route::view('/no-auth', 'no-auth')->name('auth.no-access');

Route::middleware('guest')->group(function () {
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->name('auth.social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->name('auth.social.callback');
});
