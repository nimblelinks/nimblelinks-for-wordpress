<?php

namespace NimbleLinks\Tests\Unit;

use NimbleLinks\Encryption;

class EncryptionTest extends TestCase
{
    public function test_encrypt_decrypt_roundtrip(): void
    {
        $plaintext = 'my-secret-api-token-12345';
        $encrypted = Encryption::encrypt($plaintext);

        $this->assertNotEquals($plaintext, $encrypted);
        $this->assertEquals($plaintext, Encryption::decrypt($encrypted));
    }

    public function test_encrypt_produces_different_output_each_call(): void
    {
        $plaintext = 'same-token';
        $a = Encryption::encrypt($plaintext);
        $b = Encryption::encrypt($plaintext);

        $this->assertNotEquals($a, $b, 'Each encryption should use a random nonce');
        $this->assertEquals($plaintext, Encryption::decrypt($a));
        $this->assertEquals($plaintext, Encryption::decrypt($b));
    }

    public function test_decrypt_returns_empty_for_invalid_base64(): void
    {
        $this->assertSame('', Encryption::decrypt('not-valid-base64!!!'));
    }

    public function test_decrypt_returns_empty_for_truncated_ciphertext(): void
    {
        $this->assertSame('', Encryption::decrypt(base64_encode('short')));
    }

    public function test_decrypt_returns_empty_for_tampered_ciphertext(): void
    {
        $encrypted = Encryption::encrypt('my-token');
        $decoded   = base64_decode($encrypted);
        // Flip a byte in the middle of the ciphertext
        $tampered = $decoded;
        $pos = (int) (strlen($tampered) / 2);
        $tampered[$pos] = chr(ord($tampered[$pos]) ^ 0xFF);
        $reencoded = base64_encode($tampered);

        $this->assertSame('', Encryption::decrypt($reencoded));
    }

    public function test_decrypt_returns_empty_for_empty_string(): void
    {
        $this->assertSame('', Encryption::decrypt(''));
    }
}
