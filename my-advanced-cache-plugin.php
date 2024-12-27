<?php
/*
Plugin Name: My Advanced Cache Plugin
Description: Integrates Redis for object caching and static HTML caching with WP Rocket-like interface
Version: 1.3
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

define('MACP_PLUGIN_FILE', __FILE__);
define('MACP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Load Composer autoloader
if (file_exists(MACP_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once MACP_PLUGIN_DIR . 'vendor/autoload.php';
}

// Load core files
require_once MACP_PLUGIN_DIR . 'includes/class-macp-debug.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-filesystem.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-redis.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-minification.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-html-cache.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-js-optimizer.php';

require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-optimizer.php';
require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-config.php';
require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-extractor.php';

// Load admin files
require_once MACP_PLUGIN_DIR . 'includes/admin/class-macp-admin-assets.php';
require_once MACP_PLUGIN_DIR . 'includes/admin/class-macp-admin-settings.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-admin.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-debug-utility.php';

class MACP_Plugin {
    private static $instance = null;
    private $redis;
    private $html_cache;
    private $admin;
    private $js_optimizer;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function __construct() {
        // Private constructor to prevent direct creation
    }

    private function init() {
        register_activation_hook(MACP_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(MACP_PLUGIN_FILE, [$this, 'deactivate']);

        $this->redis = new MACP_Redis();
        $this->html_cache = new MACP_HTML_Cache();
        $this->js_optimizer = new MACP_JS_Optimizer();
        $this->admin = new MACP_Admin($this->redis);

        $this->init_hooks();
        
        MACP_Debug::log('Plugin initialized');
    }

    private function init_hooks() {
        // Initialize caching based on settings
        add_action('init', [$this, 'initialize_caching'], 0);
        
        // Handle cache clearing
        add_action('macp_settings_saved', [$this->html_cache, 'clear_cache']);
        add_action('macp_js_settings_updated', [$this->html_cache, 'clear_cache']);
        
        // Add hooks for automatic cache clearing
        add_action('save_post', [$this->html_cache, 'clear_cache']);
        add_action('comment_post', [$this->html_cache, 'clear_cache']);
        add_action('wp_trash_post', [$this->html_cache, 'clear_cache']);
        add_action('switch_theme', [$this->html_cache, 'clear_cache']);
        
        // Add hook for Redis cache priming
        if (get_option('macp_enable_redis', 1)) {
            add_action('init', [$this->redis, 'prime_cache']);
        }
    }

    public function initialize_caching() {
        if (get_option('macp_enable_html_cache', 1)) {
            add_action('template_redirect', [$this->html_cache, 'start_buffer'], -9999);
        }
    }

    public function activate() {
        MACP_Debug::log('Plugin activated');
        
        // Create cache directory
        $cache_dir = WP_CONTENT_DIR . '/cache/macp';
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        // Set default options
        add_option('macp_enable_html_cache', 1);
        add_option('macp_enable_gzip', 1);
        add_option('macp_enable_redis', 1);
        add_option('macp_minify_html', 0);
        add_option('macp_enable_js_defer', 0);
        add_option('macp_enable_js_delay', 0);
        add_option('macp_excluded_scripts', []);
        add_option('macp_deferred_scripts', ['jquery-core', 'jquery-migrate']);
    }

    public function deactivate() {
        if ($this->html_cache) {
            $this->html_cache->clear_cache();
        }
        MACP_Debug::log('Plugin deactivated');
    }
}

// Initialize the plugin
function MACP() {
    return MACP_Plugin::get_instance();
}

// Start the plugin
MACP();