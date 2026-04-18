<?php
// debug_auth.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'config/constants.php';

session_start();

echo "<h1>ParkSys Pro Debug</h1>";

// 1. Check DB Connection
try {
    $db = Database::getConnection();
    echo "<p style='color:green;'>✅ Database Connection: SUCCESS</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Database Connection: FAILED (" . $e->getMessage() . ")</p>";
}

// 2. Check Session
echo "<h3>Session Data:</h3>";
if (empty($_SESSION)) {
    echo "<p>Session is EMPTY.</p>";
} else {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

// 3. Check Admin User in DB
if (isset($db)) {
    $stmt = $db->query("SELECT id, name, email, role, is_active FROM users");
    $users = $stmt->fetchAll();
    echo "<h3>Users in Database:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr>";
    foreach ($users as $u) {
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>{$u['name']}</td>";
        echo "<td>{$u['email']}</td>";
        echo "<td>{$u['role']}</td>";
        echo "<td>{$u['is_active']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Test Constants
echo "<h3>Constants:</h3>";
echo "BASE_URL: " . BASE_URL . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";

echo "<hr>";
echo "<p><a href='views/auth/login.php'>Go to Login Page</a> | <a href='index.php'>Go to Home</a></p>";
