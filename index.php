<?php
/**
 * Index Page
 * 
 * Redirects to dashboard if authenticated, otherwise to login page.
 */

require_once __DIR__ . '/includes/auth.php';

if (isAuthenticated()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
