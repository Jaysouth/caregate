<?php
/**
 * Paystack Payment Gateway integration.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_Paystack {

    /**
     * Get Paystack base URL based on mode.
     */
    private static function get_base_url() {
        $live_mode = get_option('caregate_paystack_live_mode', false);
        return 'https://api.paystack.co';
    }

    /**
     * Get Paystack secret key.
     */
    private static function get_secret_key() {
        $live_mode = get_option('caregate_paystack_live_mode', false);
        if ($live_mode) {
            return get_option('caregate_paystack_live_secret_key', '');
        }
        return get_option('caregate_paystack_test_secret_key', '');
    }

    /**
     * Get Paystack public key.
     */
    public static function get_public_key() {
        $live_mode = get_option('caregate_paystack_live_mode', false);
        if ($live_mode) {
            return get_option('caregate_paystack_live_public_key', '');
        }
        return get_option('caregate_paystack_test_public_key', '');
    }

    /**
     * Make API request to Paystack.
     */
    private static function make_request($endpoint, $method = 'GET', $data = null) {
        $secret_key = self::get_secret_key();
        if (empty($secret_key)) {
            return new WP_Error('not_configured', 'Paystack is not configured');
        }

        $url = self::get_base_url() . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type' => 'application/json'
            )
        );

        if ($data && in_array($method, array('POST', 'PUT'))) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        $result = json_decode($body, true);

        if ($code >= 400) {
            $message = isset($result['message']) ? $result['message'] : 'API Error';
            return new WP_Error('paystack_error', $message, array('status' => $code));
        }

        return $result;
    }

    /**
     * Initialize payment (receive money from customer).
     */
    public static function initialize_payment($request) {
        $params = $request->get_json_params();
        
        $amount = floatval($params['amount'] ?? 0); // In pounds
        $email = sanitize_email($params['email'] ?? '');
        $reference = sanitize_text_field($params['reference'] ?? '');
        $metadata = $params['metadata'] ?? array();

        if ($amount <= 0 || empty($email)) {
            return new WP_Error('invalid_params', 'Invalid payment parameters', array('status' => 400));
        }

        // Convert amount to kobo (Paystack uses smallest currency unit)
        $amount_kobo = $amount * 100;

        // Generate reference if not provided
        if (empty($reference)) {
            $reference = 'PAY_' . time() . '_' . wp_generate_password(8, false);
        }

        $data = array(
            'amount' => $amount_kobo,
            'email' => $email,
            'currency' => 'GBP',
            'reference' => $reference,
            'callback_url' => home_url('/caregate-payment-callback'),
            'metadata' => $metadata
        );

        $result = self::make_request('/transaction/initialize', 'POST', $data);

        if (is_wp_error($result)) {
            return $result;
        }

        // Store transaction in database
        self::store_transaction(array(
            'user_id' => get_current_user_id(),
            'type' => 'receive',
            'amount' => $amount,
            'currency' => 'GBP',
            'status' => 'pending',
            'reference' => $reference,
            'paystack_reference' => $reference,
            'metadata' => json_encode($metadata)
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'authorization_url' => $result['data']['authorization_url'],
            'access_code' => $result['data']['access_code'],
            'reference' => $reference
        ), 200);
    }

    /**
     * Verify payment.
     */
    public static function verify_payment($request) {
        $reference = sanitize_text_field($request->get_param('reference'));

        if (empty($reference)) {
            return new WP_Error('missing_reference', 'Payment reference is required', array('status' => 400));
        }

        $result = self::make_request("/transaction/verify/$reference", 'GET');

        if (is_wp_error($result)) {
            return $result;
        }

        $data = $result['data'];
        $status = $data['status'];

        // Update transaction in database
        self::update_transaction_status($reference, $status === 'success' ? 'success' : 'failed');

        return new WP_REST_Response(array(
            'success' => $status === 'success',
            'status' => $status,
            'amount' => $data['amount'] / 100, // Convert from kobo to pounds
            'reference' => $reference,
            'paid_at' => $data['paid_at'] ?? null
        ), 200);
    }

    /**
     * Transfer funds (send money to recipient).
     */
    public static function transfer_funds($request) {
        $params = $request->get_json_params();
        
        $amount = floatval($params['amount'] ?? 0);
        $recipient_code = sanitize_text_field($params['recipient_code'] ?? '');
        $reference = sanitize_text_field($params['reference'] ?? '');
        $reason = sanitize_text_field($params['reason'] ?? 'Payment');

        if ($amount <= 0 || empty($recipient_code)) {
            return new WP_Error('invalid_params', 'Invalid transfer parameters', array('status' => 400));
        }

        // Convert to kobo
        $amount_kobo = $amount * 100;

        // Generate reference if not provided
        if (empty($reference)) {
            $reference = 'TRF_' . time() . '_' . wp_generate_password(8, false);
        }

        $data = array(
            'source' => 'balance',
            'amount' => $amount_kobo,
            'recipient' => $recipient_code,
            'reason' => $reason,
            'currency' => 'GBP',
            'reference' => $reference
        );

        $result = self::make_request('/transfer', 'POST', $data);

        if (is_wp_error($result)) {
            return $result;
        }

        // Store transaction
        self::store_transaction(array(
            'user_id' => get_current_user_id(),
            'type' => 'send',
            'amount' => $amount,
            'currency' => 'GBP',
            'status' => 'pending',
            'reference' => $reference,
            'paystack_reference' => $reference,
            'metadata' => json_encode(array('recipient_code' => $recipient_code, 'reason' => $reason))
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'transfer_code' => $result['data']['transfer_code'],
            'reference' => $reference,
            'status' => $result['data']['status']
        ), 200);
    }

    /**
     * List UK banks.
     */
    public static function list_banks($request) {
        $result = self::make_request('/bank?currency=GBP&country=GB', 'GET');

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(array(
            'success' => true,
            'banks' => $result['data']
        ), 200);
    }

    /**
     * Add bank account for payouts (create transfer recipient).
     */
    public static function add_bank_account($request) {
        $params = $request->get_json_params();
        
        $account_number = sanitize_text_field($params['account_number'] ?? '');
        $sort_code = sanitize_text_field($params['sort_code'] ?? '');
        $account_name = sanitize_text_field($params['account_name'] ?? '');
        $bank_code = sanitize_text_field($params['bank_code'] ?? '');

        if (empty($account_number) || empty($sort_code) || empty($account_name)) {
            return new WP_Error('missing_fields', 'Missing required bank details', array('status' => 400));
        }

        // Format UK account number with sort code
        $uk_account = $sort_code . $account_number;

        $data = array(
            'type' => 'nuban',
            'name' => $account_name,
            'account_number' => $uk_account,
            'bank_code' => $bank_code,
            'currency' => 'GBP'
        );

        $result = self::make_request('/transferrecipient', 'POST', $data);

        if (is_wp_error($result)) {
            return $result;
        }

        // Store in database
        self::store_bank_account(array(
            'user_id' => get_current_user_id(),
            'account_number' => $account_number,
            'sort_code' => $sort_code,
            'account_name' => $account_name,
            'bank_code' => $bank_code,
            'recipient_code' => $result['data']['recipient_code'],
            'verified' => true
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'recipient_code' => $result['data']['recipient_code']
        ), 201);
    }

    /**
     * Handle Paystack webhook.
     */
    public static function handle_webhook($request) {
        $body = $request->get_body();
        $signature = $request->get_header('x-paystack-signature');

        // Verify webhook signature
        $secret_key = self::get_secret_key();
        $computed_signature = hash_hmac('sha512', $body, $secret_key);

        if ($signature !== $computed_signature) {
            return new WP_Error('invalid_signature', 'Invalid webhook signature', array('status' => 400));
        }

        $event = json_decode($body, true);
        $event_type = $event['event'] ?? '';

        // Handle different event types
        switch ($event_type) {
            case 'charge.success':
                $data = $event['data'];
                self::update_transaction_status($data['reference'], 'success');
                break;

            case 'transfer.success':
                $data = $event['data'];
                self::update_transaction_status($data['reference'], 'success');
                break;

            case 'transfer.failed':
                $data = $event['data'];
                self::update_transaction_status($data['reference'], 'failed');
                break;
        }

        return new WP_REST_Response(array('success' => true), 200);
    }

    /**
     * Get transaction history.
     */
    public static function get_transactions($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $type = $request->get_param('type'); // 'send', 'receive', or null for all
        $status = $request->get_param('status');
        
        $table = $wpdb->prefix . 'caregate_payments';
        $where = array($wpdb->prepare('user_id = %d', $user_id));

        if ($type) {
            $where[] = $wpdb->prepare('type = %s', $type);
        }

        if ($status) {
            $where[] = $wpdb->prepare('status = %s', $status);
        }

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT 50";
        $results = $wpdb->get_results($sql, ARRAY_A);

        return new WP_REST_Response(array(
            'success' => true,
            'transactions' => $results
        ), 200);
    }

    /**
     * Store transaction in database.
     */
    private static function store_transaction($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_payments';
        
        $wpdb->insert($table, array_merge($data, array(
            'created_at' => current_time('mysql')
        )), array('%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s'));

        return $wpdb->insert_id;
    }

    /**
     * Update transaction status.
     */
    private static function update_transaction_status($reference, $status) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_payments';
        
        $wpdb->update(
            $table,
            array('status' => $status),
            array('reference' => $reference),
            array('%s'),
            array('%s')
        );
    }

    /**
     * Store bank account.
     */
    private static function store_bank_account($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_bank_accounts';
        
        $wpdb->insert($table, array_merge($data, array(
            'created_at' => current_time('mysql')
        )), array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s'));

        return $wpdb->insert_id;
    }

    /**
     * Get user's bank accounts.
     */
    public static function get_bank_accounts($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_bank_accounts';
        
        $sql = $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC", $user_id);
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
}
