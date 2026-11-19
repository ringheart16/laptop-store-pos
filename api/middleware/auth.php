<?php
/**
 * Authentication Middleware
 * Laptop Store Management System
 * 
 * Provides session-based authentication and role-based authorization.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated
 * Returns 401 if not logged in
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required. Please log in.'
        ]);
        exit;
    }
    return $_SESSION;
}

/**
 * Check if user has required role
 * @param array $allowedRoles Array of allowed role strings
 * Returns 403 if role not permitted
 */
function requireRole($allowedRoles) {
    $session = requireAuth();
    
    $userRole = strtolower($session['role'] ?? '');
    $allowedLower = array_map('strtolower', $allowedRoles);
    
    if (!in_array($userRole, $allowedLower)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Insufficient permissions.'
        ]);
        exit;
    }
    return $session;
}

/**
 * Get current user from session
 */
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        return [
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['name'],
            'username' => $_SESSION['username'],
            'role' => ucfirst(strtolower($_SESSION['role']))
        ];
    }
    return null;
}

/**
 * Verify password - supports both bcrypt hashed and plain text (legacy)
 */
function verifyPassword($inputPassword, $storedPassword) {
    // Try bcrypt first
    if (password_verify($inputPassword, $storedPassword)) {
        return true;
    }
    // Fallback: plain text comparison (legacy support)
    if ($inputPassword === $storedPassword) {
        return true;
    }
    return false;
}
