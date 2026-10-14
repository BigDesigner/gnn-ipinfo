<?php
/**
 * Plugin Name:			GNN IPinfo
 * Description: 		A plugin that displays visitor IP information using the IPinfo.io API.
 * Version:				0.3.1
 * Requires at least:	6.4
 * Requires PHP: 		7.4
 * Author URI: 			https://github.com/BigDesigner
 * License: 			GPLv2 or later
 * License URI: 		https://www.gnu.org/licenses/gpl-2.0.html
 * Author: 				BigDesigner
 * Text Domain: 		gnn-ipinfo
 * Domain Path: 		/languages
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

/**
 * Add settings page to the admin menu.
 * Uses top-level menu position 79.104 adhering to the GNN Product Family standard.
 */
function gnn_ipinfo_settings_page() {
    add_menu_page(
        __('GNN IPinfo Settings', 'gnn-ipinfo'),
        __('GNN IPinfo', 'gnn-ipinfo'),
        'manage_options',
        'gnn-ipinfo',
        'gnn_ipinfo_render_settings_page',
        'dashicons-info',
        '79.104'
    );
}
add_action('admin_menu', 'gnn_ipinfo_settings_page');

/**
 * Render the settings page.
 */
function gnn_ipinfo_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
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
                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }
                    $plugin_data = get_plugin_data(__FILE__, false, false);
                    echo esc_html(!empty($plugin_data['Version']) ? $plugin_data['Version'] : '0.3.1'); 
                    ?>
                </span>
            </div>
            <div class="gnn-ipinfo-status-item">
                <span class="gnn-ipinfo-status-label"><?php esc_html_e('WordPress Version:', 'gnn-ipinfo'); ?></span>
                <span class="gnn-ipinfo-status-value"><?php echo esc_html(get_bloginfo('version')); ?></span>
            </div>
            <div class="gnn-ipinfo-status-item">
                <span class="gnn-ipinfo-status-label"><?php esc_html_e('PHP Version:', 'gnn-ipinfo'); ?></span>
                <span class="gnn-ipinfo-status-value"><?php echo esc_html(PHP_VERSION); ?></span>
            </div>
            <div class="gnn-ipinfo-status-item">
                <span class="gnn-ipinfo-status-label"><?php esc_html_e('API Provider:', 'gnn-ipinfo'); ?></span>
                <span class="gnn-ipinfo-status-value">ipinfo.io</span>
            </div>
            <div class="gnn-ipinfo-status-item">
                <span class="gnn-ipinfo-status-label"><?php esc_html_e('Cache Engine:', 'gnn-ipinfo'); ?></span>
                <span class="gnn-ipinfo-status-value"><?php echo esc_html(wp_using_ext_object_cache() ? 'External Object Cache' : 'Database Transients'); ?></span>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Register plugin settings and fields.
 */
function gnn_ipinfo_register_settings() {
    register_setting('gnn_ipinfo_options_group', 'gnn_ipinfo_token', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ));
    register_setting('gnn_ipinfo_options_group', 'gnn_ipinfo_debug_mode', array(
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 0,
    ));
    
    add_settings_section('gnn_ipinfo_main_section', __('API Settings', 'gnn-ipinfo'), null, 'gnn-ipinfo');
    
    add_settings_field('gnn_ipinfo_token_field', __('API Token', 'gnn-ipinfo'), 'gnn_ipinfo_token_field_callback', 'gnn-ipinfo', 'gnn_ipinfo_main_section');
    add_settings_field('gnn_ipinfo_debug_mode_field', __('Debug Mode', 'gnn-ipinfo'), 'gnn_ipinfo_debug_mode_field_callback', 'gnn-ipinfo', 'gnn_ipinfo_main_section');
}
add_action('admin_init', 'gnn_ipinfo_register_settings');

/**
 * Render API Token input field with password masking and show/hide toggle.
 */
function gnn_ipinfo_token_field_callback() {
    $token = get_option('gnn_ipinfo_token', '');
    echo "<div style='display:flex;align-items:center;gap:8px;'>";
    echo "<input type='password' id='gnn_ipinfo_token' name='gnn_ipinfo_token' value='" . esc_attr($token) . "' class='regular-text' autocomplete='off' />";
    echo "<button type='button' class='button button-secondary' onclick=\"const el=document.getElementById('gnn_ipinfo_token');el.type=el.type==='password'?'text':'password';\">" . esc_html__('Show/Hide', 'gnn-ipinfo') . "</button>";
    echo "</div>";
    echo "<p class='description'>" . sprintf(
        /* translators: %s: link to IPinfo dashboard */
        esc_html__('Enter your IPinfo API token. You can find or generate your token on the %s.', 'gnn-ipinfo'),
        '<a href="https://ipinfo.io/account/token" target="_blank" rel="noopener noreferrer">' . esc_html__('IPinfo dashboard', 'gnn-ipinfo') . '</a>'
    ) . "</p>";
}

/**
 * Render Debug Mode checkbox field.
 */
function gnn_ipinfo_debug_mode_field_callback() {
    $debug = get_option('gnn_ipinfo_debug_mode', 0);
    echo "<label>";
    echo "<input type='checkbox' name='gnn_ipinfo_debug_mode' value='1' " . checked(1, $debug, false) . " /> ";
    echo esc_html__('Display diagnostic technical details to administrators on the frontend.', 'gnn-ipinfo');
    echo "</label>";
    echo "<p class='description'>" . esc_html__('When enabled, logged-in administrators will see cache status (HIT/MISS), resolved client IP, and header source beneath the shortcode output.', 'gnn-ipinfo') . "</p>";
}

/**
 * Enqueue CSS for backend settings page only.
 * Avoids enqueuing styles on unrelated admin pages.
 *
 * @param string $hook Current admin page hook suffix.
 */
function gnn_ipinfo_enqueue_assets($hook) {
    if ('toplevel_page_gnn-ipinfo' !== $hook) {
        return;
    }
    
    if (!function_exists('get_plugin_data')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $plugin_data = get_plugin_data(__FILE__, false, false);
    $version     = !empty($plugin_data['Version']) ? $plugin_data['Version'] : '0.3.1';
    
    wp_enqueue_style('gnn-ipinfo-admin', plugins_url('style.css', __FILE__), array(), $version);
}
add_action('admin_enqueue_scripts', 'gnn_ipinfo_enqueue_assets');

/**
 * Resolves the client IP address reliably.
 * Supports Cloudflare proxy header and validates IP format.
 *
 * @return string Validated IP address or empty string.
 */
function gnn_ipinfo_get_client_ip() {
    $ip = '';

    // Check Cloudflare connecting IP
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $candidate = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    }

    // Fallback to REMOTE_ADDR
    if (empty($ip) && !empty($_SERVER['REMOTE_ADDR'])) {
        $candidate = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    }

    /**
     * Filter the detected client IP address.
     *
     * @param string $ip Validated IP address.
     */
    return apply_filters('gnn_ipinfo_client_ip', $ip);
}

/**
 * Render shortcode [gnn_ipinfo].
 * Outputs raw JSON in a theme-agnostic code block with caching and rate-limit protection.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function gnn_ipinfo_shortcode($atts) {
    $token = trim((string) get_option('gnn_ipinfo_token', ''));
    if (empty($token)) {
        return sprintf(
            '<div class="gnn-ipinfo-error">%s</div>',
            esc_html__('API Token not found. Please enter the API Token in the settings.', 'gnn-ipinfo')
        );
    }

    $ip = gnn_ipinfo_get_client_ip();
    if (empty($ip)) {
        return sprintf(
            '<div class="gnn-ipinfo-error">%s</div>',
            esc_html__('Visitor IP address could not be determined.', 'gnn-ipinfo')
        );
    }

    $cache_key     = 'gnn_ipinfo_' . md5($ip);
    $error_key     = 'gnn_ipinfo_err_' . md5($ip);
    $cache_status  = 'HIT';
    $data          = get_transient($cache_key);

    if (false === $data) {
        $cache_status = 'MISS';

        // Check recent failure backoff transient to prevent hammering the external API
        if (false !== get_transient($error_key)) {
            return sprintf(
                '<div class="gnn-ipinfo-error">%s</div>',
                esc_html__('IP information temporarily unavailable. Please try again in a few moments.', 'gnn-ipinfo')
            );
        }

        $url = sprintf('https://ipinfo.io/%s?token=%s', rawurlencode($ip), rawurlencode($token));
        $response = wp_remote_get($url, array(
            'timeout'    => 10,
            'user-agent' => 'GNN-IPinfo/0.3.1; ' . home_url(),
        ));

        if (is_wp_error($response)) {
            set_transient($error_key, 1, 180); // 3-minute backoff
            return sprintf(
                '<div class="gnn-ipinfo-error">%s</div>',
                esc_html__('API request failed. Please try again later.', 'gnn-ipinfo')
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if (200 !== $status_code) {
            set_transient($error_key, $status_code, 180); // 3-minute backoff
            return sprintf(
                '<div class="gnn-ipinfo-error">%s</div>',
                esc_html__('IP information could not be retrieved from provider.', 'gnn-ipinfo')
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !is_array($data) || !isset($data['ip'])) {
            set_transient($error_key, 1, 180);
            return sprintf(
                '<div class="gnn-ipinfo-error">%s</div>',
                esc_html__('Invalid response from IP information provider.', 'gnn-ipinfo')
            );
        }

        // Cache the data for 1 hour by default (filterable)
        $cache_ttl = (int) apply_filters('gnn_ipinfo_cache_duration', HOUR_IN_SECONDS);
        set_transient($cache_key, $data, max(60, $cache_ttl));
    }

    // Universal theme-agnostic code block
    $output  = '<div class="gnn-ipinfo-container" style="margin-bottom: 20px;">';
    $output .= '<pre class="gnn-ipinfo-raw" style="padding: 15px; border-radius: 5px; overflow-x: auto; font-family: monospace; font-size: 14px; border: 1px solid rgba(128, 128, 128, 0.2); background: rgba(128, 128, 128, 0.05); line-height: 1.5;">';
    $output .= '<code>' . esc_html(wp_json_encode($data, JSON_PRETTY_PRINT)) . '</code>';
    $output .= '</pre>';

    // Render debug diagnostics for administrators if debug mode is active
    if (get_option('gnn_ipinfo_debug_mode', 0) && current_user_can('manage_options')) {
        $ip_header_source = !empty($_SERVER['HTTP_CF_CONNECTING_IP']) ? 'HTTP_CF_CONNECTING_IP' : 'REMOTE_ADDR';
        $output .= '<div class="gnn-ipinfo-debug-box" style="margin-top: 10px; padding: 10px 14px; background: rgba(234, 179, 8, 0.1); border: 1px dashed rgba(234, 179, 8, 0.5); border-radius: 6px; font-family: monospace; font-size: 12px; color: #854d0e;">';
        $output .= '<strong>[GNN IPinfo Diagnostic — Admin Only]</strong><br>';
        $output .= 'Cache: ' . esc_html($cache_status) . ' | Source Header: ' . esc_html($ip_header_source) . ' | Resolved IP: ' . esc_html($ip);
        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('gnn_ipinfo', 'gnn_ipinfo_shortcode');

/**
 * Add plugin action links on the Plugins screen.
 *
 * @param array $links Current action links.
 * @return array Modified action links.
 */
function gnn_ipinfo_plugin_links($links) {
    $donate_link = '<a href="' . esc_url('https://buymeacoffee.com/bigdesigner') . '" target="_blank" rel="noopener noreferrer" style="font-weight:bold; color:#d63638;">' . esc_html__('Donate', 'gnn-ipinfo') . '</a>';
    
    // Fixed: top-level menu page uses admin.php?page=gnn-ipinfo
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=gnn-ipinfo')) . '">' . esc_html__('Settings', 'gnn-ipinfo') . '</a>';
    
    $update_url  = wp_nonce_url(admin_url('plugins.php?gnn_ipinfo_check_update=1'), 'gnn_ipinfo_manual_update');
    $update_link = '<a href="' . esc_url($update_url) . '">' . esc_html__('Check Updates', 'gnn-ipinfo') . '</a>';
    
    array_unshift($links, $donate_link, $settings_link, $update_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'gnn_ipinfo_plugin_links');

/**
 * Load plugin textdomain for internationalization.
 */
function gnn_ipinfo_load_textdomain() {
    load_plugin_textdomain('gnn-ipinfo', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'gnn_ipinfo_load_textdomain');