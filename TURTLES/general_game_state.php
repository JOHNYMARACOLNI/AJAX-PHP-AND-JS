<?php
require 'db.php';
session_start();


$room_id = $_GET['room_id'];


$stmt = $conn->prepare("SELECT game_state FROM rooms WHERE id = ?");
$stmt->bind_param('i', $room_id);
$stmt->execute();
$result = $stmt->get_result();
$game_state = $result->num_rows > 0 ? json_decode($result->fetch_assoc()['game_state'], true) : [];




//WYSYLAM DANE DO FETCHA
echo json_encode([
    'success' => true,
    'gameState' => $game_state
]);
?>