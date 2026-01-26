<?php
/**
 * UK Invoice Management Admin Page
 * Admin interface for creating and managing UK standard invoices
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get invoices
global $wpdb;
$table = $wpdb->prefix . 'caregate_uk_invoices';
$invoices = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 50", ARRAY_A);

// Get all facilities
$facilities = get_users(array('role' => 'caregate_facility'));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="caregate-admin-section">
        <h2>Create New UK Invoice</h2>
        
        <form id="caregate-invoice-form" class="caregate-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="facility_id">Care Facility *</label></th>
                    <td>
                        <select name="facility_id" id="facility_id" required>
                            <option value="">Select Facility</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?php echo esc_attr($facility->ID); ?>">
                                    <?php echo esc_html($facility->display_name); ?> (<?php echo esc_html($facility->user_email); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="invoice_date">Invoice Date *</label></th>
                    <td><input type="date" name="invoice_date" id="invoice_date" value="<?php echo date('Y-m-d'); ?>" required></td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="due_date">Due Date *</label></th>
                    <td><input type="date" name="due_date" id="due_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required></td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="billing_period">Billing Period</label></th>
                    <td><input type="text" name="billing_period" id="billing_period" value="<?php echo date('Y-m'); ?>" placeholder="YYYY-MM"></td>
                </tr>
            </table>
            
            <h3>Agency Details</h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="agency_name">Agency Name *</label></th>
                    <td><input type="text" name="agency_name" id="agency_name" value="<?php echo esc_attr(get_option('caregate_agency_name', 'CareGate Staffing Ltd')); ?>" required class="regular-text"></td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="agency_address">Address *</label></th>
                    <td><textarea name="agency_address" id="agency_address" rows="3" class="large-text" required><?php echo esc_textarea(get_option('caregate_agency_address', '')); ?></textarea></td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="agency_postcode">Postcode *</label></th>
                    <td><input type="text" name="agency_postcode" id="agency_postcode" value="<?php echo esc_attr(get_option('caregate_agency_postcode', '')); ?>" required></td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="agency_phone">Phone *</label></th>
                    <td><input type="tel" name="agency_phone" id="agency_phone" value="<?php echo esc_attr(get_option('caregate_agency_phone', '')); ?>" required></td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="agency_email">Email *</label></th>
                    <td><input type="email" name="agency_email" id="agency_email" value="<?php echo esc_attr(get_option('caregate_agency_email', get_option('admin_email'))); ?>" required class="regular-text"></td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="agency_vat_number">VAT Number</label></th>
                    <td><input type="text" name="agency_vat_number" id="agency_vat_number" value="<?php echo esc_attr(get_option('caregate_agency_vat_number', '')); ?>" placeholder="GB123456789"></td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="agency_company_number">Company Number</label></th>
                    <td><input type="text" name="agency_company_number" id="agency_company_number" value="<?php echo esc_attr(get_option('caregate_agency_company_number', '')); ?>" placeholder="12345678"></td>
                </tr>
            </table>
            
            <h3>Line Items</h3>
            <table class="widefat fixed striped" id="invoice-items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 15%;">Quantity</th>
                        <th style="width: 15%;">Rate (£)</th>
                        <th style="width: 15%;">Amount (£)</th>
                        <th style="width: 5%;">Action</th>
                    </tr>
                </thead>
                <tbody id="invoice-items">
                    <tr class="invoice-item-row">
                        <td><input type="text" class="item-description regular-text" placeholder="e.g., Night Shift Nurse - 12 hours" required></td>
                        <td><input type="number" class="item-quantity" min="0" step="0.01" value="1" required></td>
                        <td><input type="number" class="item-rate" min="0" step="0.01" placeholder="0.00" required></td>
                        <td><input type="number" class="item-amount" readonly></td>
                        <td><button type="button" class="button remove-item">Remove</button></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">
                            <button type="button" class="button" id="add-item">Add Line Item</button>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Subtotal:</strong></td>
                        <td><span id="invoice-subtotal">£0.00</span></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>VAT (20%):</strong></td>
                        <td><span id="invoice-vat">£0.00</span></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                        <td><strong><span id="invoice-total">£0.00</span></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="notes">Notes</label></th>
                    <td><textarea name="notes" id="notes" rows="3" class="large-text" placeholder="Payment terms, bank details, etc."></textarea></td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="button" id="preview-invoice" class="button button-secondary">Preview Invoice</button>
                <button type="submit" class="button button-primary">Create Invoice (Draft)</button>
            </p>
        </form>
    </div>
    
    <div class="caregate-admin-section">
        <h2>Recent Invoices</h2>
        
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Facility</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="7">No invoices found. Create your first invoice above.</td></tr>
                <?php else: ?>
                    <?php foreach ($invoices as $invoice): 
                        $facility = get_userdata($invoice['facility_id']);
                        $status_class = '';
                        if ($invoice['status'] === 'paid') $status_class = 'caregate-status-success';
                        elseif ($invoice['status'] === 'sent') $status_class = 'caregate-status-warning';
                        elseif ($invoice['status'] === 'draft') $status_class = 'caregate-status-info';
                    ?>
                        <tr>
                            <td><?php echo esc_html($invoice['invoice_number']); ?></td>
                            <td><?php echo $facility ? esc_html($facility->display_name) : 'N/A'; ?></td>
                            <td><?php echo esc_html(date('d/m/Y', strtotime($invoice['invoice_date']))); ?></td>
                            <td><?php echo esc_html(date('d/m/Y', strtotime($invoice['due_date']))); ?></td>
                            <td>£<?php echo number_format($invoice['total'], 2); ?></td>
                            <td><span class="<?php echo $status_class; ?>"><?php echo esc_html(ucfirst($invoice['status'])); ?></span></td>
                            <td>
                                <button class="button button-small view-invoice" data-id="<?php echo $invoice['id']; ?>">View</button>
                                <?php if ($invoice['status'] === 'draft'): ?>
                                    <button class="button button-small edit-invoice" data-id="<?php echo $invoice['id']; ?>">Edit</button>
                                    <button class="button button-primary button-small send-invoice" data-id="<?php echo $invoice['id']; ?>">Send</button>
                                <?php elseif ($invoice['status'] === 'sent'): ?>
                                    <button class="button button-primary button-small mark-paid" data-id="<?php echo $invoice['id']; ?>">Mark Paid</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Calculate line item amounts
    function calculateLineItem(row) {
        const quantity = parseFloat($(row).find('.item-quantity').val()) || 0;
        const rate = parseFloat($(row).find('.item-rate').val()) || 0;
        const amount = quantity * rate;
        $(row).find('.item-amount').val(amount.toFixed(2));
        calculateTotals();
    }
    
    // Calculate invoice totals
    function calculateTotals() {
        let subtotal = 0;
        $('.invoice-item-row').each(function() {
            const amount = parseFloat($(this).find('.item-amount').val()) || 0;
            subtotal += amount;
        });
        
        const vat = subtotal * 0.20;
        const total = subtotal + vat;
        
        $('#invoice-subtotal').text('£' + subtotal.toFixed(2));
        $('#invoice-vat').text('£' + vat.toFixed(2));
        $('#invoice-total').text('£' + total.toFixed(2));
    }
    
    // Add new line item
    $('#add-item').on('click', function() {
        const newRow = `
            <tr class="invoice-item-row">
                <td><input type="text" class="item-description regular-text" required></td>
                <td><input type="number" class="item-quantity" min="0" step="0.01" value="1" required></td>
                <td><input type="number" class="item-rate" min="0" step="0.01" required></td>
                <td><input type="number" class="item-amount" readonly></td>
                <td><button type="button" class="button remove-item">Remove</button></td>
            </tr>
        `;
        $('#invoice-items').append(newRow);
    });
    
    // Remove line item
    $(document).on('click', '.remove-item', function() {
        if ($('.invoice-item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        } else {
            alert('At least one line item is required.');
        }
    });
    
    // Calculate on input
    $(document).on('input', '.item-quantity, .item-rate', function() {
        calculateLineItem($(this).closest('tr'));
    });
    
    // Form submission
    $('#caregate-invoice-form').on('submit', function(e) {
        e.preventDefault();
        
        // Collect form data
        const lineItems = [];
        $('.invoice-item-row').each(function() {
            lineItems.push({
                description: $(this).find('.item-description').val(),
                quantity: parseFloat($(this).find('.item-quantity').val()),
                rate: parseFloat($(this).find('.item-rate').val())
            });
        });
        
        const formData = {
            facilityId: parseInt($('#facility_id').val()),
            invoiceDate: $('#invoice_date').val(),
            dueDate: $('#due_date').val(),
            billingPeriod: $('#billing_period').val(),
            agencyName: $('#agency_name').val(),
            agencyAddress: $('#agency_address').val(),
            agencyPostcode: $('#agency_postcode').val(),
            agencyPhone: $('#agency_phone').val(),
            agencyEmail: $('#agency_email').val(),
            agencyVatNumber: $('#agency_vat_number').val(),
            agencyCompanyNumber: $('#agency_company_number').val(),
            lineItems: lineItems,
            notes: $('#notes').val(),
            vatRegistered: true
        };
        
        // Send to API
        $.ajax({
            url: '<?php echo rest_url('caregate/v1/uk-invoices'); ?>',
            method: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                alert('Invoice created successfully!');
                location.reload();
            },
            error: function(xhr) {
                alert('Error creating invoice: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });
});
</script>

<style>
.caregate-admin-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.caregate-status-success { color: #46b450; font-weight: bold; }
.caregate-status-warning { color: #f0b849; font-weight: bold; }
.caregate-status-info { color: #0073aa; font-weight: bold; }

#invoice-items-table input[type="text"],
#invoice-items-table input[type="number"] {
    width: 100%;
}

#invoice-items-table input[readonly] {
    background: #f0f0f1;
}
</style>
