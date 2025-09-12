<?php
/**
 * This file is for debugging purposes only.
 * It will attempt to load the plugin and catch any errors.
 */

// Turn on all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define ABSPATH (required for WordPress plugins)
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
}

// Try to include the plugin file and catch any errors
try {
    require_once('360-post-types.php');
    echo "Plugin loaded successfully!\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
