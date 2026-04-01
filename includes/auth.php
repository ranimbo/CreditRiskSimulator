<?php
/**
 * Authentication Middleware
 * 
 * Include this file at the top of any page that requires authentication.
 * Handles session management, authentication checks, and CSRF protection.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(defined('SESSION_NAME') ? SESSION_NAME : 'credit_risk_session');
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

/**
 * Check if user is authenticated
 * 
 * @return bool
 */
function isAuthenticated(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if current user is admin
 * 
 * @return bool
 */
function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current user ID
 * 
 * @return int|null
 */
function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data from session
 * 
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'nom' => $_SESSION['user_nom'],
        'prenom' => $_SESSION['user_prenom'],
        'role' => $_SESSION['user_role'],
        'full_name' => $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']
    ];
}

/**
 * Set user session after successful login
 * 
 * @param User $user User object
 */
function setUserSession(User $user): void {
    $_SESSION['user_id'] = $user->id;
    $_SESSION['user_email'] = $user->email;
    $_SESSION['user_nom'] = $user->nom;
    $_SESSION['user_prenom'] = $user->prenom;
    $_SESSION['user_role'] = $user->role;
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
}

/**
 * Clear user session (logout)
 */
function clearUserSession(): void {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth(): void {
    if (!isAuthenticated()) {
        // Store the requested URL for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['login_time'])) {
        $sessionLifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600;
        if (time() - $_SESSION['login_time'] > $sessionLifetime) {
            clearUserSession();
            header('Location: login.php?timeout=1');
            exit;
        }
        // Update last activity time
        $_SESSION['login_time'] = time();
    }
}

/**
 * Require admin role - redirect if not admin
 */
function requireAdmin(): void {
    requireAuth();
    
    if (!isAdmin()) {
        header('Location: dashboard.php?error=unauthorized');
        exit;
    }
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * 
 * @param string|null $token Token to validate
 * @return bool
 */
function validateCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF hidden input field
 * 
 * @return string HTML hidden input
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

/**
 * Verify CSRF on POST requests
 * Automatically validates and exits with error if invalid
 */
function verifyCsrfToken(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!validateCsrfToken($token)) {
            http_response_code(403);
            die('Token de sécurité invalide. Veuillez rafraîchir la page et réessayer.');
        }
    }
}

/**
 * Set flash message
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash messages
 * 
 * @return array Flash messages
 */
function getFlashMessages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Check if there are flash messages
 * 
 * @return bool
 */
function hasFlashMessages(): bool {
    return !empty($_SESSION['flash_messages']);
}
