<?php
/**
 * Booking management functionality.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_Bookings {

    /**
     * Create a new booking (worker applies for shift).
     */
    public static function create_booking($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        $user_id = get_current_user_id();
        $shift_id = intval($params['shiftId'] ?? 0);

        if (!$shift_id) {
            return new WP_Error('missing_fields', 'Shift ID required', array('status' => 400));
        }

        $shift = CareGate_Shifts::get_shift_by_id($shift_id);
        
        if (!$shift) {
            return new WP_Error('not_found', 'Shift not found', array('status' => 404));
        }

        if ($shift['status'] !== 'open') {
            return new WP_Error('unavailable', 'Shift is not available', array('status' => 400));
        }

        // Check if already applied
        $table = $wpdb->prefix . 'caregate_bookings';
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE shift_id = %d AND worker_id = %d",
            $shift_id,
            $user_id
        ));

        if ($existing) {
            return new WP_Error('already_applied', 'Already applied for this shift', array('status' => 400));
        }

        $result = $wpdb->insert(
            $table,
            array(
                'shift_id' => $shift_id,
                'worker_id' => $user_id,
                'facility_id' => $shift['facilityId'],
                'status' => 'pending'
            ),
            array('%d', '%d', '%d', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create booking', array('status' => 500));
        }

        $booking = self::get_booking_by_id($wpdb->insert_id);

        return new WP_REST_Response(array(
            'message' => 'Application submitted successfully',
            'booking' => $booking
        ), 201);
    }

    /**
     * Get all bookings for current user.
     */
    public static function get_bookings($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        $table = $wpdb->prefix . 'caregate_bookings';
        
        if (in_array('caregate_worker', $user->roles)) {
            $bookings = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE worker_id = %d ORDER BY created_at DESC",
                $user_id
            ), ARRAY_A);
        } else {
            $bookings = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE facility_id = %d ORDER BY created_at DESC",
                $user_id
            ), ARRAY_A);
        }

        $enriched_bookings = array_map(array(self::class, 'enrich_booking'), $bookings);

        return new WP_REST_Response(array(
            'bookings' => $enriched_bookings
        ), 200);
    }

    /**
     * Confirm a booking (facility only).
     */
    public static function confirm_booking($request) {
        global $wpdb;
        
        $booking_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $booking = self::get_booking_by_id($booking_id);
        
        if (!$booking) {
            return new WP_Error('not_found', 'Booking not found', array('status' => 404));
        }

        if ($booking['facilityId'] != $user_id) {
            return new WP_Error('forbidden', 'Not authorized', array('status' => 403));
        }

        $table = $wpdb->prefix . 'caregate_bookings';
        $wpdb->update(
            $table,
            array(
                'status' => 'confirmed',
                'confirmed_at' => current_time('mysql')
            ),
            array('id' => $booking_id),
            array('%s', '%s'),
            array('%d')
        );

        // Update shift status
        $shifts_table = $wpdb->prefix . 'caregate_shifts';
        $wpdb->update(
            $shifts_table,
            array(
                'status' => 'filled',
                'assigned_worker_id' => $booking['workerId']
            ),
            array('id' => $booking['shiftId']),
            array('%s', '%d'),
            array('%d')
        );

        $updated_booking = self::get_booking_by_id($booking_id);

        return new WP_REST_Response(array(
            'message' => 'Booking confirmed successfully',
            'booking' => $updated_booking
        ), 200);
    }

    /**
     * Complete a booking.
     */
    public static function complete_booking($request) {
        global $wpdb;
        
        $booking_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $booking = self::get_booking_by_id($booking_id);
        
        if (!$booking) {
            return new WP_Error('not_found', 'Booking not found', array('status' => 404));
        }

        if ($booking['workerId'] != $user_id && $booking['facilityId'] != $user_id) {
            return new WP_Error('forbidden', 'Not authorized', array('status' => 403));
        }

        $table = $wpdb->prefix . 'caregate_bookings';
        $wpdb->update(
            $table,
            array(
                'status' => 'completed',
                'completed_at' => current_time('mysql')
            ),
            array('id' => $booking_id),
            array('%s', '%s'),
            array('%d')
        );

        // Update shift status
        $shifts_table = $wpdb->prefix . 'caregate_shifts';
        $wpdb->update(
            $shifts_table,
            array('status' => 'completed'),
            array('id' => $booking['shiftId']),
            array('%s'),
            array('%d')
        );

        // Update worker's completed shifts count
        $current_count = (int) CareGate_Auth::get_user_meta($booking['workerId'], 'completed_shifts', 0);
        CareGate_Auth::update_user_meta($booking['workerId'], 'completed_shifts', $current_count + 1);

        $updated_booking = self::get_booking_by_id($booking_id);

        return new WP_REST_Response(array(
            'message' => 'Booking marked as completed',
            'booking' => $updated_booking
        ), 200);
    }

    /**
     * Cancel a booking.
     */
    public static function cancel_booking($request) {
        global $wpdb;
        
        $booking_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $booking = self::get_booking_by_id($booking_id);
        
        if (!$booking) {
            return new WP_Error('not_found', 'Booking not found', array('status' => 404));
        }

        if ($booking['workerId'] != $user_id && $booking['facilityId'] != $user_id) {
            return new WP_Error('forbidden', 'Not authorized', array('status' => 403));
        }

        $table = $wpdb->prefix . 'caregate_bookings';
        $wpdb->update(
            $table,
            array(
                'status' => 'cancelled',
                'cancelled_at' => current_time('mysql')
            ),
            array('id' => $booking_id),
            array('%s', '%s'),
            array('%d')
        );

        // If shift was filled, mark it as open again
        $shift = CareGate_Shifts::get_shift_by_id($booking['shiftId']);
        if ($shift && $shift['status'] === 'filled') {
            $shifts_table = $wpdb->prefix . 'caregate_shifts';
            $wpdb->update(
                $shifts_table,
                array(
                    'status' => 'open',
                    'assigned_worker_id' => null
                ),
                array('id' => $booking['shiftId']),
                array('%s', '%d'),
                array('%d')
            );
        }

        return new WP_REST_Response(array(
            'message' => 'Booking cancelled successfully'
        ), 200);
    }

    /**
     * Get booking by ID.
     */
    private static function get_booking_by_id($booking_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_bookings';
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $booking_id
        ), ARRAY_A);

        return $booking ? self::format_booking($booking) : null;
    }

    /**
     * Enrich booking with related data.
     */
    private static function enrich_booking($booking) {
        $formatted = self::format_booking($booking);
        
        $shift = CareGate_Shifts::get_shift_by_id($booking['shift_id']);
        $worker = get_userdata($booking['worker_id']);
        $facility = get_userdata($booking['facility_id']);

        $formatted['shift'] = $shift ? array(
            'id' => $shift['id'],
            'title' => $shift['title'],
            'startTime' => $shift['startTime'],
            'endTime' => $shift['endTime'],
            'dynamicRate' => $shift['dynamicRate']
        ) : null;

        $formatted['worker'] = $worker ? array(
            'id' => $worker->ID,
            'name' => $worker->display_name,
            'rating' => CareGate_Auth::get_user_meta($worker->ID, 'rating', 5.0),
            'skills' => CareGate_Auth::get_user_meta($worker->ID, 'skills', array())
        ) : null;

        $formatted['facility'] = $facility ? array(
            'id' => $facility->ID,
            'name' => $facility->display_name,
            'facilityType' => CareGate_Auth::get_user_meta($facility->ID, 'facility_type', '')
        ) : null;

        return $formatted;
    }

    /**
     * Format booking data.
     */
    private static function format_booking($booking) {
        return array(
            'id' => $booking['id'],
            'shiftId' => $booking['shift_id'],
            'workerId' => $booking['worker_id'],
            'facilityId' => $booking['facility_id'],
            'status' => $booking['status'],
            'appliedAt' => $booking['applied_at'],
            'confirmedAt' => $booking['confirmed_at'],
            'completedAt' => $booking['completed_at'],
            'cancelledAt' => $booking['cancelled_at'],
            'createdAt' => $booking['created_at'],
            'updatedAt' => $booking['updated_at']
        );
    }
}
