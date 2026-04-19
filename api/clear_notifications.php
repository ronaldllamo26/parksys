<?php
// api/clear_notifications.php
session_start();
header('Content-Type: application/json');

// For now, we clear the session-based notifications if any, 
// or if we have a DB table for this, we mark as read.
// Assuming we use audit_logs as source for dashboard alerts.

// If we want to 'hide' them for the current session:
$_SESSION['notifications_cleared_at'] = date('Y-m-d H:i:s');

echo json_encode(['success' => true, 'message' => 'Notifications cleared']);
