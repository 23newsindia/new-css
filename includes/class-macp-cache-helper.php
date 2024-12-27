<?php
class MACP_Cache_Helper {
    public static function get_cache_key($url = null) {
        if ($url === null) {
            $url = MACP_URL_Helper::get_current_url();
        }
        return md5($url);
    }

    public static function get_cache_path($key, $is_gzip = false) {
        $cache_dir = WP_CONTENT_DIR . '/cache/macp/';
        return $cache_dir . $key . ($is_gzip ? '.gz' : '.html');
    }

    public static function is_cacheable_request() {
        // Don't cache POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return false;
        }

        // Don't cache query strings
        if (!empty($_GET)) {
            return false;
        }

        // Don't cache admin or login pages
        if (is_admin() || is_user_logged_in()) {
            return false;
        }

        return true;
    }
}