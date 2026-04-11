<?php

namespace NimbleLinks\Rest;

use NimbleLinks\Admin\SettingsPage;
use NimbleLinks\Api\Client;
use NimbleLinks\PostHandler;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class LinksController
{
    public static function register(): void
    {
        register_rest_route('nimble-links/v1', '/links', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'createLink'],
            'permission_callback' => function (WP_REST_Request $request) {
                $postId = (int) $request->get_param('post_id');
                return $postId > 0 && current_user_can('edit_post', $postId);
            },
            'args' => [
                'post_id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route('nimble-links/v1', '/links/(?P<post_id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'getLink'],
            'permission_callback' => function (WP_REST_Request $request) {
                $postId = (int) $request['post_id'];
                return $postId > 0 && current_user_can('edit_post', $postId);
            },
            'args' => [
                'post_id' => [
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function createLink(WP_REST_Request $request)
    {
        $postId = (int) $request->get_param('post_id');
        $post   = get_post($postId);

        if (! $post) {
            return new WP_Error('not_found', __('Post not found.', 'nimble-links'), ['status' => 404]);
        }

        $existing = get_post_meta($postId, '_nimble_links_url', true);
        if ($existing) {
            return self::buildLinkResponse($postId);
        }

        $token = SettingsPage::getToken();
        if (empty($token)) {
            return new WP_Error('not_configured', __('Nimble Links is not connected.', 'nimble-links'), ['status' => 400]);
        }

        $client = new Client($token);
        $result = $client->createLink(
            get_the_title($post),
            get_permalink($post)
        );

        if (is_wp_error($result)) {
            return $result;
        }

        $linkId  = $result['data']['id'] ?? '';
        $linkUrl = $result['data']['url'] ?? '';

        if (empty($linkId) || empty($linkUrl)) {
            return new WP_Error('invalid_response', __('Unexpected API response.', 'nimble-links'), ['status' => 502]);
        }

        update_post_meta($postId, '_nimble_links_id', sanitize_text_field($linkId));
        update_post_meta($postId, '_nimble_links_url', esc_url_raw($linkUrl));

        PostHandler::fetchAndStoreQr($client, $linkId, $postId);

        return self::buildLinkResponse($postId);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function getLink(WP_REST_Request $request)
    {
        $postId = (int) $request['post_id'];
        $url    = get_post_meta($postId, '_nimble_links_url', true);

        if (empty($url)) {
            return new WP_Error('not_found', __('No short link found.', 'nimble-links'), ['status' => 404]);
        }

        return self::buildLinkResponse($postId);
    }

    private static function buildLinkResponse(int $postId): WP_REST_Response
    {
        return new WP_REST_Response([
            'url'    => get_post_meta($postId, '_nimble_links_url', true),
            'qr_svg' => get_post_meta($postId, '_nimble_links_qr_svg', true),
            'qr_png' => get_post_meta($postId, '_nimble_links_qr_png', true),
        ], 200);
    }
}
