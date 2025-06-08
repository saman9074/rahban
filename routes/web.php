<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicViewController;
use App\Http\Controllers\RedirectController;

// این مسیر، صفحه وب را برای نگهبان نمایش می‌دهد
Route::get('/s/{share_token}', [PublicViewController::class, 'showTrip'])->name('trip.public_view');

// این مسیر API، داده‌های زنده را برای صفحه وب بالا فراهم می‌کند
Route::get('/api/public/trip/{share_token}', [PublicViewController::class, 'getTripData']);
Route::get('/', function () {
    return view('welcome');
});



Route::get('/r/{short_code}', [RedirectController::class, 'handle'])->name('shortlink.redirect');
