<?php
/**
 * Shift management functionality.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_Shifts {

    /**
     * Calculate distance between two locations (Haversine formula).
     */
    private static function calculate_distance($loc1, $loc2) {
        if (empty($loc1['lat']) || empty($loc2['lat'])) {
            return 999;
        }

        $earth_radius = 6371; // km

        $dLat = deg2rad($loc2['lat'] - $loc1['lat']);
        $dLon = deg2rad($loc2['lng'] - $loc1['lng']);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($loc1['lat'])) * cos(deg2rad($loc2['lat'])) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earth_radius * $c;
    }

    /**
     * Calculate dynamic pricing.
     */
    private static function calculate_dynamic_pricing($base_rate, $start_time, $skill_level = 'basic') {
        $rate = $base_rate;

        // Get configuration
        $urgent_24h = (float) get_option('caregate_urgent_24h_multiplier', 1.3);
        $urgent_48h = (float) get_option('caregate_urgent_48h_multiplier', 1.15);
        $skill_advanced = (float) get_option('caregate_skill_advanced_multiplier', 1.2);
        $skill_expert = (float) get_option('caregate_skill_expert_multiplier', 1.4);

        // Urgency multiplier
        $hours_until_shift = (strtotime($start_time) - time()) / 3600;

        if ($hours_until_shift > 0 && $hours_until_shift < 24) {
            $rate *= $urgent_24h;
        } elseif ($hours_until_shift > 0 && $hours_until_shift < 48) {
            $rate *= $urgent_48h;
        }

        // Skill level multiplier
        if ($skill_level === 'advanced') {
            $rate *= $skill_advanced;
        } elseif ($skill_level === 'expert') {
            $rate *= $skill_expert;
        }

        return round($rate, 2);
    }

    /**
     * Create a new shift.
     */
    public static function create_shift($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        $user_id = get_current_user_id();

        $title = sanitize_text_field($params['title'] ?? '');
        $description = sanitize_textarea_field($params['description'] ?? '');
        $start_time = sanitize_text_field($params['startTime'] ?? '');
        $end_time = sanitize_text_field($params['endTime'] ?? '');
        $required_skills = $params['requiredSkills'] ?? array();
        $location = $params['location'] ?? array();
        $base_rate = floatval($params['baseRate'] ?? 0);
        $skill_level = sanitize_text_field($params['skillLevel'] ?? 'basic');

        if (empty($title) || empty($start_time) || empty($end_time) || empty($required_skills) || $base_rate <= 0) {
            return new WP_Error('missing_fields', 'Missing required fields', array('status' => 400));
        }

        $dynamic_rate = self::calculate_dynamic_pricing($base_rate, $start_time, $skill_level);

        $table = $wpdb->prefix . 'caregate_shifts';
        
        $result = $wpdb->insert(
            $table,
            array(
                'facility_id' => $user_id,
                'title' => $title,
                'description' => $description,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'required_skills' => maybe_serialize($required_skills),
                'location_lat' => $location['lat'] ?? null,
                'location_lng' => $location['lng'] ?? null,
                'base_rate' => $base_rate,
                'dynamic_rate' => $dynamic_rate,
                'skill_level' => $skill_level,
                'status' => 'open'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create shift', array('status' => 500));
        }

        $shift = self::get_shift_by_id($wpdb->insert_id);

        return new WP_REST_Response(array(
            'message' => 'Shift created successfully',
            'shift' => $shift
        ), 201);
    }

    /**
     * Get all shifts with filters.
     */
    public static function get_shifts($request) {
        global $wpdb;
        
        $status = $request->get_param('status');
        $start_date = $request->get_param('startDate');
        $end_date = $request->get_param('endDate');

        $table = $wpdb->prefix . 'caregate_shifts';
        $where = array('1=1');

        if ($status) {
            $where[] = $wpdb->prepare('status = %s', $status);
        }

        if ($start_date) {
            $where[] = $wpdb->prepare('start_time >= %s', $start_date);
        }

        if ($end_date) {
            $where[] = $wpdb->prepare('start_time <= %s', $end_date);
        }

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
        $results = $wpdb->get_results($sql, ARRAY_A);

        $shifts = array_map(array(self::class, 'format_shift'), $results);

        return new WP_REST_Response(array(
            'shifts' => $shifts
        ), 200);
    }

    /**
     * Get matched shifts for a worker.
     */
    public static function get_matched_shifts($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $worker = CareGate_Auth::get_user_data($user_id);

        if (!$worker) {
            return new WP_Error('user_not_found', 'Worker not found', array('status' => 404));
        }

        // Get all open shifts
        $table = $wpdb->prefix . 'caregate_shifts';
        $sql = "SELECT * FROM $table WHERE status = 'open' ORDER BY start_time ASC";
        $shifts = $wpdb->get_results($sql, ARRAY_A);

        $max_distance = (float) get_option('caregate_max_matching_distance', 50);
        $matched_shifts = array();

        foreach ($shifts as $shift) {
            $match_score = 0;

            // Skill matching (40% weight)
            $required_skills = maybe_unserialize($shift['required_skills']);
            if (is_array($required_skills) && is_array($worker['skills'])) {
                $matching_skills = array_intersect($required_skills, $worker['skills']);
                $skill_match = count($required_skills) > 0 
                    ? count($matching_skills) / count($required_skills) 
                    : 0;
                $match_score += $skill_match * 40;
            }

            // Location proximity (30% weight)
            $shift_location = array(
                'lat' => $shift['location_lat'],
                'lng' => $shift['location_lng']
            );
            $distance = self::calculate_distance($worker['location'], $shift_location);
            $location_score = max(0, ($max_distance - $distance) / $max_distance);
            $match_score += $location_score * 30;

            // Availability (30% weight) - simplified, assume available
            $match_score += 30;

            $shift['matchScore'] = round($match_score);
            $shift['distance'] = round($distance, 1);

            if ($match_score >= 50) {
                $matched_shifts[] = self::format_shift($shift);
            }
        }

        // Sort by match score
        usort($matched_shifts, function($a, $b) {
            return $b['matchScore'] - $a['matchScore'];
        });

        return new WP_REST_Response(array(
            'matches' => $matched_shifts
        ), 200);
    }

    /**
     * Get a single shift.
     */
    public static function get_shift($request) {
        $shift_id = $request->get_param('id');
        $shift = self::get_shift_by_id($shift_id);

        if (!$shift) {
            return new WP_Error('not_found', 'Shift not found', array('status' => 404));
        }

        return new WP_REST_Response(array(
            'shift' => $shift
        ), 200);
    }

    /**
     * Update a shift.
     */
    public static function update_shift($request) {
        global $wpdb;
        
        $shift_id = $request->get_param('id');
        $params = $request->get_json_params();
        $user_id = get_current_user_id();

        $shift = self::get_shift_by_id($shift_id);
        
        if (!$shift) {
            return new WP_Error('not_found', 'Shift not found', array('status' => 404));
        }

        if ($shift['facilityId'] != $user_id) {
            return new WP_Error('forbidden', 'Not authorized to update this shift', array('status' => 403));
        }

        $table = $wpdb->prefix . 'caregate_shifts';
        $update_data = array();

        // Only update provided fields
        $allowed_fields = array('title', 'description', 'start_time', 'end_time', 'status');
        
        foreach ($allowed_fields as $field) {
            if (isset($params[$field])) {
                $update_data[$field] = $params[$field];
            }
        }

        if (!empty($update_data)) {
            $wpdb->update(
                $table,
                $update_data,
                array('id' => $shift_id),
                array('%s'),
                array('%d')
            );
        }

        $updated_shift = self::get_shift_by_id($shift_id);

        return new WP_REST_Response(array(
            'message' => 'Shift updated successfully',
            'shift' => $updated_shift
        ), 200);
    }

    /**
     * Delete a shift.
     */
    public static function delete_shift($request) {
        global $wpdb;
        
        $shift_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $shift = self::get_shift_by_id($shift_id);
        
        if (!$shift) {
            return new WP_Error('not_found', 'Shift not found', array('status' => 404));
        }

        if ($shift['facilityId'] != $user_id) {
            return new WP_Error('forbidden', 'Not authorized to delete this shift', array('status' => 403));
        }

        $table = $wpdb->prefix . 'caregate_shifts';
        $wpdb->delete($table, array('id' => $shift_id), array('%d'));

        return new WP_REST_Response(array(
            'message' => 'Shift deleted successfully'
        ), 200);
    }

    /**
     * Get shift by ID.
     */
    public static function get_shift_by_id($shift_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_shifts';
        $shift = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $shift_id
        ), ARRAY_A);

        return $shift ? self::format_shift($shift) : null;
    }

    /**
     * Format shift data.
     */
    private static function format_shift($shift) {
        return array(
            'id' => $shift['id'],
            'facilityId' => $shift['facility_id'],
            'title' => $shift['title'],
            'description' => $shift['description'],
            'startTime' => $shift['start_time'],
            'endTime' => $shift['end_time'],
            'requiredSkills' => maybe_unserialize($shift['required_skills']),
            'location' => array(
                'lat' => (float) $shift['location_lat'],
                'lng' => (float) $shift['location_lng']
            ),
            'baseRate' => (float) $shift['base_rate'],
            'dynamicRate' => (float) $shift['dynamic_rate'],
            'skillLevel' => $shift['skill_level'],
            'status' => $shift['status'],
            'assignedWorkerId' => $shift['assigned_worker_id'],
            'matchScore' => $shift['matchScore'] ?? null,
            'distance' => $shift['distance'] ?? null,
            'createdAt' => $shift['created_at'],
            'updatedAt' => $shift['updated_at']
        );
    }
}
