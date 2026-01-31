<?php
/**
 * Plugin Name: CareGate - On-Demand Care Staffing
 * Plugin URI: https://github.com/Jaysouth/caregate
 * Description: Mobile-first on-demand staffing platform connecting pre-vetted carers and nurses with UK care facilities
 * Version: 1.0.0
 * Author: CareGate Team
 * Author URI: https://github.com/Jaysouth
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: caregate
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('CAREGATE_VERSION', '1.0.0');
define('CAREGATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAREGATE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_caregate() {
    require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-activator.php';
    CareGate_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_caregate() {
    require_once CAREGATE_PLUGIN_DIR . 'includes/class-caregate-deactivator.php';
    CareGate_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_caregate');
register_deactivation_hook(__FILE__, 'deactivate_caregate');

/**
 * The core plugin class.
 */
require CAREGATE_PLUGIN_DIR . 'includes/class-caregate.php';

/**
 * Begins execution of the plugin.
 */
function run_caregate() {
    $plugin = new CareGate();
    $plugin->run();
}

run_caregate();
