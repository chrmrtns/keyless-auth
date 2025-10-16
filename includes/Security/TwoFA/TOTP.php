<?php
/**
 * TOTP (Time-based One-Time Password) functionality for Keyless Auth
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Security\TwoFA;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class TOTP {

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize TOTP functionality
    }

    /**
     * Generate a random secret key for TOTP
     *
     * @return string Base32 encoded secret key (160 bits)
     */
    public function generate_secret() {
        $secret = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 characters

        // Generate 32 characters (160 bits of entropy)
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * Generate TOTP code for given secret and time
     *
     * @param string $secret Base32 encoded secret
     * @param int $time Unix timestamp (optional, defaults to current time)
     * @return string 6-digit TOTP code
     */
    public function generate_code($secret, $time = null) {
        if ($time === null) {
            $time = time();
        }

        // Convert time to 30-second counter
        $time_counter = intval($time / 30);

        // Decode base32 secret
        $secret_key = $this->base32_decode($secret);

        if (!$secret_key) {
            return false;
        }

        // Pack time counter as 8-byte big-endian
        $time_bytes = pack('N*', 0) . pack('N*', $time_counter);

        // Generate HMAC-SHA1
        $hash = hash_hmac('sha1', $time_bytes, $secret_key, true);

        // Dynamic truncation
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, 6);

        // Return 6-digit code with leading zeros
        return sprintf('%06d', $code);
    }

    /**
     * Verify TOTP code with time drift tolerance
     *
     * @param string $code User provided code
     * @param string $secret Base32 encoded secret
     * @param int $time_window Number of 30-second windows to check (default: 2, ±1 minute)
     * @return bool True if code is valid
     */
    public function verify_code($code, $secret, $time_window = 2) {
        $current_time = time();

        // Check current time and surrounding windows
        for ($i = -$time_window; $i <= $time_window; $i++) {
            $test_time = $current_time + ($i * 30);
            $test_code = $this->generate_code($secret, $test_time);

            if ($test_code && $this->timing_safe_equals($code, $test_code)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Get TOTP URI for manual entry
     *
     * @param string $secret Base32 encoded secret
     * @param string $user_email User's email address
     * @param string $site_name Site name
     * @return string TOTP URI (not URL encoded for authenticator app compatibility)
     */
    public function get_totp_uri($secret, $user_email, $site_name = null) {
        if (!$site_name) {
            $site_name = get_bloginfo('name');
        }

        // Clean inputs but avoid URL encoding to maintain authenticator app compatibility
        // Some apps like 2FAS don't support URL-encoded TOTP URIs
        $site_name = sanitize_text_field($site_name);
        $user_email = sanitize_email($user_email);

        // However, we need to handle characters that could break URI structure
        // Replace problematic characters that break TOTP URI format
        $site_name = str_replace(array(':', '?', '&', '#'), array('-', '', '', ''), $site_name);

        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            $site_name,
            $user_email,
            $secret,
            $site_name
        );
    }

    /**
     * Generate backup codes
     *
     * @param int $count Number of backup codes to generate
     * @return array Array of hashed backup codes
     */
    public function generate_backup_codes($count = 10) {
        $codes = array();

        for ($i = 0; $i < $count; $i++) {
            // Generate 8-digit backup code
            $code = sprintf('%08d', random_int(10000000, 99999999));
            // Store hashed version
            $codes[] = wp_hash_password($code);
        }

        return $codes;
    }

    /**
     * Get backup codes for display (unhashed)
     *
     * @param int $count Number of backup codes to generate
     * @return array Array of plain text backup codes
     */
    public function get_display_backup_codes($count = 10) {
        $codes = array();

        for ($i = 0; $i < $count; $i++) {
            // Generate 8-digit backup code
            $codes[] = sprintf('%08d', random_int(10000000, 99999999));
        }

        return $codes;
    }

    /**
     * Hash backup codes for storage
     *
     * @param array $codes Plain text backup codes
     * @return array Hashed backup codes
     */
    public function hash_backup_codes($codes) {
        return array_map('wp_hash_password', $codes);
    }

    /**
     * Base32 decode function
     *
     * @param string $input Base32 encoded string
     * @return string|false Decoded binary string or false on failure
     */
    private function base32_decode($input) {
        if (empty($input)) {
            return false;
        }

        $input = strtoupper($input);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0, $j = strlen($input); $i < $j; $i++) {
            $v <<= 5;
            $pos = strpos($alphabet, $input[$i]);
            if ($pos === false) {
                return false;
            }
            $v += $pos;
            $vbits += 5;

            if ($vbits >= 8) {
                $output .= chr($v >> ($vbits - 8));
                $v <<= (32 - $vbits);
                $v >>= (32 - $vbits);
                $vbits -= 8;
            }
        }

        return $output;
    }

    /**
     * Timing-safe string comparison to prevent timing attacks
     *
     * @param string $a First string
     * @param string $b Second string
     * @return bool True if strings are equal
     */
    private function timing_safe_equals($a, $b) {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        }

        // Fallback for older PHP versions
        if (strlen($a) !== strlen($b)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }

        return $result === 0;
    }

    /**
     * Check if user is locked out from 2FA attempts
     *
     * @param int $user_id User ID
     * @return bool|int True if locked out, false if not, or seconds remaining if locked
     */
    public function is_user_locked_out($user_id) {
        global $chrmrtns_kla_database;

        $settings = $chrmrtns_kla_database->get_user_2fa_settings($user_id);

        if (!$settings || !$settings->totp_locked_until) {
            return false;
        }

        $locked_until = strtotime($settings->totp_locked_until);
        $current_time = time();

        if ($current_time < $locked_until) {
            return $locked_until - $current_time; // Return seconds remaining
        }

        return false;
    }

    /**
     * Format lockout time for display
     *
     * @param int $seconds Seconds remaining
     * @return string Formatted time string
     */
    public function format_lockout_time($seconds) {
        if ($seconds <= 60) {
            /* translators: %d: number of seconds */
            return sprintf(_n('%d second', '%d seconds', $seconds, 'keyless-auth'), $seconds);
        } else {
            $minutes = ceil($seconds / 60);
            /* translators: %d: number of minutes */
            return sprintf(_n('%d minute', '%d minutes', $minutes, 'keyless-auth'), $minutes);
        }
    }

    /**
     * Validate TOTP code format
     *
     * @param string $code Code to validate
     * @return bool True if format is valid
     */
    public function is_valid_code_format($code) {
        return preg_match('/^[0-9]{6}$/', $code) === 1;
    }

    /**
     * Validate backup code format
     *
     * @param string $code Code to validate
     * @return bool True if format is valid
     */
    public function is_valid_backup_code_format($code) {
        return preg_match('/^[0-9]{8}$/', $code) === 1;
    }
}
