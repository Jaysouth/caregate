<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    CareGate
 * @subpackage CareGate/includes
 */

class CareGate_Deactivator {

    /**
     * Deactivate the plugin.
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
