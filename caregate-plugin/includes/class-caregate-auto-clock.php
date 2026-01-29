<?php
/**
 * Auto Clock-in/Clock-out system based on GPS location and duty schedule.
 * Automatically clocks in workers when they arrive at facility during shift.
 * Automatically clocks out when they leave or go offline.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_Auto_Clock {

    /**
     * Geofence radius in meters (configurable).
     */
    private static $geofence_radius = 100; // 100 meters

    /**
     * GPS accuracy threshold in meters.
     */
    private static $gps_accuracy_threshold = 20; // 20 meters

    /**
     * Check if worker is within facility geofence.
     */
    public static function is_within_geofence($worker_location, $facility_location) {
        if (empty($worker_location['lat']) || empty($worker_location['lng']) || 
            empty($facility_location['lat']) || empty($facility_location['lng'])) {
            return false;
        }

        $distance = self::calculate_distance($worker_location, $facility_location);
        $geofence_km = self::$geofence_radius / 1000; // Convert to km

        return $distance <= $geofence_km;
    }

    /**
     * Calculate distance between two GPS coordinates (Haversine formula).
     */
    private static function calculate_distance($loc1, $loc2) {
        $earth_radius = 6371; // km

        $dLat = deg2rad($loc2['lat'] - $loc1['lat']);
        $dLon = deg2rad($loc2['lng'] - $loc1['lng']);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($loc1['lat'])) * cos(deg2rad($loc2['lat'])) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earth_radius * $c; // Distance in km
    }

    /**
     * Get active shift for worker at current time.
     */
    private static function get_active_shift_for_worker($worker_id) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'caregate_bookings';
        $shifts_table = $wpdb->prefix . 'caregate_shifts';
        
        $current_time = current_time('mysql');
        
        // Find confirmed booking with shift happening now
        $result = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, s.start_time, s.end_time, s.facility_id, s.location_lat, s.location_lng
            FROM $bookings_table b
            INNER JOIN $shifts_table s ON b.shift_id = s.id
            WHERE b.worker_id = %d 
            AND b.status = 'confirmed'
            AND s.start_time <= %s
            AND s.end_time >= %s
            AND s.status = 'open'
            LIMIT 1
        ", $worker_id, $current_time, $current_time), ARRAY_A);
        
        return $result;
    }

    /**
     * Check if worker should be auto clocked in.
     */
    public static function check_auto_clock_in($request) {
        $params = $request->get_json_params();
        
        $worker_id = intval($params['workerId'] ?? get_current_user_id());
        $worker_location = $params['location'] ?? array();
        $gps_enabled = $params['gpsEnabled'] ?? false;
        
        if (!$worker_id || !$gps_enabled) {
            return new WP_REST_Response(array(
                'shouldClockIn' => false,
                'reason' => 'GPS not enabled or worker ID missing'
            ), 200);
        }

        // Check if already clocked in
        global $wpdb;
        $clock_table = $wpdb->prefix . 'caregate_clock_records';
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $clock_table WHERE worker_id = %d AND clock_out_time IS NULL",
            $worker_id
        ));

        if ($existing) {
            return new WP_REST_Response(array(
                'shouldClockIn' => false,
                'reason' => 'Already clocked in'
            ), 200);
        }

        // Get active shift for worker
        $shift = self::get_active_shift_for_worker($worker_id);
        
        if (!$shift) {
            return new WP_REST_Response(array(
                'shouldClockIn' => false,
                'reason' => 'No active shift scheduled at this time'
            ), 200);
        }

        // Check if within geofence
        $facility_location = array(
            'lat' => $shift['location_lat'],
            'lng' => $shift['location_lng']
        );

        $within_geofence = self::is_within_geofence($worker_location, $facility_location);
        
        if (!$within_geofence) {
            $distance = self::calculate_distance($worker_location, $facility_location) * 1000; // meters
            return new WP_REST_Response(array(
                'shouldClockIn' => false,
                'reason' => 'Not at facility location',
                'distance' => round($distance, 0),
                'geofenceRadius' => self::$geofence_radius
            ), 200);
        }

        // All conditions met - should auto clock in
        return new WP_REST_Response(array(
            'shouldClockIn' => true,
            'shift' => array(
                'id' => $shift['shift_id'],
                'bookingId' => $shift['id'],
                'facilityId' => $shift['facility_id'],
                'startTime' => $shift['start_time'],
                'endTime' => $shift['end_time']
            )
        ), 200);
    }

    /**
     * Perform auto clock-in.
     */
    public static function auto_clock_in($request) {
        $params = $request->get_json_params();
        
        $worker_id = intval($params['workerId'] ?? get_current_user_id());
        $shift_id = intval($params['shiftId'] ?? 0);
        $booking_id = intval($params['bookingId'] ?? 0);
        $facility_id = intval($params['facilityId'] ?? 0);
        $location = $params['location'] ?? array();
        
        // Validate again before clocking in
        $check_response = self::check_auto_clock_in($request);
        $check_data = $check_response->get_data();
        
        if (!$check_data['shouldClockIn']) {
            return new WP_Error('cannot_clock_in', $check_data['reason'], array('status' => 400));
        }

        // Perform clock in via existing clock system
        $clock_request = new WP_REST_Request('POST', '/caregate/v1/clock/in');
        $clock_request->set_body_params(array(
            'workerId' => $worker_id,
            'facilityId' => $facility_id,
            'shiftId' => $shift_id,
            'bookingId' => $booking_id,
            'method' => 'auto',
            'location' => json_encode($location),
            'notes' => 'Auto clocked in via GPS'
        ));

        return CareGate_Clock::clock_in($clock_request);
    }

    /**
     * Check if worker should be auto clocked out.
     */
    public static function check_auto_clock_out($request) {
        $params = $request->get_json_params();
        
        $worker_id = intval($params['workerId'] ?? get_current_user_id());
        $worker_location = $params['location'] ?? array();
        $is_online = $params['isOnline'] ?? true;
        $gps_enabled = $params['gpsEnabled'] ?? false;
        
        if (!$worker_id) {
            return new WP_REST_Response(array(
                'shouldClockOut' => false,
                'reason' => 'Worker ID missing'
            ), 200);
        }

        // Check if currently clocked in
        global $wpdb;
        $clock_table = $wpdb->prefix . 'caregate_clock_records';
        $shifts_table = $wpdb->prefix . 'caregate_shifts';
        
        $clock_record = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, s.location_lat, s.location_lng, s.end_time 
             FROM $clock_table c
             LEFT JOIN $shifts_table s ON c.shift_id = s.id
             WHERE c.worker_id = %d AND c.clock_out_time IS NULL 
             ORDER BY c.clock_in_time DESC LIMIT 1",
            $worker_id
        ), ARRAY_A);

        if (!$clock_record) {
            return new WP_REST_Response(array(
                'shouldClockOut' => false,
                'reason' => 'Not currently clocked in'
            ), 200);
        }

        // Check if shift has ended
        $current_time = current_time('mysql');
        if ($clock_record['end_time'] && $current_time > $clock_record['end_time']) {
            return new WP_REST_Response(array(
                'shouldClockOut' => true,
                'reason' => 'Shift has ended',
                'clockRecordId' => $clock_record['id']
            ), 200);
        }

        // Check if app went offline
        if (!$is_online) {
            return new WP_REST_Response(array(
                'shouldClockOut' => true,
                'reason' => 'App went offline',
                'clockRecordId' => $clock_record['id']
            ), 200);
        }

        // Check if worker left facility (only if GPS enabled and location available)
        if ($gps_enabled && !empty($worker_location) && !empty($clock_record['location_lat'])) {
            $facility_location = array(
                'lat' => $clock_record['location_lat'],
                'lng' => $clock_record['location_lng']
            );

            $within_geofence = self::is_within_geofence($worker_location, $facility_location);
            
            if (!$within_geofence) {
                $distance = self::calculate_distance($worker_location, $facility_location) * 1000;
                return new WP_REST_Response(array(
                    'shouldClockOut' => true,
                    'reason' => 'Left facility location',
                    'distance' => round($distance, 0),
                    'clockRecordId' => $clock_record['id']
                ), 200);
            }
        }

        // Still within geofence and online
        return new WP_REST_Response(array(
            'shouldClockOut' => false,
            'reason' => 'Still at facility and online'
        ), 200);
    }

    /**
     * Perform auto clock-out.
     */
    public static function auto_clock_out($request) {
        $params = $request->get_json_params();
        
        $worker_id = intval($params['workerId'] ?? get_current_user_id());
        $reason = sanitize_text_field($params['reason'] ?? 'Auto clocked out');
        
        // Validate again before clocking out
        $check_response = self::check_auto_clock_out($request);
        $check_data = $check_response->get_data();
        
        if (!$check_data['shouldClockOut']) {
            return new WP_Error('cannot_clock_out', $check_data['reason'], array('status' => 400));
        }

        // Perform clock out via existing clock system
        $clock_request = new WP_REST_Request('POST', '/caregate/v1/clock/out');
        $clock_request->set_body_params(array(
            'workerId' => $worker_id,
            'method' => 'auto',
            'notes' => $reason
        ));

        return CareGate_Clock::clock_out($clock_request);
    }

    /**
     * Get geofence configuration.
     */
    public static function get_config($request) {
        return new WP_REST_Response(array(
            'geofenceRadius' => self::$geofence_radius,
            'gpsAccuracyThreshold' => self::$gps_accuracy_threshold,
            'pollingInterval' => 60 // seconds
        ), 200);
    }
}
