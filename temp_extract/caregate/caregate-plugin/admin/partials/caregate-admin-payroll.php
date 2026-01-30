<?php
/**
 * Payroll Management Admin Page
 * Admin interface for creating UK standard salary payments
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get workers
$workers = get_users(array('role' => 'caregate_worker'));

// Get recent payroll
global $wpdb;
$payroll_table = $wpdb->prefix . 'caregate_payroll';
$payroll_records = $wpdb->get_results("SELECT * FROM $payroll_table ORDER BY created_at DESC LIMIT 50", ARRAY_A);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="caregate-admin-section">
        <h2>Create Salary Payment</h2>
        
        <form id="caregate-payroll-form" class="caregate-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="worker_id">Select Care Worker *</label></th>
                    <td>
                        <select name="worker_id" id="worker_id" required>
                            <option value="">Select Worker</option>
                            <?php foreach ($workers as $worker): ?>
                                <option value="<?php echo esc_attr($worker->ID); ?>">
                                    <?php echo esc_html($worker->display_name); ?> (<?php echo esc_html($worker->user_email); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <div id="worker-profile" style="display: none;">
                <h3>Worker Profile</h3>
                <div class="worker-profile-card">
                    <div class="worker-photo">
                        <img id="worker-photo-img" src="" alt="Worker Photo" style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px;">
                    </div>
                    <div class="worker-details">
                        <p><strong>Name:</strong> <span id="worker-name"></span></p>
                        <p><strong>Email:</strong> <span id="worker-email"></span></p>
                        <p><strong>Phone:</strong> <span id="worker-phone"></span></p>
                        <p><strong>NI Number:</strong> <span id="worker-ni"></span></p>
                        <p><strong>Tax Code:</strong> <span id="worker-tax-code"></span></p>
                        <p><strong>Rating:</strong> <span id="worker-rating"></span> ⭐</p>
                        <p><strong>Completed Shifts:</strong> <span id="worker-shifts"></span></p>
                    </div>
                </div>
                
                <h3>Payment Details</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="payment_period">Payment Period *</label></th>
                        <td><input type="text" name="payment_period" id="payment_period" value="<?php echo date('Y-m'); ?>" placeholder="YYYY-MM" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="basic_pay">Basic Pay (Hourly Rate £) *</label></th>
                        <td><input type="number" name="basic_pay" id="basic_pay" min="0" step="0.01" placeholder="15.00" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="hours_worked">Hours Worked *</label></th>
                        <td><input type="number" name="hours_worked" id="hours_worked" min="0" step="0.01" placeholder="160" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="overtime_pay">Overtime Pay (£)</label></th>
                        <td><input type="number" name="overtime_pay" id="overtime_pay" min="0" step="0.01" value="0" placeholder="0.00"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="shift_differentials">Shift Differentials / Extra Pay (£)</label></th>
                        <td><input type="number" name="shift_differentials" id="shift_differentials" min="0" step="0.01" value="0" placeholder="0.00"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="bonus">Bonus (£)</label></th>
                        <td><input type="number" name="bonus" id="bonus" min="0" step="0.01" value="0" placeholder="0.00"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="health_insurance">Health Insurance (£)</label></th>
                        <td><input type="number" name="health_insurance" id="health_insurance" min="0" step="0.01" value="0" placeholder="0.00"></td>
                    </tr>
                </table>
                
                <p>
                    <button type="button" id="calculate-payroll" class="button button-primary">Calculate Payment</button>
                </p>
                
                <div id="payroll-calculation" style="display: none;">
                    <h3>Calculation Summary</h3>
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <th>Gross Pay</th>
                                <td><strong>£<span id="gross-pay">0.00</span></strong></td>
                            </tr>
                            <tr>
                                <th colspan="2" style="background: #f0f0f1;">Deductions</th>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Income Tax (PAYE)</td>
                                <td>£<span id="deduction-tax">0.00</span></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">National Insurance</td>
                                <td>£<span id="deduction-ni">0.00</span></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Pension (5%)</td>
                                <td>£<span id="deduction-pension">0.00</span></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Health Insurance</td>
                                <td>£<span id="deduction-insurance">0.00</span></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Agency Fees (10%)</td>
                                <td>£<span id="deduction-agency">0.00</span></td>
                            </tr>
                            <tr>
                                <th>Total Deductions</th>
                                <td><strong>£<span id="total-deductions">0.00</span></strong></td>
                            </tr>
                            <tr style="background: #dff0d8;">
                                <th><strong>Net Pay (Take Home)</strong></th>
                                <td><strong style="font-size: 18px; color: #46b450;">£<span id="net-pay">0.00</span></strong></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="notes">Notes</label></th>
                            <td><textarea name="notes" id="notes" rows="3" class="large-text" placeholder="Additional notes..."></textarea></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="preview-salary-slip" class="button button-secondary">Preview Salary Slip</button>
                        <button type="submit" class="button button-primary">Create & Send Salary Slip</button>
                    </p>
                </div>
            </div>
        </form>
    </div>
    
    <div class="caregate-admin-section">
        <h2>Recent Payroll Records</h2>
        
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>Period</th>
                    <th>Gross Pay</th>
                    <th>Deductions</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payroll_records)): ?>
                    <tr><td colspan="8">No payroll records found. Create your first payment above.</td></tr>
                <?php else: ?>
                    <?php foreach ($payroll_records as $record): 
                        $worker = get_userdata($record['worker_id']);
                        $status_class = $record['status'] === 'sent' ? 'caregate-status-success' : 'caregate-status-info';
                    ?>
                        <tr>
                            <td><?php echo $worker ? esc_html($worker->display_name) : 'N/A'; ?></td>
                            <td><?php echo esc_html($record['payment_period']); ?></td>
                            <td>£<?php echo number_format($record['gross_pay'], 2); ?></td>
                            <td>£<?php echo number_format($record['total_deductions'], 2); ?></td>
                            <td><strong>£<?php echo number_format($record['net_pay'], 2); ?></strong></td>
                            <td><span class="<?php echo $status_class; ?>"><?php echo esc_html(ucfirst($record['status'])); ?></span></td>
                            <td><?php echo esc_html(date('d/m/Y', strtotime($record['created_at']))); ?></td>
                            <td>
                                <button class="button button-small view-payroll" data-id="<?php echo $record['id']; ?>">View</button>
                                <?php if ($record['status'] === 'pending'): ?>
                                    <button class="button button-primary button-small send-salary-slip" data-id="<?php echo $record['id']; ?>">Send</button>
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
    let calculatedData = null;
    
    // Load worker profile
    $('#worker_id').on('change', function() {
        const workerId = $(this).val();
        if (!workerId) {
            $('#worker-profile').hide();
            return;
        }
        
        $.ajax({
            url: '<?php echo rest_url('caregate/v1/payroll/worker/'); ?>' + workerId,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                const worker = response.worker;
                $('#worker-photo-img').attr('src', worker.photo);
                $('#worker-name').text(worker.name);
                $('#worker-email').text(worker.email);
                $('#worker-phone').text(worker.phone || 'N/A');
                $('#worker-ni').text(worker.niNumber || 'N/A');
                $('#worker-tax-code').text(worker.taxDetails?.taxCode || '1257L');
                $('#worker-rating').text(worker.rating || '5.0');
                $('#worker-shifts').text(worker.completedShifts || '0');
                $('#worker-profile').show();
            },
            error: function() {
                alert('Error loading worker profile');
            }
        });
    });
    
    // Calculate payroll
    $('#calculate-payroll').on('click', function() {
        const data = {
            basicPay: parseFloat($('#basic_pay').val()) || 0,
            hoursWorked: parseFloat($('#hours_worked').val()) || 0,
            overtimePay: parseFloat($('#overtime_pay').val()) || 0,
            shiftDifferentials: parseFloat($('#shift_differentials').val()) || 0,
            bonus: parseFloat($('#bonus').val()) || 0,
            healthInsurance: parseFloat($('#health_insurance').val()) || 0
        };
        
        if (data.basicPay === 0 || data.hoursWorked === 0) {
            alert('Please enter basic pay and hours worked');
            return;
        }
        
        $.ajax({
            url: '<?php echo rest_url('caregate/v1/payroll/calculate'); ?>',
            method: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                calculatedData = response;
                $('#gross-pay').text(response.grossPay.toFixed(2));
                $('#deduction-tax').text(response.deductions.tax.toFixed(2));
                $('#deduction-ni').text(response.deductions.nationalInsurance.toFixed(2));
                $('#deduction-pension').text(response.deductions.pension.toFixed(2));
                $('#deduction-insurance').text(response.deductions.healthInsurance.toFixed(2));
                $('#deduction-agency').text(response.deductions.agencyFees.toFixed(2));
                $('#total-deductions').text(response.deductions.total.toFixed(2));
                $('#net-pay').text(response.netPay.toFixed(2));
                $('#payroll-calculation').show();
            },
            error: function() {
                alert('Error calculating payroll');
            }
        });
    });
    
    // Submit payroll
    $('#caregate-payroll-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!calculatedData) {
            alert('Please calculate the payment first');
            return;
        }
        
        const formData = {
            workerId: parseInt($('#worker_id').val()),
            paymentPeriod: $('#payment_period').val(),
            basicPay: parseFloat($('#basic_pay').val()),
            hoursWorked: parseFloat($('#hours_worked').val()),
            overtimePay: parseFloat($('#overtime_pay').val()),
            shiftDifferentials: parseFloat($('#shift_differentials').val()),
            bonus: parseFloat($('#bonus').val()),
            grossPay: calculatedData.grossPay,
            deductions: calculatedData.deductions,
            netPay: calculatedData.netPay,
            notes: $('#notes').val()
        };
        
        $.ajax({
            url: '<?php echo rest_url('caregate/v1/payroll'); ?>',
            method: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                // Auto-send salary slip
                $.ajax({
                    url: '<?php echo rest_url('caregate/v1/payroll/'); ?>' + response.payroll.id + '/send',
                    method: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                    },
                    success: function() {
                        alert('Salary slip created and sent successfully!');
                        location.reload();
                    }
                });
            },
            error: function(xhr) {
                alert('Error creating payroll: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });
});
</script>

<style>
.worker-profile-card {
    display: flex;
    gap: 30px;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 15px 0;
}

.worker-details p {
    margin: 8px 0;
}

.caregate-admin-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.caregate-status-success { color: #46b450; font-weight: bold; }
.caregate-status-info { color: #0073aa; font-weight: bold; }
</style>
