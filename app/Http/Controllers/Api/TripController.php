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
     * شروع یک سفر جدید با دریافت اولین موقعیت رمزنگاری شده
     */
    public function start(Request $request): JsonResponse
    {
        // ۱. اعتبارسنجی درخواست multipart
        $request->validate([
            'data' => 'required|json',
            'plate_photo' => 'nullable|image|max:5120',
        ]);

        $tripData = json_decode($request->data, true);

        // ۲. اعتبارسنجی داده‌های JSON (با دریافت داده رمزنگاری شده اولیه)
        $validator = Validator::make($tripData, [
            'vehicle_info' => 'nullable|array',
            'initial_encrypted_data' => 'required|string', // <-- تغییر کلیدی
            'guardians' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // ۳. بررسی نگهبانان پیش‌فرض
        $guardiansData = $tripData['guardians'] ?? [];
        if (empty($guardiansData)) {
            $defaultGuardians = $user->guardians()->where('is_default', true)->get();
            if ($defaultGuardians->isEmpty()) {
                return response()->json(['message' => 'هیچ نگهبانی برای این سفر انتخاب نشده و نگهبان پیش‌فرضی نیز وجود ندارد.'], 422);
            }
            $guardiansData = $defaultGuardians->map(fn ($g) => ['name' => $g->name, 'phone_number' => $g->phone_number])->toArray();
        }
        
        // ۴. ذخیره عکس پلاک
        $platePhotoPath = null;
        if ($request->hasFile('plate_photo')) {
            $platePhotoPath = $request->file('plate_photo')->store('plate_photos', 'public');
        }

        // ۵. ایجاد سفر جدید
        $trip = $user->trips()->create([
            'vehicle_info' => $tripData['vehicle_info'] ?? null,
            'plate_photo_path' => $platePhotoPath,
            'status' => 'active',
            'share_token' => Str::random(40),
            'expires_at' => Carbon::now()->addHours(3),
            'deletable_at' => Carbon::now()->addDays(15),
        ]);

        // ۶. ذخیره نگهبانان سفر
        foreach ($guardiansData as $guardianItem) {
            $trip->guardians()->create($guardianItem);
        }
        
        // ۷. ذخیره اولین موقعیت مکانی رمزنگاری شده
        $trip->locations()->create([
            'encrypted_data' => $tripData['initial_encrypted_data']
        ]);

        // ۸. ارسال نوتیفیکیشن
        $this->notificationService->sendTripStartedNotifications($trip->fresh('user', 'guardians'));

        return response()->json([
            'message' => 'سفر با موفقیت آغاز شد.',
            'trip' => $trip->load('guardians', 'locations')
        ], 201);
    }

    /**
     * به‌روزرسانی موقعیت مکانی با دریافت داده‌های رمزنگاری شده
     */
    public function updateLocation(Request $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate(['data' => 'required|string']);

        $trip->locations()->create([
            'encrypted_data' => $request->data
        ]);

        return response()->json(['message' => 'Location updated.']);
    }

    /**
     * اعلام اتمام سفر
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

        return response()->json(['message' => 'Trip completed successfully.']);
    }

    /**
     * فعال‌سازی وضعیت اضطراری
     */
    public function triggerSOS(Request $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $trip->status = 'emergency';
        $trip->save();

        $this->notificationService->sendSosNotifications($trip->fresh('user', 'guardians'));

        return response()->json(['message' => 'SOS triggered. Guardians have been notified.']);
    }

    /**
     * دریافت تاریخچه سفرهای کاربر
     */
    public function history(Request $request)
    {
        // با توجه به منطق جدید، کاربر به تاریخچه دسترسی ندارد.
        // اما این اندپوینت را برای استفاده‌های احتمالی آینده نگه می‌داریم.
        return $request->user()->trips()->latest()->paginate(15);
    }

    /**
     * آپلود عکس اضطراری
     */
    public function uploadEmergencyPhoto(Request $request, Trip $trip)
    {
        if ($request->user()->id !== $trip->user_id || $trip->status !== 'emergency') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate(['photo' => 'required|image|max:5120']);

        $path = $request->file('photo')->store('emergency_photos', 'public');
        $trip->emergency_photo_path = $path;
        $trip->save();

        return response()->json(['message' => 'عکس اضطراری با موفقیت آپلود شد.', 'path' => $path]);
    }
}
