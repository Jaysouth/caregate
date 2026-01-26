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
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, CAREGATE_PLUGIN_URL . 'public/css/caregate-public.css', array(), $this->version, 'all');
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
