<?php

namespace NimbleLinks\Tests\Unit\Rest;

use Brain\Monkey\Functions;
use Mockery;
use NimbleLinks\Rest\LinksController;
use NimbleLinks\Tests\Unit\TestCase;

class LinksControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('apply_filters')->alias(function () {
            $args = func_get_args();
            return $args[1] ?? null;
        });
    }

    public function test_create_link_returns_error_when_post_not_found(): void
    {
        $request = new \WP_REST_Request(['post_id' => 999]);

        Functions\expect('get_post')->with(999)->andReturn(null);

        $result = LinksController::createLink($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('not_found', $result->get_error_code());
    }

    public function test_create_link_returns_error_when_not_connected(): void
    {
        $request = new \WP_REST_Request(['post_id' => 42]);
        $post = \WP_Post::create(['ID' => 42]);

        Functions\expect('get_post')->with(42)->andReturn($post);
        Functions\expect('get_option')->with('nimble_links_api_token', '')->andReturn('');

        $result = LinksController::createLink($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('not_configured', $result->get_error_code());
    }

    public function test_create_link_returns_api_error_on_failure(): void
    {
        $request = new \WP_REST_Request(['post_id' => 42]);
        $post = \WP_Post::create(['ID' => 42]);

        Functions\expect('get_post')->with(42)->andReturn($post);

        Functions\expect('get_option')->with('nimble_links_api_token', '')->andReturn(
            \NimbleLinks\Encryption::encrypt('test-token')
        );

        Functions\expect('get_the_title')->andReturn('Test');
        Functions\expect('get_permalink')->andReturn('https://example.com/test');
        Functions\expect('wp_json_encode')->andReturnUsing('json_encode');
        Functions\expect('wp_remote_post')->once()->andReturn(
            new \WP_Error('api_error', 'Server error')
        );

        $result = LinksController::createLink($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    public function test_create_link_returns_error_on_malformed_response(): void
    {
        $request = new \WP_REST_Request(['post_id' => 42]);
        $post = \WP_Post::create(['ID' => 42]);

        Functions\expect('get_post')->with(42)->andReturn($post);

        Functions\expect('get_option')->with('nimble_links_api_token', '')->andReturn(
            \NimbleLinks\Encryption::encrypt('test-token')
        );

        Functions\expect('get_the_title')->andReturn('Test');
        Functions\expect('get_permalink')->andReturn('https://example.com/test');
        Functions\expect('wp_json_encode')->andReturnUsing('json_encode');
        Functions\expect('wp_remote_post')->once()->andReturn([]);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(201);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"data":{}}');

        $result = LinksController::createLink($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_response', $result->get_error_code());
    }

    public function test_create_link_stores_meta_and_returns_response(): void
    {
        $request = new \WP_REST_Request(['post_id' => 42]);
        $post = \WP_Post::create(['ID' => 42]);

        Functions\expect('get_post')->with(42)->andReturn($post);

        Functions\when('get_post_meta')->alias(function ($postId, $key, $single) {
            return match ($key) {
                '_nimble_links_url' => 'https://www.nimblelinks.com/xyz',
                '_nimble_links_id' => 'xyz',
                '_nimble_links_qr_svg' => 'svg-url',
                '_nimble_links_qr_png' => 'png-url',
                default => '',
            };
        });

        Functions\expect('get_option')->with('nimble_links_api_token', '')->andReturn(
            \NimbleLinks\Encryption::encrypt('test-token')
        );

        Functions\expect('get_the_title')->andReturn('Test');
        Functions\expect('get_permalink')->andReturn('https://example.com/test');
        Functions\expect('wp_json_encode')->andReturnUsing('json_encode');
        Functions\expect('wp_remote_post')->once()->andReturn([]);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(201);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn(
            '{"data":{"id":"xyz","url":"https://www.nimblelinks.com/xyz"}}'
        );

        Functions\when('sanitize_text_field')->alias(function ($v) { return $v; });
        Functions\when('esc_url_raw')->alias(function ($v) { return $v; });
        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('delete_post_meta')->justReturn(true);

        Functions\expect('wp_remote_get')->once()->andReturn([]);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"svg":"svg-url","png":"png-url"}');

        $result = LinksController::createLink($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $result);
        $data = $result->get_data();
        $this->assertEquals('https://www.nimblelinks.com/xyz', $data['url']);
        $this->assertEquals('svg-url', $data['qr_svg']);
        $this->assertEquals('png-url', $data['qr_png']);
        $this->assertEquals('https://www.nimblelinks.com/links/xyz/edit', $data['manage_url']);
    }

    public function test_get_link_returns_error_when_no_link(): void
    {
        $request = new \WP_REST_Request(['post_id' => 42]);

        Functions\when('get_post_meta')->justReturn('');

        $result = LinksController::getLink($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('not_found', $result->get_error_code());
    }

    public function test_get_link_returns_data(): void
    {
        $request = new \WP_REST_Request(['post_id' => 42]);

        Functions\when('get_post_meta')->alias(function ($postId, $key, $single) {
            return match ($key) {
                '_nimble_links_url' => 'https://nimblelinks.com/abc',
                '_nimble_links_qr_svg' => 'svg-url',
                '_nimble_links_qr_png' => 'png-url',
                default => '',
            };
        });

        $result = LinksController::getLink($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $result);
        $data = $result->get_data();
        $this->assertEquals('https://nimblelinks.com/abc', $data['url']);
        $this->assertEquals('svg-url', $data['qr_svg']);
        $this->assertEquals('png-url', $data['qr_png']);
    }
}
