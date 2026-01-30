<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    CareGate
 * @subpackage CareGate/public
 */

class CareGate_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Hide admin bar for CareGate users
        add_action('init', array($this, 'hide_admin_bar_for_caregate_users'));
        
        // Prevent CareGate users from accessing wp-admin
        add_action('admin_init', array($this, 'prevent_admin_access_for_caregate_users'));
    }
    
    /**
     * Hide WordPress admin bar for CareGate users (workers, facilities, and frontend admins).
     */
    public function hide_admin_bar_for_caregate_users() {
        $user = wp_get_current_user();
        
        if ($user && (in_array('caregate_worker', (array) $user->roles) || 
                      in_array('caregate_facility', (array) $user->roles) ||
                      in_array('caregate_frontend_admin', (array) $user->roles))) {
            show_admin_bar(false);
            add_filter('show_admin_bar', '__return_false');
            
            // Add body class for styling
            add_filter('body_class', function($classes) {
                $classes[] = 'caregate-user';
                $classes[] = 'hide-admin-bar';
                return $classes;
            });
            
            // Remove admin bar completely from DOM
            remove_action('wp_head', '_admin_bar_bump_cb');
            wp_deregister_script('admin-bar');
            wp_deregister_style('admin-bar');
        }
    }
    
    /**
     * Prevent CareGate users from accessing wp-admin dashboard.
     * Redirect them to the frontend dashboard instead.
     */
    public function prevent_admin_access_for_caregate_users() {
        $user = wp_get_current_user();
        
        // Check if user is a CareGate worker, facility, or frontend admin
        if ($user && (in_array('caregate_worker', (array) $user->roles) || 
                      in_array('caregate_facility', (array) $user->roles) ||
                      in_array('caregate_frontend_admin', (array) $user->roles))) {
            // Don't block AJAX requests
            if (defined('DOING_AJAX') && DOING_AJAX) {
                return;
            }
            
            // Redirect to home page or CareGate dashboard
            wp_redirect(home_url());
            exit;
        }
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, CAREGATE_PLUGIN_URL . 'public/css/caregate-public.css', array(), $this->version, 'all');
        
        // Add inline CSS to hide admin bar completely
        $custom_css = "
            #wpadminbar { display: none !important; }
            html { margin-top: 0 !important; }
            body { margin-top: 0 !important; padding-top: 0 !important; }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, CAREGATE_PLUGIN_URL . 'public/js/caregate-public.js', array('jquery'), $this->version, false);
        
        // Pass API URL to JavaScript
        wp_localize_script($this->plugin_name, 'careGateAPI', array(
            'root' => esc_url_raw(rest_url('caregate/v1')),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }

    /**
     * Render the CareGate app via shortcode.
     */
    public function render_app($atts) {
        ob_start();
        include CAREGATE_PLUGIN_DIR . 'public/partials/caregate-public-display.php';
        return ob_get_clean();
    }
}
