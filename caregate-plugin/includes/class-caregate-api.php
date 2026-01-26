<?php
/**
 * The API-facing functionality of the plugin.
 *
 * Registers all REST API endpoints.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_API {

    /**
     * The namespace for the API.
     */
    private $namespace = 'caregate/v1';

    /**
     * Register all REST API routes.
     */
    public function register_routes() {
        // Authentication routes
        register_rest_route($this->namespace, '/auth/register', array(
            'methods' => 'POST',
            'callback' => array($this, 'register_user'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route($this->namespace, '/auth/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'login_user'),
            'permission_callback' => '__return_true'
        ));

        // User routes
        register_rest_route($this->namespace, '/users/me', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_current_user'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        register_rest_route($this->namespace, '/users/me', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_current_user'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        // Shift routes
        register_rest_route($this->namespace, '/shifts', array(
            'methods' => 'POST',
            'callback' => array('CareGate_Shifts', 'create_shift'),
            'permission_callback' => array($this, 'can_create_shift')
        ));

        register_rest_route($this->namespace, '/shifts', array(
            'methods' => 'GET',
            'callback' => array('CareGate_Shifts', 'get_shifts'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        register_rest_route($this->namespace, '/shifts/matches', array(
            'methods' => 'GET',
            'callback' => array('CareGate_Shifts', 'get_matched_shifts'),
            'permission_callback' => array($this, 'is_worker')
        ));

        register_rest_route($this->namespace, '/shifts/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array('CareGate_Shifts', 'get_shift'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        register_rest_route($this->namespace, '/shifts/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array('CareGate_Shifts', 'update_shift'),
            'permission_callback' => array($this, 'can_edit_shift')
        ));

        register_rest_route($this->namespace, '/shifts/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('CareGate_Shifts', 'delete_shift'),
            'permission_callback' => array($this, 'can_edit_shift')
        ));

        // Booking routes
        register_rest_route($this->namespace, '/bookings', array(
            'methods' => 'POST',
            'callback' => array('CareGate_Bookings', 'create_booking'),
            'permission_callback' => array($this, 'is_worker')
        ));

        register_rest_route($this->namespace, '/bookings', array(
            'methods' => 'GET',
            'callback' => array('CareGate_Bookings', 'get_bookings'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        register_rest_route($this->namespace, '/bookings/(?P<id>\d+)/confirm', array(
            'methods' => 'PUT',
            'callback' => array('CareGate_Bookings', 'confirm_booking'),
            'permission_callback' => array($this, 'is_facility')
        ));

        register_rest_route($this->namespace, '/bookings/(?P<id>\d+)/complete', array(
            'methods' => 'PUT',
            'callback' => array('CareGate_Bookings', 'complete_booking'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        register_rest_route($this->namespace, '/bookings/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array('CareGate_Bookings', 'cancel_booking'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        // Timesheet routes
        register_rest_route($this->namespace, '/timesheets', array(
            'methods' => 'POST',
            'callback' => array('CareGate_Timesheets', 'create_timesheet'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        register_rest_route($this->namespace, '/timesheets', array(
            'methods' => 'GET',
            'callback' => array('CareGate_Timesheets', 'get_timesheets'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        register_rest_route($this->namespace, '/timesheets/(?P<id>\d+)/approve', array(
            'methods' => 'PUT',
            'callback' => array('CareGate_Timesheets', 'approve_timesheet'),
            'permission_callback' => array($this, 'is_facility')
        ));

        // Billing routes
        register_rest_route($this->namespace, '/billing/generate', array(
            'methods' => 'POST',
            'callback' => array('CareGate_Billing', 'generate_invoice'),
            'permission_callback' => array($this, 'is_facility')
        ));

        register_rest_route($this->namespace, '/billing', array(
            'methods' => 'GET',
            'callback' => array('CareGate_Billing', 'get_invoices'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        register_rest_route($this->namespace, '/billing/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array('CareGate_Billing', 'get_invoice'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        register_rest_route($this->namespace, '/billing/summary/stats', array(
            'methods' => 'GET',
            'callback' => array('CareGate_Billing', 'get_billing_stats'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        // Compliance routes
        register_rest_route($this->namespace, '/compliance', array(
            'methods' => 'POST',
            'callback' => array('CareGate_Compliance', 'create_compliance_record'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        register_rest_route($this->namespace, '/compliance/worker/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array('CareGate_Compliance', 'get_worker_compliance'),
            'permission_callback' => array($this, 'is_authenticated')
        ));

        register_rest_route($this->namespace, '/compliance/required-documents', array(
            'methods' => 'GET',
            'callback' => array('CareGate_Compliance', 'get_required_documents'),
            'permission_callback' => array($this, 'is_authenticated')
        ));
    }

    /**
     * Register a new user.
     */
    public function register_user($request) {
        return CareGate_Auth::register($request);
    }

    /**
     * Login a user.
     */
    public function login_user($request) {
        return CareGate_Auth::login($request);
    }

    /**
     * Get current user profile.
     */
    public function get_current_user($request) {
        return CareGate_Auth::get_current_user($request);
    }

    /**
     * Update current user profile.
     */
    public function update_current_user($request) {
        return CareGate_Auth::update_current_user($request);
    }

    /**
     * Check if user is authenticated.
     */
    public function is_authenticated() {
        return is_user_logged_in();
    }

    /**
     * Check if user is a worker.
     */
    public function is_worker() {
        $user = wp_get_current_user();
        return in_array('caregate_worker', $user->roles);
    }

    /**
     * Check if user is a facility.
     */
    public function is_facility() {
        $user = wp_get_current_user();
        return in_array('caregate_facility', $user->roles);
    }

    /**
     * Check if user can create shifts.
     */
    public function can_create_shift() {
        return current_user_can('caregate_create_shifts');
    }

    /**
     * Check if user can edit a shift.
     */
    public function can_edit_shift($request) {
        if (!$this->is_facility()) {
            return false;
        }
        
        $shift_id = $request->get_param('id');
        $shift = CareGate_Shifts::get_shift_by_id($shift_id);
        
        if (!$shift) {
            return false;
        }
        
        return $shift->facility_id == get_current_user_id();
    }
}
