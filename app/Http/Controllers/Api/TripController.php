<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TripController extends Controller
{
    /**
     * سرویس ارسال نوتیفیکیشن
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * تزریق سرویس از طریق Constructor برای استفاده در تمام متدها
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * شروع یک سفر جدید، ذخیره اطلاعات و ارسال نوتیفیکیشن
     * @param Request $request
     * @return JsonResponse
     */
    public function start(Request $request): JsonResponse
{
    // اعتبارسنجی اولیه درخواست
    $request->validate([
        'vehicle_info' => 'nullable|array',
        'location' => 'required|array',
        'guardians' => 'nullable|array', // نگهبانان دیگر اجباری نیستند
    ]);

    $user = Auth::user();

    // بررسی اینکه آیا نگهبان ارسال شده یا باید از پیش‌فرض‌ها استفاده کرد
    $guardiansData = $request->input('guardians');
    if (empty($guardiansData)) {
        $defaultGuardians = $user->guardians()->where('is_default', true)->get();
        if ($defaultGuardians->isEmpty()) {
            return response()->json(['message' => 'هیچ نگهبانی برای این سفر انتخاب نشده و نگهبان پیش‌فرضی نیز وجود ندارد.'], 422);
        }
        // تبدیل مدل‌های Guardian به آرایه مورد نیاز
        $guardiansData = $defaultGuardians->map(function ($guardian) {
            return ['name' => $guardian->name, 'phone_number' => $guardian->phone_number];
        })->toArray();
    }

    // *** شروع بخش اصلاح شده ***
    // ایجاد سفر جدید با تمام اطلاعات لازم
    $trip = $user->trips()->create([
        'vehicle_info' => $request->vehicle_info,
        'status' => 'active',
        'share_token' => \Illuminate\Support\Str::random(40), // تولید توکن اشتراک‌گذاری
        'expires_at' => \Carbon\Carbon::now()->addHours(3),       // انقضای لینک پس از ۳ ساعت
        'deletable_at' => \Carbon\Carbon::now()->addDays(7),    // حذف نهایی پس از ۷ روز
    ]);
    // *** پایان بخش اصلاح شده ***

    // ذخیره نگهبانان سفر (از درخواست یا از پیش‌فرض‌ها)
    foreach ($guardiansData as $guardianItem) {
        $trip->guardians()->create($guardianItem);
    }

    // ذخیره اولین موقعیت مکانی
    $trip->locations()->create($request->location);

    // ارسال نوتیفیکیشن
    $this->notificationService->sendTripStartedNotifications($trip->fresh('user', 'guardians'));

    return response()->json([
        'message' => 'سفر با موفقیت آغاز شد.',
        'trip' => $trip->load('guardians', 'locations')
    ], 201);
}

    /**
     * به‌روزرسانی موقعیت مکانی مسافر در طول سفر
     * @param Request $request
     * @param Trip $trip
     * @return JsonResponse
     */
    public function updateLocation(Request $request, Trip $trip): JsonResponse
    {
        // بررسی اینکه کاربر فعلی مالک این سفر باشد
        if ($request->user()->id !== $trip->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $trip->locations()->create($request->only('latitude', 'longitude'));

        return response()->json(['message' => 'Location updated successfully.']);
    }

    /**
     * اعلام اتمام سفر و غیرفعال کردن لینک اشتراک‌گذاری
     * @param Request $request
     * @param Trip $trip
     * @return JsonResponse
     */
    public function complete(Request $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        
        if ($trip->status !== 'active') {
            return response()->json(['message' => 'This trip is not active.'], 400);
        }

        $trip->status = 'completed';
        $trip->save();

        // در اینجا هم می‌توانید یک نوتیفیکیشن "سفر به سلامت پایان یافت" ارسال کنید.

        return response()->json(['message' => 'Trip completed successfully.']);
    }

    /**
     * فعال‌سازی وضعیت اضطراری و ارسال نوتیفیکیشن فوری
     * @param Request $request
     * @param Trip $trip
     * @return JsonResponse
     */
    public function triggerSOS(Request $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $trip->status = 'emergency';
        $trip->save();

        // ارسال پیامک هشدار اضطراری به همه نگهبانان
        $this->notificationService->sendSosNotifications($trip->fresh('user', 'guardians'));

        return response()->json(['message' => 'SOS triggered. Guardians have been notified.']);
    }

    /**
     * دریافت تاریخچه سفرهای کاربر
     * @param Request $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function history(Request $request)
    {
        return $request->user()->trips()->latest()->paginate(15);
    }


    public function uploadEmergencyPhoto(Request $request, Trip $trip)
    {
        if ($request->user()->id !== $trip->user_id || $trip->status !== 'emergency') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate(['photo' => 'required|image|max:5120']); // 5MB Max

        $path = $request->file('photo')->store('emergency_photos', 'public');
        $trip->emergency_photo_path = $path;
        $trip->save();

        return response()->json(['message' => 'عکس اضطراری با موفقیت آپلود شد.', 'path' => $path]);
    }
}
