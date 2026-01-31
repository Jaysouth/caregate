<?php
/**
 * Payroll management functionality for UK standard salary payments.
 * Handles worker payments with tax, pension, and other deductions.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_Payroll {

    /**
     * Get worker details for payroll processing.
     */
    public static function get_worker_for_payroll($request) {
        $worker_id = $request->get_param('worker_id');
        
        if (!$worker_id) {
            return new WP_Error('missing_worker_id', 'Worker ID required', array('status' => 400));
        }

        $worker = get_userdata($worker_id);
        if (!$worker || !in_array('caregate_worker', $worker->roles)) {
            return new WP_Error('worker_not_found', 'Worker not found', array('status' => 404));
        }

        // Get worker meta data
        $worker_data = array(
            'id' => $worker->ID,
            'name' => $worker->display_name,
            'email' => $worker->user_email,
            'photo' => get_avatar_url($worker->ID, array('size' => 200)),
            'phone' => CareGate_Auth::get_user_meta($worker->ID, 'phone_number'),
            'niNumber' => CareGate_Auth::get_user_meta($worker->ID, 'ni_number'),
            'dbsNumber' => CareGate_Auth::get_user_meta($worker->ID, 'dbs_number'),
            'nmcRegistration' => CareGate_Auth::get_user_meta($worker->ID, 'nmc_registration'),
            'address' => CareGate_Auth::get_user_meta($worker->ID, 'address'),
            'postcode' => CareGate_Auth::get_user_meta($worker->ID, 'postcode'),
            'rating' => CareGate_Auth::get_user_meta($worker->ID, 'rating'),
            'completedShifts' => CareGate_Auth::get_user_meta($worker->ID, 'completed_shifts'),
            'bankDetails' => array(
                'accountName' => CareGate_Auth::get_user_meta($worker->ID, 'bank_account_name'),
                'sortCode' => CareGate_Auth::get_user_meta($worker->ID, 'bank_sort_code'),
                'accountNumber' => CareGate_Auth::get_user_meta($worker->ID, 'bank_account_number'),
            ),
            'taxDetails' => array(
                'taxCode' => CareGate_Auth::get_user_meta($worker->ID, 'tax_code') ?: '1257L',
                'studentLoan' => CareGate_Auth::get_user_meta($worker->ID, 'student_loan') ?: false,
            )
        );

        return new WP_REST_Response(array('worker' => $worker_data), 200);
    }

    /**
     * Calculate UK payroll with tax and deductions.
     */
    public static function calculate_payroll($request) {
        $params = $request->get_json_params();
        
        $basic_pay = floatval($params['basicPay'] ?? 0);
        $hours_worked = floatval($params['hoursWorked'] ?? 0);
        $overtime_pay = floatval($params['overtimePay'] ?? 0);
        $shift_differentials = floatval($params['shiftDifferentials'] ?? 0);
        $bonus = floatval($params['bonus'] ?? 0);

        // Calculate gross pay
        $gross_pay = ($basic_pay * $hours_worked) + $overtime_pay + $shift_differentials + $bonus;

        // UK Tax calculations (simplified - in production use HMRC API)
        $tax_free_allowance_monthly = 1047.50; // £12,570 / 12
        $taxable_income = max(0, $gross_pay - $tax_free_allowance_monthly);
        
        // Basic rate: 20% on income up to £50,270
        // Higher rate: 40% on income between £50,270 and £125,140
        $basic_rate_limit_monthly = 4189.17; // £50,270 / 12
        
        $tax = 0;
        if ($taxable_income <= $basic_rate_limit_monthly) {
            $tax = $taxable_income * 0.20;
        } else {
            $tax = ($basic_rate_limit_monthly * 0.20) + (($taxable_income - $basic_rate_limit_monthly) * 0.40);
        }

        // National Insurance (simplified)
        // Class 1 NI: 12% on earnings between £242 and £967 per week (£1,048 - £4,189 monthly)
        $ni_lower_limit = 1048;
        $ni_upper_limit = 4189;
        
        $ni = 0;
        if ($gross_pay > $ni_lower_limit) {
            $ni_able_income = min($gross_pay, $ni_upper_limit) - $ni_lower_limit;
            $ni = $ni_able_income * 0.12;
            
            // Additional 2% on income above upper limit
            if ($gross_pay > $ni_upper_limit) {
                $ni += ($gross_pay - $ni_upper_limit) * 0.02;
            }
        }

        // Pension (5% employee contribution)
        $pension = $gross_pay * 0.05;

        // Health insurance (flat rate or percentage)
        $health_insurance = floatval($params['healthInsurance'] ?? 0);

        // Agency fees (configurable percentage)
        $agency_fee_pct = floatval(get_option('caregate_agency_fee_percentage', 0.10));
        $agency_fees = $gross_pay * $agency_fee_pct;

        // Calculate net pay
        $total_deductions = $tax + $ni + $pension + $health_insurance + $agency_fees;
        $net_pay = $gross_pay - $total_deductions;

        return new WP_REST_Response(array(
            'grossPay' => round($gross_pay, 2),
            'deductions' => array(
                'tax' => round($tax, 2),
                'nationalInsurance' => round($ni, 2),
                'pension' => round($pension, 2),
                'healthInsurance' => round($health_insurance, 2),
                'agencyFees' => round($agency_fees, 2),
                'total' => round($total_deductions, 2)
            ),
            'netPay' => round($net_pay, 2)
        ), 200);
    }

    /**
     * Create payroll record and generate salary slip.
     */
    public static function create_payroll($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        $admin_id = get_current_user_id();
        
        $worker_id = intval($params['workerId'] ?? 0);
        $basic_pay = floatval($params['basicPay'] ?? 0);
        $hours_worked = floatval($params['hoursWorked'] ?? 0);
        $overtime_pay = floatval($params['overtimePay'] ?? 0);
        $shift_differentials = floatval($params['shiftDifferentials'] ?? 0);
        $bonus = floatval($params['bonus'] ?? 0);
        $tax = floatval($params['deductions']['tax'] ?? 0);
        $ni = floatval($params['deductions']['nationalInsurance'] ?? 0);
        $pension = floatval($params['deductions']['pension'] ?? 0);
        $health_insurance = floatval($params['deductions']['healthInsurance'] ?? 0);
        $agency_fees = floatval($params['deductions']['agencyFees'] ?? 0);
        $gross_pay = floatval($params['grossPay'] ?? 0);
        $net_pay = floatval($params['netPay'] ?? 0);
        $payment_period = sanitize_text_field($params['paymentPeriod'] ?? date('Y-m'));
        $notes = sanitize_textarea_field($params['notes'] ?? '');

        if (!$worker_id || !$gross_pay) {
            return new WP_Error('missing_fields', 'Worker ID and payment details required', array('status' => 400));
        }

        $table = $wpdb->prefix . 'caregate_payroll';
        $wpdb->insert(
            $table,
            array(
                'worker_id' => $worker_id,
                'admin_id' => $admin_id,
                'payment_period' => $payment_period,
                'basic_pay_rate' => $basic_pay,
                'hours_worked' => $hours_worked,
                'overtime_pay' => $overtime_pay,
                'shift_differentials' => $shift_differentials,
                'bonus' => $bonus,
                'gross_pay' => $gross_pay,
                'tax' => $tax,
                'national_insurance' => $ni,
                'pension' => $pension,
                'health_insurance' => $health_insurance,
                'agency_fees' => $agency_fees,
                'total_deductions' => $tax + $ni + $pension + $health_insurance + $agency_fees,
                'net_pay' => $net_pay,
                'notes' => $notes,
                'status' => 'pending',
                'payment_date' => null
            )
        );

        $payroll_id = $wpdb->insert_id;
        $payroll = self::get_payroll_by_id($payroll_id);

        return new WP_REST_Response(array(
            'message' => 'Payroll record created successfully',
            'payroll' => $payroll
        ), 201);
    }

    /**
     * Get payroll by ID.
     */
    public static function get_payroll_by_id($payroll_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_payroll';
        $payroll = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $payroll_id
        ), ARRAY_A);

        if (!$payroll) {
            return null;
        }

        return self::format_payroll($payroll);
    }

    /**
     * Format payroll data.
     */
    private static function format_payroll($payroll) {
        return array(
            'id' => intval($payroll['id']),
            'workerId' => intval($payroll['worker_id']),
            'adminId' => intval($payroll['admin_id']),
            'paymentPeriod' => $payroll['payment_period'],
            'basicPayRate' => floatval($payroll['basic_pay_rate']),
            'hoursWorked' => floatval($payroll['hours_worked']),
            'overtimePay' => floatval($payroll['overtime_pay']),
            'shiftDifferentials' => floatval($payroll['shift_differentials']),
            'bonus' => floatval($payroll['bonus']),
            'grossPay' => floatval($payroll['gross_pay']),
            'deductions' => array(
                'tax' => floatval($payroll['tax']),
                'nationalInsurance' => floatval($payroll['national_insurance']),
                'pension' => floatval($payroll['pension']),
                'healthInsurance' => floatval($payroll['health_insurance']),
                'agencyFees' => floatval($payroll['agency_fees']),
                'total' => floatval($payroll['total_deductions'])
            ),
            'netPay' => floatval($payroll['net_pay']),
            'notes' => $payroll['notes'],
            'status' => $payroll['status'],
            'paymentDate' => $payroll['payment_date'],
            'createdAt' => $payroll['created_at']
        );
    }

    /**
     * Get all payroll records.
     */
    public static function get_payroll_records($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'caregate_payroll';
        $worker_id = $request->get_param('worker_id');
        
        if ($worker_id) {
            $records = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE worker_id = %d ORDER BY created_at DESC",
                $worker_id
            ), ARRAY_A);
        } else {
            $records = $wpdb->get_results(
                "SELECT * FROM $table ORDER BY created_at DESC LIMIT 100",
                ARRAY_A
            );
        }

        $formatted = array_map(array(self::class, 'format_payroll'), $records);

        return new WP_REST_Response(array('payroll' => $formatted), 200);
    }

    /**
     * Update payroll status and send salary slip.
     */
    public static function send_salary_slip($request) {
        global $wpdb;
        
        $payroll_id = $request->get_param('id');
        
        if (!$payroll_id) {
            return new WP_Error('missing_id', 'Payroll ID required', array('status' => 400));
        }

        $table = $wpdb->prefix . 'caregate_payroll';
        $wpdb->update(
            $table,
            array(
                'status' => 'sent',
                'payment_date' => current_time('mysql')
            ),
            array('id' => $payroll_id)
        );

        $payroll = self::get_payroll_by_id($payroll_id);
        
        // Send email with PDF attachment (implementation would generate PDF)
        $worker = get_userdata($payroll['workerId']);
        $subject = 'CareGate Salary Slip - ' . $payroll['paymentPeriod'];
        $message = 'Please find your salary slip attached.';
        
        wp_mail($worker->user_email, $subject, $message);

        return new WP_REST_Response(array(
            'message' => 'Salary slip sent successfully',
            'payroll' => $payroll
        ), 200);
    }
}
