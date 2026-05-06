<?php
/**
 * Plugin Name:			GNN IPinfo
 * Description: 		A plugin that displays visitor IP information using the IPinfo.io API.
 * Version:				0.2.8
 * Requires at least:	6.4
 * Requires PHP: 		7.4
 * Author URI: 			https://github.com/BigDesigner
 * License: 			GPLv2 or later
 * License URI: 		https://www.gnu.org/licenses/gpl-2.0.html
 * Author: 				BigDesigner
 * Text Domain: 		gnn-ipinfo
 */
 
if (!defined('ABSPATH')) {
    exit;
}

// Include the GitHub updater
require_once plugin_dir_path(__FILE__) . 'inc/updater.php';

// Function to run when the plugin is activated
function gnn_ipinfo_activate() {
    // Actions to take upon plugin activation
}
register_activation_hook(__FILE__, 'gnn_ipinfo_activate');

// Function to run when the plugin is deactivated
function gnn_ipinfo_deactivate() {
    // Actions to take upon plugin deactivation
}
register_deactivation_hook(__FILE__, 'gnn_ipinfo_deactivate');

// Add settings page to the admin panel
function gnn_ipinfo_settings_page() {
    add_options_page(
        __('GNN IPinfo Settings', 'gnn-ipinfo'),
        __('GNN IPinfo', 'gnn-ipinfo'),
        'manage_options',
        'gnn-ipinfo',
        'gnn_ipinfo_render_settings_page'
    );
}
add_action('admin_menu', 'gnn_ipinfo_settings_page');

// Render the settings page
function gnn_ipinfo_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('GNN IPinfo Settings', 'gnn-ipinfo'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('gnn_ipinfo_options_group');
            do_settings_sections('gnn-ipinfo');
            submit_button();
            ?>
        </form>
        <hr>
        <div class="gnn-ipinfo-status-card">
            <h2><?php esc_html_e('GNN System Info', 'gnn-ipinfo'); ?></h2>
            <div class="gnn-ipinfo-status-item">
                <span class="gnn-ipinfo-status-label"><?php esc_html_e('Plugin Version:', 'gnn-ipinfo'); ?></span>
                <span class="gnn-ipinfo-status-value">
                    <?php 
                    if (!function_exists('get_plugin_data')) {
                        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                    }
                    $plugin_data = get_plugin_data(__FILE__);
                    echo esc_html($plugin_data['Version']); 
                    ?>
                </span>
            </div>
            <div class="gnn-ipinfo-status-item">
                <span class="gnn-ipinfo-status-label"><?php esc_html_e('API Provider:', 'gnn-ipinfo'); ?></span>
                <span class="gnn-ipinfo-status-value">ipinfo.io</span>
            </div>
        </div>
    </div>
    <?php
}

// Register settings
function gnn_ipinfo_register_settings() {
    register_setting('gnn_ipinfo_options_group', 'gnn_ipinfo_token', 'sanitize_text_field');
    register_setting('gnn_ipinfo_options_group', 'gnn_ipinfo_debug_mode', 'absint');
    
    add_settings_section('gnn_ipinfo_main_section', __('API Settings', 'gnn-ipinfo'), null, 'gnn-ipinfo');
    
    add_settings_field('gnn_ipinfo_token_field', __('API Token', 'gnn-ipinfo'), 'gnn_ipinfo_token_field_callback', 'gnn-ipinfo', 'gnn_ipinfo_main_section');
    add_settings_field('gnn_ipinfo_debug_mode_field', __('Debug Mode', 'gnn-ipinfo'), 'gnn_ipinfo_debug_mode_field_callback', 'gnn-ipinfo', 'gnn_ipinfo_main_section');
}
add_action('admin_init', 'gnn_ipinfo_register_settings');

// Create API Token field
function gnn_ipinfo_token_field_callback() {
    $token = get_option('gnn_ipinfo_token');
    echo "<input type='text' name='gnn_ipinfo_token' value='" . esc_attr($token) . "' class='regular-text' />";
}

// Create Debug Mode field
function gnn_ipinfo_debug_mode_field_callback() {
    $debug = get_option('gnn_ipinfo_debug_mode');
    echo "<input type='checkbox' name='gnn_ipinfo_debug_mode' value='1' " . checked(1, $debug, false) . " /> ";
    echo "<span class='description'>" . esc_html__('Display raw API response to administrators on the frontend.', 'gnn-ipinfo') . "</span>";
}

// Enqueue CSS for backend only
function gnn_ipinfo_enqueue_assets() {
    $version = '0.2.8'; // Bumped for pure raw output release
    
    if (is_admin()) {
        wp_enqueue_style('gnn-ipinfo-admin', plugins_url('style.css', __FILE__), array(), $version);
    }
}
add_action('admin_enqueue_scripts', 'gnn_ipinfo_enqueue_assets');


// Create shortcode
function gnn_ipinfo_shortcode($atts) {
    $token = get_option('gnn_ipinfo_token');
    if (!$token) {
        return sprintf(
            '<div class="gnn-ipinfo-error">%s</div>',
            esc_html__('API Token not found. Please enter the API Token in the settings.', 'gnn-ipinfo')
        );
    }

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    
    if (empty($ip)) {
        return '';
    }
    
    // Attempt to get data from cache (transient)
    $cache_key = 'gnn_ipinfo_cache_' . md5($ip);
    $data = get_transient($cache_key);

    if (false === $data) {
        $url = "https://ipinfo.io/$ip?token=$token";
        $response = wp_remote_get($url, array('timeout' => 10));

        if (is_wp_error($response)) {
            return sprintf(
                '<div class="gnn-ipinfo-error">%s</div>',
                esc_html__('API request failed. Please try again later.', 'gnn-ipinfo')
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !isset($data['ip'])) {
            return sprintf(
                '<div class="gnn-ipinfo-error">%s</div>',
                esc_html__('IP information could not be retrieved.', 'gnn-ipinfo')
            );
        }

        // Cache the data for 1 hour
        set_transient($cache_key, $data, HOUR_IN_SECONDS);
    }

    // Universal theme-agnostic "anam babam usulü" code block
    $output = '<div class="gnn-ipinfo-container" style="margin-bottom: 20px;">';
    $output .= '<pre class="gnn-ipinfo-raw" style="padding: 15px; border-radius: 5px; overflow-x: auto; font-family: monospace; font-size: 14px; border: 1px solid rgba(128, 128, 128, 0.2); background: rgba(128, 128, 128, 0.05); line-height: 1.5;">';
    $output .= '<code>' . esc_html(wp_json_encode($data, JSON_PRETTY_PRINT)) . '</code>';
    $output .= '</pre>';
    $output .= '</div>';

    return $output;
}
add_shortcode('gnn_ipinfo', 'gnn_ipinfo_shortcode');

// Add plugin action links
function gnn_ipinfo_plugin_links($links) {
    $donate_link = '<a href="' . esc_url('https://buymeacoffee.com/bigdesigner') . '" target="_blank" style="font-weight:bold; color:#d63638;">' . esc_html__('Donate', 'gnn-ipinfo') . '</a>';
    
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=gnn-ipinfo')) . '">' . esc_html__('Settings', 'gnn-ipinfo') . '</a>';
    
    $update_url = wp_nonce_url(admin_url('plugins.php?gnn_ipinfo_check_update=1'), 'gnn_ipinfo_manual_update');
    $update_link = '<a href="' . esc_url($update_url) . '">' . esc_html__('Check Updates', 'gnn-ipinfo') . '</a>';
    
    array_unshift($links, $donate_link, $settings_link, $update_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'gnn_ipinfo_plugin_links');

// Load text domain for translations
function gnn_ipinfo_load_textdomain() {
    // WordPress 4.6+ automatically loads translations if the plugin is on WordPress.org.
    // We keep this for local development or non-org distribution.
    load_plugin_textdomain('gnn-ipinfo', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'gnn_ipinfo_load_textdomain');