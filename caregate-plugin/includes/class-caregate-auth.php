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
        $recaptcha_token = sanitize_text_field($params['recaptchaToken'] ?? '');
        
        // UK Standard Fields for Worker
        $nmc_registration = sanitize_text_field($params['nmcRegistration'] ?? '');
        $dbs_number = sanitize_text_field($params['dbsNumber'] ?? '');
        $ni_number = sanitize_text_field($params['niNumber'] ?? '');
        $phone_number = sanitize_text_field($params['phoneNumber'] ?? '');
        $address = sanitize_textarea_field($params['address'] ?? '');
        $postcode = sanitize_text_field($params['postcode'] ?? '');
        
        // UK Standard Fields for Facility
        $cqc_registration = sanitize_text_field($params['cqcRegistration'] ?? '');
        $company_number = sanitize_text_field($params['companyNumber'] ?? '');
        $vat_number = sanitize_text_field($params['vatNumber'] ?? '');

        // Verify reCAPTCHA
        if (CareGate_ReCaptcha::is_enabled()) {
            if (!CareGate_ReCaptcha::verify($recaptcha_token)) {
                return new WP_Error('recaptcha_failed', 'reCAPTCHA verification failed', array('status' => 400));
            }
        }

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
        
        // UK Standard validation for workers
        if ($role === 'worker') {
            if (empty($phone_number) || empty($ni_number) || empty($postcode)) {
                return new WP_Error('missing_uk_fields', 'Missing required UK standard fields (phone, NI number, postcode)', array('status' => 400));
            }
        }
        
        // UK Standard validation for facilities
        if ($role === 'facility') {
            if (empty($phone_number) || empty($cqc_registration) || empty($postcode)) {
                return new WP_Error('missing_uk_fields', 'Missing required UK standard fields (phone, CQC registration, postcode)', array('status' => 400));
            }
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
        self::update_user_meta($user_id, 'verified', false); // Requires 2FA verification
        self::update_user_meta($user_id, 'phone_number', $phone_number);
        self::update_user_meta($user_id, 'address', $address);
        self::update_user_meta($user_id, 'postcode', $postcode);
        
        if ($role === 'worker') {
            self::update_user_meta($user_id, 'nmc_registration', $nmc_registration);
            self::update_user_meta($user_id, 'dbs_number', $dbs_number);
            self::update_user_meta($user_id, 'ni_number', $ni_number);
        } else {
            self::update_user_meta($user_id, 'facility_type', $facility_type);
            self::update_user_meta($user_id, 'cqc_registration', $cqc_registration);
            self::update_user_meta($user_id, 'company_number', $company_number);
            self::update_user_meta($user_id, 'vat_number', $vat_number);
        }

        // Enable 2FA by default
        CareGate_TwoFA::enable_2fa($user_id);

        // Generate and send OTP
        $otp_code = CareGate_TwoFA::generate_otp();
        $otp_result = CareGate_TwoFA::store_otp($user_id, $otp_code, 'email');
        
        if (is_wp_error($otp_result)) {
            // Still create user but inform about OTP issue
            $user_data = self::get_user_data($user_id);
            return new WP_REST_Response(array(
                'message' => 'User registered but OTP limit reached',
                'user' => $user_data,
                'requiresOtp' => false,
                'warning' => 'OTP verification temporarily unavailable'
            ), 201);
        }

        // Send OTP via email and SMS
        CareGate_TwoFA::send_otp_email($user_id, $otp_code);
        CareGate_TwoFA::send_otp_sms($user_id, $otp_code);

        // Get user data
        $user_data = self::get_user_data($user_id);

        return new WP_REST_Response(array(
            'message' => 'User registered successfully. Please verify OTP sent to your email/phone.',
            'user' => $user_data,
            'requiresOtp' => true,
            'userId' => $user_id
        ), 201);
    }

    /**
     * Login a user.
     */
    public static function login($request) {
        $params = $request->get_json_params();
        
        $email = sanitize_email($params['email'] ?? '');
        $password = $params['password'] ?? '';
        $recaptcha_token = sanitize_text_field($params['recaptchaToken'] ?? '');

        // Verify reCAPTCHA
        if (CareGate_ReCaptcha::is_enabled()) {
            if (!CareGate_ReCaptcha::verify($recaptcha_token)) {
                return new WP_Error('recaptcha_failed', 'reCAPTCHA verification failed', array('status' => 400));
            }
        }

        if (empty($email) || empty($password)) {
            return new WP_Error('missing_fields', 'Email and password required', array('status' => 400));
        }

    /**
     * Verify OTP and complete login/registration.
     */
    public static function verify_otp($request) {
        $params = $request->get_json_params();
        
        $user_id = intval($params['userId'] ?? 0);
        $otp_code = sanitize_text_field($params['otpCode'] ?? '');

        if (!$user_id || !$otp_code) {
            return new WP_Error('missing_fields', 'User ID and OTP code required', array('status' => 400));
        }

        // Verify OTP
        if (!CareGate_TwoFA::verify_otp($user_id, $otp_code)) {
            return new WP_Error('invalid_otp', 'Invalid or expired OTP code', array('status' => 401));
        }

        // Mark user as verified
        self::update_user_meta($user_id, 'verified', true);

        // Generate token
        $token = self::generate_token($user_id);

        // Get user data
        $user_data = self::get_user_data($user_id);

        return new WP_REST_Response(array(
            'message' => 'Verification successful',
            'user' => $user_data,
            'token' => $token
        ), 200);
    }

    /**
     * Resend OTP.
     */
    public static function resend_otp($request) {
        $params = $request->get_json_params();
        
        $user_id = intval($params['userId'] ?? 0);

        if (!$user_id) {
            return new WP_Error('missing_fields', 'User ID required', array('status' => 400));
        }

        // Generate and send new OTP
        $otp_code = CareGate_TwoFA::generate_otp();
        $otp_result = CareGate_TwoFA::store_otp($user_id, $otp_code, 'email');
        
        if (is_wp_error($otp_result)) {
            return $otp_result;
        }

        // Send OTP via email and SMS
        CareGate_TwoFA::send_otp_email($user_id, $otp_code);
        CareGate_TwoFA::send_otp_sms($user_id, $otp_code);

        return new WP_REST_Response(array(
            'message' => 'OTP resent successfully'
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
