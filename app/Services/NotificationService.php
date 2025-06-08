<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\ShortUrl;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    private ?string $apiKey;
    private ?string $senderNumber;
    private ?string $tripStartedPatternCode;
    private ?string $sosPatternCode;

    public function __construct()
    {
        // خواندن تنظیمات از فایل .env
        $this->apiKey = env('IPPANEL_API_KEY');
        $this->senderNumber = env('IPPANEL_SENDER_NUMBER');
        $this->tripStartedPatternCode = env('IPPANEL_TRIP_STARTED_PATTERN_CODE');
        $this->sosPatternCode = env('IPPANEL_SOS_PATTERN_CODE');
    }

    /**
     * ارسال پیامک شروع سفر به تمام نگهبانان
     */
    public function sendTripStartedNotifications(Trip $trip)
    {
        // ایجاد لینک کوتاه
        $longUrl = route('trip.public_view', ['share_token' => $trip->share_token]);
        $shortUrl = $this->createShortUrl($trip);

        $variables = [
            'passengerName' => $trip->user->name,
            //'link' => $shortUrl
        ];

        foreach ($trip->guardians as $guardian) {
            $this->sendPatternSms($guardian->phone_number, $this->tripStartedPatternCode, $variables);
        }
    }

    /**
     * ارسال پیامک هشدار اضطراری (SOS)
     */
    public function sendSosNotifications(Trip $trip)
    {
        // ایجاد لینک کوتاه
        $longUrl = route('trip.public_view', ['share_token' => $trip->share_token]);
        $shortUrl = $this->createShortUrl($trip);
        
        $variables = [
            'passengerName' => $trip->user->name,
            //'link' => $shortUrl
        ];

        foreach ($trip->guardians as $guardian) {
            $this->sendPatternSms($guardian->phone_number, $this->sosPatternCode, $variables);
        }
    }

    /**
     * متد برای ایجاد و ذخیره لینک کوتاه
     */
    private function createShortUrl(Trip $trip): string // ورودی متد را به $trip تغییر دهید
    {
        $longUrl = route('trip.public_view', ['share_token' => $trip->share_token]);
        $shortCode = Str::random(8);
        
        ShortUrl::create([
            'trip_id' => $trip->id, // این خط اضافه شده
            'short_code' => $shortCode,
            'long_url' => $longUrl,
        ]);

        return route('shortlink.redirect', ['short_code' => $shortCode]);
    }

    /**
     * متد اصلی برای ارسال پیامک پترن با استفاده از cURL
     *
     * @param string $recipient شماره گیرنده
     * @param string|null $patternCode کد پترن
     * @param array $variables متغیرهای پترن
     * @return bool
     */
    private function sendPatternSms(string $recipient, ?string $patternCode, array $variables): bool
    {
        if (empty($this->apiKey) || empty($this->senderNumber) || empty($patternCode)) {
            Log::error('SMS not sent: API Key, Sender Number or Pattern Code is not configured in .env file.');
            return false;
        }

        $url = 'https://api2.ippanel.com/api/v1/sms/pattern/normal/send';

        $body = [
            'code' => $patternCode,
            'sender' => $this->senderNumber,
            'recipient' => $recipient,
            'variable' => $variables,
        ];

        $headers = [
            'Content-Type: application/json',
            'apikey:' . $this->apiKey,
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::error('cURL Error while sending pattern SMS: ' . $curlError);
                return false;
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                Log::info('Pattern SMS sent successfully to ' . $recipient . '. Response: ' . $response);
                return true;
            } else {
                Log::error('Failed to send pattern SMS to ' . $recipient . '. HTTP Code: ' . $httpCode . '. Response: ' . $response);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Exception while sending pattern SMS: ' . $e->getMessage());
            return false;
        }
    }
}
