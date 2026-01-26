<?php
/**
 * Authentication functionality.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_Auth {

    /**
     * Register a new user.
     */
    public static function register($request) {
        $params = $request->get_json_params();
        
        $email = sanitize_email($params['email'] ?? '');
        $password = $params['password'] ?? '';
        $name = sanitize_text_field($params['name'] ?? '');
        $role = sanitize_text_field($params['role'] ?? '');
        $skills = $params['skills'] ?? array();
        $location = $params['location'] ?? array();
        $facility_type = sanitize_text_field($params['facilityType'] ?? '');

        // Validation
        if (empty($email) || empty($password) || empty($name) || empty($role)) {
            return new WP_Error('missing_fields', 'Missing required fields', array('status' => 400));
        }

        if (!in_array($role, array('worker', 'facility'))) {
            return new WP_Error('invalid_role', 'Invalid role', array('status' => 400));
        }

        if (email_exists($email)) {
            return new WP_Error('user_exists', 'User already exists', array('status' => 400));
        }

        // Create user
        $user_id = wp_create_user($email, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $name,
            'nickname' => $name
        ));

        // Assign role
        $wp_user = new WP_User($user_id);
        $wp_user->set_role($role === 'worker' ? 'caregate_worker' : 'caregate_facility');

        // Store additional meta
        self::update_user_meta($user_id, 'skills', $skills);
        self::update_user_meta($user_id, 'location', $location);
        self::update_user_meta($user_id, 'rating', 5.0);
        self::update_user_meta($user_id, 'completed_shifts', 0);
        self::update_user_meta($user_id, 'verified', true);
        
        if ($role === 'facility') {
            self::update_user_meta($user_id, 'facility_type', $facility_type);
        }

        // Generate token
        $token = self::generate_token($user_id);

        // Get user data
        $user_data = self::get_user_data($user_id);

        return new WP_REST_Response(array(
            'message' => 'User registered successfully',
            'user' => $user_data,
            'token' => $token
        ), 201);
    }

    /**
     * Login a user.
     */
    public static function login($request) {
        $params = $request->get_json_params();
        
        $email = sanitize_email($params['email'] ?? '');
        $password = $params['password'] ?? '';

        if (empty($email) || empty($password)) {
            return new WP_Error('missing_fields', 'Email and password required', array('status' => 400));
        }

        // Authenticate user
        $user = wp_authenticate($email, $password);

        if (is_wp_error($user)) {
            return new WP_Error('invalid_credentials', 'Invalid credentials', array('status' => 401));
        }

        // Generate token
        $token = self::generate_token($user->ID);

        // Get user data
        $user_data = self::get_user_data($user->ID);

        return new WP_REST_Response(array(
            'message' => 'Login successful',
            'user' => $user_data,
            'token' => $token
        ), 200);
    }

    /**
     * Get current user profile.
     */
    public static function get_current_user($request) {
        $user_id = get_current_user_id();
        $user_data = self::get_user_data($user_id);

        return new WP_REST_Response(array(
            'user' => $user_data
        ), 200);
    }

    /**
     * Update current user profile.
     */
    public static function update_current_user($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        // Update allowed fields
        if (isset($params['skills'])) {
            self::update_user_meta($user_id, 'skills', $params['skills']);
        }

        if (isset($params['location'])) {
            self::update_user_meta($user_id, 'location', $params['location']);
        }

        if (isset($params['facilityType'])) {
            self::update_user_meta($user_id, 'facility_type', sanitize_text_field($params['facilityType']));
        }

        $user_data = self::get_user_data($user_id);

        return new WP_REST_Response(array(
            'message' => 'Profile updated successfully',
            'user' => $user_data
        ), 200);
    }

    /**
     * Get user data without sensitive information.
     */
    private static function get_user_data($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return null;
        }

        $role = 'worker';
        if (in_array('caregate_facility', $user->roles)) {
            $role = 'facility';
        }

        return array(
            'id' => $user->ID,
            'email' => $user->user_email,
            'name' => $user->display_name,
            'role' => $role,
            'skills' => self::get_user_meta($user_id, 'skills', array()),
            'location' => self::get_user_meta($user_id, 'location', array()),
            'rating' => (float) self::get_user_meta($user_id, 'rating', 5.0),
            'completedShifts' => (int) self::get_user_meta($user_id, 'completed_shifts', 0),
            'verified' => (bool) self::get_user_meta($user_id, 'verified', true),
            'facilityType' => self::get_user_meta($user_id, 'facility_type', ''),
            'createdAt' => $user->user_registered,
            'updatedAt' => get_user_meta($user_id, 'profile_updated_at', true) ?: $user->user_registered
        );
    }

    /**
     * Generate JWT token (simplified - use a proper JWT library in production).
     */
    private static function generate_token($user_id) {
        $payload = array(
            'userId' => $user_id,
            'email' => get_userdata($user_id)->user_email,
            'exp' => time() + (30 * DAY_IN_SECONDS)
        );

        // In production, use a proper JWT library
        return base64_encode(json_encode($payload));
    }

    /**
     * Store user meta in custom table.
     */
    private static function update_user_meta($user_id, $meta_key, $meta_value) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_user_meta';
        
        $wpdb->replace(
            $table,
            array(
                'user_id' => $user_id,
                'meta_key' => $meta_key,
                'meta_value' => maybe_serialize($meta_value)
            ),
            array('%d', '%s', '%s')
        );
    }

    /**
     * Get user meta from custom table.
     */
    private static function get_user_meta($user_id, $meta_key, $default = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_user_meta';
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM $table WHERE user_id = %d AND meta_key = %s",
            $user_id,
            $meta_key
        ));

        if ($value === null) {
            return $default;
        }

        return maybe_unserialize($value);
    }
}
