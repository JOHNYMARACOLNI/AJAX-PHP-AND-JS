<?php
require 'db.php';
session_start();

$room_id = $_GET['room_id'] ?? 0;
$last_check = $_GET['last_check'] ?? 0; // Teraz przekazujemy przez GET, nie przez sesję

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT MAX(UNIX_TIMESTAMP(updated_at)) as last_change 
                          FROM users WHERE room_id = ?");
    $stmt->bind_param('i', $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $last_change = $row['last_change'] ?? 0;
    $changed = ($last_change > $last_check);
    
    echo json_encode([
        'changed' => $changed,
        'new_timestamp' => $last_change
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}