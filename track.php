<?php
session_start();
include 'backend/db_connect.php';

if (!isset($_GET['alert_id'])) {
    die("No Alert ID specified.");
}
$alert_id = $_GET['alert_id'];

// Get Request Location to center map
$sql = "SELECT latitude, longitude FROM sos_alerts WHERE alert_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$alert_id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

// Default to College Location (Bishop Heber) if no GPS
$req_lat = $req['latitude'] ? $req['latitude'] : 10.8211; 
$req_lng = $req['longitude'] ? $req['longitude'] : 78.6934;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Fleet Tracking - Community Blood Donor Finder</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --primary: #ff2d55;
            --bg-dark: #050505;
            --glass: rgba(20, 20, 30, 0.8);
            --border: rgba(255, 255, 255, 0.1);
        }

        body {
            background-color: var(--bg-dark);
            color: white;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            overflow: hidden;
            height: 100vh;
        }

        #map { 
            height: 100vh; 
            width: 100vw; 
            z-index: 1;
        }

        .overlay-ui {
            position: fixed;
            top: 30px;
            left: 30px;
            z-index: 100;
            width: 360px;
            pointer-events: none;
        }

        .glass-panel {
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            pointer-events: auto;
            margin-bottom: 20px;
        }

        .status-badge {
            background: rgba(255, 45, 85, 0.1);
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 45, 85, 0.2);
        }

        .pulse-red {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: var(--primary);
            border-radius: 50%;
            margin-right: 8px;
            box-shadow: 0 0 0 rgba(255, 45, 85, 0.4);
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 45, 85, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255, 45, 85, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 45, 85, 0); }
        }

        .back-btn {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 100;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            color: white;
            padding: 12px 24px;
            border-radius: 15px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateX(-5px);
        }

        /* Leaflet Overrides */
        .leaflet-container { background: var(--bg-dark) !important; }
        .leaflet-bar { border: none !important; box-shadow: 0 10px 20px rgba(0,0,0,0.3) !important; }
        .leaflet-bar a { background: var(--glass) !important; color: white !important; border-bottom: 1px solid var(--border) !important; backdrop-filter: blur(10px); }
    </style>
</head>
<body>

    <div class="overlay-ui">
        <div class="glass-panel">
            <div class="status-badge"><span class="pulse-red"></span> LIVE SOS TRACKING</div>
            <h2 class="h4 fw-bold mb-1">Donor Response Fleet</h2>
            <p class="text-white-50 small mb-4">Tracking active verified donors for Alert #<?php echo $alert_id; ?></p>
            
            <div id="status-card" class="p-3 rounded-4 bg-white bg-opacity-5 border border-white border-opacity-10">
                <div id="status-text" class="small fw-bold text-warning">Initializing tracker...</div>
                <div id="sync-time" class="text-white-50 mt-2" style="font-size: 0.7rem;"></div>
            </div>
        </div>
    </div>

    <a href="index.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i> Return to Radar
    </a>

    <div id="map"></div>

    <script>
        // Init Map with Dark Theme
        var reqLat = <?php echo $req_lat; ?>;
        var reqLng = <?php echo $req_lng; ?>;
        
        var map = L.map('map', {
            zoomControl: false,
            attributionControl: false
        }).setView([reqLat, reqLng], 15);

        L.control.zoom({ position: 'bottomright' }).addTo(map);
        
        // CartoDB Dark Matter tiles
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19
        }).addTo(map);

        // Requester Marker
        var reqIcon = L.divIcon({
            className: 'custom-div-icon',
            html: "<div style='background: #ff2d55; width: 14px; height: 14px; border-radius: 50%; box-shadow: 0 0 15px #ff2d55; border: 2px solid white;'></div>",
            iconSize: [14, 14],
            iconAnchor: [7, 7]
        });
        L.marker([reqLat, reqLng], {icon: reqIcon}).addTo(map).bindPopup("<b style='color: #000'> SOS Location </b>");

        var markers = {};
        var polylines = {};
        
        function updateMap() {
            $.get('backend/fetch_tracking.php?alert_id=<?php echo $alert_id; ?>', function(donors) {
                if(typeof donors === 'string') { donors = JSON.parse(donors); }

                if(donors.length > 0) {
                    $('#status-text').html('<span class="text-success"><i class="fas fa-check-circle me-1"></i> ' + donors.length + ' Donor(s) Intercepting</span>');
                    
                    var bounds = L.latLngBounds();
                    bounds.extend([reqLat, reqLng]);

                    donors.forEach(function(d) {
                        var lat = parseFloat(d.latitude);
                        var lng = parseFloat(d.longitude);
                        var key = "d_" + d.phone;
                        bounds.extend([lat, lng]);

                        var donorIcon = L.divIcon({
                            className: 'donor-icon',
                            html: "<div style='background: #10b981; width: 12px; height: 12px; border-radius: 50%; box-shadow: 0 0 10px #10b981; border: 2px solid white;'></div>",
                            iconSize: [12, 12],
                            iconAnchor: [6, 6]
                        });

                        if (markers[key]) {
                            markers[key].setLatLng([lat, lng]);
                        } else {
                            markers[key] = L.marker([lat, lng], {icon: donorIcon}).addTo(map)
                                .bindPopup("<div style='color: #000'><b>" + d.name + "</b><br>Group: " + d.blood_group + "</div>");
                        }

                        if (polylines[key]) {
                            polylines[key].setLatLngs([[reqLat, reqLng], [lat, lng]]);
                        } else {
                            polylines[key] = L.polyline([[reqLat, reqLng], [lat, lng]], {
                                color: '#3b82f6', 
                                weight: 2, 
                                opacity: 0.4, 
                                dashArray: '4, 8'
                            }).addTo(map);
                        }
                    });

                    map.fitBounds(bounds, {padding: [100, 100]});

                } else {
                    $('#status-text').html("<i class='fas fa-satellite-dish animate-pulse me-2'></i> Waiting for responses...");
                }
                
                $('#sync-time').html("<i class='far fa-clock me-1'></i> Last Update: " + new Date().toLocaleTimeString());
                
            });
        }

        setInterval(updateMap, 3000);
        updateMap();

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'donor'): ?>
        if ("geolocation" in navigator) {
            navigator.geolocation.watchPosition(
                function(p) {
                    $.post('backend/update_location.php', { latitude: p.coords.latitude, longitude: p.coords.longitude });
                }, 
                function(e) { console.log(e); },
                { enableHighAccuracy: true }
            );
        }
        <?php endif; ?>
    </script>

</body>
</html>
