<?php
/**
 * Clock-in/Clock-out system for timesheets.
 * Supports both manual entry and automatic card scanning.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_Clock {

    /**
     * Distance threshold for GPS verification (in meters).
     */
    const GPS_PROXIMITY_THRESHOLD = 100; // 100 meters

    /**
     * Heartbeat timeout for auto clock-out (in minutes).
     */
    const HEARTBEAT_TIMEOUT = 5; // 5 minutes

    /**
     * Calculate distance between two GPS coordinates (Haversine formula).
     * Returns distance in meters.
     */
    private static function calculate_gps_distance($lat1, $lng1, $lat2, $lng2) {
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
     * Check if current time is within shift schedule.
     */
    private static function is_within_shift_schedule($shift_id, $booking_id = 0) {
        if ($shift_id) {
            $shift = CareGate_Shifts::get_shift_by_id($shift_id);
            if ($shift) {
                $now = current_time('timestamp');
                $start = strtotime($shift['startTime']);
                $end = strtotime($shift['endTime']);
                
                // Allow clock-in up to 15 minutes before shift start
                $early_threshold = $start - (15 * 60);
                // Allow clock-out up to 30 minutes after shift end
                $late_threshold = $end + (30 * 60);
                
                return ($now >= $early_threshold && $now <= $late_threshold);
            }
        }
        
        // If no shift specified, allow clock-in/out anytime
        return true;
    }

    /**
     * Auto clock-in with GPS verification.
     */
    public static function auto_clock_in($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        
        $worker_id = intval($params['workerId'] ?? get_current_user_id());
        $facility_id = intval($params['facilityId'] ?? 0);
        $shift_id = intval($params['shiftId'] ?? 0);
        $booking_id = intval($params['bookingId'] ?? 0);
        
        // GPS coordinates
        $worker_lat = floatval($params['workerLat'] ?? 0);
        $worker_lng = floatval($params['workerLng'] ?? 0);
        $facility_lat = floatval($params['facilityLat'] ?? 0);
        $facility_lng = floatval($params['facilityLng'] ?? 0);

        // Validation
        if (!$worker_id || !$facility_id) {
            return new WP_Error('missing_fields', 'Worker and facility required', array('status' => 400));
        }

        if (!$worker_lat || !$worker_lng || !$facility_lat || !$facility_lng) {
            return new WP_Error('missing_gps', 'GPS coordinates required for auto clock-in. Please enable location services.', array('status' => 400));
        }

        // Check if shift schedule allows clock-in
        if (!self::is_within_shift_schedule($shift_id, $booking_id)) {
            return new WP_Error('outside_schedule', 'Current time is outside shift schedule. Cannot auto clock-in.', array('status' => 400));
        }

        // Calculate GPS distance
        $distance = self::calculate_gps_distance($worker_lat, $worker_lng, $facility_lat, $facility_lng);
        
        if ($distance > self::GPS_PROXIMITY_THRESHOLD) {
            return new WP_Error(
                'gps_too_far', 
                sprintf('Worker is %.0f meters from facility. Must be within %d meters for auto clock-in.', $distance, self::GPS_PROXIMITY_THRESHOLD),
                array('status' => 400)
            );
        }

        // Check if already clocked in
        $table = $wpdb->prefix . 'caregate_clock_records';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE worker_id = %d AND clock_out_time IS NULL ORDER BY clock_in_time DESC LIMIT 1",
            $worker_id
        ), ARRAY_A);

        if ($existing) {
            return new WP_Error('already_clocked_in', 'Worker is already clocked in', array('status' => 400));
        }

        // Create clock record with GPS verification
        $wpdb->insert(
            $table,
            array(
                'worker_id' => $worker_id,
                'facility_id' => $facility_id,
                'shift_id' => $shift_id,
                'booking_id' => $booking_id,
                'clock_in_time' => current_time('mysql'),
                'clock_in_method' => 'auto_gps',
                'clock_in_lat' => $worker_lat,
                'clock_in_lng' => $worker_lng,
                'facility_lat' => $facility_lat,
                'facility_lng' => $facility_lng,
                'gps_verified' => 1,
                'last_heartbeat' => current_time('mysql'),
                'location' => sprintf('GPS: %.6f, %.6f (%.0fm from facility)', $worker_lat, $worker_lng, $distance),
                'status' => 'in_progress'
            )
        );

        $clock_id = $wpdb->insert_id;
        $clock_record = self::get_clock_record_by_id($clock_id);

        // Update worker profile status
        CareGate_Auth::update_user_meta($worker_id, 'current_shift_status', 'clocked_in');
        CareGate_Auth::update_user_meta($worker_id, 'current_clock_id', $clock_id);

        return new WP_REST_Response(array(
            'message' => 'Auto clocked in successfully via GPS verification',
            'clockRecord' => $clock_record,
            'distance' => round($distance, 2)
        ), 201);
    }

    /**
     * Update heartbeat to prevent auto clock-out.
     */
    public static function update_heartbeat($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        $worker_id = intval($params['workerId'] ?? get_current_user_id());
        $worker_lat = floatval($params['workerLat'] ?? 0);
        $worker_lng = floatval($params['workerLng'] ?? 0);

        if (!$worker_id) {
            return new WP_Error('missing_fields', 'Worker ID required', array('status' => 400));
        }

        // Find active clock record
        $table = $wpdb->prefix . 'caregate_clock_records';
        $clock_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE worker_id = %d AND clock_out_time IS NULL ORDER BY clock_in_time DESC LIMIT 1",
            $worker_id
        ), ARRAY_A);

        if (!$clock_record) {
            return new WP_Error('not_clocked_in', 'Worker is not clocked in', array('status' => 400));
        }

        // Check if worker moved too far from facility (off-site)
        if ($worker_lat && $worker_lng && $clock_record['facility_lat'] && $clock_record['facility_lng']) {
            $distance = self::calculate_gps_distance(
                $worker_lat, 
                $worker_lng, 
                $clock_record['facility_lat'], 
                $clock_record['facility_lng']
            );
            
            // If worker is more than 500m away, auto clock-out (off-site)
            if ($distance > 500) {
                return self::auto_clock_out_internal($clock_record, 'off_site', sprintf('Worker moved %.0fm from facility', $distance));
            }
        }

        // Update heartbeat
        $wpdb->update(
            $table,
            array('last_heartbeat' => current_time('mysql')),
            array('id' => $clock_record['id'])
        );

        return new WP_REST_Response(array(
            'message' => 'Heartbeat updated',
            'clockId' => intval($clock_record['id'])
        ), 200);
    }

    /**
     * Auto clock-out (internal method).
     */
    private static function auto_clock_out_internal($clock_record, $reason = 'offline', $notes = '') {
        global $wpdb;
        
        // Calculate hours worked
        $clock_in = strtotime($clock_record['clock_in_time']);
        $clock_out = time();
        $total_minutes = ($clock_out - $clock_in) / 60;
        $break_time = floatval($clock_record['break_time']);
        $hours_worked = ($total_minutes - ($break_time * 60)) / 60;

        $table = $wpdb->prefix . 'caregate_clock_records';
        
        // Update clock record
        $wpdb->update(
            $table,
            array(
                'clock_out_time' => current_time('mysql'),
                'clock_out_method' => 'auto_' . $reason,
                'hours_worked' => $hours_worked,
                'auto_clocked_out' => 1,
                'notes' => $notes ? $notes : 'Auto clocked-out: ' . $reason,
                'status' => 'completed'
            ),
            array('id' => $clock_record['id'])
        );

        $updated_record = self::get_clock_record_by_id($clock_record['id']);

        // Update worker profile status
        CareGate_Auth::update_user_meta($clock_record['worker_id'], 'current_shift_status', 'clocked_out');
        CareGate_Auth::update_user_meta($clock_record['worker_id'], 'current_clock_id', null);

        // Auto-create timesheet if booking exists
        if ($clock_record['booking_id']) {
            self::create_timesheet_from_clock($clock_record['id']);
        }

        return new WP_REST_Response(array(
            'message' => 'Auto clocked out: ' . $reason,
            'clockRecord' => $updated_record
        ), 200);
    }

    /**
     * Check for stale heartbeats and auto clock-out offline workers.
     * This should be called via WordPress cron or external monitoring.
     */
    public static function process_auto_clockouts() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_clock_records';
        $timeout_minutes = self::HEARTBEAT_TIMEOUT;
        
        // Find records with stale heartbeats
        $stale_records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE clock_out_time IS NULL 
             AND last_heartbeat IS NOT NULL
             AND last_heartbeat < DATE_SUB(NOW(), INTERVAL %d MINUTE)",
            $timeout_minutes
        ), ARRAY_A);

        $processed = 0;
        foreach ($stale_records as $record) {
            // Check if within shift schedule
            if (self::is_within_shift_schedule($record['shift_id'], $record['booking_id'])) {
                self::auto_clock_out_internal($record, 'offline', 'App went offline - no heartbeat for ' . $timeout_minutes . ' minutes');
                $processed++;
            }
        }

        return array(
            'processed' => $processed,
            'message' => sprintf('Processed %d auto clock-outs', $processed)
        );
    }

    public static function clock_in($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        
        $worker_id = intval($params['workerId'] ?? get_current_user_id());
        $facility_id = intval($params['facilityId'] ?? get_current_user_id());
        $shift_id = intval($params['shiftId'] ?? 0);
        $booking_id = intval($params['bookingId'] ?? 0);
        $method = sanitize_text_field($params['method'] ?? 'manual'); // 'manual' or 'auto'
        $card_id = sanitize_text_field($params['cardId'] ?? '');
        $location = sanitize_text_field($params['location'] ?? '');

        if (!$worker_id || !$facility_id) {
            return new WP_Error('missing_fields', 'Worker and facility required', array('status' => 400));
        }

        // Check if already clocked in
        $table = $wpdb->prefix . 'caregate_clock_records';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE worker_id = %d AND clock_out_time IS NULL ORDER BY clock_in_time DESC LIMIT 1",
            $worker_id
        ), ARRAY_A);

        if ($existing) {
            return new WP_Error('already_clocked_in', 'Worker is already clocked in', array('status' => 400));
        }

        // Create clock record
        $wpdb->insert(
            $table,
            array(
                'worker_id' => $worker_id,
                'facility_id' => $facility_id,
                'shift_id' => $shift_id,
                'booking_id' => $booking_id,
                'clock_in_time' => current_time('mysql'),
                'clock_in_method' => $method,
                'card_id' => $card_id,
                'location' => $location,
                'status' => 'in_progress'
            )
        );

        $clock_id = $wpdb->insert_id;
        $clock_record = self::get_clock_record_by_id($clock_id);

        // Update worker profile status
        CareGate_Auth::update_user_meta($worker_id, 'current_shift_status', 'clocked_in');
        CareGate_Auth::update_user_meta($worker_id, 'current_clock_id', $clock_id);

        return new WP_REST_Response(array(
            'message' => 'Clocked in successfully',
            'clockRecord' => $clock_record
        ), 201);
    }

    /**
     * Clock out (end shift).
     */
    public static function clock_out($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        
        $worker_id = intval($params['workerId'] ?? get_current_user_id());
        $method = sanitize_text_field($params['method'] ?? 'manual');
        $card_id = sanitize_text_field($params['cardId'] ?? '');
        $break_time = floatval($params['breakTime'] ?? 0);
        $notes = sanitize_textarea_field($params['notes'] ?? '');

        if (!$worker_id) {
            return new WP_Error('missing_fields', 'Worker ID required', array('status' => 400));
        }

        // Find active clock record
        $table = $wpdb->prefix . 'caregate_clock_records';
        $clock_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE worker_id = %d AND clock_out_time IS NULL ORDER BY clock_in_time DESC LIMIT 1",
            $worker_id
        ), ARRAY_A);

        if (!$clock_record) {
            return new WP_Error('not_clocked_in', 'Worker is not clocked in', array('status' => 400));
        }

        // Calculate hours worked
        $clock_in = strtotime($clock_record['clock_in_time']);
        $clock_out = time();
        $total_minutes = ($clock_out - $clock_in) / 60;
        $hours_worked = ($total_minutes - ($break_time * 60)) / 60;

        // Update clock record
        $wpdb->update(
            $table,
            array(
                'clock_out_time' => current_time('mysql'),
                'clock_out_method' => $method,
                'break_time' => $break_time,
                'hours_worked' => $hours_worked,
                'notes' => $notes,
                'status' => 'completed'
            ),
            array('id' => $clock_record['id'])
        );

        $updated_record = self::get_clock_record_by_id($clock_record['id']);

        // Update worker profile status
        CareGate_Auth::update_user_meta($worker_id, 'current_shift_status', 'clocked_out');
        CareGate_Auth::update_user_meta($worker_id, 'current_clock_id', null);

        // Auto-create timesheet if booking exists
        if ($clock_record['booking_id']) {
            self::create_timesheet_from_clock($clock_record['id']);
        }

        return new WP_REST_Response(array(
            'message' => 'Clocked out successfully',
            'clockRecord' => $updated_record
        ), 200);
    }

    /**
     * Get clock record by ID.
     */
    public static function get_clock_record_by_id($clock_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_clock_records';
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $clock_id
        ), ARRAY_A);

        if (!$record) {
            return null;
        }

        return self::format_clock_record($record);
    }

    /**
     * Format clock record.
     */
    private static function format_clock_record($record) {
        $worker = get_userdata($record['worker_id']);
        $facility = get_userdata($record['facility_id']);

        return array(
            'id' => intval($record['id']),
            'workerId' => intval($record['worker_id']),
            'workerName' => $worker ? $worker->display_name : '',
            'facilityId' => intval($record['facility_id']),
            'facilityName' => $facility ? $facility->display_name : '',
            'shiftId' => intval($record['shift_id']),
            'bookingId' => intval($record['booking_id']),
            'clockInTime' => $record['clock_in_time'],
            'clockOutTime' => $record['clock_out_time'],
            'clockInMethod' => $record['clock_in_method'],
            'clockOutMethod' => $record['clock_out_method'],
            'cardId' => $record['card_id'],
            'location' => $record['location'],
            'clockInLat' => floatval($record['clock_in_lat'] ?? 0),
            'clockInLng' => floatval($record['clock_in_lng'] ?? 0),
            'clockOutLat' => floatval($record['clock_out_lat'] ?? 0),
            'clockOutLng' => floatval($record['clock_out_lng'] ?? 0),
            'facilityLat' => floatval($record['facility_lat'] ?? 0),
            'facilityLng' => floatval($record['facility_lng'] ?? 0),
            'gpsVerified' => boolval($record['gps_verified'] ?? 0),
            'autoClocked Out' => boolval($record['auto_clocked_out'] ?? 0),
            'lastHeartbeat' => $record['last_heartbeat'] ?? null,
            'breakTime' => floatval($record['break_time']),
            'hoursWorked' => floatval($record['hours_worked']),
            'notes' => $record['notes'],
            'status' => $record['status'],
            'createdAt' => $record['created_at']
        );
    }

    /**
     * Get clock records.
     */
    public static function get_clock_records($request) {
        global $wpdb;
        
        $worker_id = $request->get_param('worker_id');
        $facility_id = $request->get_param('facility_id');
        $status = $request->get_param('status');
        
        $table = $wpdb->prefix . 'caregate_clock_records';
        $where = array('1=1');
        $params = array();

        if ($worker_id) {
            $where[] = 'worker_id = %d';
            $params[] = $worker_id;
        }

        if ($facility_id) {
            $where[] = 'facility_id = %d';
            $params[] = $facility_id;
        }

        if ($status) {
            $where[] = 'status = %s';
            $params[] = $status;
        }

        $where_clause = implode(' AND ', $where);
        
        if (empty($params)) {
            $records = $wpdb->get_results(
                "SELECT * FROM $table WHERE $where_clause ORDER BY clock_in_time DESC LIMIT 100",
                ARRAY_A
            );
        } else {
            $records = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE $where_clause ORDER BY clock_in_time DESC LIMIT 100",
                ...$params
            ), ARRAY_A);
        }

        $formatted = array_map(array(self::class, 'format_clock_record'), $records);

        return new WP_REST_Response(array('clockRecords' => $formatted), 200);
    }

    /**
     * Get current clocked-in status for a worker.
     */
    public static function get_clock_status($request) {
        global $wpdb;
        
        $worker_id = $request->get_param('worker_id') ?? get_current_user_id();
        
        $table = $wpdb->prefix . 'caregate_clock_records';
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE worker_id = %d AND clock_out_time IS NULL ORDER BY clock_in_time DESC LIMIT 1",
            $worker_id
        ), ARRAY_A);

        if (!$record) {
            return new WP_REST_Response(array(
                'isClockedIn' => false,
                'clockRecord' => null
            ), 200);
        }

        return new WP_REST_Response(array(
            'isClockedIn' => true,
            'clockRecord' => self::format_clock_record($record)
        ), 200);
    }

    /**
     * Create timesheet from clock record.
     */
    private static function create_timesheet_from_clock($clock_id) {
        global $wpdb;
        
        $clock_record = self::get_clock_record_by_id($clock_id);
        
        if (!$clock_record || !$clock_record['bookingId']) {
            return false;
        }

        $booking = CareGate_Bookings::get_booking_by_id($clock_record['bookingId']);
        if (!$booking) {
            return false;
        }

        $shift = CareGate_Shifts::get_shift_by_id($booking['shiftId']);
        $calculated_pay = $shift['dynamicRate'] * $clock_record['hoursWorked'];

        $timesheet_table = $wpdb->prefix . 'caregate_timesheets';
        $wpdb->insert(
            $timesheet_table,
            array(
                'booking_id' => $booking['id'],
                'shift_id' => $booking['shiftId'],
                'worker_id' => $booking['workerId'],
                'facility_id' => $booking['facilityId'],
                'hours_worked' => $clock_record['hoursWorked'],
                'break_time' => $clock_record['breakTime'],
                'hourly_rate' => $shift['dynamicRate'],
                'total_pay' => $calculated_pay,
                'notes' => $clock_record['notes'],
                'status' => 'pending',
                'submitted_by' => $clock_record['workerId'],
                'clock_record_id' => $clock_id
            )
        );

        return $wpdb->insert_id;
    }

    /**
     * Manual clock entry by facility (admin override).
     */
    public static function manual_clock_entry($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        $admin_id = get_current_user_id();
        
        $worker_id = intval($params['workerId'] ?? 0);
        $facility_id = intval($params['facilityId'] ?? $admin_id);
        $shift_id = intval($params['shiftId'] ?? 0);
        $booking_id = intval($params['bookingId'] ?? 0);
        $clock_in_time = sanitize_text_field($params['clockInTime']);
        $clock_out_time = sanitize_text_field($params['clockOutTime']);
        $break_time = floatval($params['breakTime'] ?? 0);
        $notes = sanitize_textarea_field($params['notes'] ?? '');

        if (!$worker_id || !$clock_in_time || !$clock_out_time) {
            return new WP_Error('missing_fields', 'Worker, clock in and clock out times required', array('status' => 400));
        }

        // Calculate hours
        $clock_in = strtotime($clock_in_time);
        $clock_out = strtotime($clock_out_time);
        $total_minutes = ($clock_out - $clock_in) / 60;
        $hours_worked = ($total_minutes - ($break_time * 60)) / 60;

        $table = $wpdb->prefix . 'caregate_clock_records';
        $wpdb->insert(
            $table,
            array(
                'worker_id' => $worker_id,
                'facility_id' => $facility_id,
                'shift_id' => $shift_id,
                'booking_id' => $booking_id,
                'clock_in_time' => $clock_in_time,
                'clock_out_time' => $clock_out_time,
                'clock_in_method' => 'manual',
                'clock_out_method' => 'manual',
                'break_time' => $break_time,
                'hours_worked' => $hours_worked,
                'notes' => $notes,
                'status' => 'completed',
                'entered_by' => $admin_id
            )
        );

        $clock_id = $wpdb->insert_id;
        $clock_record = self::get_clock_record_by_id($clock_id);

        // Auto-create timesheet if booking exists
        if ($booking_id) {
            self::create_timesheet_from_clock($clock_id);
        }

        return new WP_REST_Response(array(
            'message' => 'Manual clock entry created successfully',
            'clockRecord' => $clock_record
        ), 201);
    }
}
