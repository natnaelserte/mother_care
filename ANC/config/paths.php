<?php
/*
 * Application Paths Configuration
 *
 * Define the subdirectory the project is installed in, relative to the web server's
 * document root. This is used for generating absolute paths for HTTP redirects.
 */

// Define the project subdirectory path relative to the web server's document root.
// Include the leading slash (if any) but DO NOT include a trailing slash.
//
// Examples:
// If your project folder 'ANC' is directly under the web server's document root (e.g., htdocs):
// Access URL: http://localhost/ANC/
// Set this constant to: define('PROJECT_SUBDIRECTORY', '/ANC');
//
// If your project files ARE the web server's document root (e.g., htdocs):
// Access URL: http://localhost/
// Set this constant to: define('PROJECT_SUBDIRECTORY', ''); // Empty string

// IMPORTANT: **SET THIS VALUE** according to your server setup
define('PROJECT_SUBDIRECTORY', '/ANC'); // <-- CHANGE '/ANC' if your folder name is different, or to '' if it's the web root.

?>