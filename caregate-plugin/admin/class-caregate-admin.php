<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    CareGate
 * @subpackage CareGate/admin
 */

class CareGate_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, CAREGATE_PLUGIN_URL . 'admin/css/caregate-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, CAREGATE_PLUGIN_URL . 'admin/js/caregate-admin.js', array('jquery'), $this->version, false);
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            'CareGate Settings',
            'CareGate',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'),
            'dashicons-groups',
            26
        );
        
        add_submenu_page(
            $this->plugin_name,
            'Settings',
            'Settings',
            'manage_options',
            $this->plugin_name . '-settings',
            array($this, 'display_plugin_settings_page')
        );
    }

    public function display_plugin_setup_page() {
        include_once CAREGATE_PLUGIN_DIR . 'admin/partials/caregate-admin-display.php';
    }

    public function display_plugin_settings_page() {
        include_once CAREGATE_PLUGIN_DIR . 'admin/partials/caregate-admin-settings.php';
    }
}
