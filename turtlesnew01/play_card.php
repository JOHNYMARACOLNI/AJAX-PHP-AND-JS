<?php
require 'db.php';
require 'deck_manager.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);

$room_id = $data['roomId'];
$player_id = $data['playerId'];
$card_id = $data['id'];
$card_value = $data['value'];


$stmt = $conn->prepare("SELECT game_state FROM rooms WHERE id = ?");
$stmt->bind_param('i', $room_id);
$stmt->execute();
$result = $stmt->get_result();
$game_state = $result->num_rows > 0 ? json_decode($result->fetch_assoc()['game_state'], true) : [];



//NOWA POZYCJA

if (empty($game_state['positions'])) {
    $game_state['positions'] = [];
}

foreach ($game_state['positions'] as &$pos) {
    if ($pos['player_id'] == $player_id) {
        $new_field = min(10, $pos['field'] + $card_value); 
        $pos['field'] = $new_field;
        break;
    }
}
unset($pos);

// USUNIECIE KARTY
$deckManager = new DeckManager($conn);
$player_cards = $deckManager->getPlayerCards($player_id);
$player_cards = array_filter($player_cards, fn($card) => $card['id'] != $card_id);
$deckManager->savePlayerCards($player_id, $player_cards);



$parsedGameState = json_encode($game_state);
//NOWY STAN
$stmt = $conn->prepare("UPDATE rooms SET game_state = ? WHERE id = ?");
$stmt->bind_param('si', $parsedGameState, $room_id);
$stmt->execute();


$finalData = array_filter($game_state['positions'],fn($p) => $p['player_id'] == $player_id)[0]['color'];

//WYSYLAM DANE DO FETCHA
echo json_encode([
    'success' => true,
    'newPosition' => $new_field,
    'color' => $finalData
]);
?>