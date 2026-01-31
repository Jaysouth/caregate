<?php
/**
 * Timesheet management functionality.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_Timesheets {

    public static function create_timesheet($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        $user_id = get_current_user_id();
        
        $booking_id = intval($params['bookingId'] ?? 0);
        $hours_worked = floatval($params['hoursWorked'] ?? 0);
        $break_time = floatval($params['breakTime'] ?? 0);
        $notes = sanitize_textarea_field($params['notes'] ?? '');

        if (!$booking_id || !$hours_worked) {
            return new WP_Error('missing_fields', 'Booking ID and hours worked required', array('status' => 400));
        }

        $booking = CareGate_Bookings::get_booking_by_id($booking_id);
        if (!$booking) {
            return new WP_Error('not_found', 'Booking not found', array('status' => 404));
        }

        $shift = CareGate_Shifts::get_shift_by_id($booking['shiftId']);
        $calculated_pay = $shift['dynamicRate'] * $hours_worked;

        $table = $wpdb->prefix . 'caregate_timesheets';
        $wpdb->insert(
            $table,
            array(
                'booking_id' => $booking_id,
                'shift_id' => $booking['shiftId'],
                'worker_id' => $booking['workerId'],
                'facility_id' => $booking['facilityId'],
                'hours_worked' => $hours_worked,
                'break_time' => $break_time,
                'hourly_rate' => $shift['dynamicRate'],
                'total_pay' => $calculated_pay,
                'notes' => $notes,
                'status' => 'pending',
                'submitted_by' => $user_id
            )
        );

        $timesheet = self::get_timesheet_by_id($wpdb->insert_id);

        return new WP_REST_Response(array(
            'message' => 'Timesheet created successfully',
            'timesheet' => $timesheet
        ), 201);
    }

    public static function get_timesheets($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $table = $wpdb->prefix . 'caregate_timesheets';
        
        if (in_array('caregate_worker', $user->roles)) {
            $timesheets = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE worker_id = %d ORDER BY created_at DESC",
                $user_id
            ), ARRAY_A);
        } else {
            $timesheets = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE facility_id = %d ORDER BY created_at DESC",
                $user_id
            ), ARRAY_A);
        }

        $formatted = array_map(array(self::class, 'format_timesheet'), $timesheets);

        return new WP_REST_Response(array('timesheets' => $formatted), 200);
    }

    public static function approve_timesheet($request) {
        global $wpdb;
        
        $timesheet_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $timesheet = self::get_timesheet_by_id($timesheet_id);
        if (!$timesheet || $timesheet['facilityId'] != $user_id) {
            return new WP_Error('forbidden', 'Not authorized', array('status' => 403));
        }

        $table = $wpdb->prefix . 'caregate_timesheets';
        $wpdb->update(
            $table,
            array(
                'status' => 'approved',
                'approved_at' => current_time('mysql'),
                'approved_by' => $user_id
            ),
            array('id' => $timesheet_id)
        );

        $updated = self::get_timesheet_by_id($timesheet_id);

        return new WP_REST_Response(array(
            'message' => 'Timesheet approved successfully',
            'timesheet' => $updated
        ), 200);
    }

    private static function get_timesheet_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'caregate_timesheets';
        $timesheet = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
        return $timesheet ? self::format_timesheet($timesheet) : null;
    }

    private static function format_timesheet($ts) {
        return array(
            'id' => $ts['id'],
            'bookingId' => $ts['booking_id'],
            'shiftId' => $ts['shift_id'],
            'workerId' => $ts['worker_id'],
            'facilityId' => $ts['facility_id'],
            'hoursWorked' => (float) $ts['hours_worked'],
            'breakTime' => (float) $ts['break_time'],
            'hourlyRate' => (float) $ts['hourly_rate'],
            'totalPay' => (float) $ts['total_pay'],
            'notes' => $ts['notes'],
            'status' => $ts['status'],
            'submittedBy' => $ts['submitted_by'],
            'submittedAt' => $ts['submitted_at'],
            'approvedAt' => $ts['approved_at'],
            'approvedBy' => $ts['approved_by']
        );
    }
}
