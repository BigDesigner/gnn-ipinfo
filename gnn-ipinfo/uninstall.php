<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up options and transients stored in the database
 * for both single-site and multisite WordPress installations.
 *
 * @package GNN_IPinfo
 * @since   0.3.1
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up all plugin options and cached transients for the current site.
 */
function gnn_ipinfo_cleanup_site_data() {
    global $wpdb;

    delete_option('gnn_ipinfo_token');
    delete_option('gnn_ipinfo_debug_mode');
    delete_transient('gnn_ipinfo_github_update_check');

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like('_transient_gnn_ipinfo_') . '%',
            $wpdb->esc_like('_transient_timeout_gnn_ipinfo_') . '%'
        )
    );
}

if (is_multisite()) {
    delete_site_transient('gnn_ipinfo_github_update_check');

    $site_ids = get_sites(array(
        'fields' => 'ids',
        'number' => 0,
    ));

    foreach ($site_ids as $site_id) {
        switch_to_blog((int) $site_id);
        gnn_ipinfo_cleanup_site_data();
        restore_current_blog();
    }
} else {
    gnn_ipinfo_cleanup_site_data();
}
