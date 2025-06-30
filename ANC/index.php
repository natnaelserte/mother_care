<?php
// This is the main entry point for the DPH-ANC System.
// It checks if a user is logged in and redirects them
// to the appropriate page (Dashboard if logged in, Login if not).

// Include the authentication core file
// The path is relative from the root directory (where index.php is)
require_once(__DIR__ . '/core/auth.php');

// Check if the user is currently logged in
if (is_loggedin()) {
    // User is logged in, redirect them to the dashboard
    // The path is relative from the root directory
    header("location: views/dashboard.php");
    exit; // Important: Always exit after a header redirect
} else {
    // User is not logged in, redirect them to the login page
    // The path is relative from the root directory
    header("location: views/login.php");
    exit; // Important: Always exit after a header redirect
}

// Note: Since we are always redirecting, there's no HTML content needed in this file.
?>