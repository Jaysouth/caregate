<?php
/**
 * Billing management functionality.
 */

class CareGate_Billing {

    public static function generate_invoice($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        $user_id = get_current_user_id();
        
        $timesheet_ids = $params['timesheetIds'] ?? array();
        $billing_period = sanitize_text_field($params['billingPeriod'] ?? date('Y-m'));

        if (empty($timesheet_ids)) {
            return new WP_Error('missing_fields', 'At least one timesheet required', array('status' => 400));
        }

        $subtotal = 0;
        foreach ($timesheet_ids as $ts_id) {
            $ts = CareGate_Timesheets::get_timesheet_by_id($ts_id);
            if ($ts && $ts['status'] === 'approved') {
                $subtotal += $ts['totalPay'];
            }
        }

        $platform_fee_pct = (float) get_option('caregate_platform_fee_percentage', 0.15);
        $platform_fee = $subtotal * $platform_fee_pct;
        $total = $subtotal + $platform_fee;

        $invoice_table = $wpdb->prefix . 'caregate_invoices';
        $wpdb->insert(
            $invoice_table,
            array(
                'facility_id' => $user_id,
                'billing_period' => $billing_period,
                'subtotal' => $subtotal,
                'platform_fee' => $platform_fee,
                'total' => $total,
                'status' => 'pending',
                'due_date' => date('Y-m-d H:i:s', strtotime('+30 days'))
            )
        );

        $invoice_id = $wpdb->insert_id;

        // Link timesheets to invoice
        $junction_table = $wpdb->prefix . 'caregate_invoice_timesheets';
        foreach ($timesheet_ids as $ts_id) {
            $wpdb->insert(
                $junction_table,
                array('invoice_id' => $invoice_id, 'timesheet_id' => $ts_id)
            );
        }

        $invoice = self::get_invoice_by_id($invoice_id);

        return new WP_REST_Response(array(
            'message' => 'Invoice generated successfully',
            'invoice' => $invoice
        ), 201);
    }

    public static function get_invoices($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'caregate_invoices';
        
        $invoices = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE facility_id = %d ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);

        $formatted = array_map(array(self::class, 'format_invoice'), $invoices);

        return new WP_REST_Response(array('invoices' => $formatted), 200);
    }

    public static function get_invoice($request) {
        $invoice_id = $request->get_param('id');
        $invoice = self::get_invoice_by_id($invoice_id);

        if (!$invoice) {
            return new WP_Error('not_found', 'Invoice not found', array('status' => 404));
        }

        return new WP_REST_Response(array('invoice' => $invoice), 200);
    }

    public static function get_billing_stats($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        if (in_array('caregate_facility', $user->roles)) {
            $invoice_table = $wpdb->prefix . 'caregate_invoices';
            $invoices = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $invoice_table WHERE facility_id = %d",
                $user_id
            ), ARRAY_A);

            $total_billed = array_sum(array_column($invoices, 'total'));
            $total_paid = 0;
            $total_pending = 0;

            foreach ($invoices as $inv) {
                if ($inv['status'] === 'paid') {
                    $total_paid += $inv['total'];
                } else {
                    $total_pending += $inv['total'];
                }
            }

            $stats = array(
                'totalInvoices' => count($invoices),
                'totalBilled' => $total_billed,
                'totalPaid' => $total_paid,
                'totalPending' => $total_pending
            );
        } else {
            $timesheet_table = $wpdb->prefix . 'caregate_timesheets';
            $timesheets = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $timesheet_table WHERE worker_id = %d",
                $user_id
            ), ARRAY_A);

            $total_earned = 0;
            $pending_pay = 0;
            $total_hours = 0;

            foreach ($timesheets as $ts) {
                $total_hours += $ts['hours_worked'];
                if ($ts['status'] === 'approved') {
                    $total_earned += $ts['total_pay'];
                } else {
                    $pending_pay += $ts['total_pay'];
                }
            }

            $stats = array(
                'totalTimesheets' => count($timesheets),
                'totalEarned' => $total_earned,
                'pendingPay' => $pending_pay,
                'totalHoursWorked' => $total_hours
            );
        }

        return new WP_REST_Response(array('stats' => $stats), 200);
    }

    private static function get_invoice_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'caregate_invoices';
        $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
        return $invoice ? self::format_invoice($invoice) : null;
    }

    private static function format_invoice($inv) {
        return array(
            'id' => $inv['id'],
            'facilityId' => $inv['facility_id'],
            'billingPeriod' => $inv['billing_period'],
            'subtotal' => (float) $inv['subtotal'],
            'platformFee' => (float) $inv['platform_fee'],
            'total' => (float) $inv['total'],
            'status' => $inv['status'],
            'dueDate' => $inv['due_date'],
            'paidAt' => $inv['paid_at']
        );
    }
}
