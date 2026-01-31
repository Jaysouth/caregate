<?php
/**
 * Settings page
 */

// Save settings
if (isset($_POST['caregate_save_settings']) && check_admin_referer('caregate_settings')) {
    update_option('caregate_platform_fee_percentage', floatval($_POST['platform_fee_percentage']));
    update_option('caregate_max_matching_distance', floatval($_POST['max_matching_distance']));
    update_option('caregate_urgent_24h_multiplier', floatval($_POST['urgent_24h_multiplier']));
    update_option('caregate_urgent_48h_multiplier', floatval($_POST['urgent_48h_multiplier']));
    update_option('caregate_skill_advanced_multiplier', floatval($_POST['skill_advanced_multiplier']));
    update_option('caregate_skill_expert_multiplier', floatval($_POST['skill_expert_multiplier']));
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Get current values
$platform_fee = get_option('caregate_platform_fee_percentage', 0.15);
$max_distance = get_option('caregate_max_matching_distance', 50);
$urgent_24h = get_option('caregate_urgent_24h_multiplier', 1.3);
$urgent_48h = get_option('caregate_urgent_48h_multiplier', 1.15);
$skill_advanced = get_option('caregate_skill_advanced_multiplier', 1.2);
$skill_expert = get_option('caregate_skill_expert_multiplier', 1.4);
?>

<div class="wrap">
    <h1>CareGate Settings</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('caregate_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="platform_fee_percentage">Platform Fee Percentage</label>
                </th>
                <td>
                    <input type="number" step="0.01" min="0" max="1" name="platform_fee_percentage" 
                           id="platform_fee_percentage" value="<?php echo esc_attr($platform_fee); ?>" />
                    <p class="description">Decimal value (e.g., 0.15 for 15%)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="max_matching_distance">Max Matching Distance (km)</label>
                </th>
                <td>
                    <input type="number" step="1" min="1" name="max_matching_distance" 
                           id="max_matching_distance" value="<?php echo esc_attr($max_distance); ?>" />
                    <p class="description">Maximum distance for shift matching</p>
                </td>
            </tr>
            
            <tr>
                <th colspan="2"><h2>Dynamic Pricing Multipliers</h2></th>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="urgent_24h_multiplier">Urgent (< 24 hours)</label>
                </th>
                <td>
                    <input type="number" step="0.01" min="1" name="urgent_24h_multiplier" 
                           id="urgent_24h_multiplier" value="<?php echo esc_attr($urgent_24h); ?>" />
                    <p class="description">e.g., 1.3 = 30% premium</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="urgent_48h_multiplier">Urgent (< 48 hours)</label>
                </th>
                <td>
                    <input type="number" step="0.01" min="1" name="urgent_48h_multiplier" 
                           id="urgent_48h_multiplier" value="<?php echo esc_attr($urgent_48h); ?>" />
                    <p class="description">e.g., 1.15 = 15% premium</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="skill_advanced_multiplier">Advanced Skill Level</label>
                </th>
                <td>
                    <input type="number" step="0.01" min="1" name="skill_advanced_multiplier" 
                           id="skill_advanced_multiplier" value="<?php echo esc_attr($skill_advanced); ?>" />
                    <p class="description">e.g., 1.2 = 20% premium</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="skill_expert_multiplier">Expert Skill Level</label>
                </th>
                <td>
                    <input type="number" step="0.01" min="1" name="skill_expert_multiplier" 
                           id="skill_expert_multiplier" value="<?php echo esc_attr($skill_expert); ?>" />
                    <p class="description">e.g., 1.4 = 40% premium</p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="caregate_save_settings" class="button button-primary" value="Save Settings" />
        </p>
    </form>
</div>
