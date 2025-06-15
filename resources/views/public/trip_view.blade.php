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
        .table-container { max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body class="bg-gray-100">

    <!-- مودال برای دریافت کلید رمزگشایی -->
    <div id="key-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm text-center">
            <h2 class="text-xl font-bold mb-4">ورود به سفر امن</h2>
            <p class="text-gray-600 mb-6">برای مشاهده موقعیت، لطفاً کلمات امنیتی که از مسافر دریافت کرده‌اید را وارد کنید.</p>
            <input type="text" id="decryption-key-input" class="w-full p-3 text-center border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500 transition" placeholder="مثال: میز-آبی-سفر-جاده-۹">
            <p id="error-message" class="text-red-500 text-sm mt-2 h-4"></p>
            <button id="unlock-button" class="w-full mt-2 bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition-all">مشاهده سفر</button>
        </div>
    </div>

    <!-- محتوای اصلی صفحه -->
    <div id="main-content" class="opacity-0 transition-opacity duration-500">
        <div class="container mx-auto p-4 max-w-4xl">
            <div id="main-card" class="bg-white rounded-2xl shadow-md p-5 mb-4">
                <!-- بخش اطلاعات سفر در اینجا با جاوا اسکریپت اضافه می‌شود -->
            </div>
            <div id="map" class="mb-4"></div>

            <!-- جدول داده‌های اضطراری (در ابتدا مخفی) -->
            <div id="sos-table-container" class="bg-white rounded-2xl shadow-md p-5" style="display: none;">
                <h2 class="text-lg font-bold text-red-700 mb-4">گزارش لحظه‌ای داده‌های اضطراری</h2>
                <div class="table-container">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">زمان</th>
                                <th scope="col" class="px-6 py-3">GPS (Lat, Lon, Acc)</th>
                                <th scope="col" class="px-6 py-3">اطلاعات دکل (Cell ID)</th>
                                <th scope="col" class="px-6 py-3">شبکه‌های Wi-Fi اطراف</th>
                            </tr>
                        </thead>
                        <tbody id="sos-table-body">
                            <!-- ردیف‌ها با جاوا اسکریپت اضافه می‌شوند -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ... (بخش دریافت المنت‌ها و متغیرهای سراسری)
            const sosTableContainer = document.getElementById('sos-table-container');
            const sosTableBody = document.getElementById('sos-table-body');
            
            // ... (توابع generateKeyFromWords و initializeMap بدون تغییر)
            
            async function decryptData(encryptedBase64, key) {
                // ... (این تابع بدون تغییر باقی می‌ماند)
            }

            function buildTripInfoCard(data) {
                // ... (این تابع بدون تغییر باقی می‌ماند)
            }

            // --- تابع اصلی برای دریافت و پردازش داده‌ها ---
            async function fetchAndProcessTripData() {
                if (!decryptionCryptoKey) return;
                
                try {
                    const response = await fetch(`/api/public/trip/${shareToken}`);
                    if (!response.ok) throw new Error('سفر یافت نشد.');
                    
                    const data = await response.json();
                    buildTripInfoCard(data);

                    const decryptionPromises = data.encrypted_locations.map(encrypted => decryptData(encrypted, decryptionCryptoKey));
                    const decryptedResults = await Promise.all(decryptionPromises);
                    const validResults = decryptedResults.filter(p => p !== null);

                    if (data.encrypted_locations.length > 0 && validResults.length === 0) {
                        errorMessage.textContent = 'کلمات امنیتی اشتباه است.';
                        return;
                    }
                    errorMessage.textContent = ''; 

                    if (data.status === 'emergency') {
                        sosTableContainer.style.display = 'block';
                        populateSosTable(validResults);
                        updateMapForSos(validResults);
                    } else {
                        sosTableContainer.style.display = 'none';
                        updateMapForNormal(validResults);
                    }
                    
                    if (data.status === 'completed') {
                        clearInterval(updateInterval);
                    }
                } catch (e) {
                    console.error("Error fetching/processing data:", e);
                    clearInterval(updateInterval);
                }
            }
            
            function updateMapForNormal(points) {
                if (points.length === 0) return;
                const latLngs = points.map(p => [p.lat, p.lon]);
                if (!map) initializeMap(latLngs[0]);
                polyline.setLatLngs(latLngs);
                marker.setLatLng(latLngs[latLngs.length - 1]);
                map.panTo(latLngs[latLngs.length - 1]);
            }

            function updateMapForSos(packets) {
                const gpsPoints = packets.map(p => p.gps ? [p.gps.lat, p.gps.lon] : null).filter(p => p !== null);
                if (gpsPoints.length === 0) return;
                if (!map) initializeMap(gpsPoints[0]);
                polyline.setLatLngs(gpsPoints);
                marker.setLatLng(gpsPoints[gpsPoints.length - 1]);
                map.panTo(gpsPoints[gpsPoints.length - 1]);
            }

            function populateSosTable(packets) {
                sosTableBody.innerHTML = ''; // پاک کردن جدول
                packets.forEach(packet => {
                    const row = sosTableBody.insertRow();
                    const time = new Date(packet.ts * 1000).toLocaleTimeString('fa-IR');
                    const gps = packet.gps ? `${packet.gps.lat.toFixed(4)}, ${packet.gps.lon.toFixed(4)} (دقت: ${packet.gps.acc}متر)` : 'N/A';
                    const cell = packet.cell ? `${packet.cell.id}` : 'N/A';
                    const wifiCount = packet.wifi ? packet.wifi.length : 0;

                    row.innerHTML = `
                        <td class="px-6 py-4">${time}</td>
                        <td class="px-6 py-4 font-mono">${gps}</td>
                        <td class="px-6 py-4">${cell}</td>
                        <td class="px-6 py-4">${wifiCount} شبکه</td>
                    `;
                });
            }

            // --- رویداد کلیک روی دکمه بازگشایی ---
            unlockButton.addEventListener('click', async () => {
                const words = keyInput.value.trim();
                if (!words) {
                    errorMessage.textContent = 'لطفاً کلمات امنیتی را وارد کنید.';
                    return;
                }
                unlockButton.disabled = true;
                unlockButton.textContent = 'درحال بررسی...';

                try {
                    decryptionCryptoKey = await generateKeyFromWords(words);
                    await fetchAndProcessTripData(); // اولین فراخوانی برای تست کلید

                    if (!errorMessage.textContent) {
                        keyModal.style.display = 'none';
                        mainContent.style.opacity = '1';
                        mapElement.classList.add('unlocked');
                        updateInterval = setInterval(fetchAndProcessTripData, 15000);
                    } else {
                        unlockButton.disabled = false;
                        unlockButton.textContent = 'مشاهده سفر';
                    }
                } catch (e) {
                     console.error("Key generation failed: ", e);
                     errorMessage.textContent = 'خطا در پردازش کلید.';
                     unlockButton.disabled = false;
                     unlockButton.textContent = 'مشاهده سفر';
                }
            });
        });
    </script>
</body>
</html>
