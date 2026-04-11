<?php

namespace NimbleLinks\Tests\Unit;

use Brain\Monkey\Functions;
use Mockery;
use NimbleLinks\Api\Client;
use NimbleLinks\Encryption;
use NimbleLinks\PostHandler;

class PostHandlerTest extends TestCase
{
    private \WP_Post $post;
    private string $encryptedToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->post = \WP_Post::create([
            'ID'         => 42,
            'post_type'  => 'post',
            'post_title' => 'Test Post',
        ]);

        $this->encryptedToken = Encryption::encrypt('test-token');

        Functions\when('apply_filters')->alias(function () {
            $args = func_get_args();
            return $args[1] ?? null;
        });
    }

    public function test_ignores_non_publish_status(): void
    {
        PostHandler::onTransition('draft', 'draft', $this->post);
        $this->assertTrue(true);
    }

    public function test_ignores_non_post_type(): void
    {
        $this->post->post_type = 'page';

        PostHandler::onTransition('publish', 'draft', $this->post);
        $this->assertTrue(true);
    }

    public function test_ignores_when_auto_short_link_disabled(): void
    {
        Functions\when('get_option')->alias(function ($key, $default = false) {
            if ($key === 'nimble_links_auto_short_link') {
                return false;
            }
            return $default;
        });

        PostHandler::onTransition('publish', 'draft', $this->post);
        $this->assertTrue(true);
    }

    public function test_ignores_when_no_token(): void
    {
        Functions\when('get_option')->alias(function ($key, $default = false) {
            if ($key === 'nimble_links_auto_short_link') {
                return true;
            }
            return $default;
        });

        PostHandler::onTransition('publish', 'draft', $this->post);
        $this->assertTrue(true);
    }

    public function test_ignores_when_link_already_exists(): void
    {
        $this->stubOptionsWithToken();

        Functions\when('get_post_meta')->alias(function ($postId, $key, $single) {
            if ($key === '_nimble_links_id') {
                return 'existing-id';
            }
            return '';
        });

        PostHandler::onTransition('publish', 'draft', $this->post);
        $this->assertTrue(true);
    }

    public function test_logs_error_on_api_failure(): void
    {
        $this->stubOptionsWithToken();
        Functions\when('get_post_meta')->justReturn('');
        Functions\expect('get_the_title')->andReturn('Test Post');
        Functions\expect('get_permalink')->andReturn('https://example.com/test-post');
        Functions\expect('wp_json_encode')->andReturnUsing('json_encode');

        Functions\expect('wp_remote_post')->once()->andReturn(
            new \WP_Error('http_error', 'Connection failed')
        );

        Functions\expect('error_log')
            ->once()
            ->with(Mockery::on(fn($msg) => str_contains($msg, 'failed to create short link for post 42')));

        PostHandler::onTransition('publish', 'draft', $this->post);
        $this->assertTrue(true);
    }

    public function test_sets_transient_on_401_error(): void
    {
        $this->stubOptionsWithToken();
        Functions\when('get_post_meta')->justReturn('');
        Functions\expect('get_the_title')->andReturn('Test Post');
        Functions\expect('get_permalink')->andReturn('https://example.com/test-post');
        Functions\expect('wp_json_encode')->andReturnUsing('json_encode');

        Functions\expect('wp_remote_post')->once()->andReturn([]);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(401);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"message":"Unauthenticated."}');

        Functions\expect('set_transient')
            ->once()
            ->with('nimble_links_invalid_token', true, 60);

        Functions\expect('error_log')->once();

        PostHandler::onTransition('publish', 'draft', $this->post);
        $this->assertTrue(true);
    }

    public function test_stores_meta_on_success(): void
    {
        $this->stubOptionsWithToken(['nimble_links_auto_qr_code' => false]);
        Functions\when('get_post_meta')->justReturn('');
        Functions\expect('get_the_title')->andReturn('Test Post');
        Functions\expect('get_permalink')->andReturn('https://example.com/test-post');
        Functions\expect('wp_json_encode')->andReturnUsing('json_encode');

        $this->stubSuccessfulLinkCreation();

        Functions\when('sanitize_text_field')->alias(function ($v) { return $v; });
        Functions\when('esc_url_raw')->alias(function ($v) { return $v; });

        Functions\expect('update_post_meta')->once()->with(42, '_nimble_links_id', 'aBc123');
        Functions\expect('update_post_meta')->once()->with(42, '_nimble_links_url', 'https://nimblelinks.com/aBc123');

        PostHandler::onTransition('publish', 'draft', $this->post);
        $this->assertTrue(true);
    }

    public function test_fetches_qr_when_auto_qr_enabled(): void
    {
        $this->stubOptionsWithToken(['nimble_links_auto_qr_code' => true]);
        Functions\when('get_post_meta')->justReturn('');
        Functions\expect('get_the_title')->andReturn('Test Post');
        Functions\expect('get_permalink')->andReturn('https://example.com/test-post');
        Functions\expect('wp_json_encode')->andReturnUsing('json_encode');

        $this->stubSuccessfulLinkCreation();

        Functions\when('sanitize_text_field')->alias(function ($v) { return $v; });
        Functions\when('esc_url_raw')->alias(function ($v) { return $v; });
        Functions\when('update_post_meta')->justReturn(true);

        // QR fetch
        Functions\expect('wp_remote_get')
            ->once()
            ->with(Mockery::on(fn($url) => str_contains($url, '/links/aBc123/qr')), Mockery::type('array'))
            ->andReturn([]);

        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"svg":"https://nimblelinks.com/qr/aBc123?format=svg","png":"https://nimblelinks.com/qr/aBc123?format=png"}');

        PostHandler::onTransition('publish', 'draft', $this->post);
        $this->assertTrue(true);
    }

    public function test_fetch_and_store_qr_returns_false_on_error(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('getQr')
            ->with('link-id')
            ->andReturn(new \WP_Error('fail', 'QR fetch failed'));

        Functions\expect('error_log')->once();

        $this->assertFalse(PostHandler::fetchAndStoreQr($client, 'link-id', 42));
    }

    public function test_fetch_and_store_qr_stores_urls(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('getQr')
            ->with('link-id')
            ->andReturn([
                'svg' => 'https://nimblelinks.com/qr/link-id?format=svg',
                'png' => 'https://nimblelinks.com/qr/link-id?format=png',
            ]);

        Functions\when('esc_url_raw')->alias(function ($v) { return $v; });
        Functions\expect('update_post_meta')->once()->with(42, '_nimble_links_qr_svg', 'https://nimblelinks.com/qr/link-id?format=svg');
        Functions\expect('update_post_meta')->once()->with(42, '_nimble_links_qr_png', 'https://nimblelinks.com/qr/link-id?format=png');

        $this->assertTrue(PostHandler::fetchAndStoreQr($client, 'link-id', 42));
    }

    // --- Helpers ---

    private function stubOptionsWithToken(array $overrides = []): void
    {
        $token = $this->encryptedToken;

        Functions\when('get_option')->alias(function ($key, $default = false) use ($token, $overrides) {
            if (array_key_exists($key, $overrides)) {
                return $overrides[$key];
            }

            return match ($key) {
                'nimble_links_auto_short_link' => true,
                'nimble_links_api_token' => $token,
                'nimble_links_auto_qr_code' => true,
                default => $default,
            };
        });
    }

    private function stubSuccessfulLinkCreation(): void
    {
        $responseBody = json_encode([
            'data' => [
                'id'  => 'aBc123',
                'url' => 'https://nimblelinks.com/aBc123',
            ],
        ]);

        Functions\expect('wp_remote_post')->once()->andReturn([]);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(201);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn($responseBody);
    }
}
