<?php
// includes/helpers.php — Global Utility Functions

/**
 * Generate a unique transaction reference ID.
 * Format: TXN-YYYYMMDD-XXXXXXXX
 */
function generateRefId(): string {
    return 'TXN-' . date('Ymd') . '-' . strtoupper(substr(uniqid('', true), -8));
}

/**
 * Generate a receipt number.
 */
function generateReceiptNo(): string {
    return 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

/**
 * Format peso amount.
 */
function peso(float $amount): string {
    return '₱' . number_format($amount, 2);
}

/**
 * Sanitize string input.
 */
function clean(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Calculate duration in minutes between two DateTimes.
 */
function durationMins(DateTime $entry, DateTime $exit): int {
    return (int) round(($exit->getTimestamp() - $entry->getTimestamp()) / 60);
}

/**
 * Format minutes as human-readable duration.
 * e.g. 135 → "2 hrs 15 min"
 */
function formatDuration(int $mins): string {
    $h = intdiv($mins, 60);
    $m = $mins % 60;
    $parts = [];
    if ($h) $parts[] = "{$h} hr" . ($h > 1 ? 's' : '');
    if ($m) $parts[] = "{$m} min";
    return $parts ? implode(' ', $parts) : '< 1 min';
}

/**
 * Return JSON response and exit (for API endpoints).
 */
function jsonResponse(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * CSRF Protection Functions
 */
function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
    if (!validateCsrfToken($token)) {
        jsonResponse(['success' => false, 'message' => 'Security Error: Invalid CSRF Token'], 403);
    }
}

/**
 * Fetch a system setting from the database.
 */
function getSetting(PDO $db, string $key, string $default = ''): string {
    try {
        $stmt = $db->prepare("SELECT meta_value FROM system_settings WHERE meta_key = :key LIMIT 1");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();
        return $row ? $row['meta_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Log audit trail.
 */
function auditLog(PDO $db, ?int $userId, string $action, string $table, ?int $recordId, array $details = []): void {
    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address)
        VALUES (:uid, :action, :table, :rid, :details, :ip)
    ");
    $stmt->execute([
        ':uid'     => $userId,
        ':action'  => $action,
        ':table'   => $table,
        ':rid'     => $recordId,
        ':details' => json_encode($details),
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);
}