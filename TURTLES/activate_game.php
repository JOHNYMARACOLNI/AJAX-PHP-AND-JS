<?php
require 'db.php';
require 'deck_manager.php';

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
    // POBIERAM LISTĘ GRACZY DO INICJALIZACJI STANU GRY
    $stmt = $conn->prepare("SELECT id, color FROM users WHERE room_id = ?");
    $stmt->bind_param('i', $room_id);
    $stmt->execute();
    $gracze = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // INICJALIZACJA STANU GRY - NOWA STRUKTURA
    $initialState = [
        'positions' => [
            1 => array_map(function($player) {
                return [
                    'player_id' => $player['id'],
                    'color' => $player['color']
                ];
            }, $gracze)
        ],
        'current_turn' => 1,
        'last_move' => null,
        'game_started' => true
    ];

    // PIERWSZY GRACZ
    $first_player = $conn->query("SELECT id FROM users WHERE room_id = $room_id ORDER BY id LIMIT 1")->fetch_assoc();
    
    // AKTYWACJA GRY Z INICJALNYM STANEM
    $stmt = $conn->prepare("UPDATE rooms SET 
        game_active = 1,
        game_state = ?,
        current_player_id = ?,
        turn_ends_at = DATE_ADD(NOW(), INTERVAL 30 SECOND)
        WHERE id = ?");
    
    $game_state_json = json_encode($initialState);
    $stmt->bind_param('sii', $game_state_json, $first_player['id'], $room_id);
    $stmt->execute();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}