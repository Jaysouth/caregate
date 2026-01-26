<?php
/**
 * Fired during plugin activation.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_Activator {

    /**
     * Activate the plugin.
     *
     * Creates database tables for the CareGate platform.
     */
    public static function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for shifts
        $table_shifts = $wpdb->prefix . 'caregate_shifts';
        $sql_shifts = "CREATE TABLE IF NOT EXISTS $table_shifts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            facility_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            required_skills text,
            location_lat decimal(10, 8),
            location_lng decimal(11, 8),
            base_rate decimal(10, 2) NOT NULL,
            dynamic_rate decimal(10, 2) NOT NULL,
            skill_level varchar(50) DEFAULT 'basic',
            status varchar(50) DEFAULT 'open',
            assigned_worker_id bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY facility_id (facility_id),
            KEY status (status)
        ) $charset_collate;";

        // Table for bookings
        $table_bookings = $wpdb->prefix . 'caregate_bookings';
        $sql_bookings = "CREATE TABLE IF NOT EXISTS $table_bookings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            shift_id bigint(20) NOT NULL,
            worker_id bigint(20) NOT NULL,
            facility_id bigint(20) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            applied_at datetime DEFAULT CURRENT_TIMESTAMP,
            confirmed_at datetime,
            completed_at datetime,
            cancelled_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY shift_id (shift_id),
            KEY worker_id (worker_id),
            KEY facility_id (facility_id),
            KEY status (status)
        ) $charset_collate;";

        // Table for timesheets
        $table_timesheets = $wpdb->prefix . 'caregate_timesheets';
        $sql_timesheets = "CREATE TABLE IF NOT EXISTS $table_timesheets (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            shift_id bigint(20) NOT NULL,
            worker_id bigint(20) NOT NULL,
            facility_id bigint(20) NOT NULL,
            hours_worked decimal(5, 2) NOT NULL,
            break_time decimal(5, 2) DEFAULT 0,
            hourly_rate decimal(10, 2) NOT NULL,
            total_pay decimal(10, 2) NOT NULL,
            notes text,
            status varchar(50) DEFAULT 'pending',
            submitted_by bigint(20) NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            approved_at datetime,
            approved_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY booking_id (booking_id),
            KEY worker_id (worker_id),
            KEY facility_id (facility_id),
            KEY status (status)
        ) $charset_collate;";

        // Table for invoices
        $table_invoices = $wpdb->prefix . 'caregate_invoices';
        $sql_invoices = "CREATE TABLE IF NOT EXISTS $table_invoices (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            facility_id bigint(20) NOT NULL,
            billing_period varchar(50),
            subtotal decimal(10, 2) NOT NULL,
            platform_fee decimal(10, 2) NOT NULL,
            total decimal(10, 2) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            due_date datetime,
            paid_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY facility_id (facility_id),
            KEY status (status)
        ) $charset_collate;";

        // Table for invoice timesheets (junction table)
        $table_invoice_timesheets = $wpdb->prefix . 'caregate_invoice_timesheets';
        $sql_invoice_timesheets = "CREATE TABLE IF NOT EXISTS $table_invoice_timesheets (
            invoice_id bigint(20) NOT NULL,
            timesheet_id bigint(20) NOT NULL,
            PRIMARY KEY  (invoice_id, timesheet_id),
            KEY invoice_id (invoice_id),
            KEY timesheet_id (timesheet_id)
        ) $charset_collate;";

        // Table for compliance records
        $table_compliance = $wpdb->prefix . 'caregate_compliance';
        $sql_compliance = "CREATE TABLE IF NOT EXISTS $table_compliance (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            worker_id bigint(20) NOT NULL,
            document_type varchar(100) NOT NULL,
            document_number varchar(255),
            issue_date date,
            expiry_date date,
            status varchar(50) DEFAULT 'pending',
            verified_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY worker_id (worker_id),
            KEY document_type (document_type),
            KEY status (status)
        ) $charset_collate;";

        // Table for user meta (skills, location, etc.)
        $table_user_meta = $wpdb->prefix . 'caregate_user_meta';
        $sql_user_meta = "CREATE TABLE IF NOT EXISTS $table_user_meta (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY meta_key (meta_key)
        ) $charset_collate;";

        // Table for OTP/2FA
        $table_otp = $wpdb->prefix . 'caregate_otp';
        $sql_otp = "CREATE TABLE IF NOT EXISTS $table_otp (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            otp_code varchar(10) NOT NULL,
            method varchar(20) DEFAULT 'email',
            expiry datetime NOT NULL,
            verified tinyint(1) DEFAULT 0,
            notification_count int DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY otp_code (otp_code),
            KEY expiry (expiry)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_shifts);
        dbDelta($sql_bookings);
        dbDelta($sql_timesheets);
        dbDelta($sql_invoices);
        dbDelta($sql_invoice_timesheets);
        dbDelta($sql_compliance);
        dbDelta($sql_user_meta);
        dbDelta($sql_otp);

        // Set default options
        add_option('caregate_platform_fee_percentage', 0.15);
        add_option('caregate_max_matching_distance', 50);
        add_option('caregate_urgent_24h_multiplier', 1.3);
        add_option('caregate_urgent_48h_multiplier', 1.15);
        add_option('caregate_skill_advanced_multiplier', 1.2);
        add_option('caregate_skill_expert_multiplier', 1.4);
        add_option('caregate_2fa_enabled', true);
        add_option('caregate_recaptcha_site_key', '');
        add_option('caregate_recaptcha_secret_key', '');
        add_option('caregate_sms_api_key', '');
        add_option('caregate_sms_api_url', '');
        
        // Create custom roles
        self::create_roles();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create custom user roles.
     */
    private static function create_roles() {
        // Worker role
        add_role(
            'caregate_worker',
            __('Care Worker', 'caregate'),
            array(
                'read' => true,
                'caregate_view_shifts' => true,
                'caregate_apply_shifts' => true,
                'caregate_manage_bookings' => true,
                'caregate_submit_timesheets' => true,
            )
        );

        // Facility role
        add_role(
            'caregate_facility',
            __('Care Facility', 'caregate'),
            array(
                'read' => true,
                'caregate_create_shifts' => true,
                'caregate_manage_shifts' => true,
                'caregate_approve_bookings' => true,
                'caregate_approve_timesheets' => true,
                'caregate_generate_invoices' => true,
            )
        );
    }
}
