<?php
// Include authentication functions
require_once(__DIR__ . '/../core/auth.php');

// Call the logout function
logout_user();

// Redirect is handled within logout_user()
?>