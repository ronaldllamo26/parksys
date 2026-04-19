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
            SELECT id, name, email, password, role, is_active, login_attempts, lockout_until
            FROM users
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        // Check for Account Lockout
        if ($user['lockout_until'] && new DateTime($user['lockout_until']) > new DateTime()) {
            $diff = (new DateTime($user['lockout_until']))->getTimestamp() - (new DateTime())->getTimestamp();
            $mins = ceil($diff / 60);
            return ['success' => false, 'message' => "Account locked. Please try again in {$mins} minutes."];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Your account has been deactivated.'];
        }

        if (!password_verify($password, $user['password'])) {
            // Increment failed attempts
            $attempts = $user['login_attempts'] + 1;
            $lockout = null;
            if ($attempts >= 5) {
                $lockout = (new DateTime())->add(new DateInterval('PT15M'))->format('Y-m-d H:i:s');
            }
            
            $upd = $this->db->prepare("UPDATE users SET login_attempts = :a, lockout_until = :l WHERE id = :id");
            $upd->execute([':a' => $attempts, ':l' => $lockout, ':id' => $user['id']]);

            auditLog($this->db, null, 'LOGIN_FAILED', 'users', $user['id'], ['email' => $email, 'attempts' => $attempts]);
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        // Reset attempts on success
        $reset = $this->db->prepare("UPDATE users SET login_attempts = 0, lockout_until = NULL WHERE id = :id");
        $reset->execute([':id' => $user['id']]);

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
        header('Location: ' . BASE_URL . '/index.php');
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