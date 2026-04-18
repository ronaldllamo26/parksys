<?php
// controllers/AuthController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';

class AuthController {

    private PDO $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = Database::getConnection();
    }

    /**
     * Attempt login with email + password.
     * Returns ['success' => bool, 'message' => string, 'redirect' => string]
     */
    public function login(string $email, string $password): array {
        $email = strtolower(trim($email));

        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required.'];
        }

        $stmt = $this->db->prepare("
            SELECT id, name, email, password, role, is_active
            FROM users
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Your account has been deactivated.'];
        }

        if (!password_verify($password, $user['password'])) {
            auditLog($this->db, null, 'LOGIN_FAILED', 'users', null, ['email' => $email]);
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        // ✅ Regenerate session ID to prevent fixation attacks
        session_regenerate_id(true);

        $_SESSION[SESSION_USER_ID]   = $user['id'];
        $_SESSION[SESSION_USER_ROLE] = $user['role'];
        $_SESSION[SESSION_USER_NAME] = $user['name'];

        auditLog($this->db, $user['id'], 'LOGIN_SUCCESS', 'users', $user['id'], []);

        $redirect = BASE_URL . '/views/admin/dashboard.php';
        if ($user['role'] === ROLE_SUPERADMIN) {
            $redirect = BASE_URL . '/views/superadmin/analytics.php';
        } elseif ($user['role'] !== ROLE_ADMIN) {
            $redirect = BASE_URL . '/views/customer/check_bill.php';
        }

        return ['success' => true, 'message' => 'Welcome, ' . $user['name'], 'redirect' => $redirect];
    }

    /**
     * Destroy session and redirect to login.
     */
    public function logout(): void {
        $userId = $_SESSION[SESSION_USER_ID] ?? null;
        auditLog($this->db, $userId, 'LOGOUT', 'users', $userId, []);

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/views/auth/login.php');
        exit;
    }

    /**
     * Register a new user (Super Admin only).
     */
    public function register(string $name, string $email, string $password, string $role): array {
        if (!in_array($role, [ROLE_SUPERADMIN, ROLE_ADMIN, ROLE_CUSTOMER], true)) {
            return ['success' => false, 'message' => 'Invalid role specified.'];
        }

        // Check duplicate email
        $check = $this->db->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute([':email' => strtolower($email)]);
        if ($check->fetch()) {
            return ['success' => false, 'message' => 'Email already in use.'];
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password, role)
            VALUES (:name, :email, :password, :role)
        ");
        $stmt->execute([
            ':name'     => clean($name),
            ':email'    => strtolower(trim($email)),
            ':password' => $hashed,
            ':role'     => $role,
        ]);

        $newId = (int) $this->db->lastInsertId();
        auditLog($this->db, $_SESSION[SESSION_USER_ID] ?? null, 'USER_CREATED', 'users', $newId, ['role' => $role]);

        return ['success' => true, 'message' => "User '{$name}' created successfully.", 'user_id' => $newId];
    }
}