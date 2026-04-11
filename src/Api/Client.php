<?php

namespace NimbleLinks\Api;

use WP_Error;

class Client
{
    private string $token;
    private string $baseUrl;

    public function __construct(string $token, string $baseUrl = 'https://nimblelinks.com/api/v1.0')
    {
        $this->token = $token;
        $this->baseUrl = apply_filters('nimble_links_api_base_url', $baseUrl);
    }

    /**
     * @return array|WP_Error
     */
    public function me()
    {
        return $this->get('/me');
    }

    /**
     * @return array|WP_Error
     */
    public function createLink(string $title, string $destination)
    {
        return $this->post('/links', [
            'type'        => 'short-link',
            'title'       => mb_substr($title, 0, 100),
            'destination' => $destination,
        ]);
    }

    /**
     * @return array|WP_Error
     */
    public function getQr(string $linkId)
    {
        return $this->get('/links/' . urlencode($linkId) . '/qr');
    }

    /**
     * @return array|WP_Error
     */
    private function get(string $endpoint)
    {
        $response = wp_remote_get($this->baseUrl . $endpoint, [
            'timeout' => 15,
            'headers' => $this->headers(),
        ]);

        return $this->parse($response);
    }

    /**
     * @return array|WP_Error
     */
    private function post(string $endpoint, array $body)
    {
        $response = wp_remote_post($this->baseUrl . $endpoint, [
            'timeout' => 15,
            'headers' => $this->headers(),
            'body'    => wp_json_encode($body),
        ]);

        return $this->parse($response);
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'NimbleLinksWP/' . NIMBLE_LINKS_VERSION,
        ];
    }

    /**
     * @return array|WP_Error
     */
    private function parse($response)
    {
        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 400) {
            $message = $body['message'] ?? 'API request failed';
            return new WP_Error('nimble_links_api_error', $message, [
                'status' => $code,
                'body'   => $body,
            ]);
        }

        return $body ?? [];
    }
}
