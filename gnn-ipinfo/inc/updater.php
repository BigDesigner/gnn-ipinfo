<?php
/**
 * GNN GitHub Plugin Updater
 *
 * Checks the GitHub Releases API for new versions and integrates
 * with the WordPress plugin update system.
 *
 * @package GNN_IPinfo
 * @since   0.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GNN_IPinfo_Updater
 */
class GNN_IPinfo_Updater
{
    /**
     * GitHub repository owner/name.
     *
     * @var string
     */
    private $repo = 'BigDesigner/gnn-ipinfo';

    /**
     * Plugin slug (directory name/main file).
     *
     * @var string
     */
    private $plugin_slug = 'gnn-ipinfo/gnn-ipinfo.php';

    /**
     * Transient key for caching the GitHub API response.
     *
     * @var string
     */
    private $transient_key = 'gnn_ipinfo_github_update_check';

    /**
     * Cache duration in seconds (12 hours).
     *
     * @var int
     */
    private $cache_duration = 43200;

    /**
     * Initialize hooks.
     */
    public function __construct()
    {
        // Add filters for the update pipeline
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);

        // Admin hooks
        add_action('admin_init', array($this, 'handle_manual_check'));
        add_action('load-update-core.php', array($this, 'clear_cache'));
    }

    /**
     * Get the current plugin version reliably from the main plugin file header.
     *
     * @return string
     */
    private function get_local_version()
    {
        $main_file = dirname(__DIR__) . '/gnn-ipinfo.php';
        if (file_exists($main_file)) {
            if (!function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $plugin_data = get_plugin_data($main_file, false, false);
            if (!empty($plugin_data['Version'])) {
                return $plugin_data['Version'];
            }
        }
        return '0.3.1';
    }

    /**
     * Fetch the latest release data from GitHub API.
     *
     * Uses positive and negative caching to protect against rate limits.
     *
     * @return object|false
     */
    private function get_remote_release()
    {
        $cached = get_transient($this->transient_key);
        if (false !== $cached) {
            return (is_object($cached) && !empty($cached->version)) ? $cached : false;
        }

        $url = sprintf('https://api.github.com/repos/%s/releases/latest', $this->repo);
        $args = array(
            'headers' => array(
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            ),
            'timeout' => 10,
        );

        $response = wp_remote_get($url, $args);

        // On failure or non-200 code, cache negative result for 5 minutes
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            $negative_cache = (object) array('empty' => true, 'version' => '');
            set_transient($this->transient_key, $negative_cache, 300);
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response));

        if (empty($body) || !isset($body->tag_name)) {
            $negative_cache = (object) array('empty' => true, 'version' => '');
            set_transient($this->transient_key, $negative_cache, 300);
            return false;
        }

        $remote_version = ltrim((string) $body->tag_name, 'v');
        $download_url   = '';

        if (!empty($body->assets) && is_array($body->assets)) {
            foreach ($body->assets as $asset) {
                if (isset($asset->name, $asset->browser_download_url) && false !== strpos($asset->name, '.zip')) {
                    $download_url = $asset->browser_download_url;
                    break;
                }
            }
        }

        if (empty($download_url) && !empty($body->zipball_url)) {
            $download_url = $body->zipball_url;
        }

        $release_data = (object) array(
            'version'      => $remote_version,
            'download_url' => $download_url,
            'changelog'    => isset($body->body) ? (string) $body->body : '',
            'published_at' => isset($body->published_at) ? (string) $body->published_at : '',
            'html_url'     => isset($body->html_url) ? (string) $body->html_url : '',
        );

        set_transient($this->transient_key, $release_data, $this->cache_duration);

        return $release_data;
    }

    /**
     * Hook into the plugin update check transient.
     *
     * @param object|false $transient
     * @return object|false
     */
    public function check_for_update($transient)
    {
        if (empty($transient) || !is_object($transient) || empty($transient->checked)) {
            return $transient;
        }

        $release = $this->get_remote_release();
        if (false === $release) {
            return $transient;
        }

        $local_version = $this->get_local_version();

        if (!isset($transient->response) || !is_array($transient->response)) {
            $transient->response = array();
        }

        if (version_compare($release->version, $local_version, '>')) {
            $obj              = new stdClass();
            $obj->id          = 'gnn-ipinfo/gnn-ipinfo.php';
            $obj->slug        = 'gnn-ipinfo';
            $obj->plugin      = $this->plugin_slug;
            $obj->new_version = $release->version;
            $obj->url         = $release->html_url;
            $obj->package     = $release->download_url;

            $transient->response[$this->plugin_slug] = $obj;

            if (isset($transient->no_update[$this->plugin_slug])) {
                unset($transient->no_update[$this->plugin_slug]);
            }
        } else {
            if (!isset($transient->no_update) || !is_array($transient->no_update)) {
                $transient->no_update = array();
            }

            $item              = new stdClass();
            $item->id          = 'gnn-ipinfo/gnn-ipinfo.php';
            $item->slug        = 'gnn-ipinfo';
            $item->plugin      = $this->plugin_slug;
            $item->new_version = $local_version;
            $item->url         = $release->html_url;
            $item->package     = '';

            $transient->no_update[$this->plugin_slug] = $item;
        }

        return $transient;
    }

    /**
     * Provide plugin information for the update details modal.
     *
     * @param false|object|array $result
     * @param string             $action
     * @param object             $args
     * @return false|object|array
     */
    public function plugin_info($result, $action, $args)
    {
        if ('plugin_information' !== $action || !is_object($args) || !isset($args->slug) || 'gnn-ipinfo' !== $args->slug) {
            return $result;
        }

        $release = $this->get_remote_release();
        if (false === $release) {
            return $result;
        }

        return (object) array(
            'name'          => 'GNN IPinfo',
            'slug'          => 'gnn-ipinfo',
            'version'       => $release->version,
            'author'        => '<a href="https://github.com/BigDesigner" target="_blank" rel="noopener noreferrer">BigDesigner</a>',
            'homepage'      => 'https://github.com/BigDesigner/gnn-ipinfo',
            'download_link' => $release->download_url,
            'requires'      => '6.4',
            'tested'        => '6.8',
            'requires_php'  => '7.4',
            'last_updated'  => $release->published_at,
            'sections'      => array(
                'description' => esc_html__('A plugin that displays visitor IP information using the IPinfo.io API.', 'gnn-ipinfo'),
                'changelog'   => nl2br(esc_html($release->changelog)),
            ),
        );
    }

    /**
     * Fix directory renaming after update installation.
     *
     * @param bool|WP_Error $response
     * @param array         $hook_extra
     * @param array         $result
     * @return array|WP_Error
     */
    public function after_install($response, $hook_extra, $result)
    {
        if (is_wp_error($result)) {
            return $result;
        }

        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_slug) {
            return $result;
        }

        global $wp_filesystem;
        $plugin_dir = WP_PLUGIN_DIR . '/gnn-ipinfo';

        // Ensure the directory is correctly named
        if (isset($result['destination']) && $result['destination'] !== $plugin_dir && is_object($wp_filesystem)) {
            $wp_filesystem->move($result['destination'], $plugin_dir);
            $result['destination'] = $plugin_dir;
        }

        delete_transient($this->transient_key);
        return $result;
    }

    /**
     * Manual check handler triggered via admin link.
     */
    public function handle_manual_check()
    {
        if (isset($_GET['gnn_ipinfo_check_update']) && '1' === $_GET['gnn_ipinfo_check_update']) {
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'gnn_ipinfo_manual_update')) {
                wp_die(esc_html__('Security check failed.', 'gnn-ipinfo'), '', array('response' => 403));
            }

            if (!current_user_can('update_plugins')) {
                wp_die(esc_html__('Sorry, you are not allowed to update plugins on this site.', 'gnn-ipinfo'), '', array('response' => 403));
            }

            delete_transient($this->transient_key);
            delete_site_transient('update_plugins');

            // Redirect to the core updates page to show the result
            wp_safe_redirect(admin_url('update-core.php?force-check=1'));
            exit;
        }
    }

    /**
     * Clear cache on core updates page.
     */
    public function clear_cache()
    {
        delete_transient($this->transient_key);
    }
}

// Initialize
new GNN_IPinfo_Updater();
