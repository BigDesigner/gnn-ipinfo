<?php
/**
 * Plugin Name:			GNN IPinfo
 * Description: 		A plugin that displays visitor IP information using the IPinfo.io API.
 * Version:				0.3.2
 * Requires at least:	6.4
 * Tested up to:		7.1
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

define('GNN_IPINFO_VERSION', '0.3.2');
define('GNN_IPINFO_FILE', __FILE__);
define('GNN_IPINFO_BASENAME', plugin_basename(__FILE__));

// Include the GitHub updater
require_once plugin_dir_path(__FILE__) . 'inc/updater.php';

/**
 * Function to run when the plugin is activated.
 */
function gnn_ipinfo_activate() {
    // Actions to take upon plugin activation
}
register_activation_hook(__FILE__, 'gnn_ipinfo_activate');

/**
 * Function to run when the plugin is deactivated.
 */
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

    add_settings_field(
        'gnn_ipinfo_token_field',
        __('API Token', 'gnn-ipinfo'),
        'gnn_ipinfo_token_field_callback',
        'gnn-ipinfo',
        'gnn_ipinfo_main_section',
        array('label_for' => 'gnn_ipinfo_token')
    );
    add_settings_field(
        'gnn_ipinfo_debug_mode_field',
        __('Debug Mode', 'gnn-ipinfo'),
        'gnn_ipinfo_debug_mode_field_callback',
        'gnn-ipinfo',
        'gnn_ipinfo_main_section',
        array('label_for' => 'gnn_ipinfo_debug_mode')
    );
}
add_action('admin_init', 'gnn_ipinfo_register_settings');

/**
 * Render API Token input field with password masking and accessible show/hide toggle.
 */
function gnn_ipinfo_token_field_callback() {
    $token      = get_option('gnn_ipinfo_token', '');
    $show_label = __('Show', 'gnn-ipinfo');
    $hide_label = __('Hide', 'gnn-ipinfo');
    echo "<div class='gnn-ipinfo-token-wrap'>";
    echo "<input type='password' id='gnn_ipinfo_token' name='gnn_ipinfo_token' value='" . esc_attr($token) . "' class='regular-text' autocomplete='off' aria-describedby='gnn_ipinfo_token_desc' />";
    echo "<button type='button' id='gnn_ipinfo_toggle_token' class='button button-secondary' aria-pressed='false' aria-controls='gnn_ipinfo_token' data-show-label='" . esc_attr($show_label) . "' data-hide-label='" . esc_attr($hide_label) . "'>" . esc_html($show_label) . "</button>";
    echo "</div>";
    echo "<p id='gnn_ipinfo_token_desc' class='description'>" . sprintf(
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
    echo "<label for='gnn_ipinfo_debug_mode'>";
    echo "<input type='checkbox' id='gnn_ipinfo_debug_mode' name='gnn_ipinfo_debug_mode' value='1' aria-describedby='gnn_ipinfo_debug_mode_desc' " . checked(1, $debug, false) . " /> ";
    echo esc_html__('Display diagnostic technical details to administrators on the frontend.', 'gnn-ipinfo');
    echo "</label>";
    echo "<p id='gnn_ipinfo_debug_mode_desc' class='description'>" . esc_html__('When enabled, logged-in administrators will see cache status (HIT/MISS), resolved client IP, and header source beneath the shortcode output.', 'gnn-ipinfo') . "</p>";
}

/**
 * Enqueue CSS and CSP-safe toggle script for backend settings page only.
 * Avoids enqueuing styles on unrelated admin pages.
 *
 * @param string $hook Current admin page hook suffix.
 */
function gnn_ipinfo_enqueue_assets($hook) {
    if ('toplevel_page_gnn-ipinfo' !== $hook) {
        return;
    }

    wp_enqueue_style('gnn-ipinfo-admin', plugins_url('style.css', __FILE__), array(), GNN_IPINFO_VERSION);

    wp_register_script('gnn-ipinfo-admin', false, array(), GNN_IPINFO_VERSION, true);
    wp_enqueue_script('gnn-ipinfo-admin');
    wp_add_inline_script(
        'gnn-ipinfo-admin',
        'document.addEventListener("DOMContentLoaded",function(){var b=document.getElementById("gnn_ipinfo_toggle_token"),i=document.getElementById("gnn_ipinfo_token");if(!b||!i){return;}b.addEventListener("click",function(){var p=i.type==="password";i.type=p?"text":"password";b.setAttribute("aria-pressed",p?"true":"false");b.textContent=p?(b.dataset.hideLabel||"Hide"):(b.dataset.showLabel||"Show");});});'
    );
}
add_action('admin_enqueue_scripts', 'gnn_ipinfo_enqueue_assets');

/**
 * Resolves the client IP address reliably and protects against header spoofing.
 * Only trusts CF-Connecting-IP when Cloudflare proxy header (CF-Ray) is present
 * or explicitly permitted via filter.
 *
 * @param string|null $source Optional reference receiving the resolution source.
 * @return string Validated IP address or empty string.
 */
function gnn_ipinfo_get_client_ip(&$source = null) {
    $ip     = '';
    $source = 'NONE';

    $trust_cloudflare = !empty($_SERVER['HTTP_CF_RAY']);
    $trust_cloudflare = (bool) apply_filters('gnn_ipinfo_trust_cloudflare_header', $trust_cloudflare);

    if ($trust_cloudflare && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $candidate = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip     = $candidate;
            $source = 'HTTP_CF_CONNECTING_IP';
        }
    }

    // Fallback to REMOTE_ADDR
    if (empty($ip) && !empty($_SERVER['REMOTE_ADDR'])) {
        $candidate = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip     = $candidate;
            $source = 'REMOTE_ADDR';
        }
    }

    /**
     * Filter the detected client IP address.
     *
     * @param string $ip     Validated IP address.
     * @param string $source Header/source used to resolve the IP.
     */
    $filtered_ip = (string) apply_filters('gnn_ipinfo_client_ip', $ip, $source);

    // Re-validate post-filter IP to prevent SSRF / URL path manipulation
    if (!empty($filtered_ip) && filter_var($filtered_ip, FILTER_VALIDATE_IP)) {
        if ($filtered_ip !== $ip) {
            $source = 'FILTER';
        }
        return $filtered_ip;
    }

    $source = 'NONE';
    return '';
}

/**
 * Render an accessible, theme-agnostic error notice for the frontend shortcode.
 *
 * @param string $message Plain-text error message to escape and display.
 * @return string HTML markup.
 */
function gnn_ipinfo_error_html($message) {
    return sprintf(
        '<div class="gnn-ipinfo-error" role="alert" style="padding:14px 18px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.35);border-left:4px solid #ef4444;border-radius:8px;color:inherit;font-size:14px;margin-bottom:15px;">%s</div>',
        esc_html($message)
    );
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
        return gnn_ipinfo_error_html(
            __('API Token not found. Please enter the API Token in the settings.', 'gnn-ipinfo')
        );
    }

    $ip_header_source = 'NONE';
    $ip               = gnn_ipinfo_get_client_ip($ip_header_source);
    if (empty($ip)) {
        return gnn_ipinfo_error_html(
            __('Visitor IP address could not be determined.', 'gnn-ipinfo')
        );
    }

    $cache_key    = 'gnn_ipinfo_' . md5($ip);
    $error_key    = 'gnn_ipinfo_err_' . md5($ip);
    $cache_status = 'HIT';
    $data         = get_transient($cache_key);

    if (false === $data) {
        $cache_status = 'MISS';

        // Check recent failure backoff transient to prevent hammering the external API
        if (false !== get_transient($error_key)) {
            return gnn_ipinfo_error_html(
                __('IP information temporarily unavailable. Please try again in a few moments.', 'gnn-ipinfo')
            );
        }

        $url      = sprintf('https://ipinfo.io/%s?token=%s', rawurlencode($ip), rawurlencode($token));
        $response = wp_remote_get($url, array(
            'timeout'    => 10,
            'user-agent' => 'GNN-IPinfo/' . GNN_IPINFO_VERSION . '; ' . home_url(),
        ));

        if (is_wp_error($response)) {
            set_transient($error_key, 1, 180); // 3-minute backoff
            return gnn_ipinfo_error_html(
                __('API request failed. Please try again later.', 'gnn-ipinfo')
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if (200 !== $status_code) {
            set_transient($error_key, $status_code, 180); // 3-minute backoff
            return gnn_ipinfo_error_html(
                __('IP information could not be retrieved from provider.', 'gnn-ipinfo')
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !is_array($data) || !isset($data['ip'])) {
            set_transient($error_key, 1, 180);
            return gnn_ipinfo_error_html(
                __('Invalid response from IP information provider.', 'gnn-ipinfo')
            );
        }

        // Cache the data for 1 hour by default (filterable)
        $cache_ttl = (int) apply_filters('gnn_ipinfo_cache_duration', HOUR_IN_SECONDS);
        set_transient($cache_key, $data, max(60, $cache_ttl));
    }

    // Universal theme-agnostic, keyboard-accessible code block
    $output  = '<div class="gnn-ipinfo-container" style="margin-bottom: 20px;">';
    $output .= '<pre class="gnn-ipinfo-raw" tabindex="0" role="region" aria-label="' . esc_attr__('Visitor IP Information', 'gnn-ipinfo') . '" style="padding: 15px; border-radius: 5px; overflow-x: auto; font-family: monospace; font-size: 14px; border: 1px solid rgba(128, 128, 128, 0.2); background: rgba(128, 128, 128, 0.05); line-height: 1.5; max-width: 100%; box-sizing: border-box;">';
    $output .= '<code>' . esc_html(wp_json_encode($data, JSON_PRETTY_PRINT)) . '</code>';
    $output .= '</pre>';

    // Render debug diagnostics for administrators if debug mode is active
    if (get_option('gnn_ipinfo_debug_mode', 0) && current_user_can('manage_options')) {
        $output .= '<div class="gnn-ipinfo-debug-box" style="margin-top: 10px; padding: 10px 14px; background: rgba(234, 179, 8, 0.12); border: 1px dashed rgba(234, 179, 8, 0.6); border-radius: 6px; font-family: monospace; font-size: 12px; color: inherit;">';
        $output .= '<strong>' . esc_html__('[GNN IPinfo Diagnostic — Admin Only]', 'gnn-ipinfo') . '</strong><br>';
        $output .= esc_html(
            sprintf(
                /* translators: 1: cache status (HIT/MISS), 2: resolved header source, 3: resolved IP address */
                __('Cache: %1$s | Source Header: %2$s | Resolved IP: %3$s', 'gnn-ipinfo'),
                $cache_status,
                $ip_header_source,
                $ip
            )
        );
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

    // Top-level menu page uses admin.php?page=gnn-ipinfo
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=gnn-ipinfo')) . '">' . esc_html__('Settings', 'gnn-ipinfo') . '</a>';

    $update_url  = wp_nonce_url(admin_url('plugins.php?gnn_ipinfo_check_update=1'), 'gnn_ipinfo_manual_update');
    $update_link = '<a href="' . esc_url($update_url) . '">' . esc_html__('Check Updates', 'gnn-ipinfo') . '</a>';

    array_unshift($links, $donate_link, $settings_link, $update_link);
    return $links;
}
add_filter('plugin_action_links_' . GNN_IPINFO_BASENAME, 'gnn_ipinfo_plugin_links');

/**
 * Load plugin textdomain on init (priority 1) to comply with WP 6.7+ JIT translation rules.
 */
function gnn_ipinfo_load_textdomain() {
    load_plugin_textdomain('gnn-ipinfo', false, dirname(GNN_IPINFO_BASENAME) . '/languages/');
}
add_action('init', 'gnn_ipinfo_load_textdomain', 1);
