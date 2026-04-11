<?php

namespace NimbleLinks;

class Encryption
{
    public static function encrypt(string $plaintext): string
    {
        $key = self::deriveKey();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key);

        return base64_encode($nonce . $ciphertext);
    }

    public static function decrypt(string $encrypted): string
    {
        $key = self::deriveKey();
        $decoded = base64_decode($encrypted, true);

        if ($decoded === false) {
            return '';
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        if (strlen($nonce) !== SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return '';
        }

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

        if ($plaintext === false) {
            return '';
        }

        return $plaintext;
    }

    private static function deriveKey(): string
    {
        return sodium_crypto_generichash(AUTH_KEY, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
