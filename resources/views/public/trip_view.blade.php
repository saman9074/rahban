<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رهبان - ردیابی زنده سفر</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700&display=swap">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        #map { height: 65vh; width: 100%; border-radius: 1rem; z-index: 10; filter: blur(8px); transition: filter 0.5s ease-in-out; }
        #map.unlocked { filter: blur(0px); }
        .status-emergency { animation: pulse-bg 1.5s infinite; }
        @keyframes pulse-bg {
            0% { background-color: #fecaca; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { background-color: #ef4444; color: white; box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { background-color: #fecaca; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- مودال برای دریافت کلید رمزگشایی -->
    <div id="key-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm text-center">
            <h2 class="text-xl font-bold mb-4">ورود به سفر امن</h2>
            <p class="text-gray-600 mb-6">برای مشاهده موقعیت، لطفاً ۵ کلمه امنیتی که از مسافر دریافت کرده‌اید را وارد کنید.</p>
            <input type="text" id="decryption-key-input" class="w-full p-3 text-center border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500 transition" placeholder="مثال: میز-آبی-سفر-جاده-۹">
            <p id="error-message" class="text-red-500 text-sm mt-2 h-4"></p>
            <button id="unlock-button" class="w-full mt-2 bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition-all">مشاهده سفر</button>
        </div>
    </div>

    <!-- محتوای اصلی صفحه (در ابتدا مخفی) -->
    <div id="main-content" class="opacity-0 transition-opacity duration-500">
        <div class="container mx-auto p-4 max-w-2xl">
            <div class="bg-white rounded-2xl shadow-md p-5 mb-4">
                <!-- ... بخش اطلاعات سفر ... -->
            </div>
            <div id="map"></div>
        </div>
    </div>

    <!-- کتابخانه‌های مورد نیاز -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- دریافت المنت‌های DOM ---
            const keyModal = document.getElementById('key-modal');
            const mainContent = document.getElementById('main-content');
            const keyInput = document.getElementById('decryption-key-input');
            const unlockButton = document.getElementById('unlock-button');
            const mapElement = document.getElementById('map');
            const errorMessage = document.getElementById('error-message');
            // ... (سایر المنت‌ها)

            // --- متغیرهای سراسری ---
            const shareToken = "{{ $share_token }}";
            let decryptionKey;
            let map;
            let polyline;
            let marker;
            let updateInterval;

            // --- تابع برای تولید کلید از کلمات ---
            async function generateKeyFromWords(words) {
                const combined = words.replace(/[\s-]/g, ''); // حذف فاصله و خط تیره
                const hash = CryptoJS.SHA256(combined);
                return hash.toString(CryptoJS.enc.Hex);
            }

            // --- تابع برای رمزگشایی داده‌ها ---
            function decryptLocation(encryptedData, key) {
                try {
                    const decryptedBytes = CryptoJS.AES.decrypt(encryptedData, key);
                    const decryptedJson = decryptedBytes.toString(CryptoJS.enc.Utf8);
                    if (!decryptedJson) return null;
                    return JSON.parse(decryptedJson);
                } catch (e) {
                    console.error("Decryption failed:", e);
                    return null;
                }
            }
            
            // --- تابع برای مقداردهی اولیه نقشه ---
            function initializeMap(initialCoords) {
                map = L.map('map').setView(initialCoords, 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(map);
                polyline = L.polyline([], {color: 'blue'}).addTo(map);
                marker = L.marker(initialCoords).addTo(map);
            }

            // --- تابع اصلی برای دریافت و به‌روزرسانی داده‌ها ---
            async function fetchAndProcessTripData() {
                if (!decryptionKey) return;
                
                try {
                    const response = await fetch(`/api/public/trip/${shareToken}`);
                    if (!response.ok) throw new Error('سفر یافت نشد.');
                    
                    const data = await response.json();
                    
                    // ... (به‌روزرسانی اطلاعات وضعیت و خودرو همانند قبل) ...

                    const decryptedPoints = [];
                    for (const encrypted of data.encrypted_locations) {
                        const point = decryptLocation(encrypted, decryptionKey);
                        if (point) decryptedPoints.push([point.lat, point.lon]);
                    }

                    if (decryptedPoints.length === 0) {
                        errorMessage.textContent = 'کلمات امنیتی اشتباه است.';
                        // به صورت ایده‌آل می‌توان مودال را دوباره نمایش داد
                        return;
                    }

                    errorMessage.textContent = ''; // پاک کردن خطا در صورت موفقیت
                    
                    if (!map) {
                        initializeMap(decryptedPoints[0]);
                    }
                    
                    polyline.setLatLngs(decryptedPoints);
                    if (decryptedPoints.length > 0) {
                        const lastPoint = decryptedPoints[decryptedPoints.length - 1];
                        marker.setLatLng(lastPoint);
                        map.panTo(lastPoint);
                    }

                    if (data.status === 'completed') {
                        clearInterval(updateInterval);
                    }
                } catch (e) {
                    // ... (مدیریت خطا) ...
                    clearInterval(updateInterval);
                }
            }

            // --- رویداد کلیک روی دکمه بازگشایی ---
            unlockButton.addEventListener('click', async () => {
                const words = keyInput.value.trim();
                if (!words) {
                    errorMessage.textContent = 'لطفاً کلمات امنیتی را وارد کنید.';
                    return;
                }

                decryptionKey = await generateKeyFromWords(words);
                
                keyModal.style.display = 'none';
                mainContent.style.opacity = '1';
                mapElement.classList.add('unlocked');
                
                await fetchAndProcessTripData(); // فراخوانی اولیه
                updateInterval = setInterval(fetchAndProcessTripData, 15000); // به‌روزرسانی هر ۱۵ ثانیه
            });
        });
    </script>
</body>
</html>
