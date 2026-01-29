<?php
/**
 * The core plugin class.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * Initialize the plugin.
     */
    public function __construct() {
        $this->version = CAREGATE_VERSION;
        $this->plugin_name = 'caregate';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
        $this->define_cron_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-loader.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-recaptcha.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-twofa.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-api.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-auth.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-shifts.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-bookings.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-timesheets.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-billing.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-compliance.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-payroll.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-clock.php';
        require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-uk-invoice.php';
        require_once CAREGATE_PLUGIN_DIR . 'admin/class-caregate-admin.php';
        require_once CAREGATE_PLUGIN_DIR . 'public/class-caregate-public.php';

        $this->loader = new CareGate_Loader();
    }

    /**
     * Register all hooks related to the admin area.
     */
    private function define_admin_hooks() {
        $plugin_admin = new CareGate_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
    }

    /**
     * Register all hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        $plugin_public = new CareGate_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Register shortcodes
        $this->loader->add_shortcode('caregate_app', $plugin_public, 'render_app');
    }

    /**
     * Register all REST API endpoints.
     */
    private function define_api_hooks() {
        $plugin_api = new CareGate_API();

        $this->loader->add_action('rest_api_init', $plugin_api, 'register_routes');
    }

    /**
     * Register cron jobs for background processing.
     */
    private function define_cron_hooks() {
        // Add custom cron schedule for 5 minutes
        $this->loader->add_filter('cron_schedules', $this, 'add_cron_schedules');
        
        // Hook for processing auto clock-outs
        $this->loader->add_action('caregate_process_auto_clockouts', 'CareGate_Clock', 'process_auto_clockouts');
    }

    /**
     * Add custom cron schedules.
     */
    public function add_cron_schedules($schedules) {
        if (!isset($schedules['every_5_minutes'])) {
            $schedules['every_5_minutes'] = array(
                'interval' => 300, // 5 minutes in seconds
                'display' => __('Every 5 Minutes', 'caregate')
            );
        }
        return $schedules;
    }

    /**
     * Run the loader to execute all hooks.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * The reference to the loader class.
     */
    public function get_loader() {
        return $this->loader;
    }
}
