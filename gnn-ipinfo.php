<?php
/**
 * Plugin Name:			GNN IPinfo
 * Description: 		A plugin that displays visitor IP information using the IPinfo.io API.
 * Version:				0.1.0
 * Requires at least:	6.4
 * Requires PHP: 		7.4
 * Author URI: 			https://www.bigdesigner.com
 * License: 			GPLv2
 * License URI: 		https://www.gnu.org/licenses/gpl-2.0.html
 * Author: 				BigDesigner
 * Text Domain: 		gnn-ipinfo
 */

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
        <h1><?php _e('GNN IPinfo Settings', 'gnn-ipinfo'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('gnn_ipinfo_options_group');
            do_settings_sections('gnn-ipinfo');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function gnn_ipinfo_register_settings() {
    register_setting('gnn_ipinfo_options_group', 'gnn_ipinfo_token', 'sanitize_text_field');
    add_settings_section('gnn_ipinfo_main_section', __('API Settings', 'gnn-ipinfo'), null, 'gnn-ipinfo');
    add_settings_field('gnn_ipinfo_token_field', __('API Token', 'gnn-ipinfo'), 'gnn_ipinfo_token_field_callback', 'gnn-ipinfo', 'gnn_ipinfo_main_section');
}
add_action('admin_init', 'gnn_ipinfo_register_settings');

// Create API Token field
function gnn_ipinfo_token_field_callback() {
    $token = get_option('gnn_ipinfo_token');
    echo "<input type='text' name='gnn_ipinfo_token' value='" . esc_attr($token) . "' />";
}

// Enqueue CSS styles for both frontend and backend
function gnn_ipinfo_enqueue_styles() {
    wp_enqueue_style('gnn-ipinfo-style', plugins_url('style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'gnn_ipinfo_enqueue_styles');
add_action('admin_enqueue_scripts', 'gnn_ipinfo_enqueue_styles');


// Create shortcode
function gnn_ipinfo_shortcode($atts) {
    $token = get_option('gnn_ipinfo_token');
    if (!$token) {
        return __('API Token not found. Please enter the API Token in the settings.', 'gnn-ipinfo');
    }

    $ip = $_SERVER['REMOTE_ADDR'];
    $url = "https://ipinfo.io/$ip?token=$token";

    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return __('API request failed.', 'gnn-ipinfo');
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data['ip'])) {
        return __('IP information could not be retrieved.', 'gnn-ipinfo');
    }

    $output = '<div class="gnn-ipinfo-container">';
    $output .= '<div class="gnn-ipinfo-ip">' . esc_html($data['ip']) . '</div>';
    $output .= '<ul class="gnn-ipinfo-list">';
    $output .= '<li><strong>' . __('Country:', 'gnn-ipinfo') . '</strong> ' . esc_html($data['country']) . '</li>';
    $output .= '<li><strong>' . __('Region:', 'gnn-ipinfo') . '</strong> ' . esc_html($data['region']) . '</li>';
    $output .= '<li><strong>' . __('City:', 'gnn-ipinfo') . '</strong> ' . esc_html($data['city']) . '</li>';
    $output .= '<li><strong>' . __('Postal Code:', 'gnn-ipinfo') . '</strong> ' . esc_html($data['postal']) . '</li>';
    $output .= '<li><strong>' . __('Organization:', 'gnn-ipinfo') . '</strong> ' . esc_html($data['org']) . '</li>';
    $output .= '<li><strong>' . __('Hostname:', 'gnn-ipinfo') . '</strong> ' . esc_html($data['hostname']) . '</li>';
    $output .= '<li><strong>' . __('Time Zone:', 'gnn-ipinfo') . '</strong> ' . esc_html($data['timezone']) . '</li>';
    $output .= '<li><strong>' . __('Location:', 'gnn-ipinfo') . '</strong> ' . esc_html($data['loc']) . '</li>';
    $output .= '</ul>';
    $output .= '</div>';

    return $output;
}
add_shortcode('gnn_ipinfo', 'gnn_ipinfo_shortcode');

// Add settings and donation links to the plugin page
function gnn_ipinfo_plugin_links($links) {
    $settings_link = '<a href="options-general.php?page=gnn-ipinfo">' . __('Settings', 'gnn-ipinfo') . '</a>';
    $donate_link = '<a href="https://buymeacoffee.com/bigdesigner" target="_blank">' . __('Donate', 'gnn-ipinfo') . '<span class="gnn-ipinfo-donate-icon"></span></a>';
    array_unshift($links, $settings_link, $donate_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'gnn_ipinfo_plugin_links');

// Load text domain for translations
function gnn_ipinfo_load_textdomain() {
    load_plugin_textdomain('gnn-ipinfo', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    if (function_exists('get_available_languages')) {
        $languages = get_available_languages(dirname(plugin_basename(__FILE__)) . '/languages/');
        if (in_array('tr_TR.mo', $languages)) {
            error_log('tr_TR.mo dosyası yüklendi.');
        } else {
            error_log('tr_TR.mo dosyası yüklenemedi.');
        }
    }
}
add_action('plugins_loaded', 'gnn_ipinfo_load_textdomain');