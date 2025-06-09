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
        #map { height: 65vh; width: 100%; border-radius: 1rem; z-index: 10; }
        .status-emergency { animation: pulse-bg 1.5s infinite; }
        @keyframes pulse-bg {
            0% { background-color: #fecaca; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { background-color: #ef4444; color: white; box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { background-color: #fecaca; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4 max-w-2xl">
        <div class="bg-white rounded-2xl shadow-md p-5 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">سفر <span id="passenger-name" class="text-blue-600">...</span></h1>
                    <p class="text-sm text-gray-500">وضعیت فعلی:</p>
                </div>
                <div id="trip-status" class="px-4 py-2 rounded-full font-semibold text-sm transition-all text-gray-700 bg-gray-200">
                    درحال بارگذاری...
                </div>
            </div>
            <hr class="my-4">
            <div id="details-container" class="space-y-3" style="display: none;">
                <div class="flex items-start">
                    <img id="plate-photo" src="" alt="عکس پلاک" class="w-32 h-auto rounded-lg ml-4 border object-cover" style="display: none;">
                    <div>
                        <p class="text-gray-700 font-semibold">اطلاعات خودرو:</p>
                        <p id="vehicle-info" class="text-gray-600 text-sm">...</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="map"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- دریافت المنت‌های DOM ---
            const passengerNameElement = document.getElementById('passenger-name');
            const tripStatusElement = document.getElementById('trip-status');
            const detailsContainer = document.getElementById('details-container');
            const platePhotoElement = document.getElementById('plate-photo');
            const vehicleInfoElement = document.getElementById('vehicle-info');
            
            // --- متغیرهای سراسری ---
            const shareToken = "{{ $share_token }}";
            let map;
            let marker;
            let updateInterval;

            // --- تابع برای مقداردهی اولیه نقشه ---
            function initializeMap(lat, lon) {
                map = L.map('map').setView([lat, lon], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(map);
                marker = L.marker([lat, lon]).addTo(map);
            }

            // --- تابع برای دریافت متن و کلاس وضعیت ---
            function getStatusInfo(status) {
                switch (status) {
                    case 'active':
                        return { text: 'در حال انجام', class: 'bg-blue-100 text-blue-800' };
                    case 'emergency':
                        return { text: 'وضعیت اضطراری!', class: 'status-emergency' };
                    case 'completed':
                        return { text: 'به پایان رسیده', class: 'bg-green-100 text-green-800' };
                    default:
                        return { text: 'نامشخص', class: 'bg-gray-200 text-gray-700' };
                }
            }

            // --- تابع اصلی برای دریافت و به‌روزرسانی داده‌ها ---
            async function fetchTripData() {
                try {
                    const response = await fetch(`/api/public/trip/${shareToken}`);
                    if (!response.ok) {
                        throw new Error('Trip not found or has ended.');
                    }
                    const data = await response.json();

                    // به‌روزرسانی نام مسافر (فقط بار اول)
                    if (passengerNameElement.textContent === '...') {
                        passengerNameElement.textContent = data.passenger_name;
                    }

                    // به‌روزرسانی وضعیت
                    const statusInfo = getStatusInfo(data.status);
                    tripStatusElement.textContent = statusInfo.text;
                    tripStatusElement.className = `px-4 py-2 rounded-full font-semibold text-sm transition-all ${statusInfo.class}`;

                    // نمایش اطلاعات خودرو و عکس
                    if (data.vehicle_info || data.plate_photo_url) {
                        detailsContainer.style.display = 'block';
                        
                        if(data.plate_photo_url) {
                            platePhotoElement.src = data.plate_photo_url;
                            platePhotoElement.style.display = 'block';
                        }
                        
                        if(data.vehicle_info) {
                            const vehicleInfoText = `${data.vehicle_info.type || ''} - ${data.vehicle_info.color || ''}`.trim();
                            vehicleInfoElement.textContent = vehicleInfoText || 'ثبت نشده';
                        }
                    }

                    // به‌روزرسانی نقشه
                    if (data.last_location) {
                        const { latitude, longitude } = data.last_location;
                        if (!map) {
                            initializeMap(latitude, longitude);
                        } else {
                            const newLatLng = new L.LatLng(latitude, longitude);
                            marker.setLatLng(newLatLng);
                            map.panTo(newLatLng);
                        }
                    }
                    
                    // توقف به‌روزرسانی در صورت اتمام سفر
                    if (data.status === 'completed') {
                        clearInterval(updateInterval);
                    }

                } catch (error) {
                    console.error(error);
                    document.body.innerHTML = `<div class="text-center p-8 bg-white rounded-2xl shadow-lg max-w-md mx-auto mt-20">
                        <h1 class="text-2xl font-bold text-gray-800 mb-2">لینک نامعتبر</h1>
                        <p class="text-gray-600">این سفر به پایان رسیده یا یافت نشد.</p></div>`;
                    clearInterval(updateInterval);
                }
            }

            // --- شروع فرآیند ---
            fetchTripData(); // فراخوانی اولیه
            updateInterval = setInterval(fetchTripData, 10000); // تکرار هر ۱۰ ثانیه
        });
    </script>
</body>
</html>
