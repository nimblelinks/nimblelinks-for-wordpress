<?php

namespace NimbleLinks\Tests\Unit\Api;

use Brain\Monkey\Functions;
use NimbleLinks\Api\Client;
use NimbleLinks\Tests\Unit\TestCase;

class ClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Default: apply_filters returns the value (second arg)
        Functions\when('apply_filters')->alias(function () {
            $args = func_get_args();
            return $args[1] ?? null;
        });
    }

    public function test_me_calls_correct_endpoint_with_headers(): void
    {
        Functions\expect('wp_remote_get')
            ->once()
            ->with(
                'https://www.nimblelinks.com/api/v1.0/me',
                \Mockery::on(function ($args) {
                    return $args['headers']['Authorization'] === 'Bearer test-token'
                        && $args['headers']['Content-Type'] === 'application/json'
                        && $args['headers']['Accept'] === 'application/json'
                        && str_starts_with($args['headers']['User-Agent'], 'NimbleLinksWP/')
                        && $args['timeout'] === 15;
                })
            )
            ->andReturn(['response' => ['code' => 200], 'body' => '{"email":"a@b.com"}']);

        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"email":"a@b.com"}');

        $client = new Client('test-token');
        $result = $client->me();

        $this->assertIsArray($result);
        $this->assertEquals('a@b.com', $result['email']);
    }

    public function test_create_link_posts_with_correct_body(): void
    {
        Functions\expect('wp_json_encode')
            ->once()
            ->with(\Mockery::on(function ($body) {
                return $body['type'] === 'short-link'
                    && $body['title'] === 'My Post'
                    && $body['destination'] === 'https://example.com/my-post';
            }))
            ->andReturnUsing(function ($body) {
                return json_encode($body);
            });

        Functions\expect('wp_remote_post')
            ->once()
            ->with(
                'https://www.nimblelinks.com/api/v1.0/links',
                \Mockery::type('array')
            )
            ->andReturn(['response' => ['code' => 201]]);

        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(201);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"data":{"id":"abc","url":"https://nimblelinks.com/abc"}}');

        $client = new Client('test-token');
        $result = $client->createLink('My Post', 'https://example.com/my-post');

        $this->assertEquals('abc', $result['data']['id']);
    }

    public function test_create_link_truncates_title_to_100_chars(): void
    {
        $longTitle = str_repeat('a', 150);

        Functions\expect('wp_json_encode')
            ->once()
            ->with(\Mockery::on(function ($body) {
                return strlen($body['title']) === 100;
            }))
            ->andReturnUsing(function ($body) {
                return json_encode($body);
            });

        Functions\expect('wp_remote_post')->once()->andReturn([]);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(201);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"data":{"id":"x","url":"https://nimblelinks.com/x"}}');

        $client = new Client('test-token');
        $result = $client->createLink($longTitle, 'https://example.com');

        $this->assertIsArray($result);
    }

    public function test_get_qr_calls_correct_endpoint(): void
    {
        Functions\expect('wp_remote_get')
            ->once()
            ->with(
                'https://www.nimblelinks.com/api/v1.0/links/aBc123/qr',
                \Mockery::type('array')
            )
            ->andReturn([]);

        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"svg":"https://nimblelinks.com/qr/aBc123?format=svg","png":"https://nimblelinks.com/qr/aBc123?format=png"}');

        $client = new Client('test-token');
        $result = $client->getQr('aBc123');

        $this->assertArrayHasKey('svg', $result);
        $this->assertArrayHasKey('png', $result);
    }

    public function test_returns_wp_error_when_http_request_fails(): void
    {
        $error = new \WP_Error('http_error', 'Connection timed out');

        Functions\expect('wp_remote_get')->once()->andReturn($error);

        $client = new Client('test-token');
        $result = $client->me();

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('Connection timed out', $result->get_error_message());
    }

    public function test_returns_wp_error_for_4xx_response(): void
    {
        Functions\expect('wp_remote_get')->once()->andReturn([]);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(422);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"message":"The destination must be a valid URL."}');

        $client = new Client('test-token');
        $result = $client->me();

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('nimble_links_api_error', $result->get_error_code());
        $this->assertEquals('The destination must be a valid URL.', $result->get_error_message());
        $this->assertEquals(422, $result->get_error_data()['status']);
    }

    public function test_returns_wp_error_with_default_message_when_body_has_no_message(): void
    {
        Functions\expect('wp_remote_get')->once()->andReturn([]);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(500);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{}');

        $client = new Client('test-token');
        $result = $client->me();

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('API request failed', $result->get_error_message());
    }

    public function test_base_url_is_filterable(): void
    {
        // Override the default apply_filters to return custom URL for our filter
        Functions\when('apply_filters')->alias(function () {
            $args = func_get_args();
            if ($args[0] === 'nimble_links_api_base_url') {
                return 'https://custom.example.com/api';
            }
            return $args[1] ?? null;
        });

        Functions\expect('wp_remote_get')
            ->once()
            ->with('https://custom.example.com/api/me', \Mockery::type('array'))
            ->andReturn([]);

        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"email":"a@b.com"}');

        $client = new Client('test-token');
        $result = $client->me();

        $this->assertIsArray($result);
    }
}
