<?php
/**
 * Twilio SMS API integration.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_Twilio {

    /**
     * Send SMS via Twilio API.
     *
     * @param string $to Phone number in E.164 format (+44XXXXXXXXXX)
     * @param string $message SMS message content
     * @return array|WP_Error Response with status or error
     */
    public static function send_sms($to, $message) {
        // Get Twilio credentials from settings
        $account_sid = get_option('caregate_twilio_account_sid', '');
        $auth_token = get_option('caregate_twilio_auth_token', '');
        $from_number = get_option('caregate_twilio_from_number', '');
        $twilio_enabled = get_option('caregate_twilio_enabled', false);

        // Check if Twilio is enabled and configured
        if (!$twilio_enabled || empty($account_sid) || empty($auth_token) || empty($from_number)) {
            return new WP_Error('twilio_not_configured', 'Twilio SMS is not configured', array('status' => 500));
        }

        // Validate and format phone number
        $to = self::format_phone_number($to);
        if (!$to) {
            return new WP_Error('invalid_phone', 'Invalid phone number format', array('status' => 400));
        }

        // Twilio API endpoint
        $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";

        // Prepare request
        $response = wp_remote_post($url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode("$account_sid:$auth_token"),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => array(
                'From' => $from_number,
                'To' => $to,
                'Body' => $message
            )
        ));

        // Handle errors
        if (is_wp_error($response)) {
            self::log_sms('failed', $to, $message, null, $response->get_error_message());
            return new WP_Error('twilio_error', $response->get_error_message(), array('status' => 500));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 201 && $response_code !== 200) {
            $error_message = isset($data['message']) ? $data['message'] : 'Unknown error';
            self::log_sms('failed', $to, $message, null, $error_message);
            return new WP_Error('twilio_api_error', $error_message, array('status' => $response_code));
        }

        // Success - log the SMS
        $sid = isset($data['sid']) ? $data['sid'] : null;
        $status = isset($data['status']) ? $data['status'] : 'sent';
        self::log_sms($status, $to, $message, $sid);

        return array(
            'success' => true,
            'sid' => $sid,
            'status' => $status,
            'to' => $to
        );
    }

    /**
     * Format phone number to E.164 format.
     *
     * @param string $phone Phone number
     * @return string|false Formatted phone number or false if invalid
     */
    private static function format_phone_number($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If starts with +44, it's already formatted
        if (strpos($phone, '+44') === 0) {
            return $phone;
        }

        // If starts with 44, add +
        if (strpos($phone, '44') === 0) {
            return '+' . $phone;
        }

        // If starts with 0, replace with +44
        if (strpos($phone, '0') === 0) {
            return '+44' . substr($phone, 1);
        }

        // Otherwise, add +44 prefix
        if (strlen($phone) === 10) {
            return '+44' . $phone;
        }

        return false;
    }

    /**
     * Validate UK phone number format.
     *
     * @param string $phone Phone number
     * @return bool True if valid
     */
    public static function validate_phone_number($phone) {
        $formatted = self::format_phone_number($phone);
        if (!$formatted) {
            return false;
        }

        // UK phone numbers should be +44 followed by 10 digits
        return preg_match('/^\+44[0-9]{10}$/', $formatted);
    }

    /**
     * Get SMS delivery status from Twilio.
     *
     * @param string $sid Twilio message SID
     * @return array|WP_Error Status data or error
     */
    public static function get_message_status($sid) {
        $account_sid = get_option('caregate_twilio_account_sid', '');
        $auth_token = get_option('caregate_twilio_auth_token', '');

        if (empty($account_sid) || empty($auth_token)) {
            return new WP_Error('twilio_not_configured', 'Twilio is not configured');
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages/$sid.json";

        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode("$account_sid:$auth_token")
            )
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $response_body = wp_remote_retrieve_body($response);
        return json_decode($response_body, true);
    }

    /**
     * Log SMS attempt.
     *
     * @param string $status Status (sent, delivered, failed)
     * @param string $phone Phone number
     * @param string $message Message content
     * @param string|null $twilio_sid Twilio message SID
     * @param string|null $error_message Error message if failed
     */
    private static function log_sms($status, $phone, $message, $twilio_sid = null, $error_message = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_sms_log';
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => get_current_user_id(),
                'phone' => $phone,
                'message' => $message,
                'twilio_sid' => $twilio_sid,
                'status' => $status,
                'error_message' => $error_message,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Get SMS log for a user.
     *
     * @param int $user_id User ID
     * @param int $limit Number of records to return
     * @return array SMS log records
     */
    public static function get_sms_log($user_id, $limit = 10) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_sms_log';
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        );
        
        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Send OTP via Twilio (integration with existing 2FA system).
     *
     * @param int $user_id User ID
     * @param string $otp_code OTP code
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function send_otp($user_id, $otp_code) {
        // Get user's phone number
        $phone = get_user_meta($user_id, 'phone_number', true);
        
        if (empty($phone)) {
            return new WP_Error('no_phone', 'User has no phone number', array('status' => 400));
        }

        $message = "Your CareGate verification code is: $otp_code\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please ignore this message.";

        $result = self::send_sms($phone, $message);

        if (is_wp_error($result)) {
            // Fallback to email if SMS fails
            return CareGate_TwoFA::send_otp_email($user_id, $otp_code);
        }

        return true;
    }
}
