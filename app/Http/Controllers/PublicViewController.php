<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PublicViewController extends Controller
{
    /**
     * نمایش صفحه وب عمومی برای نگهبانان سفر
     *
     * @param string $share_token
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function showTrip(string $share_token): View|\Illuminate\Http\RedirectResponse
    {
        // پیدا کردن سفر با استفاده از توکن اشتراک‌گذاری
        $trip = Trip::where('share_token', $share_token)->first();

        // بررسی اینکه آیا سفر معتبر است یا خیر
        if (!$trip || $trip->status === 'completed' || $trip->expires_at < Carbon::now()) {
            // اگر سفر یافت نشد، تمام شده یا منقضی شده باشد، یک صفحه خطا نمایش بده
             return view('public.trip_invalid');
        }

        // ارسال توکن به ویو برای استفاده در درخواست‌های AJAX
        return view('public.trip_view', ['share_token' => $share_token]);
    }

    /**
     * ارائه داده‌های زنده سفر برای صفحه عمومی (API endpoint)
     *
     * @param string $share_token
     * @return JsonResponse
     */
    public function getTripData(string $share_token): JsonResponse
    {
        // پیدا کردن سفر و بارگذاری روابط مورد نیاز
        $trip = Trip::with(['user', 'locations' => function ($query) {
            // فقط آخرین موقعیت مکانی را دریافت کن
            $query->latest()->limit(1);
        }])->where('share_token', $share_token)->first();

        // بررسی اعتبار سفر
        if (!$trip || $trip->expires_at < Carbon::now()) {
            return response()->json(['message' => 'Trip not found or has expired.'], 404);
        }

        // اگر سفر تمام شده بود، یک وضعیت خاص برگردان
        if ($trip->status === 'completed') {
             return response()->json([
                'status' => 'completed',
                'message' => 'Trip has been successfully completed.'
            ]);
        }
        
        // آماده‌سازی داده‌ها برای پاسخ JSON
        $lastLocation = $trip->locations->first();

        return response()->json([
            'status' => $trip->status, // 'active' or 'emergency'
            'passenger_name' => $trip->user->name,
            'vehicle_info' => $trip->vehicle_info,
            'last_location' => $lastLocation ? [
                'latitude' => $lastLocation->latitude,
                'longitude' => $lastLocation->longitude,
                'timestamp' => $lastLocation->created_at->toIso8601String(),
            ] : null,
        ]);
    }
}

