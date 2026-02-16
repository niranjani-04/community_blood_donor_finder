function switchSection(sectionId) {
    $('.tab-section').removeClass('active');
    $('#section-' + sectionId).addClass('active');
    $('.nav-link').removeClass('active text-danger').addClass('text-white');
    $('#tab-' + sectionId).addClass('active');
    if (sectionId === 'sos') $('#tab-sos').addClass('text-danger');
}

let userLat, userLng, gpsActive = false;

$(document).ready(function () {
    updateAlerts();
    setInterval(updateAlerts, 10000);
    if ("geolocation" in navigator) {
        navigator.geolocation.watchPosition(function (pos) {
            userLat = pos.coords.latitude;
            userLng = pos.coords.longitude;
            gpsActive = true;
            $('#gps-status').text('Location Active (Live)').removeClass('text-white-50').addClass('text-success fw-bold');
            $.post('backend/update_location.php', { latitude: userLat, longitude: userLng });
        }, function (err) {
            $('#gps-status').text('GPS Error: ' + err.message).addClass('text-danger');
        });
    }
});

function updateAlerts() {
    $.get('backend/fetch_alerts.php', function (data) {
        $('#alert-container').html(data);
    });
}

function triggerSOS() {
    const bg = $('#blood_req').val();
    if (!bg) return alert('Select blood group');
    if (!gpsActive) return alert('GPS required');
    $('#sos-feedback').html('<div class="spinner-border text-danger"></div> Signaling...');
    $.post('backend/sos_create.php', { blood_group: bg, latitude: userLat, longitude: userLng }, function (res) {
        try {
            const data = JSON.parse(res);
            if (data.status === 'success') {
                $('#sos-feedback').html('<div class="alert alert-success">SOS Sent!</div>');
                setTimeout(() => window.location.href = 'track.php?alert_id=' + data.alert_id, 1000);
            } else {
                $('#sos-feedback').html('<div class="alert alert-danger">' + data.message + '</div>');
            }
        } catch (e) {
            $('#sos-feedback').html('<div class="alert alert-danger">Server Error</div>');
        }
    });
}
