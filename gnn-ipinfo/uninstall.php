<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up options and transients stored in the database.
 *
 * @package GNN_IPinfo
 * @since   0.3.1
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete registered options
delete_option('gnn_ipinfo_token');
delete_option('gnn_ipinfo_debug_mode');

// Delete updater transient
delete_transient('gnn_ipinfo_github_update_check');

// Clean up all cached IP information and error backoff transients
global $wpdb;
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_gnn_ipinfo_') . '%',
        $wpdb->esc_like('_transient_timeout_gnn_ipinfo_') . '%'
    )
);
