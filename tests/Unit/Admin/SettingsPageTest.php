<?php

namespace NimbleLinks\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use Mockery;
use NimbleLinks\Admin\SettingsPage;
use NimbleLinks\Encryption;
use NimbleLinks\Tests\Unit\TestCase;

class SettingsPageTest extends TestCase
{
    public function test_is_connected_returns_false_when_no_token(): void
    {
        Functions\expect('get_option')
            ->with('nimble_links_api_token', '')
            ->andReturn('');

        $this->assertFalse(SettingsPage::isConnected());
    }

    public function test_is_connected_returns_false_when_decrypt_fails(): void
    {
        Functions\expect('get_option')
            ->with('nimble_links_api_token', '')
            ->andReturn('not-a-valid-encrypted-string');

        $this->assertFalse(SettingsPage::isConnected());
    }

    public function test_is_connected_returns_true_with_valid_token(): void
    {
        $encrypted = Encryption::encrypt('valid-token');

        Functions\expect('get_option')
            ->with('nimble_links_api_token', '')
            ->andReturn($encrypted);

        $this->assertTrue(SettingsPage::isConnected());
    }

    public function test_get_token_returns_empty_when_no_token(): void
    {
        Functions\expect('get_option')
            ->with('nimble_links_api_token', '')
            ->andReturn('');

        $this->assertSame('', SettingsPage::getToken());
    }

    public function test_get_token_decrypts_stored_value(): void
    {
        $encrypted = Encryption::encrypt('my-api-token');

        Functions\expect('get_option')
            ->with('nimble_links_api_token', '')
            ->andReturn($encrypted);

        $this->assertEquals('my-api-token', SettingsPage::getToken());
    }

    public function test_validate_token_rejects_empty_token(): void
    {
        $_POST['token'] = '';

        Functions\expect('check_ajax_referer')->once();
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        Functions\when('sanitize_text_field')->alias(function ($v) { return $v; });
        Functions\when('wp_unslash')->alias(function ($v) { return $v; });

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::on(fn($data) => $data['message'] === 'Please enter an API token.'))
            ->andThrow(new \RuntimeException('json_error'));

        $this->expectException(\RuntimeException::class);
        SettingsPage::ajaxValidateToken();
    }

    public function test_validate_token_reports_invalid_token_on_401(): void
    {
        $_POST['token'] = 'bad-token';

        Functions\expect('check_ajax_referer')->once();
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        Functions\when('sanitize_text_field')->alias(function ($v) { return $v; });
        Functions\when('wp_unslash')->alias(function ($v) { return $v; });
        Functions\when('apply_filters')->alias(function () {
            return func_get_arg(1);
        });
        Functions\expect('wp_json_encode')->andReturnUsing('json_encode');

        Functions\expect('wp_remote_get')->once()->andReturn([]);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(401);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"message":"Unauthenticated."}');

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::on(fn($data) => $data['message'] === 'Invalid token.'))
            ->andThrow(new \RuntimeException('json_error'));

        $this->expectException(\RuntimeException::class);
        SettingsPage::ajaxValidateToken();
    }

    public function test_validate_token_reports_connection_error(): void
    {
        $_POST['token'] = 'some-token';

        Functions\expect('check_ajax_referer')->once();
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        Functions\when('sanitize_text_field')->alias(function ($v) { return $v; });
        Functions\when('wp_unslash')->alias(function ($v) { return $v; });
        Functions\when('apply_filters')->alias(function () {
            return func_get_arg(1);
        });
        Functions\expect('wp_json_encode')->andReturnUsing('json_encode');

        Functions\expect('wp_remote_get')->once()->andReturn(
            new \WP_Error('http_error', 'Timeout')
        );

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::on(fn($data) => $data['message'] === 'Could not connect to Nimble Links.'))
            ->andThrow(new \RuntimeException('json_error'));

        $this->expectException(\RuntimeException::class);
        SettingsPage::ajaxValidateToken();
    }

    public function test_validate_token_stores_token_on_success(): void
    {
        $_POST['token'] = 'good-token';

        Functions\expect('check_ajax_referer')->once();
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);
        Functions\when('sanitize_text_field')->alias(function ($v) { return $v; });
        Functions\when('wp_unslash')->alias(function ($v) { return $v; });
        Functions\when('apply_filters')->alias(function () {
            return func_get_arg(1);
        });
        Functions\expect('wp_json_encode')->andReturnUsing('json_encode');

        Functions\expect('wp_remote_get')->once()->andReturn([]);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('{"email":"a@b.com","team":{"id":"uuid","name":"My Team"}}');

        Functions\expect('update_option')
            ->once()
            ->with('nimble_links_api_token', Mockery::type('string'));

        Functions\expect('update_option')
            ->once()
            ->with('nimble_links_team_name', 'My Team');

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on(fn($data) => $data['team_name'] === 'My Team'))
            ->andThrow(new \RuntimeException('json_success'));

        $this->expectException(\RuntimeException::class);
        SettingsPage::ajaxValidateToken();
    }

    public function test_disconnect_deletes_options(): void
    {
        Functions\expect('check_ajax_referer')->once();
        Functions\expect('current_user_can')->with('manage_options')->andReturn(true);

        Functions\expect('delete_option')->once()->with('nimble_links_api_token');
        Functions\expect('delete_option')->once()->with('nimble_links_team_name');

        Functions\expect('wp_send_json_success')
            ->once()
            ->andThrow(new \RuntimeException('json_success'));

        $this->expectException(\RuntimeException::class);
        SettingsPage::ajaxDisconnect();
    }

    protected function tearDown(): void
    {
        unset($_POST['token']);
        parent::tearDown();
    }
}
