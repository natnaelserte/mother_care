<?php
// Language Loading and Translation Helper

// Supported languages (add more as needed)
$supported_languages = ['en' => 'English', 'am' => 'አማርኛ'];

// Default language if no session language is set or supported
$default_language = 'en';

// Determine the current language
$current_language = $default_language; // Start with default

// Check if a language preference is stored in the session
// session_start() must have been called BEFORE including this file.
if (isset($_SESSION['language'])) {
    $session_lang = $_SESSION['language'];
    // Validate if the session language is supported
    if (array_key_exists($session_lang, $supported_languages)) {
        $current_language = $session_lang;
    } else {
        // If session language is not supported, reset to default and log
        // We should use error_log here as __() might not be fully available yet
        error_log("Unsupported language code '" . htmlspecialchars($session_lang) . "' found in session. Resetting to default.");
        $_SESSION['language'] = $default_language;
        $current_language = $default_language; // Ensure current logic uses the default
    }
} else {
    // If no language is set in the session, set the default
    $_SESSION['language'] = $default_language;
}

// Define the path to the language files
// Assumes this file is in core/ and language files are in project_root/lang/
$lang_file = __DIR__ . '/../lang/' . $current_language . '.php';

// Load the language file
if (file_exists($lang_file)) {
    require_once($lang_file);
} else {
    // Fallback to default language if the selected language file is missing
    error_log("Language file not found for '" . htmlspecialchars($current_language) . "'. Falling back to default ('" . $default_language . "').");
    require_once(__DIR__ . '/../lang/' . $default_language . '.php');
    $current_language = $default_language; // Update current_language if fallback occurs
    $_SESSION['language'] = $default_language; // Ensure session reflects fallback
}

// Make the language array globally accessible
// $lang array is populated by requiring the language file
global $lang;

/**
 * Get a translated string by key.
 *
 * @param string $key The key of the string in the language array.
 * @param mixed ...$args Optional arguments for sprintf (e.g., '%s' placeholders).
 * @return string The translated string, or the key itself if not found.
 */
function __($key, ...$args) {
    global $lang; // Use the global language array populated by the language file

    // Ensure $lang is an array before trying to access keys
    if (is_array($lang) && isset($lang[$key])) {
        // If arguments are provided, use sprintf for placeholders
        if (!empty($args)) {
            // Use vsprintf for variable number of arguments
            return vsprintf($lang[$key], $args);
        }
        return $lang[$key];
    }

    // If the key is not found or $lang is not valid, return the key itself
    error_log("Missing translation key: '" . $key . "' (Current Lang: " . ($_SESSION['language'] ?? 'N/A') . ")"); // Log missing keys
    // Optionally prepend something to missing keys for visibility during development
    // return '??' . $key . '??';
    return $key; // Fallback: return the key
}

// Get the full name of the current language for display (used in header)
$current_language_name = $supported_languages[$current_language] ?? $current_language;
?>