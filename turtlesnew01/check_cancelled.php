<?php
require 'db.php';
session_start();

header('Content-Type: application/json');
$room_id = $_GET['room_id'] ?? 0;

// sprawdzam ilosc ready count w bazie co oznacza ze ktos anulowal gre lub nie
$stmt = $conn->prepare("SELECT COUNT(*) as ready_count FROM users WHERE room_id = ? AND is_ready = 1");
$stmt->bind_param('i', $room_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode([
    'cancelled' => ($data['ready_count'] == 0)
]);

$stmt->close();
$conn->close();
?>