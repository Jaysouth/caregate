<?php
/**
 * Provide an admin area view for the plugin
 */
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="caregate-dashboard">
        <h2>Welcome to CareGate</h2>
        <p>Mobile-first on-demand staffing platform for connecting carers and nurses with care facilities.</p>
        
        <div class="caregate-stats">
            <?php
            global $wpdb;
            $shifts_table = $wpdb->prefix . 'caregate_shifts';
            $bookings_table = $wpdb->prefix . 'caregate_bookings';
            
            $total_shifts = $wpdb->get_var("SELECT COUNT(*) FROM $shifts_table");
            $total_bookings = $wpdb->prefix . 'caregate_bookings';
            $total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table");
            ?>
            
            <div class="caregate-stat-box">
                <h3><?php echo esc_html($total_shifts); ?></h3>
                <p>Total Shifts</p>
            </div>
            
            <div class="caregate-stat-box">
                <h3><?php echo esc_html($total_bookings); ?></h3>
                <p>Total Bookings</p>
            </div>
        </div>
        
        <h3>Quick Start</h3>
        <ol>
            <li>Use the shortcode <code>[caregate_app]</code> on any page to display the CareGate interface</li>
            <li>Configure settings in the Settings page</li>
            <li>Users can register as Workers or Facilities</li>
        </ol>
        
        <h3>Features</h3>
        <ul>
            <li>✓ Intelligent shift matching based on skills, location, and availability</li>
            <li>✓ Dynamic pricing with urgency and skill level premiums</li>
            <li>✓ UK healthcare compliance tracking</li>
            <li>✓ Automated timesheets and billing</li>
            <li>✓ Mobile-first responsive design</li>
        </ul>
    </div>
</div>

<style>
.caregate-dashboard {
    background: #fff;
    padding: 20px;
    margin-top: 20px;
}

.caregate-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.caregate-stat-box {
    background: #f0f0f1;
    padding: 20px;
    border-radius: 4px;
    text-align: center;
    flex: 1;
}

.caregate-stat-box h3 {
    font-size: 32px;
    margin: 0 0 10px 0;
    color: #667eea;
}

.caregate-stat-box p {
    margin: 0;
    color: #666;
}
</style>
