<?php
// api/save_user.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
requireRole(ROLE_SUPERADMIN);

$db = Database::getConnection();

$id         = $_POST['id'] ?? null;
$name       = clean($_POST['name'] ?? '');
$email      = clean($_POST['email'] ?? '');
$role       = clean($_POST['role'] ?? 'customer');
$membership = clean($_POST['membership'] ?? 'standard');
$plate      = strtoupper(clean($_POST['plate'] ?? ''));
$balance    = (float)($_POST['balance'] ?? 0);
$loyalty    = (int)($_POST['loyalty'] ?? 0);
$password   = $_POST['password'] ?? null;

if (!$name || !$email) {
    jsonResponse(['success' => false, 'message' => 'Name and Email are required'], 400);
}

try {
    if ($id) {
        // UPDATE
        $sql = "UPDATE users SET name = :name, email = :email, role = :role, 
                membership_type = :mem, plate_number = :plate, wallet_balance = :bal,
                loyalty_points = :pts
                WHERE id = :id";
        $params = [
            ':name'  => $name,
            ':email' => $email,
            ':role'  => $role,
            ':mem'   => $membership,
            ':plate' => $plate ?: null,
            ':bal'   => $balance,
            ':pts'   => $loyalty,
            ':id'    => $id
        ];
        
        if ($password) {
            $sql = str_replace("WHERE id", ", password = :pass WHERE id", $sql);
            $params[':pass'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $msg = "User updated successfully!";
    } else {
        // CREATE
        if (!$password) {
            jsonResponse(['success' => false, 'message' => 'Password is required for new users'], 400);
        }
        
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role, membership_type, plate_number, wallet_balance, loyalty_points)
            VALUES (:name, :email, :pass, :role, :mem, :plate, :bal, :pts)
        ");
        $stmt->execute([
            ':name'  => $name,
            ':email' => $email,
            ':pass'  => password_hash($password, PASSWORD_DEFAULT),
            ':role'  => $role,
            ':mem'   => $membership,
            ':plate' => $plate ?: null,
            ':bal'   => $balance,
            ':pts'   => $loyalty
        ]);
        $msg = "New user created successfully!";
    }

    jsonResponse(['success' => true, 'message' => $msg]);

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        jsonResponse(['success' => false, 'message' => 'Email or Plate Number already exists.'], 400);
    }
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
