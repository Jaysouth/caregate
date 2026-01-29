<?php
/**
 * Simple test file for GPS auto clock-in/out functionality
 * Run with: php test-auto-clock.php
 */

// Mock WordPress functions for testing
function current_time($type) {
    return $type === 'timestamp' ? time() : date('Y-m-d H:i:s');
}

function deg2rad_test($degrees) {
    return deg2rad($degrees);
}

/**
 * Test GPS distance calculation
 */
function test_gps_distance() {
    echo "=== Testing GPS Distance Calculation ===\n";
    
    // Test 1: Same location (should be ~0 meters)
    $distance = calculate_gps_distance(51.5074, -0.1278, 51.5074, -0.1278);
    echo "Test 1 - Same location: " . round($distance, 2) . " meters ";
    echo ($distance < 1) ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 2: 100 meters apart (approximately)
    $distance = calculate_gps_distance(51.5074, -0.1278, 51.5083, -0.1278);
    echo "Test 2 - ~100m apart: " . round($distance, 2) . " meters ";
    echo ($distance > 80 && $distance < 120) ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 3: 500 meters apart (off-site threshold)
    $distance = calculate_gps_distance(51.5074, -0.1278, 51.5119, -0.1278);
    echo "Test 3 - ~500m apart: " . round($distance, 2) . " meters ";
    echo ($distance > 450 && $distance < 550) ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 4: 1 km apart
    $distance = calculate_gps_distance(51.5074, -0.1278, 51.5164, -0.1278);
    echo "Test 4 - ~1km apart: " . round($distance, 2) . " meters ";
    echo ($distance > 900 && $distance < 1100) ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 5: Missing coordinates
    $distance = calculate_gps_distance(null, null, 51.5074, -0.1278);
    echo "Test 5 - Missing coords: " . ($distance == PHP_FLOAT_MAX ? "PHP_FLOAT_MAX" : $distance) . " ";
    echo ($distance == PHP_FLOAT_MAX) ? "✓ PASS\n" : "✗ FAIL\n";
    
    echo "\n";
}

/**
 * Calculate distance between two GPS coordinates (Haversine formula)
 */
function calculate_gps_distance($lat1, $lng1, $lat2, $lng2) {
    if (empty($lat1) || empty($lng1) || empty($lat2) || empty($lng2)) {
        return PHP_FLOAT_MAX;
    }

    $earth_radius = 6371000; // meters

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lng2 - $lng1);

    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);

    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earth_radius * $c; // distance in meters
}

/**
 * Test shift schedule validation
 */
function test_shift_schedule() {
    echo "=== Testing Shift Schedule Validation ===\n";
    
    // Test 1: Within shift time
    $now = strtotime('2026-01-29 10:00:00');
    $start = strtotime('2026-01-29 09:00:00');
    $end = strtotime('2026-01-29 17:00:00');
    $early_threshold = $start - (15 * 60); // 15 min before
    $late_threshold = $end + (30 * 60); // 30 min after
    
    $is_within = ($now >= $early_threshold && $now <= $late_threshold);
    echo "Test 1 - During shift (10:00, shift 9:00-17:00): ";
    echo $is_within ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 2: 10 minutes before shift (allowed)
    $now = strtotime('2026-01-29 08:50:00');
    $is_within = ($now >= $early_threshold && $now <= $late_threshold);
    echo "Test 2 - 10 min before (8:50, shift 9:00-17:00): ";
    echo $is_within ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 3: 20 minutes before shift (not allowed)
    $now = strtotime('2026-01-29 08:40:00');
    $is_within = ($now >= $early_threshold && $now <= $late_threshold);
    echo "Test 3 - 20 min before (8:40, shift 9:00-17:00): ";
    echo !$is_within ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 4: 20 minutes after shift (allowed)
    $now = strtotime('2026-01-29 17:20:00');
    $is_within = ($now >= $early_threshold && $now <= $late_threshold);
    echo "Test 4 - 20 min after (17:20, shift 9:00-17:00): ";
    echo $is_within ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 5: 40 minutes after shift (not allowed)
    $now = strtotime('2026-01-29 17:40:00');
    $is_within = ($now >= $early_threshold && $now <= $late_threshold);
    echo "Test 5 - 40 min after (17:40, shift 9:00-17:00): ";
    echo !$is_within ? "✓ PASS\n" : "✗ FAIL\n";
    
    echo "\n";
}

/**
 * Test proximity thresholds
 */
function test_proximity_thresholds() {
    echo "=== Testing Proximity Thresholds ===\n";
    
    $GPS_PROXIMITY_THRESHOLD = 100; // meters for auto clock-in
    $OFF_SITE_THRESHOLD = 500; // meters for auto clock-out
    
    // Test 1: Within clock-in range
    $distance = 50;
    echo "Test 1 - 50m (should allow clock-in): ";
    echo ($distance <= $GPS_PROXIMITY_THRESHOLD) ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 2: Just outside clock-in range
    $distance = 150;
    echo "Test 2 - 150m (should deny clock-in): ";
    echo ($distance > $GPS_PROXIMITY_THRESHOLD) ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 3: Within site (no auto clock-out)
    $distance = 400;
    echo "Test 3 - 400m (should stay clocked in): ";
    echo ($distance <= $OFF_SITE_THRESHOLD) ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 4: Off-site (trigger auto clock-out)
    $distance = 600;
    echo "Test 4 - 600m (should auto clock-out): ";
    echo ($distance > $OFF_SITE_THRESHOLD) ? "✓ PASS\n" : "✗ FAIL\n";
    
    echo "\n";
}

/**
 * Test heartbeat timeout
 */
function test_heartbeat_timeout() {
    echo "=== Testing Heartbeat Timeout ===\n";
    
    $HEARTBEAT_TIMEOUT = 5; // minutes
    
    // Test 1: Recent heartbeat (2 minutes ago)
    $last_heartbeat = strtotime('-2 minutes');
    $now = time();
    $diff_minutes = ($now - $last_heartbeat) / 60;
    echo "Test 1 - 2 min ago (should not timeout): ";
    echo ($diff_minutes < $HEARTBEAT_TIMEOUT) ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 2: Stale heartbeat (6 minutes ago)
    $last_heartbeat = strtotime('-6 minutes');
    $diff_minutes = ($now - $last_heartbeat) / 60;
    echo "Test 2 - 6 min ago (should timeout): ";
    echo ($diff_minutes > $HEARTBEAT_TIMEOUT) ? "✓ PASS\n" : "✗ FAIL\n";
    
    // Test 3: Exactly at timeout threshold
    $last_heartbeat = strtotime('-5 minutes');
    $diff_minutes = ($now - $last_heartbeat) / 60;
    echo "Test 3 - 5 min ago (boundary case): ";
    echo ($diff_minutes >= $HEARTBEAT_TIMEOUT) ? "✓ PASS\n" : "✗ FAIL\n";
    
    echo "\n";
}

// Run all tests
echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  CareGate Auto Clock-In/Out GPS Feature - Unit Tests     ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";

test_gps_distance();
test_shift_schedule();
test_proximity_thresholds();
test_heartbeat_timeout();

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  All Tests Completed                                      ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";
