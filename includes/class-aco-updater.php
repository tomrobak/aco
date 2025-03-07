<?php
/**
 * GitHub Updater Class
 *
 * Enables automatic updates from GitHub releases
 *
 * @package ACO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('ACO_Updater')) {

    class ACO_Updater {
        private $slug;
        private $plugin_data;
        private $username;
        private $repo;
        private $plugin_file;
        private $github_response;
        private $access_token;

        /**
         * Constructor
         *
         * @param string $plugin_file Path to the plugin file
         * @param string $github_username GitHub username
         * @param string $github_repo GitHub repo name
         */
        public function __construct($plugin_file, $github_username, $github_repo) {
            $this->plugin_file = $plugin_file;
            $this->username = $github_username;
            $this->repo = $github_repo;
            $this->slug = basename(dirname($plugin_file));

            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
            add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
            add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
            add_filter('upgrader_source_selection', array($this, 'fix_source_dir'), 10, 4);
        }

        /**
         * Get repo data from GitHub
         *
         * @return array|bool Repository info or false on failure
         */
        private function get_repository_info() {
            if (null !== $this->github_response) {
                return $this->github_response;
            }

            // Get plugin data
            if (!function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $this->plugin_data = get_plugin_data($this->plugin_file);

            // GitHub API endpoint for latest release
            $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";
            
            // Get the response
            $response = wp_remote_get($url);

            if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
                return false;
            }

            $response = json_decode(wp_remote_retrieve_body($response));
            if (empty($response)) {
                return false;
            }

            // Find the aco.zip asset
            $download_url = null;
            foreach ($response->assets as $asset) {
                if ('aco.zip' === $asset->name) {
                    $download_url = $asset->browser_download_url;
                    break;
                }
            }

            // If no specific aco.zip found, use the first zip file
            if (!$download_url && !empty($response->assets)) {
                foreach ($response->assets as $asset) {
                    if (strpos($asset->name, '.zip') !== false) {
                        $download_url = $asset->browser_download_url;
                        break;
                    }
                }
            }

            // If no assets found, use the source code download
            if (!$download_url && isset($response->zipball_url)) {
                $download_url = $response->zipball_url;
            }

            // Store the response
            $this->github_response = array(
                'version' => ltrim($response->tag_name, 'v'),
                'published_at' => $response->published_at,
                'download_url' => $download_url,
                'requires' => $this->plugin_data['RequiresWP'] ?: '6.7',
                'requires_php' => $this->plugin_data['RequiresPHP'] ?: '8.0',
                'body' => $response->body
            );

            return $this->github_response;
        }

        /**
         * Check for plugin updates
         *
         * @param object $transient Transient data for plugin updates
         * @return object Modified transient data
         */
        public function check_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }

            // Get the repo info
            $repo_info = $this->get_repository_info();

            // If we have no response, return the unchanged transient
            if (empty($repo_info)) {
                return $transient;
            }

            // Compare versions
            $current_version = $this->plugin_data['Version'];
            $new_version = $repo_info['version'];

            if (version_compare($new_version, $current_version, 'gt')) {
                $plugin_slug = plugin_basename($this->plugin_file);
                
                $update = array(
                    'slug' => $this->slug,
                    'plugin' => $plugin_slug,
                    'new_version' => $new_version,
                    'url' => "https://github.com/{$this->username}/{$this->repo}",
                    'package' => $repo_info['download_url'],
                    'requires' => $repo_info['requires'],
                    'requires_php' => $repo_info['requires_php']
                );

                $transient->response[$plugin_slug] = (object) $update;
            }

            return $transient;
        }

        /**
         * Populate the plugin update details popup
         *
         * @param bool|object $result The result object or false
         * @param string $action The type of information being requested
         * @param object $args Plugin arguments
         * @return object|bool Plugin information or false
         */
        public function plugin_popup($result, $action, $args) {
            if ('plugin_information' !== $action || $args->slug !== $this->slug) {
                return $result;
            }

            $repo_info = $this->get_repository_info();
            if (empty($repo_info)) {
                return $result;
            }

            $plugin_data = $this->plugin_data;

            $information = array(
                'name' => $plugin_data['Name'],
                'slug' => $this->slug,
                'version' => $repo_info['version'],
                'author' => $plugin_data['Author'],
                'author_profile' => $plugin_data['AuthorURI'],
                'homepage' => $plugin_data['PluginURI'],
                'requires' => $repo_info['requires'],
                'requires_php' => $repo_info['requires_php'],
                'downloaded' => 0,
                'last_updated' => $repo_info['published_at'],
                'sections' => array(
                    'description' => $plugin_data['Description'],
                    'changelog' => $this->format_github_markdown($repo_info['body']),
                ),
                'download_link' => $repo_info['download_url']
            );

            return (object) $information;
        }

        /**
         * Format GitHub markdown for WordPress
         *
         * @param string $markdown The markdown string to format
         * @return string Formatted HTML
         */
        private function format_github_markdown($markdown) {
            // Simple formatting for now
            $markdown = nl2br(esc_html($markdown));
            
            // Convert ## headers
            $markdown = preg_replace('/## (.*?)\n/i', '<h2>$1</h2>', $markdown);
            
            // Convert # headers
            $markdown = preg_replace('/# (.*?)\n/i', '<h1>$1</h1>', $markdown);
            
            // Convert **bold**
            $markdown = preg_replace('/\*\*(.*?)\*\*/i', '<strong>$1</strong>', $markdown);
            
            return $markdown;
        }
        
        /**
         * After installation, ensure the plugin directory name is correct
         *
         * @param bool $response Installation response
         * @param array $hook_extra Extra arguments passed to hooked filters
         * @param array $result Installation result data
         * @return array Modified installation result data
         */
        public function after_install($response, $hook_extra, $result) {
            global $wp_filesystem;

            // If this is not our plugin, exit early
            if (isset($hook_extra['plugin']) && $hook_extra['plugin'] !== plugin_basename($this->plugin_file)) {
                return $result;
            }

            // Ensure the proper destination directory
            $plugin_folder = WP_PLUGIN_DIR . '/' . $this->slug;
            $wp_filesystem->move($result['destination'], $plugin_folder);
            $result['destination'] = $plugin_folder;

            // Activate the plugin
            $activate = activate_plugin(plugin_basename($this->plugin_file));

            return $result;
        }

        /**
         * Fix the plugin source directory name
         *
         * @param string $source Source directory
         * @param string $remote_source Remote source directory
         * @param object $upgrader Upgrader object
         * @param array $hook_extra Extra parameters
         * @return string Modified source directory
         */
        public function fix_source_dir($source, $remote_source, $upgrader, $hook_extra = null) {
            global $wp_filesystem;
            
            // Only for this plugin
            if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === plugin_basename($this->plugin_file)) {
                // If we have a source directory that's not our plugin name
                $corrected_source = trailingslashit($remote_source) . $this->slug;
                
                // If we're using a zipball from GitHub
                if (strpos($source, 'tomrobak-aco') !== false) {
                    $upgrader->skin->feedback("Correcting the plugin directory name...");
                    
                    if ($wp_filesystem->is_dir($corrected_source)) {
                        $wp_filesystem->delete($corrected_source, true);
                    }
                    
                    $wp_filesystem->move($source, $corrected_source);
                    return $corrected_source;
                }
                
                // Check if there's a subdirectory called 'aco' inside the extracted directory
                $potential_source = trailingslashit($source) . 'aco';
                if ($wp_filesystem->is_dir($potential_source)) {
                    $upgrader->skin->feedback("Fixing the source directory structure...");
                    $wp_filesystem->move($potential_source, $corrected_source);
                    $wp_filesystem->delete($source, true); // Remove the original directory
                    return $corrected_source;
                }
            }
            
            return $source;
        }
    }
} 