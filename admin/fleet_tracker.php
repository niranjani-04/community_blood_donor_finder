<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Fleet Radar | Community-Based Emergency Blood Donor Finder System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --primary: #ff2d55;
            --secondary: #10b981;
            --dark: #0a0a0c;
            --glass: rgba(15, 15, 20, 0.85);
            --border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
        }

        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background-color: var(--dark);
            color: var(--text-main);
            overflow: hidden;
        }

        #map {
            height: 100vh;
            width: 100vw;
            z-index: 1;
        }

        /* --- UI Overlays --- */
        .sidebar-panel {
            position: fixed;
            top: 20px;
            left: 20px;
            bottom: 20px;
            width: 380px;
            background: var(--glass);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--border);
            border-radius: 28px;
            z-index: 1000;
            padding: 30px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .header-section {
            margin-bottom: 25px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            background: var(--secondary);
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            box-shadow: 0 0 10px var(--secondary);
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .fleet-list {
            flex-grow: 1;
            overflow-y: auto;
            margin-top: 10px;
            scrollbar-width: none;
        }

        .fleet-list::-webkit-scrollbar { display: none; }

        .fleet-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 15px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .fleet-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
            border-color: rgba(255, 45, 85, 0.3);
        }

        .type-badge {
            font-size: 0.65rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .badge-alert { background: rgba(255, 45, 85, 0.15); color: var(--primary); }
        .badge-donor { background: rgba(16, 185, 129, 0.15); color: var(--secondary); }

        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 15px;
            text-align: center;
        }

        .stat-val { font-size: 1.5rem; font-weight: 800; display: block; }
        .stat-label { font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }

        /* --- Map Overrides --- */
        .leaflet-container { background: #0b0d10 !important; }
        .leaflet-bar { border: none !important; box-shadow: 0 10px 25px rgba(0,0,0,0.5) !important; }
        .leaflet-bar a { background: #1e1e24 !important; color: #fff !important; border-bottom: 1px solid #333 !important; }
        
        /* Custom Markers */
        .marker-pulse {
            width: 20px;
            height: 20px;
            background: var(--primary);
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 15px var(--primary);
            animation: pulse-marker 2s infinite;
        }

        @keyframes pulse-marker {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }

        #return-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background: var(--primary);
            color: white;
            padding: 15px 30px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 15px 35px rgba(255, 45, 85, 0.4);
            transition: all 0.3s ease;
            border: none;
        }

        #return-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 40px rgba(255, 45, 85, 0.5);
        }

        .info-card {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 15px 25px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .last-sync {
            font-size: 0.75rem;
            color: #94a3b8;
        }

    </style>
</head>
<body>

    <div class="sidebar-panel">
        <div class="header-section">
            <div class="d-flex align-items-center mb-1">
                <span class="status-dot"></span>
                <span class="fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 2px; color: var(--secondary);">System Online</span>
            </div>
            <h2 class="fw-extrabold mb-0" style="font-size: 1.75rem;">Global Fleet Radar</h2>
            <p class="text-secondary small">Real-time emergency monitoring system</p>
        </div>

        <div class="stat-grid">
            <div class="stat-box">
                <span class="stat-val text-danger" id="count-alerts">0</span>
                <span class="stat-label">Critical Alerts</span>
            </div>
            <div class="stat-box">
                <span class="stat-val text-success" id="count-donors">0</span>
                <span class="stat-label">Active Donors</span>
            </div>
        </div>

        <div class="fleet-list" id="fleet-container">
            <!-- List populated dynamically -->
            <div class="text-center py-5 text-secondary opacity-50">
                <i class="fas fa-satellite fa-3x mb-3"></i>
                <p>Establishing link...</p>
            </div>
        </div>
    </div>

    <div class="info-card">
        <div class="text-end me-3 border-end border-secondary pe-3">
            <div class="fw-bold small text-white">BHC EMERGENCY NETWORK</div>
            <div class="last-sync" id="sync-time">Synchronizing...</div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="bg-danger rounded-circle p-2 d-flex align-items-center justify-content-center shadow-lg" style="width: 44px; height: 44px;">
                <i class="fas fa-shield-alt text-white"></i>
            </div>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm border-0 rounded-circle p-2" title="Log Out">
                <i class="fas fa-power-off fa-lg"></i>
            </a>
        </div>
    </div>

    <a href="dashboard.php" id="return-btn">
        <i class="fas fa-th-large me-2"></i> Control Center
    </a>

    <div id="map"></div>

    <!-- Bootstrap JS for potential dropdowns/modals -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Use a Dark Map Theme
        var map = L.map('map', {
            zoomControl: false,
            attributionControl: false
        }).setView([10.8211, 78.6934], 14);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // CartoDB Dark Matter tiles
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            subdomains: 'abcd'
        }).addTo(map);

        var markers = {};
        
        function getIcon(type, detail) {
            let color = type === 'alert' ? '#ff2d55' : (type === 'donor' ? '#10b981' : '#3b82f6');
            let icon = type === 'alert' ? 'fa-exclamation-triangle' : (type === 'donor' ? 'fa-user' : 'fa-hospital');
            
            return L.divIcon({
                className: 'custom-marker',
                html: `<div style="background: ${color}; width: 32px; height: 32px; border-radius: 12px; border: 3px solid rgba(255,255,255,0.2); box-shadow: 0 5px 15px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white;">
                         <i class="fas ${icon}" style="font-size: 14px;"></i>
                       </div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });
        }

        // SSE Connection
        const evtSource = new EventSource("../backend/sse_tracking.php");

        evtSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            console.log("Update received:", data);

            $('#sync-time').text("Last Sync: " + data.timestamp);
            $('#count-alerts').text(data.alerts.length);
            $('#count-donors').text(data.donors.length);

            updateMap(data);
            updateList(data);
        };

        var polylines = {};
        
        function getDistance(lat1, lon1, lat2, lon2) {
            var R = 6371; // km
            var dLat = (lat2-lat1) * Math.PI / 180;
            var dLon = (lon2-lon1) * Math.PI / 180;
            var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
                    Math.sin(dLon/2) * Math.sin(dLon/2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function updateMap(data) {
            // Map alerts for quick lookup
            let alertMap = {};
            data.alerts.forEach(a => alertMap[a.alert_id] = a);

            // Process Hospitals (only once)
            data.hospitals.forEach(h => {
                let key = 'h_' + h.name;
                if (!markers[key]) {
                    markers[key] = L.marker([h.latitude, h.longitude], {icon: getIcon('hospital')})
                        .addTo(map).bindPopup(`<b>${h.name}</b><br>Hospital`);
                }
            });

            // Process Alerts
            data.alerts.forEach(a => {
                let key = 'a_' + a.alert_id;
                if (markers[key]) {
                    markers[key].setLatLng([a.latitude, a.longitude]);
                } else {
                    markers[key] = L.marker([a.latitude, a.longitude], {icon: getIcon('alert')})
                        .addTo(map).bindPopup(`<b>SOS Alert #${a.alert_id}</b><br>Blood: ${a.blood_group}<br>Req: ${a.requester}`);
                }
            });

            // Process Donors
            data.donors.forEach(d => {
                let key = 'd_' + d.user_id;
                let targetAlert = alertMap[d.alert_id];
                
                let distStr = '';
                let etaStr = '';
                
                if (targetAlert) {
                    let d_dist = getDistance(d.latitude, d.longitude, targetAlert.latitude, targetAlert.longitude);
                    let d_eta = Math.ceil((d_dist / 20) * 60);
                    distStr = (d_dist < 1) ? (d_dist * 1000).toFixed(0) + 'm' : d_dist.toFixed(1) + 'km';
                    etaStr = d_eta + ' min';

                    // Draw/Update Polyline
                    if (polylines[key]) {
                        polylines[key].setLatLngs([[d.latitude, d.longitude], [targetAlert.latitude, targetAlert.longitude]]);
                    } else {
                        polylines[key] = L.polyline([[d.latitude, d.longitude], [targetAlert.latitude, targetAlert.longitude]], {
                            color: '#10b981',
                            weight: 2,
                            opacity: 0.3,
                            dashArray: '5, 10'
                        }).addTo(map);
                    }
                }

                let popupContent = `<b>Donor: ${d.name}</b><br>Blood: ${d.blood_group}`;
                if (distStr) popupContent += `<br><hr style="margin:5px 0"><b>${distStr}</b> away | <b>${etaStr} ETA</b>`;

                if (markers[key]) {
                    markers[key].setLatLng([d.latitude, d.longitude]).setPopupContent(popupContent);
                } else {
                    markers[key] = L.marker([d.latitude, d.longitude], {icon: getIcon('donor')})
                        .addTo(map).bindPopup(popupContent);
                }
            });
        }

        function updateList(data) {
            let html = '';
            let alertMap = {};
            data.alerts.forEach(a => alertMap[a.alert_id] = a);
            
            if (data.alerts.length === 0 && data.donors.length === 0) {
                html = `<div class="text-center py-5 text-secondary opacity-50">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                            <p>All Quiet. No active emergencies.</p>
                        </div>`;
            }

            data.alerts.forEach(a => {
                html += `
                    <div class="fleet-item" onclick="map.flyTo([${a.latitude}, ${a.longitude}], 16)">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="type-badge badge-alert">SOS Alert</span>
                            <span class="small text-danger fw-bold">${a.blood_group}</span>
                        </div>
                        <div class="fw-bold">${a.requester}</div>
                        <div class="text-secondary small mt-1"><i class="fas fa-map-marker-alt me-1"></i> Active Emergency</div>
                    </div>
                `;
            });

            data.donors.forEach(d => {
                let targetAlert = alertMap[d.alert_id];
                let subtext = `Responding to Alert #${d.alert_id}`;
                
                if (targetAlert) {
                    let d_dist = getDistance(d.latitude, d.longitude, targetAlert.latitude, targetAlert.longitude);
                    let d_eta = Math.ceil((d_dist / 20) * 60);
                    let d_distStr = (d_dist < 1) ? (d_dist * 1000).toFixed(0) + 'm' : d_dist.toFixed(1) + 'km';
                    subtext = `<span class="text-success fw-bold">${d_distStr} • ${d_eta} mins</span> away from Alert #${d.alert_id}`;
                }

                html += `
                    <div class="fleet-item" onclick="map.flyTo([${d.latitude}, ${d.longitude}], 16)">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="type-badge badge-donor">Donor</span>
                            <span class="small text-success fw-bold">${d.blood_group}</span>
                        </div>
                        <div class="fw-bold">${d.name}</div>
                        <div class="text-secondary small mt-1">
                            <i class="fas fa-truck-moving me-1"></i> ${subtext}
                        </div>
                    </div>
                `;
            });

            $('#fleet-container').html(html);
        }

        evtSource.onerror = function(err) {
            console.error("SSE failed:", err);
            $('.status-dot').css('background', '#ff2d55').css('box-shadow', '0 0 10px #ff2d55');
            $('.status-dot').next().text('Connection Lost').css('color', '#ff2d55');
        };
    </script>

</body>
</html>
