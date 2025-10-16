<?php
/**
 * PSR-4 Autoloader for Keyless Auth
 *
 * Automatically loads classes from the Chrmrtns\KeylessAuth namespace
 * Maps namespace to file path in /includes/ directory
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(function ($class) {
    // Only handle our namespace
    $prefix = 'Chrmrtns\\KeylessAuth\\';

    // Check if the class uses our namespace
    if (strpos($class, $prefix) !== 0) {
        return; // Not our responsibility
    }

    // Remove namespace prefix
    $relative_class = substr($class, strlen($prefix));

    // Convert namespace separators to directory separators
    $relative_class = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class);

    // Build the file path
    $file = CHRMRTNS_KLA_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . $relative_class . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
