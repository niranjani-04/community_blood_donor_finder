<?php
include_once __DIR__ . '/db_connect.php';
include_once __DIR__ . '/hospital_helper.php';

function renderHospitalStats($conn) {
    $stocks = getBloodStocks();
    $camps = getUpcomingCamps();

    $html = '<div class="row g-3 mt-2">';
    
    // 1. Blood Availability Card
    $html .= '<div class="col-md-6">
                <div class="glass-card h-100">
                    <h5 class="mb-3 text-info"><i class="fas fa-tint"></i> Live Blood Stock</h5>
                    <div class="table-responsive small">
                        <table class="table table-dark table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Hospital</th>
                                    <th>Group</th>
                                    <th>Units</th>
                                </tr>
                            </thead>
                            <tbody>';
    
    if ($stocks) {
        foreach ($stocks as $s) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($s['hospital_name']) . '</td>
                        <td><span class="badge bg-danger">' . $s['blood_group'] . '</span></td>
                        <td class="text-info fw-bold">' . $s['units'] . '</td>
                      </tr>';
        }
    } else {
        $html .= '<tr><td colspan="3" class="text-center text-white-50 py-2">No stock data available</td></tr>';
    }
    
    $html .= '      </tbody>
                        </table>
                    </div>
                </div>
              </div>';

    // 2. Upcoming Camps Card
    $html .= '<div class="col-md-6">
                <div class="glass-card h-100">
                    <h5 class="mb-3 text-warning"><i class="fas fa-calendar-alt"></i> Donation Camps</h5>
                    <div class="camp-list">';
    
    if ($camps) {
        foreach ($camps as $c) {
            $date = date('M j (D)', strtotime($c['camp_date']));
            $time_range = date('h:i A', strtotime($c['start_time'])) . ($c['end_time'] ? ' - ' . date('h:i A', strtotime($c['end_time'])) : ' onwards');
            $map_url = "https://www.google.com/maps/search/" . urlencode($c['location']);
            
            $html .= '<div class="p-3 mb-3 border border-light border-opacity-10 rounded bg-white bg-opacity-10 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0 text-white fw-bold">' . htmlspecialchars($c['title']) . '</h6>
                            <span class="badge bg-warning text-dark">' . $date . '</span>
                        </div>
                        <div class="mb-2 small">
                            <div class="text-white mb-1"><i class="fas fa-university me-2 text-warning"></i><span class="opacity-75">Organized by:</span> ' . htmlspecialchars($c['organized_by']) . '</div>
                            <div class="text-white mb-1">
                                <a href="' . $map_url . '" target="_blank" class="text-white text-decoration-none">
                                    <i class="fas fa-map-marker-alt me-2 text-danger"></i><span class="opacity-75">Venue:</span> <u>' . htmlspecialchars($c['location']) . '</u>
                                </a>
                            </div>
                            <div class="text-white mb-1"><i class="fas fa-clock me-2 text-info"></i><span class="opacity-75">Time:</span> ' . $time_range . '</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-light border-opacity-10">
                            <span class="small text-white"><i class="fas fa-phone-alt me-1 text-success"></i> ' . htmlspecialchars($c['contact_phone'] ?? 'N/A') . '</span>
                            <button class="btn btn-xs btn-info rounded-pill py-0 px-2 text-dark fw-bold" style="font-size: 0.75rem;" onclick="alert(\'Details: ' . addslashes(htmlspecialchars($c['description'])) . '\')">View More</button>
                        </div>
                      </div>';
        }
    } else {
        $html .= '<div class="text-center text-white-50 py-4">No upcoming camps scheduled</div>';
    }
    
    $html .= '      </div>
                </div>
              </div>';
              
    $html .= '</div>';
    
    return $html;
}
?>
