<?php
/**
 * Plugin Name: Nimble Links
 * Plugin URI:  https://github.com/nimblelinks/nimblelinks-for-wordpress
 * Description: Generate short links and QR codes for your posts and pages via Nimble Links.
 * Version:     1.0.0
 * Author:      Nimble Links
 * Author URI:  https://nimblelinks.com
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nimble-links
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Tested up to: 6.9
 */

if (! defined('ABSPATH')) {
    exit;
}

define('NIMBLE_LINKS_VERSION', '1.0.0');
define('NIMBLE_LINKS_PLUGIN_FILE', __FILE__);
define('NIMBLE_LINKS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NIMBLE_LINKS_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once NIMBLE_LINKS_PLUGIN_DIR . 'vendor/autoload.php';

add_action('admin_menu', [NimbleLinks\Admin\SettingsPage::class, 'register']);
add_action('wp_ajax_nimble_links_validate_token', [NimbleLinks\Admin\SettingsPage::class, 'ajaxValidateToken']);
add_action('wp_ajax_nimble_links_disconnect', [NimbleLinks\Admin\SettingsPage::class, 'ajaxDisconnect']);
add_action('admin_enqueue_scripts', [NimbleLinks\Admin\SettingsPage::class, 'enqueueAssets']);
add_action('rest_api_init', [NimbleLinks\Rest\LinksController::class, 'register']);
add_action('enqueue_block_editor_assets', [NimbleLinks\Admin\SettingsPage::class, 'enqueueSidebarAssets']);
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function (array $links): array {
    $url = admin_url('options-general.php?page=nimble-links');
    array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('Settings', 'nimble-links') . '</a>');
    return $links;
});
