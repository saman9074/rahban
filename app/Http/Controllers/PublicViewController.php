<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Carbon\Carbon;
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
        $trip = Trip::where('share_token', $share_token)->first();

        // اگر سفر یافت نشد، تمام شده یا منقضی شده باشد، یک صفحه خطا نمایش بده
        if (!$trip || $trip->status === 'completed' || $trip->expires_at < Carbon::now()) {
            return view('public.trip_invalid');
        }

        // ارسال توکن اشتراک‌گذاری به ویو برای استفاده در درخواست‌های بعدی
        return view('public.trip_view', ['share_token' => $share_token]);
    }

    /**
     * ارائه داده‌های رمزنگاری شده سفر برای صفحه عمومی (API endpoint)
     *
     * @param string $share_token
     * @return JsonResponse
     */
    public function getTripData(string $share_token): JsonResponse
    {
        // پیدا کردن سفر و بارگذاری روابط مورد نیاز (کاربر و موقعیت‌ها)
        $trip = Trip::with(['user', 'locations'])
                     ->where('share_token', $share_token)
                     ->first();

        // بررسی اعتبار سفر
        if (!$trip || $trip->expires_at < Carbon::now()) {
            return response()->json(['message' => 'سفر یافت نشد یا منقضی شده است.'], 404);
        }

        // اگر سفر تمام شده بود، یک وضعیت خاص برگردان
        if ($trip->status === 'completed') {
            return response()->json([
                'status' => 'completed',
                'message' => 'سفر با موفقیت به پایان رسیده است.'
            ]);
        }
        
        // استخراج تمام داده‌های موقعیت مکانی که به صورت رمزنگاری شده ذخیره شده‌اند
        $encrypted_locations = $trip->locations->pluck('encrypted_data');

        // آماده‌سازی پاسخ نهایی برای ارسال به صفحه وب نگهبان
        return response()->json([
            'status' => $trip->status, // وضعیت سفر: 'active' یا 'emergency'
            'passenger_name' => $trip->user->name,
            'vehicle_info' => $trip->vehicle_info,
            'plate_photo_url' => $trip->plate_photo_path ? asset('storage/' . $trip->plate_photo_path) : null,
            'encrypted_locations' => $encrypted_locations,
        ]);
    }
}
