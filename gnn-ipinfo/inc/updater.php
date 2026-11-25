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
     * Plugin directory slug.
     *
     * @var string
     */
    private $slug = 'gnn-ipinfo';

    /**
     * Plugin basename (directory name/main file).
     *
     * @var string
     */
    private $plugin_basename = 'gnn-ipinfo/gnn-ipinfo.php';

    /**
     * Transient key for caching the GitHub release response.
     *
     * @var string
     */
    private $transient_key = 'gnn_ipinfo_github_update_check';

    /**
     * Minimum supported WordPress version.
     *
     * @var string
     */
    private $requires_wp = '6.4';

    /**
     * Tested up to WordPress version.
     *
     * @var string
     */
    private $tested_wp = '7.1';

    /**
     * Minimum supported PHP version.
     *
     * @var string
     */
    private $requires_php = '7.4';

    /**
     * Whether the plugin was active prior to upgrade.
     *
     * @var bool
     */
    private $was_active = false;

    /**
     * Allowed GitHub URL hosts for release metadata and package downloads.
     *
     * @var array
     */
    private $allowed_hosts = array(
        'github.com',
        'api.github.com',
        'codeload.github.com',
        'objects.githubusercontent.com',
        'release-assets.githubusercontent.com',
    );

    /**
     * Initialize hooks and resolve plugin basename dynamically.
     */
    public function __construct()
    {
        $this->plugin_basename = defined('GNN_IPINFO_BASENAME')
            ? GNN_IPINFO_BASENAME
            : plugin_basename(dirname(__DIR__) . '/gnn-ipinfo.php');

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_filter('upgrader_pre_install', array($this, 'capture_active_state'), 10, 2);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);

        add_action('admin_init', array($this, 'handle_manual_check'));
        add_action('load-update-core.php', array($this, 'clear_cache'));
    }

    /**
     * Get the current plugin version directly from the on-disk plugin header.
     * Reading from disk avoids stale in-memory constants during the same-request upgrade cycle.
     *
     * @return string
     */
    private function get_local_version()
    {
        $main_file = WP_PLUGIN_DIR . '/' . $this->plugin_basename;
        if (file_exists($main_file)) {
            if (function_exists('clearstatcache')) {
                clearstatcache(true, $main_file);
            }
            $headers = get_file_data($main_file, array('Version' => 'Version'), 'plugin');
            if (!empty($headers['Version'])) {
                return trim((string) $headers['Version']);
            }
        }

        if (defined('GNN_IPINFO_VERSION')) {
            return (string) GNN_IPINFO_VERSION;
        }

        return '0.0.0';
    }

    /**
     * Validate and sanitize a GitHub release/package URL.
     * Enforces HTTPS scheme and an explicit GitHub host allowlist.
     *
     * @param string $url Raw URL from GitHub.
     * @return string Sanitized URL or empty string if invalid/untrusted.
     */
    private function sanitize_github_url($url)
    {
        if (empty($url) || !is_string($url)) {
            return '';
        }

        $sanitized = esc_url_raw($url, array('https'));
        if (empty($sanitized)) {
            return '';
        }

        $parsed = wp_parse_url($sanitized);
        if (empty($parsed['scheme']) || 'https' !== strtolower($parsed['scheme']) || empty($parsed['host'])) {
            return '';
        }

        $host = strtolower($parsed['host']);
        if (in_array($host, $this->allowed_hosts, true)) {
            return $sanitized;
        }

        if (substr($host, -strlen('.githubusercontent.com')) === '.githubusercontent.com') {
            return $sanitized;
        }

        return '';
    }

    /**
     * Store a short-lived negative cache sentinel to avoid hammering GitHub on failures.
     */
    private function cache_failure()
    {
        set_transient($this->transient_key, (object) array('failed' => true), 300);
    }

    /**
     * Build User-Agent header for GitHub requests.
     *
     * @return string
     */
    private function get_user_agent()
    {
        return 'WordPress/' . get_bloginfo('version') . '; ' . home_url();
    }

    /**
     * Fallback release lookup via GitHub web redirect and expanded_assets HTML.
     *
     * @return object|false
     */
    private function get_remote_release_fallback()
    {
        $latest_url = sprintf('https://github.com/%s/releases/latest', $this->repo);
        $head       = wp_remote_head($latest_url, array(
            'redirection' => 0,
            'timeout'     => 10,
            'headers'     => array(
                'User-Agent' => $this->get_user_agent(),
            ),
        ));

        if (is_wp_error($head)) {
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($head);
        if (!in_array($code, array(301, 302, 303, 307, 308), true)) {
            return false;
        }

        $location = wp_remote_retrieve_header($head, 'location');
        if (empty($location) || !is_string($location)) {
            return false;
        }

        if (!preg_match('#/releases/tag/([^/?\#]+)#', $location, $matches)) {
            return false;
        }

        $tag            = rawurldecode(trim($matches[1]));
        $remote_version = ltrim($tag, 'vV');

        if (!preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $remote_version)) {
            return false;
        }

        $download_url  = '';
        $expanded_url  = sprintf('https://github.com/%s/releases/expanded_assets/%s', $this->repo, rawurlencode($tag));
        $expanded_resp = wp_remote_get($expanded_url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => $this->get_user_agent(),
            ),
        ));

        if (!is_wp_error($expanded_resp) && 200 === (int) wp_remote_retrieve_response_code($expanded_resp)) {
            $html = wp_remote_retrieve_body($expanded_resp);
            if (is_string($html) && preg_match('/href=["\']([^"\']*\/releases\/download\/[^"\']+\.zip)["\']/i', $html, $asset_match)) {
                $candidate    = (0 === strpos($asset_match[1], '/')) ? 'https://github.com' . $asset_match[1] : $asset_match[1];
                $download_url = $this->sanitize_github_url($candidate);
            }
        }

        if (empty($download_url)) {
            $archive_url  = sprintf('https://github.com/%s/archive/refs/tags/%s.zip', $this->repo, rawurlencode($tag));
            $download_url = $this->sanitize_github_url($archive_url);
        }

        if (empty($download_url)) {
            return false;
        }

        $html_url = $this->sanitize_github_url(sprintf('https://github.com/%s/releases/tag/%s', $this->repo, rawurlencode($tag)));
        if (empty($html_url)) {
            $html_url = sprintf('https://github.com/%s', $this->repo);
        }

        return (object) array(
            'version'      => $remote_version,
            'download_url' => $download_url,
            'changelog'    => '',
            'published_at' => '',
            'html_url'     => $html_url,
        );
    }

    /**
     * Fetch the latest release data from GitHub API with web fallback.
     *
     * @return object|false
     */
    private function get_remote_release()
    {
        $cached = get_transient($this->transient_key);
        if (false !== $cached) {
            if ('error' === $cached || (is_object($cached) && !empty($cached->failed))) {
                return false;
            }
            if (is_object($cached) && !empty($cached->version) && !empty($cached->download_url)) {
                return $cached;
            }
            return false;
        }

        $url  = sprintf('https://api.github.com/repos/%s/releases/latest', $this->repo);
        $args = array(
            'headers' => array(
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => $this->get_user_agent(),
            ),
            'timeout' => 10,
        );

        $response     = wp_remote_get($url, $args);
        $release_data = false;

        if (!is_wp_error($response) && 200 === (int) wp_remote_retrieve_response_code($response)) {
            $body = json_decode(wp_remote_retrieve_body($response));

            if (JSON_ERROR_NONE === json_last_error() && is_object($body) && !empty($body->tag_name)) {
                $remote_version = ltrim(trim((string) $body->tag_name), 'vV');

                if (preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $remote_version)) {
                    $download_url = '';

                    if (!empty($body->assets) && is_array($body->assets)) {
                        foreach ($body->assets as $asset) {
                            if (
                                isset($asset->name, $asset->browser_download_url) &&
                                '.zip' === strtolower(substr((string) $asset->name, -4))
                            ) {
                                $download_url = $this->sanitize_github_url($asset->browser_download_url);
                                if (!empty($download_url)) {
                                    break;
                                }
                            }
                        }
                    }

                    if (empty($download_url) && !empty($body->zipball_url)) {
                        $download_url = $this->sanitize_github_url($body->zipball_url);
                    }

                    if (!empty($download_url)) {
                        $html_url = isset($body->html_url) ? $this->sanitize_github_url($body->html_url) : '';
                        if (empty($html_url)) {
                            $html_url = sprintf('https://github.com/%s', $this->repo);
                        }

                        $release_data = (object) array(
                            'version'      => $remote_version,
                            'download_url' => $download_url,
                            'changelog'    => isset($body->body) ? (string) $body->body : '',
                            'published_at' => isset($body->published_at) ? sanitize_text_field((string) $body->published_at) : '',
                            'html_url'     => $html_url,
                        );
                    }
                }
            }
        }

        if (false === $release_data) {
            $release_data = $this->get_remote_release_fallback();
        }

        if (false === $release_data) {
            $this->cache_failure();
            return false;
        }

        set_transient($this->transient_key, $release_data, 12 * HOUR_IN_SECONDS);

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
        if (!isset($transient->no_update) || !is_array($transient->no_update)) {
            $transient->no_update = array();
        }

        if (version_compare($release->version, $local_version, '>')) {
            $obj               = new stdClass();
            $obj->id           = $this->plugin_basename;
            $obj->slug         = $this->slug;
            $obj->plugin       = $this->plugin_basename;
            $obj->new_version  = $release->version;
            $obj->url          = $release->html_url;
            $obj->package      = $release->download_url;
            $obj->requires     = $this->requires_wp;
            $obj->tested       = $this->tested_wp;
            $obj->requires_php = $this->requires_php;

            $transient->response[$this->plugin_basename] = $obj;
            unset($transient->no_update[$this->plugin_basename]);
        } else {
            unset($transient->response[$this->plugin_basename]);

            $item              = new stdClass();
            $item->id          = $this->plugin_basename;
            $item->slug        = $this->slug;
            $item->plugin      = $this->plugin_basename;
            $item->new_version = $local_version;
            $item->url         = $release->html_url;
            $item->package     = '';

            $transient->no_update[$this->plugin_basename] = $item;
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
        if ('plugin_information' !== $action || !is_object($args) || !isset($args->slug) || $this->slug !== $args->slug) {
            return $result;
        }

        $release = $this->get_remote_release();
        if (false === $release) {
            return $result;
        }

        return (object) array(
            'name'          => 'GNN IPinfo',
            'slug'          => $this->slug,
            'version'       => $release->version,
            'author'        => '<a href="https://github.com/BigDesigner" target="_blank" rel="noopener noreferrer">BigDesigner</a>',
            'homepage'      => 'https://github.com/BigDesigner/gnn-ipinfo',
            'download_link' => $release->download_url,
            'requires'      => $this->requires_wp,
            'tested'        => $this->tested_wp,
            'requires_php'  => $this->requires_php,
            'last_updated'  => $release->published_at,
            'sections'      => array(
                'description' => esc_html__('A plugin that displays visitor IP information using the IPinfo.io API.', 'gnn-ipinfo'),
                'changelog'   => nl2br(esc_html($release->changelog)),
            ),
        );
    }

    /**
     * Capture whether the plugin was active before WordPress deactivates it for upgrade.
     *
     * @param bool|WP_Error $response
     * @param array         $hook_extra
     * @return bool|WP_Error
     */
    public function capture_active_state($response, $hook_extra)
    {
        if (is_wp_error($response)) {
            return $response;
        }

        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->plugin_basename) {
            if (!function_exists('is_plugin_active')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $this->was_active = is_plugin_active($this->plugin_basename);
        }

        return $response;
    }

    /**
     * Normalize plugin directory name after upgrade and restore active state.
     *
     * @param bool|WP_Error  $response
     * @param array          $hook_extra
     * @param array|WP_Error $result
     * @return array|WP_Error
     */
    public function after_install($response, $hook_extra, $result)
    {
        if (is_wp_error($response)) {
            return $response;
        }

        if (is_wp_error($result) || !is_array($result)) {
            return $result;
        }

        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_basename) {
            return $result;
        }

        global $wp_filesystem;
        if (!$wp_filesystem) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $plugin_dir  = wp_normalize_path(untrailingslashit(WP_PLUGIN_DIR . '/' . $this->slug));
        $destination = !empty($result['destination']) ? wp_normalize_path(untrailingslashit($result['destination'])) : '';

        if (is_object($wp_filesystem) && '' !== $destination && $destination !== $plugin_dir && $wp_filesystem->exists($destination)) {
            if ($wp_filesystem->move($destination, $plugin_dir, true)) {
                $result['destination']      = trailingslashit($plugin_dir);
                $result['destination_name'] = $this->slug;
            }
        }

        wp_clean_plugins_cache(true);

        if ($this->was_active) {
            if (!function_exists('activate_plugin')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            activate_plugin($this->plugin_basename);
            $this->was_active = false;
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
