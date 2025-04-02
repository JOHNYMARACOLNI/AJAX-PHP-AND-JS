<?php
require 'db.php';
session_start();



// NAGLOWEK
header('Content-Type: application/json');

$room_id = $_GET['room_id'] ?? 0;

// DOSTAJE OBECNY STAN GRY Z ID GRACZA
$stmt = $conn->prepare("SELECT game_active, current_player_id, turn_ends_at FROM rooms WHERE id = ?");
$stmt->bind_param('i', $room_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();



// SPRAWDZILEM CZY CZAS NA RUCH GRACZA SIE SKONCZYL I ZMIENIAM KOLEJKE 
if ($data['game_active'] && strtotime($data['turn_ends_at']) < time()) {
    
    // DOSTAJE GRACZA Z KOLEJNYM ID 
    $stmt = $conn->prepare("SELECT id FROM users WHERE room_id = ? AND id > ? ORDER BY id LIMIT 1");
    $stmt->bind_param('ii', $room_id, $data['current_player_id']);
    $stmt->execute();
    $next_player = $stmt->get_result()->fetch_assoc();
    
    $new_player_id = $next_player ? $next_player['id'] : null;
    
    // JESLI DOTARLEM DO OSTATNIEGO GRACZA ZMIENIAM NA PIERWSZEGO, U GORY JEST ZE NIE MA GRACZA
    if (!$new_player_id) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE room_id = ? ORDER BY id LIMIT 1");
        $stmt->bind_param('i', $room_id);
        $stmt->execute();
        $new_player_id = $stmt->get_result()->fetch_assoc()['id'];
    }
    
    // UPDATE POKOJU NA NOWEGO GRACZA I NOWY INTERVAL
    $stmt = $conn->prepare("UPDATE rooms SET current_player_id = ?, turn_ends_at = DATE_ADD(NOW(), INTERVAL 30 SECOND) WHERE id = ?");
    $stmt->bind_param('ii', $new_player_id, $room_id);
    $stmt->execute();
    
    // POBIERAM DANE I JE POZNIEJ WYSYLAM DO GAME.PHP JSONEM 
    $stmt = $conn->prepare("
        SELECT r.game_active, r.current_player_id, r.turn_ends_at, u.name as current_player_name
        FROM rooms r
        LEFT JOIN users u ON r.current_player_id = u.id
        WHERE r.id = ?
    ");
    $stmt->bind_param('i', $room_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
}

echo json_encode([
    'game_active' => (bool)$data['game_active'],
    'current_player_id' => $data['current_player_id'],
    'turn_ends_at' => $data['turn_ends_at'],
    'current_player_name' => $data['current_player_name'] ?? null
]);

$stmt->close();
$conn->close();
?>