<?php
/**
 * Google reCAPTCHA functionality.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_ReCaptcha {

    /**
     * Verify reCAPTCHA response.
     */
    public static function verify($recaptcha_response) {
        $secret_key = get_option('caregate_recaptcha_secret_key', '');
        
        if (empty($secret_key)) {
            // reCAPTCHA not configured, skip verification
            return true;
        }

        if (empty($recaptcha_response)) {
            return false;
        }

        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        
        $response = wp_remote_post($verify_url, array(
            'body' => array(
                'secret' => $secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            )
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        return isset($response_body['success']) && $response_body['success'] === true;
    }

    /**
     * Get reCAPTCHA site key for frontend.
     */
    public static function get_site_key() {
        return get_option('caregate_recaptcha_site_key', '');
    }

    /**
     * Check if reCAPTCHA is enabled.
     */
    public static function is_enabled() {
        $site_key = self::get_site_key();
        $secret_key = get_option('caregate_recaptcha_secret_key', '');
        
        return !empty($site_key) && !empty($secret_key);
    }
}
