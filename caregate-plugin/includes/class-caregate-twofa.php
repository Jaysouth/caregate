<?php
/**
 * Two-Factor Authentication functionality.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_TwoFA {

    /**
     * Generate OTP code (6 digits).
     */
    public static function generate_otp() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via email.
     */
    public static function send_otp_email($user_id, $otp_code) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $to = $user->user_email;
        $subject = 'CareGate - Your Verification Code';
        $message = "Your CareGate verification code is: <strong>$otp_code</strong>\n\n";
        $message .= "This code will expire in 10 minutes.\n\n";
        $message .= "If you didn't request this code, please ignore this email.";

        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Send OTP via SMS (using a third-party service).
     */
    public static function send_otp_sms($user_id, $otp_code) {
        $phone = self::get_user_meta($user_id, 'phone_number', '');
        
        if (empty($phone)) {
            return false;
        }

        // Integration with SMS service (Twilio, AWS SNS, etc.)
        // This is a placeholder - implement with your SMS provider
        $api_key = get_option('caregate_sms_api_key', '');
        $api_url = get_option('caregate_sms_api_url', '');

        if (empty($api_key) || empty($api_url)) {
            // Fallback to email if SMS not configured
            return self::send_otp_email($user_id, $otp_code);
        }

        // Example SMS API call (adjust based on your provider)
        $message = "Your CareGate verification code is: $otp_code. Valid for 10 minutes.";
        
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'to' => $phone,
                'message' => $message
            ))
        ));

        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    /**
     * Store OTP with notification tracking.
     */
    public static function store_otp($user_id, $otp_code, $method = 'email') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_otp';
        
        // Check notification count in last 12 hours
        $notification_count = self::get_notification_count($user_id);
        
        if ($notification_count >= 3) {
            return new WP_Error('max_notifications', 'Maximum OTP notifications reached. Please try again later.', array('status' => 429));
        }

        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'otp_code' => $otp_code,
                'method' => $method,
                'expiry' => $expiry,
                'verified' => 0,
                'notification_count' => $notification_count + 1
            ),
            array('%d', '%s', '%s', '%s', '%d', '%d')
        );

        return $wpdb->insert_id;
    }

    /**
     * Get notification count in last 12 hours.
     */
    private static function get_notification_count($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_otp';
        $twelve_hours_ago = date('Y-m-d H:i:s', strtotime('-12 hours'));
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND created_at >= %s",
            $user_id,
            $twelve_hours_ago
        ));
    }

    /**
     * Verify OTP code.
     */
    public static function verify_otp($user_id, $otp_code) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_otp';
        
        $otp_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND otp_code = %s AND verified = 0 AND expiry > NOW() ORDER BY created_at DESC LIMIT 1",
            $user_id,
            $otp_code
        ));

        if (!$otp_record) {
            return false;
        }

        // Mark as verified
        $wpdb->update(
            $table,
            array('verified' => 1),
            array('id' => $otp_record->id),
            array('%d'),
            array('%d')
        );

        return true;
    }

    /**
     * Check if user has 2FA enabled.
     */
    public static function is_2fa_enabled($user_id) {
        return (bool) get_user_meta($user_id, 'caregate_2fa_enabled', true);
    }

    /**
     * Enable 2FA for user.
     */
    public static function enable_2fa($user_id) {
        update_user_meta($user_id, 'caregate_2fa_enabled', true);
    }

    /**
     * Disable 2FA for user.
     */
    public static function disable_2fa($user_id) {
        update_user_meta($user_id, 'caregate_2fa_enabled', false);
    }

    /**
     * Get user meta helper.
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
