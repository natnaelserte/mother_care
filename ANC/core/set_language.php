<?php
// Script to set user's language preference

// Start the session if it hasn't been already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include application paths configuration (defines PROJECT_SUBDIRECTORY)
require_once(__DIR__ . '/../config/paths.php');

// Include the language logic itself to get supported languages
require_once(__DIR__ . '/language.php');
// Access supported languages
global $supported_languages;


// Get the requested language code from the GET parameter
$requested_lang = $_GET['lang'] ?? '';

// Validate the requested language code
if (array_key_exists($requested_lang, $supported_languages)) {
    // If the requested language is supported, set it in the session
    $_SESSION['language'] = $requested_lang;
    // Success message is usually not needed here as we redirect immediately
} else {
    // If the requested language is not supported, set an error message
    // This message will be displayed on the page the user is redirected back to
    // Use __() to translate the error message key
    $_SESSION['error_message'] = __("Invalid language selection:") . " " . htmlspecialchars($requested_lang);
    $_SESSION['message_type'] = "warning";
    error_log("Attempted to set unsupported language: " . htmlspecialchars($requested_lang));
}

// Redirect the user back to the page they came from
// Get the redirect URL from the GET parameter, default to dashboard
$redirect_url = $_GET['redirect'] ?? PROJECT_SUBDIRECTORY . '/views/dashboard.php';

// Basic security check: Ensure the redirect URL starts with PROJECT_SUBDIRECTORY
// This prevents redirecting to external sites via URL manipulation
if (strpos($redirect_url, PROJECT_SUBDIRECTORY) === 0) {
    header("Location: " . $redirect_url);
} else {
    // If the redirect URL is suspicious, send them to the dashboard
    error_log("Suspicious redirect attempt detected: " . htmlspecialchars($redirect_url));
    // Use __() to translate the error message key
    $_SESSION['error_message'] = ($_SESSION['error_message'] ?? '') . " " . __("Suspicious redirect attempt blocked.");
    $_SESSION['message_type'] = "danger";
    header("Location: " . PROJECT_SUBDIRECTORY . "/views/dashboard.php");
}

exit(); // Important to exit after header redirect
?>