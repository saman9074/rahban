
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رهبان - ردیابی سفر</title>

    <!-- استایل Leaflet (برای نقشه) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { font-family: sans-serif; margin: 0; }
        #map { height: 70vh; width: 100%; }
        .info-panel { padding: 15px; background-color: #f5f5f5; }
        .info-panel h1 { margin-top: 0; }
        .status-active { color: green; }
        .status-emergency { color: red; font-weight: bold; animation: blink 1s linear infinite; }
        @keyframes blink { 50% { opacity: 0; } }
    </style>
</head>
<body>

    <div class="info-panel">
        <h1>سفر <span id="passenger-name"></span></h1>
        <p>وضعیت: <b id="trip-status">درحال بارگذاری...</b></p>
        <div id="vehicle-info-container" style="display:none;">
            <p>اطلاعات خودرو: <span id="vehicle-info"></span></p>
        </div>
    </div>

    <div id="map"></div>

    <!-- اسکریپت Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const shareToken = "{{ $share_token }}";
        const mapElement = document.getElementById('map');
        const statusElement = document.getElementById('trip-status');
        const passengerNameElement = document.getElementById('passenger-name');
        const vehicleInfoContainer = document.getElementById('vehicle-info-container');
        const vehicleInfoElement = document.getElementById('vehicle-info');

        let map;
        let marker;

        function initializeMap(lat, lon) {
            map = L.map(mapElement).setView([lat, lon], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            marker = L.marker([lat, lon]).addTo(map);
        }

        async function fetchTripData() {
            try {
                const response = await fetch(`/api/public/trip/${shareToken}`);
                if (!response.ok) {
                    throw new Error('Trip not found or ended.');
                }
                const data = await response.json();

                // به‌روزرسانی نام مسافر (فقط بار اول)
                if (passengerNameElement.textContent === '') {
                    passengerNameElement.textContent = data.passenger_name;
                }

                // به‌روزرسانی وضعیت
                statusElement.textContent = getStatusText(data.status);
                statusElement.className = `status-${data.status}`;

                // به‌روزرسانی اطلاعات خودرو
                if (data.vehicle_info && data.vehicle_info.plate) {
                    vehicleInfoElement.textContent = `${data.vehicle_info.type || ''} - ${data.vehicle_info.color || ''} - ${data.vehicle_info.plate}`;
                    vehicleInfoContainer.style.display = 'block';
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
                if (data.status === 'completed' || data.status === 'emergency') {
                    clearInterval(intervalId);
                }

            } catch (error) {
                statusElement.textContent = "سفر پایان یافته یا نامعتبر است.";
                statusElement.className = '';
                clearInterval(intervalId);
            }
        }
        
        function getStatusText(status) {
            if (status === 'active') return 'در حال انجام';
            if (status === 'emergency') return 'وضعیت اضطراری!';
            if (status === 'completed') return 'به پایان رسیده';
            return 'نامشخص';
        }

        // اولین فراخوانی و سپس تکرار هر ۱۰ ثانیه
        fetchTripData();
        const intervalId = setInterval(fetchTripData, 10000);

    </script>
</body>
</html>

<!-- resources/views/public/trip_invalid.blade.php -->
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سفر نامعتبر</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding-top: 50px; }
    </style>
</head>
<body>
    <h1>لینک نامعتبر</h1>
    <p>این سفر به پایان رسیده، منقضی شده و یا یافت نشد.</p>
</body>
</html>
