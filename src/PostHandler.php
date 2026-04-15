<?php

namespace NimbleLinks;

use NimbleLinks\Api\Client;

class PostHandler
{
    public static function fetchAndStoreQr(Client $client, string $linkId, int $postId): bool
    {
        $qr = $client->getQr($linkId);

        if (is_wp_error($qr)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('Nimble Links: failed to fetch QR code for link ' . $linkId . ' — ' . $qr->get_error_message());
            }
            return false;
        }

        $svg = self::toInlineQrUrl($qr['svg'] ?? '');
        $png = self::toInlineQrUrl($qr['png'] ?? '');

        if ($svg) {
            update_post_meta($postId, '_nimble_links_qr_svg', esc_url_raw($svg));
        }
        if ($png) {
            update_post_meta($postId, '_nimble_links_qr_png', esc_url_raw($png));
        }

        return true;
    }

    private static function toInlineQrUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $url = preg_replace('#/qr/download(\?|$)#', '/qr$1', $url);

        return preg_replace('#\?format=svg$#', '', $url);
    }
}
