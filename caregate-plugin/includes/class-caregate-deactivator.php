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
        // Clear scheduled cron jobs
        $timestamp = wp_next_scheduled('caregate_process_auto_clockouts');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'caregate_process_auto_clockouts');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
