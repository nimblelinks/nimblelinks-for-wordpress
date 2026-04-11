<?php

namespace NimbleLinks\Admin;

use NimbleLinks\Api\Client;
use NimbleLinks\Encryption;

class SettingsPage
{
    public static function register(): void
    {
        add_options_page(
            __('Nimble Links', 'nimble-links'),
            __('Nimble Links', 'nimble-links'),
            'manage_options',
            'nimble-links',
            [self::class, 'render']
        );
    }

    public static function registerSettings(): void
    {
        register_setting('nimble_links', 'nimble_links_auto_short_link', [
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);

        register_setting('nimble_links', 'nimble_links_auto_qr_code', [
            'type'              => 'boolean',
            'default'           => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);

        add_settings_section(
            'nimble_links_connection',
            __('Connection', 'nimble-links'),
            '__return_null',
            'nimble-links'
        );

        add_settings_section(
            'nimble_links_options',
            __('Options', 'nimble-links'),
            '__return_null',
            'nimble-links'
        );

        add_settings_field(
            'nimble_links_auto_short_link',
            __('Auto short link', 'nimble-links'),
            [self::class, 'renderCheckbox'],
            'nimble-links',
            'nimble_links_options',
            [
                'label_for' => 'nimble_links_auto_short_link',
                'label'     => __('Automatically create a short link for new posts', 'nimble-links'),
            ]
        );

        add_settings_field(
            'nimble_links_auto_qr_code',
            __('Auto QR code', 'nimble-links'),
            [self::class, 'renderCheckbox'],
            'nimble-links',
            'nimble_links_options',
            [
                'label_for' => 'nimble_links_auto_qr_code',
                'label'     => __('Automatically generate a QR code for new posts', 'nimble-links'),
            ]
        );
    }

    public static function renderCheckbox(array $args): void
    {
        $option = $args['label_for'];
        $value  = get_option($option, true);
        ?>
        <label>
            <input type="checkbox"
                   id="<?php echo esc_attr($option); ?>"
                   name="<?php echo esc_attr($option); ?>"
                   value="1"
                   <?php checked($value); ?>>
            <?php echo esc_html($args['label']); ?>
        </label>
        <?php
    }

    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $isConnected = self::isConnected();
        $teamName    = get_option('nimble_links_team_name', '');
        ?>
        <div class="wrap" id="nimble-links-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div id="nimble-links-connection" class="nimble-links-card">
                <?php if ($isConnected): ?>
                    <div id="nimble-links-connected">
                        <p>
                            <span class="nimble-links-status-dot"></span>
                            <?php
                            printf(
                                /* translators: %s: team name */
                                esc_html__('Connected to %s', 'nimble-links'),
                                '<strong>' . esc_html($teamName) . '</strong>'
                            );
                            ?>
                        </p>
                        <button type="button" class="button" id="nimble-links-disconnect">
                            <?php esc_html_e('Disconnect', 'nimble-links'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div id="nimble-links-disconnected">
                        <p>
                            <label for="nimble-links-token">
                                <?php esc_html_e('API Token', 'nimble-links'); ?>
                            </label>
                        </p>
                        <input type="password"
                               id="nimble-links-token"
                               class="regular-text"
                               placeholder="<?php esc_attr_e('Paste your API token', 'nimble-links'); ?>">
                        <button type="button" class="button button-primary" id="nimble-links-connect">
                            <?php esc_html_e('Connect', 'nimble-links'); ?>
                        </button>
                        <span id="nimble-links-spinner" class="spinner"></span>
                        <p id="nimble-links-error" class="nimble-links-error" style="display:none;"></p>
                        <p class="description">
                            <?php esc_html_e('Find your API token at nimblelinks.com → Team Settings → API Tokens', 'nimble-links'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($isConnected): ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('nimble_links');
                    do_settings_sections('nimble-links');
                    submit_button();
                    ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function enqueueAssets(string $hook): void
    {
        if ($hook !== 'settings_page_nimble-links') {
            return;
        }

        wp_enqueue_style(
            'nimble-links-admin',
            NIMBLE_LINKS_PLUGIN_URL . 'css/admin.css',
            [],
            NIMBLE_LINKS_VERSION
        );

        wp_enqueue_script(
            'nimble-links-admin',
            NIMBLE_LINKS_PLUGIN_URL . 'js/admin.js',
            ['jquery'],
            NIMBLE_LINKS_VERSION,
            true
        );

        wp_localize_script('nimble-links-admin', 'nimbleLinksAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('nimble_links_admin'),
        ]);
    }

    public static function enqueueSidebarAssets(): void
    {
        $asset_file = NIMBLE_LINKS_PLUGIN_DIR . 'build/index.asset.php';

        if (! file_exists($asset_file)) {
            return;
        }

        $asset = require $asset_file;

        wp_enqueue_script(
            'nimble-links-sidebar',
            NIMBLE_LINKS_PLUGIN_URL . 'build/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_localize_script('nimble-links-sidebar', 'nimbleLinks', [
            'restUrl'     => esc_url_raw(rest_url('nimble-links/v1')),
            'nonce'       => wp_create_nonce('wp_rest'),
            'isConnected' => self::isConnected(),
            'settingsUrl' => admin_url('options-general.php?page=nimble-links'),
        ]);
    }

    public static function ajaxValidateToken(): void
    {
        check_ajax_referer('nimble_links_admin', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'nimble-links')], 403);
        }

        $token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';

        if (empty($token)) {
            wp_send_json_error(['message' => __('Please enter an API token.', 'nimble-links')]);
        }

        $client = new Client($token);
        $result = $client->me();

        if (is_wp_error($result)) {
            $data   = $result->get_error_data();
            $status = $data['status'] ?? 0;

            if ($status === 401) {
                wp_send_json_error(['message' => __('Invalid token.', 'nimble-links')]);
            }

            wp_send_json_error(['message' => __('Could not connect to Nimble Links.', 'nimble-links')]);
        }

        $teamName = $result['team']['name'] ?? '';

        update_option('nimble_links_api_token', Encryption::encrypt($token));
        update_option('nimble_links_team_name', sanitize_text_field($teamName));

        wp_send_json_success(['team_name' => $teamName]);
    }

    public static function ajaxDisconnect(): void
    {
        check_ajax_referer('nimble_links_admin', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'nimble-links')], 403);
        }

        delete_option('nimble_links_api_token');
        delete_option('nimble_links_team_name');

        wp_send_json_success();
    }

    public static function isConnected(): bool
    {
        $encrypted = get_option('nimble_links_api_token', '');
        return ! empty($encrypted) && ! empty(Encryption::decrypt($encrypted));
    }

    public static function getToken(): string
    {
        $encrypted = get_option('nimble_links_api_token', '');
        return $encrypted ? Encryption::decrypt($encrypted) : '';
    }
}
