<?php
// api/save_zone.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
requireRole(ROLE_SUPERADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method Not Allowed'], 405);
}

$name = clean($_POST['name'] ?? '');
$floor = clean($_POST['floor'] ?? '');
$description = clean($_POST['description'] ?? '');

if (!$name || !$floor) {
    jsonResponse(['success' => false, 'message' => 'Name and Floor are required.']);
}

$db = Database::getConnection();

try {
    $stmt = $db->prepare("INSERT INTO zones (name, floor, description) VALUES (:name, :floor, :desc)");
    $stmt->execute([
        ':name'  => $name,
        ':floor' => $floor,
        ':desc'  => $description
    ]);

    jsonResponse(['success' => true, 'message' => 'Zone created successfully.']);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
