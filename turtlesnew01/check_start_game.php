<?php
require 'db.php';
session_start();

$room_id = $_GET['room_id'];
$response = ['can_start' => false];

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_players,
        SUM(is_ready) as ready_players 
    FROM users 
    WHERE room_id = ?
");
$stmt->bind_param('i', $room_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data['total_players'] >= 5 || $data['ready_players'] == $data['total_players']) {
    $response['can_start'] = true;
}

header('Content-Type: application/json');
echo json_encode($response);
?>