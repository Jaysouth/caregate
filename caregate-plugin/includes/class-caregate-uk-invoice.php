<?php
/**
 * UK Standard Invoice management.
 * HMRC-compliant invoice generation for agency to care facility billing.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_UK_Invoice {

    /**
     * Create UK standard invoice with line items.
     */
    public static function create_invoice($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        $admin_id = get_current_user_id();
        
        $facility_id = intval($params['facilityId'] ?? 0);
        $invoice_date = sanitize_text_field($params['invoiceDate'] ?? current_time('Y-m-d'));
        $due_date = sanitize_text_field($params['dueDate'] ?? date('Y-m-d', strtotime('+30 days')));
        $billing_period = sanitize_text_field($params['billingPeriod'] ?? date('Y-m'));
        $line_items = $params['lineItems'] ?? array();
        $notes = sanitize_textarea_field($params['notes'] ?? '');
        $vat_registered = boolval($params['vatRegistered'] ?? true);
        
        // Agency details
        $agency_name = sanitize_text_field($params['agencyName'] ?? get_option('caregate_agency_name', 'CareGate Staffing Ltd'));
        $agency_address = sanitize_textarea_field($params['agencyAddress'] ?? get_option('caregate_agency_address', ''));
        $agency_postcode = sanitize_text_field($params['agencyPostcode'] ?? get_option('caregate_agency_postcode', ''));
        $agency_phone = sanitize_text_field($params['agencyPhone'] ?? get_option('caregate_agency_phone', ''));
        $agency_email = sanitize_email($params['agencyEmail'] ?? get_option('caregate_agency_email', get_option('admin_email')));
        $agency_vat_number = sanitize_text_field($params['agencyVatNumber'] ?? get_option('caregate_agency_vat_number', ''));
        $agency_company_number = sanitize_text_field($params['agencyCompanyNumber'] ?? get_option('caregate_agency_company_number', ''));

        if (!$facility_id || empty($line_items)) {
            return new WP_Error('missing_fields', 'Facility and line items required', array('status' => 400));
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($line_items as $item) {
            $quantity = floatval($item['quantity'] ?? 0);
            $rate = floatval($item['rate'] ?? 0);
            $subtotal += $quantity * $rate;
        }

        // VAT calculation (20% UK standard rate)
        $vat_rate = $vat_registered ? 0.20 : 0;
        $vat_amount = $subtotal * $vat_rate;
        $total = $subtotal + $vat_amount;

        // Generate invoice number (INV-YYYY-NNNN format)
        $year = date('Y');
        $last_invoice = $wpdb->get_var($wpdb->prepare(
            "SELECT invoice_number FROM {$wpdb->prefix}caregate_uk_invoices WHERE invoice_number LIKE %s ORDER BY id DESC LIMIT 1",
            "INV-{$year}-%"
        ));
        
        $next_number = 1;
        if ($last_invoice) {
            $parts = explode('-', $last_invoice);
            $next_number = intval($parts[2] ?? 0) + 1;
        }
        $invoice_number = sprintf('INV-%s-%04d', $year, $next_number);

        // Create invoice
        $invoice_table = $wpdb->prefix . 'caregate_uk_invoices';
        $wpdb->insert(
            $invoice_table,
            array(
                'invoice_number' => $invoice_number,
                'facility_id' => $facility_id,
                'admin_id' => $admin_id,
                'invoice_date' => $invoice_date,
                'due_date' => $due_date,
                'billing_period' => $billing_period,
                'agency_name' => $agency_name,
                'agency_address' => $agency_address,
                'agency_postcode' => $agency_postcode,
                'agency_phone' => $agency_phone,
                'agency_email' => $agency_email,
                'agency_vat_number' => $agency_vat_number,
                'agency_company_number' => $agency_company_number,
                'subtotal' => $subtotal,
                'vat_rate' => $vat_rate,
                'vat_amount' => $vat_amount,
                'total' => $total,
                'notes' => $notes,
                'status' => 'draft'
            )
        );

        $invoice_id = $wpdb->insert_id;

        // Create line items
        $items_table = $wpdb->prefix . 'caregate_invoice_items';
        foreach ($line_items as $item) {
            $wpdb->insert(
                $items_table,
                array(
                    'invoice_id' => $invoice_id,
                    'description' => sanitize_text_field($item['description'] ?? ''),
                    'quantity' => floatval($item['quantity'] ?? 0),
                    'rate' => floatval($item['rate'] ?? 0),
                    'amount' => floatval($item['quantity'] ?? 0) * floatval($item['rate'] ?? 0)
                )
            );
        }

        $invoice = self::get_invoice_by_id($invoice_id);

        return new WP_REST_Response(array(
            'message' => 'Invoice created successfully',
            'invoice' => $invoice
        ), 201);
    }

    /**
     * Get invoice by ID with line items.
     */
    public static function get_invoice_by_id($invoice_id) {
        global $wpdb;
        
        $invoice_table = $wpdb->prefix . 'caregate_uk_invoices';
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $invoice_table WHERE id = %d",
            $invoice_id
        ), ARRAY_A);

        if (!$invoice) {
            return null;
        }

        // Get line items
        $items_table = $wpdb->prefix . 'caregate_invoice_items';
        $line_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $items_table WHERE invoice_id = %d ORDER BY id",
            $invoice_id
        ), ARRAY_A);

        return self::format_invoice($invoice, $line_items);
    }

    /**
     * Format invoice data.
     */
    private static function format_invoice($invoice, $line_items = null) {
        $facility = get_userdata($invoice['facility_id']);
        
        if ($line_items === null) {
            global $wpdb;
            $items_table = $wpdb->prefix . 'caregate_invoice_items';
            $line_items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $items_table WHERE invoice_id = %d ORDER BY id",
                $invoice['id']
            ), ARRAY_A);
        }

        $formatted_items = array_map(function($item) {
            return array(
                'id' => intval($item['id']),
                'description' => $item['description'],
                'quantity' => floatval($item['quantity']),
                'rate' => floatval($item['rate']),
                'amount' => floatval($item['amount'])
            );
        }, $line_items);

        return array(
            'id' => intval($invoice['id']),
            'invoiceNumber' => $invoice['invoice_number'],
            'facilityId' => intval($invoice['facility_id']),
            'facilityName' => $facility ? $facility->display_name : '',
            'facilityEmail' => $facility ? $facility->user_email : '',
            'invoiceDate' => $invoice['invoice_date'],
            'dueDate' => $invoice['due_date'],
            'billingPeriod' => $invoice['billing_period'],
            'agency' => array(
                'name' => $invoice['agency_name'],
                'address' => $invoice['agency_address'],
                'postcode' => $invoice['agency_postcode'],
                'phone' => $invoice['agency_phone'],
                'email' => $invoice['agency_email'],
                'vatNumber' => $invoice['agency_vat_number'],
                'companyNumber' => $invoice['agency_company_number']
            ),
            'lineItems' => $formatted_items,
            'subtotal' => floatval($invoice['subtotal']),
            'vatRate' => floatval($invoice['vat_rate']),
            'vatAmount' => floatval($invoice['vat_amount']),
            'total' => floatval($invoice['total']),
            'notes' => $invoice['notes'],
            'status' => $invoice['status'],
            'sentDate' => $invoice['sent_date'],
            'paidDate' => $invoice['paid_date'],
            'createdAt' => $invoice['created_at']
        );
    }

    /**
     * Get all invoices.
     */
    public static function get_invoices($request) {
        global $wpdb;
        
        $facility_id = $request->get_param('facility_id');
        $status = $request->get_param('status');
        
        $invoice_table = $wpdb->prefix . 'caregate_uk_invoices';
        $where = array('1=1');
        $params = array();

        if ($facility_id) {
            $where[] = 'facility_id = %d';
            $params[] = $facility_id;
        }

        if ($status) {
            $where[] = 'status = %s';
            $params[] = $status;
        }

        $where_clause = implode(' AND ', $where);
        
        if (empty($params)) {
            $invoices = $wpdb->get_results(
                "SELECT * FROM $invoice_table WHERE $where_clause ORDER BY created_at DESC LIMIT 100",
                ARRAY_A
            );
        } else {
            $invoices = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $invoice_table WHERE $where_clause ORDER BY created_at DESC LIMIT 100",
                ...$params
            ), ARRAY_A);
        }

        $formatted = array_map(array(self::class, 'format_invoice'), $invoices);

        return new WP_REST_Response(array('invoices' => $formatted), 200);
    }

    /**
     * Update invoice.
     */
    public static function update_invoice($request) {
        global $wpdb;
        
        $invoice_id = $request->get_param('id');
        $params = $request->get_json_params();
        
        if (!$invoice_id) {
            return new WP_Error('missing_id', 'Invoice ID required', array('status' => 400));
        }

        $update_data = array();
        
        if (isset($params['invoiceDate'])) {
            $update_data['invoice_date'] = sanitize_text_field($params['invoiceDate']);
        }
        if (isset($params['dueDate'])) {
            $update_data['due_date'] = sanitize_text_field($params['dueDate']);
        }
        if (isset($params['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($params['notes']);
        }
        if (isset($params['status'])) {
            $update_data['status'] = sanitize_text_field($params['status']);
        }

        // Update line items if provided
        if (isset($params['lineItems'])) {
            $items_table = $wpdb->prefix . 'caregate_invoice_items';
            
            // Delete existing items
            $wpdb->delete($items_table, array('invoice_id' => $invoice_id));
            
            // Insert new items and recalculate totals
            $subtotal = 0;
            foreach ($params['lineItems'] as $item) {
                $quantity = floatval($item['quantity'] ?? 0);
                $rate = floatval($item['rate'] ?? 0);
                $amount = $quantity * $rate;
                $subtotal += $amount;
                
                $wpdb->insert(
                    $items_table,
                    array(
                        'invoice_id' => $invoice_id,
                        'description' => sanitize_text_field($item['description'] ?? ''),
                        'quantity' => $quantity,
                        'rate' => $rate,
                        'amount' => $amount
                    )
                );
            }
            
            // Recalculate VAT and total
            $invoice_table = $wpdb->prefix . 'caregate_uk_invoices';
            $current_invoice = $wpdb->get_row($wpdb->prepare(
                "SELECT vat_rate FROM $invoice_table WHERE id = %d",
                $invoice_id
            ), ARRAY_A);
            
            $vat_amount = $subtotal * floatval($current_invoice['vat_rate']);
            $total = $subtotal + $vat_amount;
            
            $update_data['subtotal'] = $subtotal;
            $update_data['vat_amount'] = $vat_amount;
            $update_data['total'] = $total;
        }

        if (!empty($update_data)) {
            $invoice_table = $wpdb->prefix . 'caregate_uk_invoices';
            $wpdb->update($invoice_table, $update_data, array('id' => $invoice_id));
        }

        $invoice = self::get_invoice_by_id($invoice_id);

        return new WP_REST_Response(array(
            'message' => 'Invoice updated successfully',
            'invoice' => $invoice
        ), 200);
    }

    /**
     * Send invoice to facility.
     */
    public static function send_invoice($request) {
        global $wpdb;
        
        $invoice_id = $request->get_param('id');
        
        if (!$invoice_id) {
            return new WP_Error('missing_id', 'Invoice ID required', array('status' => 400));
        }

        $invoice = self::get_invoice_by_id($invoice_id);
        
        if (!$invoice) {
            return new WP_Error('not_found', 'Invoice not found', array('status' => 404));
        }

        // Update status
        $invoice_table = $wpdb->prefix . 'caregate_uk_invoices';
        $wpdb->update(
            $invoice_table,
            array(
                'status' => 'sent',
                'sent_date' => current_time('mysql')
            ),
            array('id' => $invoice_id)
        );

        // Send email with PDF attachment (PDF generation would be implemented separately)
        $facility = get_userdata($invoice['facilityId']);
        $subject = 'Invoice ' . $invoice['invoiceNumber'] . ' from ' . $invoice['agency']['name'];
        $message = 'Please find your invoice attached. Payment is due by ' . $invoice['dueDate'] . '.';
        
        wp_mail($facility->user_email, $subject, $message);

        $updated_invoice = self::get_invoice_by_id($invoice_id);

        return new WP_REST_Response(array(
            'message' => 'Invoice sent successfully',
            'invoice' => $updated_invoice
        ), 200);
    }

    /**
     * Mark invoice as paid.
     */
    public static function mark_paid($request) {
        global $wpdb;
        
        $invoice_id = $request->get_param('id');
        
        if (!$invoice_id) {
            return new WP_Error('missing_id', 'Invoice ID required', array('status' => 400));
        }

        $invoice_table = $wpdb->prefix . 'caregate_uk_invoices';
        $wpdb->update(
            $invoice_table,
            array(
                'status' => 'paid',
                'paid_date' => current_time('mysql')
            ),
            array('id' => $invoice_id)
        );

        $invoice = self::get_invoice_by_id($invoice_id);

        return new WP_REST_Response(array(
            'message' => 'Invoice marked as paid',
            'invoice' => $invoice
        ), 200);
    }
}
