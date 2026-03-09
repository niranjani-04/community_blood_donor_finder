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
    <title>Live Fleet Tracking - Community-Based Emergency Blood Donor Finder System</title>
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
            --bg-light: #f8f9fa;
            --glass: rgba(255, 255, 255, 0.9);
            --border: rgba(0, 0, 0, 0.08);
            --text-main: #1a1a1a;
            --text-muted: #666666;
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-main);
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
            top: 20px;
            left: 20px;
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
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            pointer-events: auto;
            margin-bottom: 20px;
        }

        .status-badge {
            background: rgba(255, 45, 85, 0.08);
            color: var(--primary);
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 45, 85, 0.15);
        }

        .pulse-red {
            display: inline-block;
            width: 8px;
            height: 8px;
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
            background: white;
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 14px 28px;
            border-radius: 18px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .back-btn:hover {
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }

        /* Leaflet Overrides */
        .leaflet-container { background: #e0e0e0 !important; }
        .leaflet-bar { border: none !important; box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; border-radius: 12px !important; overflow: hidden; }
        .leaflet-bar a { background: white !important; color: #333 !important; border-bottom: 1px solid #eee !important; transition: background 0.2s; }
        .leaflet-bar a:hover { background: #f8f9fa !important; color: var(--primary) !important; }

        /* Custom Popup Style */
        .leaflet-popup-content-wrapper {
            border-radius: 16px;
            padding: 5px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <div class="overlay-ui">
        <div class="glass-panel">
            <div class="status-badge"><span class="pulse-red"></span> LIVE SOS TRACKING</div>
            <h2 class="h4 fw-bold mb-1">Donor Response Fleet</h2>
            <p class="text-muted small mb-4">Tracking active verified donors for Alert #<?php echo $alert_id; ?></p>
            
            <div id="status-card" class="p-3 rounded-4 bg-light border border-dark border-opacity-10">
                <div id="status-text" class="small fw-bold text-primary">Initializing tracker...</div>
                <div id="sync-time" class="text-muted mt-2" style="font-size: 0.7rem;"></div>
            </div>

            <?php 
            if(isset($_SESSION['role']) && $_SESSION['role'] == 'donor'): 
                $check_stmt = $conn->prepare("SELECT response_id FROM sos_responses WHERE alert_id = ? AND donor_id = ? AND status = 'accepted'");
                $check_stmt->execute([$alert_id, $_SESSION['user_id']]);
                if($check_stmt->rowCount() > 0):
            ?>
            <div id="donor-controls" class="mt-3">
                <button onclick="withdrawResponse()" class="btn btn-danger btn-sm w-100 rounded-pill py-2 shadow-sm font-weight-bold">
                    <i class="fas fa-times-circle me-1"></i> Cancel My Response
                </button>
                <p class="text-xs text-muted mt-2 px-2 text-center">Only cancel if you are absolutely unable to reach the location.</p>
            </div>
            <?php 
                endif;
            endif; 
            ?>
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
        
        // CartoDB Voyager tiles (Google Maps-like light theme)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            subdomains: 'abcd'
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
        
        // Haversine formula to calculate distance in km
        function getDistance(lat1, lon1, lat2, lon2) {
            var R = 6371; // Radius of the earth in km
            var dLat = deg2rad(lat2-lat1);
            var dLon = deg2rad(lon2-lon1); 
            var a = 
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
                Math.sin(dLon/2) * Math.sin(dLon/2); 
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
            var d = R * c; // Distance in km
            return d;
        }

        function deg2rad(deg) {
            return deg * (Math.PI/180);
        }

        const evtSource = new EventSource('backend/sse_single_track.php?alert_id=<?php echo $alert_id; ?>');

        evtSource.onmessage = function(event) {
            const donors = JSON.parse(event.data);
            
            if(donors.length > 0) {
                $('#status-text').html('<span class="text-success"><i class="fas fa-check-circle me-1"></i> ' + donors.length + ' Donor(s) Intercepting</span>');
                
                var bounds = L.latLngBounds();
                bounds.extend([reqLat, reqLng]);

                var listHtml = '<h6 class="text-xs fw-bold text-muted text-uppercase mb-3 mt-4" style="font-size: 0.7rem; letter-spacing: 1px;">Fleet Status</h6>';

                donors.forEach(function(d) {
                    var lat = parseFloat(d.latitude);
                    var lng = parseFloat(d.longitude);
                    var key = "d_" + d.phone;
                    bounds.extend([lat, lng]);

                    // Calculate distance and ETA (avg speed 20km/h)
                    var dist = getDistance(reqLat, reqLng, lat, lng);
                    var eta = Math.ceil((dist / 20) * 60); // minutes
                    var distStr = (dist < 1) ? (dist * 1000).toFixed(0) + 'm' : dist.toFixed(1) + 'km';

                    // Check for Staleness (No update in 2 minutes for warning, 5 mins for auto-stale)
                    var updatedAt = new Date(d.updated_at).getTime();
                    var now = new Date().getTime();
                    var diffMins = (now - updatedAt) / (1000 * 60);
                    var isStale = diffMins > 2;
                    var statusColor = isStale ? 'bg-warning' : 'bg-success';
                    var statusText = isStale ? '⚠️ No recent movement' : 'On the move';

                    listHtml += `
                        <div class="d-flex align-items-center mb-3 p-3 rounded-4" style="background: white; border: 1px solid var(--border); box-shadow: 0 5px 15px rgba(0,0,0,0.04);">
                            <div class="${statusColor} rounded-circle me-3 shadow-sm" style="width:10px; height:10px; border: 2px solid white;"></div>
                            <div class="flex-grow-1">
                                <div class="small fw-bold text-dark">${d.name}</div>
                                <div class="text-xs ${isStale ? 'text-warning' : 'text-success'} fw-bold" style="font-size: 0.75rem;">
                                    ${isStale ? 'STALLING' : distStr + ' • ' + eta + ' mins away'}
                                </div>
                            </div>
                        </div>
                    `;

                    var donorIcon = L.divIcon({
                        className: 'donor-icon',
                        html: "<div style='background: #10b981; width: 12px; height: 12px; border-radius: 50%; box-shadow: 0 0 10px #10b981; border: 2px solid white;'></div>",
                        iconSize: [12, 12],
                        iconAnchor: [6, 6]
                    });

                    var popupContent = `
                        <div style="color: #000; font-family: 'Outfit';">
                            <b>${d.name}</b><br>
                            <span class="badge bg-success">${d.blood_group}</span><br>
                            <hr style="margin:5px 0">
                            <b>${distStr}</b> | <b>${eta} min ETA</b>
                        </div>
                    `;

                    if (markers[key]) {
                        markers[key].setLatLng([lat, lng]).setPopupContent(popupContent);
                        if(d.accuracy && markers[key + "_acc"]) {
                            markers[key + "_acc"].setLatLng([lat, lng]).setRadius(d.accuracy);
                        }
                    } else {
                        markers[key] = L.marker([lat, lng], {icon: donorIcon}).addTo(map)
                            .bindPopup(popupContent);
                        
                        if(d.accuracy) {
                            markers[key + "_acc"] = L.circle([lat, lng], {
                                radius: d.accuracy,
                                color: '#10b981',
                                fillColor: '#10b981',
                                fillOpacity: 0.1,
                                weight: 1
                            }).addTo(map);
                        }
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

                $('#status-card').nextAll('.fleet-list').remove();
                $('#status-card').after('<div class="fleet-list">' + listHtml + '</div>');

                map.fitBounds(bounds, {padding: [100, 100]});

            } else {
                $('#status-text').html("<i class='fas fa-satellite-dish animate-pulse me-2'></i> Waiting for responses...");
                $('.fleet-list').remove();
            }
            
            $('#sync-time').html("<i class='far fa-clock me-1'></i> Last Update: " + new Date().toLocaleTimeString());
        };

        evtSource.onerror = function(err) {
            console.error("SSE connection failed", err);
        };

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'donor'): ?>
        if ("geolocation" in navigator) {
            navigator.geolocation.watchPosition(
                function(p) {
                    $.post('backend/update_location.php', { 
                        latitude: p.coords.latitude, 
                        longitude: p.coords.longitude,
                        accuracy: p.coords.accuracy
                    });
                }, 
                function(e) { console.log(e); },
                { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
            );
        }

        function withdrawResponse() {
            if (confirm("Are you sure you want to withdraw your response? Use this ONLY if you are unable to reach the location.")) {
                $.post('backend/sos_withdraw.php', { alert_id: '<?php echo $alert_id; ?>' }, function(res) {
                    if (res.status === 'success') {
                        alert("Response withdrawn. Redirecting to dashboard...");
                        window.location.href = 'index.php';
                    } else {
                        alert("Error: " + res.message);
                    }
                }, 'json');
            }
        }
        <?php endif; ?>
    </script>

</body>
</html>
