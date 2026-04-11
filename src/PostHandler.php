<?php

namespace NimbleLinks;

use NimbleLinks\Admin\SettingsPage;
use NimbleLinks\Api\Client;

class PostHandler
{
    public static function onTransition(string $newStatus, string $oldStatus, \WP_Post $post): void
    {
        if ($newStatus !== 'publish') {
            return;
        }

        if ($post->post_type !== 'post') {
            return;
        }

        if (! get_option('nimble_links_auto_short_link', true)) {
            return;
        }

        $token = SettingsPage::getToken();

        if (empty($token)) {
            return;
        }

        if (get_post_meta($post->ID, '_nimble_links_id', true)) {
            return;
        }

        $client = new Client($token);
        $result = $client->createLink(
            get_the_title($post),
            get_permalink($post)
        );

        if (is_wp_error($result)) {
            $data = $result->get_error_data();

            if (isset($data['status']) && $data['status'] === 401) {
                set_transient('nimble_links_invalid_token', true, 60);
            }

            error_log('Nimble Links: failed to create short link for post ' . $post->ID . ' — ' . $result->get_error_message());
            return;
        }

        $linkId  = $result['data']['id'] ?? '';
        $linkUrl = $result['data']['url'] ?? '';

        if (empty($linkId) || empty($linkUrl)) {
            return;
        }

        update_post_meta($post->ID, '_nimble_links_id', sanitize_text_field($linkId));
        update_post_meta($post->ID, '_nimble_links_url', esc_url_raw($linkUrl));

        if (get_option('nimble_links_auto_qr_code', true)) {
            self::fetchAndStoreQr($client, $linkId, $post->ID);
        }
    }

    public static function fetchAndStoreQr(Client $client, string $linkId, int $postId): bool
    {
        $qr = $client->getQr($linkId);

        if (is_wp_error($qr)) {
            error_log('Nimble Links: failed to fetch QR code for link ' . $linkId . ' — ' . $qr->get_error_message());
            return false;
        }

        $svg = $qr['svg'] ?? '';
        $png = $qr['png'] ?? '';

        if ($svg) {
            update_post_meta($postId, '_nimble_links_qr_svg', esc_url_raw($svg));
        }
        if ($png) {
            update_post_meta($postId, '_nimble_links_qr_png', esc_url_raw($png));
        }

        return true;
    }
}
