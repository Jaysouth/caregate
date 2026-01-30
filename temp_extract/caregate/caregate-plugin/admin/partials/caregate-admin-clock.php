<?php
/**
 * Timesheet Clock Management Admin Page
 * Admin interface for managing clock in/out records
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get workers and facilities
$workers = get_users(array('role' => 'caregate_worker'));
$facilities = get_users(array('role' => 'caregate_facility'));

// Get recent clock records
global $wpdb;
$clock_table = $wpdb->prefix . 'caregate_clock_records';
$clock_records = $wpdb->get_results("SELECT * FROM $clock_table ORDER BY clock_in_time DESC LIMIT 100", ARRAY_A);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="caregate-admin-section">
        <h2>Manual Clock Entry</h2>
        <p>Use this form to manually enter clock in/out times for workers (Care Facility responsibility)</p>
        
        <form id="caregate-clock-manual-form" class="caregate-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="worker_id">Care Worker *</label></th>
                    <td>
                        <select name="worker_id" id="worker_id" required style="width: 400px;">
                            <option value="">Select Worker</option>
                            <?php foreach ($workers as $worker): ?>
                                <option value="<?php echo esc_attr($worker->ID); ?>">
                                    <?php echo esc_html($worker->display_name); ?> (<?php echo esc_html($worker->user_email); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="facility_id">Care Facility *</label></th>
                    <td>
                        <select name="facility_id" id="facility_id" required style="width: 400px;">
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
                    <th scope="row"><label for="clock_in_time">Clock In Time *</label></th>
                    <td><input type="datetime-local" name="clock_in_time" id="clock_in_time" required></td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="clock_out_time">Clock Out Time *</label></th>
                    <td><input type="datetime-local" name="clock_out_time" id="clock_out_time" required></td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="break_time">Break Time (hours)</label></th>
                    <td><input type="number" name="break_time" id="break_time" min="0" step="0.5" value="0.5" placeholder="0.5"></td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="notes">Notes</label></th>
                    <td><textarea name="notes" id="notes" rows="3" class="large-text" placeholder="Additional notes..."></textarea></td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">Create Manual Clock Entry</button>
            </p>
        </form>
    </div>
    
    <div class="caregate-admin-section">
        <h2>Automatic Card Scanning (Auto Mode)</h2>
        <p><strong>Note:</strong> For automatic clock in/out via card scanning, workers can use the frontend dashboard or a dedicated card reader interface.</p>
        
        <div class="auto-clock-info" style="background: #e7f4f9; padding: 15px; border-left: 4px solid #0073aa; margin: 15px 0;">
            <h4 style="margin-top: 0;">How Auto Mode Works:</h4>
            <ol style="margin: 10px 0;">
                <li>Worker scans their ID card at the facility</li>
                <li>System automatically records clock in/out time</li>
                <li>Worker profile is immediately updated</li>
                <li>Changes reflect on admin dashboard in real-time</li>
                <li>Timesheet is automatically created when shift completes</li>
            </ol>
            <p><strong>Current Implementation:</strong> Workers can clock in/out via:</p>
            <ul style="margin: 10px 0;">
                <li>WordPress frontend dashboard (shortcode: <code>[caregate_app]</code>)</li>
                <li>REST API endpoints: <code>/wp-json/caregate/v1/clock/in</code> and <code>/wp-json/caregate/v1/clock/out</code></li>
                <li>Custom card scanner integration (via API)</li>
            </ul>
        </div>
    </div>
    
    <div class="caregate-admin-section">
        <h2>Live Clock Status Dashboard</h2>
        
        <div class="dashboard-filters" style="margin: 15px 0;">
            <label>
                <input type="radio" name="status-filter" value="in_progress" checked> Currently Clocked In
            </label>
            <label style="margin-left: 20px;">
                <input type="radio" name="status-filter" value="completed"> Completed Shifts
            </label>
            <label style="margin-left: 20px;">
                <input type="radio" name="status-filter" value="all"> All Records
            </label>
            <button id="refresh-dashboard" class="button" style="margin-left: 20px;">🔄 Refresh</button>
        </div>
        
        <table class="widefat fixed striped" id="clock-records-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Worker</th>
                    <th style="width: 15%;">Facility</th>
                    <th style="width: 15%;">Clock In</th>
                    <th style="width: 15%;">Clock Out</th>
                    <th style="width: 10%;">Hours</th>
                    <th style="width: 10%;">Method</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%;">Actions</th>
                </tr>
            </thead>
            <tbody id="clock-records-body">
                <?php if (empty($clock_records)): ?>
                    <tr><td colspan="8">No clock records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($clock_records as $record): 
                        $worker = get_userdata($record['worker_id']);
                        $facility = get_userdata($record['facility_id']);
                        $status_class = '';
                        $status_icon = '';
                        if ($record['status'] === 'in_progress') {
                            $status_class = 'caregate-status-warning';
                            $status_icon = '🟢';
                        } else {
                            $status_class = 'caregate-status-success';
                            $status_icon = '✅';
                        }
                    ?>
                        <tr>
                            <td><?php echo $worker ? esc_html($worker->display_name) : 'N/A'; ?></td>
                            <td><?php echo $facility ? esc_html($facility->display_name) : 'N/A'; ?></td>
                            <td><?php echo esc_html(date('d/m/Y H:i', strtotime($record['clock_in_time']))); ?></td>
                            <td><?php echo $record['clock_out_time'] ? esc_html(date('d/m/Y H:i', strtotime($record['clock_out_time']))) : '<em>In progress</em>'; ?></td>
                            <td><?php echo $record['hours_worked'] ? number_format($record['hours_worked'], 2) . ' hrs' : '-'; ?></td>
                            <td><?php echo esc_html(ucfirst($record['clock_in_method'])); ?></td>
                            <td><span class="<?php echo $status_class; ?>"><?php echo $status_icon; ?> <?php echo esc_html(ucfirst(str_replace('_', ' ', $record['status']))); ?></span></td>
                            <td>
                                <button class="button button-small view-clock" data-id="<?php echo $record['id']; ?>">View</button>
                                <?php if ($record['status'] === 'in_progress'): ?>
                                    <button class="button button-small clock-out-manual" data-id="<?php echo $record['id']; ?>" data-worker="<?php echo $record['worker_id']; ?>">Clock Out</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="caregate-admin-section">
        <h2>Clock Statistics</h2>
        <div class="clock-stats" style="display: flex; gap: 20px; margin: 15px 0;">
            <div class="stat-card" style="flex: 1; background: #e7f4f9; padding: 20px; border-radius: 8px;">
                <h3 style="margin: 0 0 10px 0; color: #0073aa;">Currently Working</h3>
                <p style="font-size: 32px; font-weight: bold; margin: 0;" id="stat-active">
                    <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $clock_table WHERE status = 'in_progress'"); ?>
                </p>
            </div>
            <div class="stat-card" style="flex: 1; background: #dff0d8; padding: 20px; border-radius: 8px;">
                <h3 style="margin: 0 0 10px 0; color: #46b450;">Today's Completed</h3>
                <p style="font-size: 32px; font-weight: bold; margin: 0;" id="stat-completed">
                    <?php echo $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $clock_table WHERE status = 'completed' AND DATE(clock_in_time) = %s",
                        date('Y-m-d')
                    )); ?>
                </p>
            </div>
            <div class="stat-card" style="flex: 1; background: #f9f3e7; padding: 20px; border-radius: 8px;">
                <h3 style="margin: 0 0 10px 0; color: #f0b849;">Total Hours Today</h3>
                <p style="font-size: 32px; font-weight: bold; margin: 0;" id="stat-hours">
                    <?php 
                    $total_hours = $wpdb->get_var($wpdb->prepare(
                        "SELECT SUM(hours_worked) FROM $clock_table WHERE DATE(clock_in_time) = %s",
                        date('Y-m-d')
                    ));
                    echo number_format($total_hours ?: 0, 1);
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Manual clock entry
    $('#caregate-clock-manual-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            workerId: parseInt($('#worker_id').val()),
            facilityId: parseInt($('#facility_id').val()),
            clockInTime: $('#clock_in_time').val().replace('T', ' ') + ':00',
            clockOutTime: $('#clock_out_time').val().replace('T', ' ') + ':00',
            breakTime: parseFloat($('#break_time').val()) || 0,
            notes: $('#notes').val()
        };
        
        $.ajax({
            url: '<?php echo rest_url('caregate/v1/clock/manual'); ?>',
            method: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                alert('Manual clock entry created successfully!');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });
    
    // Refresh dashboard
    $('#refresh-dashboard').on('click', function() {
        location.reload();
    });
    
    // Filter records
    $('input[name="status-filter"]').on('change', function() {
        const status = $(this).val();
        
        if (status === 'all') {
            $('#clock-records-table tbody tr').show();
        } else {
            $('#clock-records-table tbody tr').hide();
            $('#clock-records-table tbody tr').each(function() {
                const rowStatus = $(this).find('td:eq(6)').text().toLowerCase();
                if ((status === 'in_progress' && rowStatus.includes('in progress')) ||
                    (status === 'completed' && rowStatus.includes('completed'))) {
                    $(this).show();
                }
            });
        }
    });
    
    // Auto-refresh every 30 seconds for live updates
    setInterval(function() {
        // Silently refresh stats without reloading
        $.get(window.location.href, function(data) {
            const $newPage = $(data);
            $('#stat-active').text($newPage.find('#stat-active').text());
            $('#stat-completed').text($newPage.find('#stat-completed').text());
            $('#stat-hours').text($newPage.find('#stat-hours').text());
        });
    }, 30000);
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

.dashboard-filters label {
    cursor: pointer;
}

.auto-clock-info code {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
}
</style>
