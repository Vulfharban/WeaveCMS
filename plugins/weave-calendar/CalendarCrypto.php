<?php
/**
 * ============================================================================
 * WeaveCMS Plugin: Weave Calendar – Verschlüsselung (CalendarCrypto)
 * ============================================================================
 * Verschlüsselt sensible Sync-Zugangsdaten (OAuth-Refresh-Tokens, CalDAV-Passwörter)
 * mittels OpenSSL AES-256-GCM (Fallback: AES-256-CBC + HMAC-SHA256).
 * ============================================================================
 */

if (!defined('WEAVE_VERSION') && !defined('DEFAULT_ADMIN_PASSWORD') && !class_exists('PluginManager') && php_sapi_name() !== 'cli') {
    exit;
}

class CalendarCrypto {

    public static function deriveKey(string $secret): string {
        return hash('sha256', $secret !== '' ? $secret : 'weave-calendar-fallback-key', true);
    }

    public static function encrypt(string $plaintext, string $secret): string {
        if ($plaintext === '') {
            return '';
        }
        $key = self::deriveKey($secret);
        if (function_exists('openssl_encrypt')) {
            $ciphers = array_map('strtolower', openssl_get_cipher_methods());
            if (in_array('aes-256-gcm', $ciphers, true)) {
                $iv = random_bytes(12);
                $tag = '';
                $ct = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
                if ($ct !== false) {
                    return 'enc:v1:gcm:' . base64_encode($iv . $tag . $ct);
                }
            }
            $iv = random_bytes(16);
            $ct = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            if ($ct !== false) {
                $mac = hash_hmac('sha256', $iv . $ct, $key, true);
                return 'enc:v1:cbc:' . base64_encode($iv . $mac . $ct);
            }
        }
        return 'enc:v1:b64:' . base64_encode($plaintext);
    }

    public static function decrypt(string $ciphertext, string $secret): string {
        if ($ciphertext === '') {
            return '';
        }
        if (strpos($ciphertext, 'enc:v1:') !== 0) {
            return $ciphertext;
        }
        $key = self::deriveKey($secret);
        if (strpos($ciphertext, 'enc:v1:gcm:') === 0 && function_exists('openssl_decrypt')) {
            $raw = base64_decode(substr($ciphertext, 11), true);
            if ($raw === false || strlen($raw) <= 28) {
                return '';
            }
            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $ct = substr($raw, 28);
            $pt = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            return $pt !== false ? $pt : '';
        }
        if (strpos($ciphertext, 'enc:v1:cbc:') === 0 && function_exists('openssl_decrypt')) {
            $raw = base64_decode(substr($ciphertext, 11), true);
            if ($raw === false || strlen($raw) <= 48) {
                return '';
            }
            $iv = substr($raw, 0, 16);
            $mac = substr($raw, 16, 32);
            $ct = substr($raw, 48);
            $calc = hash_hmac('sha256', $iv . $ct, $key, true);
            if (!hash_equals($mac, $calc)) {
                return '';
            }
            $pt = openssl_decrypt($ct, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            return $pt !== false ? $pt : '';
        }
        if (strpos($ciphertext, 'enc:v1:b64:') === 0) {
            $pt = base64_decode(substr($ciphertext, 11), true);
            return $pt !== false ? $pt : '';
        }
        return '';
    }
}
