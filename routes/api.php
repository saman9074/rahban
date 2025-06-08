<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\GuardianController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// مسیرهای محافظت شده که نیاز به توکن دارند
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Trip Management
    Route::post('/trips/start', [TripController::class, 'start']);
    Route::post('/trips/{trip}/location', [TripController::class, 'updateLocation']);
    Route::post('/trips/{trip}/complete', [TripController::class, 'complete']);
    Route::post('/trips/{trip}/sos', [TripController::class, 'triggerSOS']);

    // ۱. مسیرهای تاریخچه سفر و پروفایل کاربری
    Route::get('/trips', [TripController::class, 'history']);
    Route::get('/user', [ProfileController::class, 'show']);
    Route::post('/user/change-password', [ProfileController::class, 'changePassword']);

    // ۲. مسیرهای مدیریت نگهبانان پیش‌فرض
    Route::apiResource('guardians', GuardianController::class);
    Route::post('/guardians/{guardian}/set-default', [GuardianController::class, 'setDefault']);
    
    // ۳. مسیر آپلود عکس اضطراری
    Route::post('/trips/{trip}/upload-photo', [TripController::class, 'uploadEmergencyPhoto']);
});