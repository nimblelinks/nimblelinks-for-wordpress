<?php

define('ABSPATH', '/tmp/wordpress/');
define('AUTH_KEY', 'test-auth-key-for-unit-tests-only');
define('NIMBLE_LINKS_VERSION', '1.0.0');
define('NIMBLE_LINKS_PLUGIN_DIR', dirname(__DIR__) . '/');
define('NIMBLE_LINKS_PLUGIN_URL', 'https://example.com/wp-content/plugins/nimble-links/');

require_once dirname(__DIR__) . '/vendor/autoload.php';

function is_wp_error($thing): bool
{
    return $thing instanceof WP_Error;
}
