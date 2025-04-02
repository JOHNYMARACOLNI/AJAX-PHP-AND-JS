<?php
require 'db.php';
session_start();

$room_id = $_GET['room_id'];


$conn->query("UPDATE rooms SET game_active = 1 WHERE id = $room_id");


$first_player = $conn->query("SELECT id FROM users WHERE room_id = $room_id ORDER BY id LIMIT 1")->fetch_assoc();


$conn->query("UPDATE rooms SET 
    current_player_id = {$first_player['id']},
    turn_ends_at = DATE_ADD(NOW(), INTERVAL 30 SECOND)
WHERE id = $room_id");

echo json_encode(['success' => true]);
?>