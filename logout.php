<?php
/**
 * Logout Page
 * 
 * Destroys user session and redirects to login page.
 */

require_once __DIR__ . '/includes/auth.php';

// Clear the session
clearUserSession();

// Redirect to login page with logout message
header('Location: login.php');
exit;
