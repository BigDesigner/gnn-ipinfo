<?php
/**
 * Plugin Name:			GNN IPinfo
 * Description: 		A plugin that displays visitor IP information using the IPinfo.io API.
 * Version:				0.1.1
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
        <hr>
        <div class="gnn-ipinfo-status-card">
            <h2><?php _e('GNN System Info', 'gnn-ipinfo'); ?></h2>
            <div class="gnn-ipinfo-status-item">
                <span class="gnn-ipinfo-status-label"><?php _e('Plugin Version:', 'gnn-ipinfo'); ?></span>
                <span class="gnn-ipinfo-status-value">0.1.1</span>
            </div>
            <div class="gnn-ipinfo-status-item">
                <span class="gnn-ipinfo-status-label"><?php _e('API Provider:', 'gnn-ipinfo'); ?></span>
                <span class="gnn-ipinfo-status-value">ipinfo.io</span>
            </div>
            <p style="margin-top:15px; margin-bottom:0;">
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('options-general.php?page=gnn-ipinfo&gnn_ipinfo_check_update=1'), 'gnn_ipinfo_manual_update')); ?>" class="button button-secondary">
                    <?php _e('Check for Updates Now', 'gnn-ipinfo'); ?>
                </a>
            </p>
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
    echo "<span class='description'>" . __('Display raw API response to administrators on the frontend.', 'gnn-ipinfo') . "</span>";
}

// Enqueue CSS and JS for both frontend and backend
function gnn_ipinfo_enqueue_assets() {
    $version = '0.1.1'; // Match plugin version
    wp_enqueue_style('gnn-ipinfo-style', plugins_url('style.css', __FILE__), array(), $version);
    
    // Only enqueue JS on the frontend
    if (!is_admin()) {
        wp_enqueue_script('gnn-ipinfo-copy', plugins_url('assets/js/copy-ip.js', __FILE__), array(), $version, true);
    }
}
add_action('wp_enqueue_scripts', 'gnn_ipinfo_enqueue_assets');
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

    $ip = $_SERVER['REMOTE_ADDR'];
    
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

    $output = '<div class="gnn-ipinfo-container">';
    $output .= '<div class="gnn-ipinfo-ip-wrapper">';
    $output .= '<span class="gnn-ipinfo-ip-text">' . esc_html($data['ip']) . '</span>';
    $output .= '<button class="gnn-ipinfo-copy-btn" title="' . esc_attr__('Copy IP', 'gnn-ipinfo') . '" aria-label="' . esc_attr__('Copy IP address', 'gnn-ipinfo') . '">';
    $output .= '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
    $output .= '</button>';
    $output .= '</div>';
    $output .= '<ul class="gnn-ipinfo-list">';
    
    $fields = array(
        'country'  => __('Country:', 'gnn-ipinfo'),
        'region'   => __('Region:', 'gnn-ipinfo'),
        'city'     => __('City:', 'gnn-ipinfo'),
        'postal'   => __('Postal Code:', 'gnn-ipinfo'),
        'org'      => __('Organization:', 'gnn-ipinfo'),
        'hostname' => __('Hostname:', 'gnn-ipinfo'),
        'timezone' => __('Time Zone:', 'gnn-ipinfo'),
        'loc'      => __('Location:', 'gnn-ipinfo'),
    );

    foreach ($fields as $key => $label) {
        if (!empty($data[$key])) {
            $output .= '<li><strong>' . esc_html($label) . '</strong> ' . esc_html($data[$key]) . '</li>';
        }
    }

    $output .= '</ul>';

    // Debug Mode output
    if (get_option('gnn_ipinfo_debug_mode') && current_user_can('manage_options')) {
        $output .= '<div class="gnn-ipinfo-debug">';
        $output .= '<h4>' . esc_html__('Debug Info (Admin Only):', 'gnn-ipinfo') . '</h4>';
        $output .= '<pre>' . esc_html(wp_json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
        $output .= '</div>';
    }

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