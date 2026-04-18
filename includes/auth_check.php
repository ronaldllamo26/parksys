<?php
// includes/auth_check.php — Session Guard Middleware

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/constants.php';

function isLoggedIn(): bool {
    return !empty($_SESSION[SESSION_USER_ID]);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(401);
            die(json_encode(['success' => false, 'message' => 'Session expired. Please login again.']));
        }
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION[SESSION_USER_ROLE], $roles, true)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
        }
        http_response_code(403);
        include __DIR__ . '/../views/errors/403.php';
        exit;
    }
}

function currentUser(): array {
    return [
        'id'   => $_SESSION[SESSION_USER_ID]   ?? null,
        'role' => $_SESSION[SESSION_USER_ROLE] ?? null,
        'name' => $_SESSION[SESSION_USER_NAME] ?? 'Guest',
    ];
}