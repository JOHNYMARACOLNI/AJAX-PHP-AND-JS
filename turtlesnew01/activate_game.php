<?php
require 'db.php';

session_start();
$room_id = $_GET['room_id'];

// SPRAWDZAM CZY POKOJ ISTNIEJE I CZY GRA JEST AKTYWNA
$stmt = $conn->prepare('SELECT * FROM rooms WHERE id = ?');
$stmt->bind_param('i', $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if(!$room) {
    die(json_encode([
        'success' => false,
        'error' => 'Pokój nie istnieje!'
    ]));
}

if($room['game_active']) {
    die(json_encode([
        'success' => false,
        'error' => 'Gra już jest aktywna!'
    ]));
}

try {
    // AKTYWACJA GRY I DODANIE REKORDU W BAZIE
    $conn->query("UPDATE rooms SET game_active = 1 WHERE id = $room_id");
    
    // PIERWSZY GRACZ
    $first_player = $conn->query("SELECT id FROM users WHERE room_id = $room_id ORDER BY id LIMIT 1")->fetch_assoc();
    
    $conn->query("UPDATE rooms SET 
        current_player_id = {$first_player['id']},
        turn_ends_at = DATE_ADD(NOW(), INTERVAL 30 SECOND)
        WHERE id = $room_id");

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}