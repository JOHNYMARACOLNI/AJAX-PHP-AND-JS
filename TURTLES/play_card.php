<?php
require 'db.php';
require 'deck_manager.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);

$room_id = $data['roomId'];
$player_id = $data['playerId'];
$card_id = $data['id'];
$card_value = $data['value'];
$color = $data['color'];

$stmt = $conn->prepare("SELECT game_state FROM rooms WHERE id = ?");
$stmt->bind_param('i', $room_id);
$stmt->execute();
$result = $stmt->get_result();
$game_state = $result->num_rows > 0 ? json_decode($result->fetch_assoc()['game_state'], true) : [];

if (empty($game_state['positions'])) {
    $game_state['positions'] = [0 => []];
}

$found_turtle = false;
$updated_turtles = [];
$new_field = 0;

// SZUKANIE ZOLWIA W KOLORZE KARTY
foreach ($game_state['positions'] as $field => &$turtles) {
    foreach ($turtles as $key => $turtle) {
        if ($turtle['color'] === $color) {
            $found_turtle = true;
            $new_field = min(10, $field + $card_value);
            
            // POZYCJA WIEKSZA OD 1 BO INACZEJ NIE MOGE ODJAC BARDZIEJ
            if($new_field < 1) {
                echo json_encode([
                    'success' => false,
                    "error" => 'Żółw o danym kolorze nie może się już bardziej cofnąć'
                ]);
                exit;
            }
            
            // TABLICA ZOLWI DO PRZESUNIECIA
            $turtles_to_move = [];
            
            // JESLI OSTATNI W TABLICY
            if ($key === count($turtles) - 1) {
                $turtles_to_move = [array_splice($turtles, $key, 1)[0]];
            } 
            // JESLI GDZIES W SRODKU 
            else {
                $turtles_to_move = array_splice($turtles, $key);
            }
            
            // DODANIE PUSTEJ TABLICY NA NOWYM MIEJSCU JESLI NIE JEST ISTNIEJACA
            if (!isset($game_state['positions'][$new_field])) {
                $game_state['positions'][$new_field] = [];
            }
            
            // DODAJE ZOLWIE DO NOWEJ POZYCJI
            foreach ($turtles_to_move as $moving_turtle) {
                array_push($game_state['positions'][$new_field], $moving_turtle);
            }
            
            $updated_turtles = $game_state['positions'][$new_field];
            break 2;
        }
    }
}
unset($turtles);

if(!$found_turtle){
    echo json_encode([
        'success' => false,
        "error" => 'Nie ma żółwia w kolorze tej karty'
    ]);
    exit;
}

// USUNIECIE KARTY
$deckManager = new DeckManager($conn);
$card_removed = $deckManager->removeCardFromPlayer($player_id, $card_id);

if (!$card_removed) {
    echo json_encode([
        'success' => false,
        "error" => 'Nie znaleziono karty do usunięcia'
    ]);
    exit;
}

$deckManager->addCardToPlayer($player_id, $room_id);

$parsedGameState = json_encode($game_state);
$stmt = $conn->prepare("UPDATE rooms SET game_state = ? WHERE id = ?");
$stmt->bind_param('si', $parsedGameState, $room_id);
$stmt->execute();

echo json_encode([
    'success' => true,
    'newPosition' => $new_field,
    'gameState' => $game_state,
    'color' => $color,
    'updatedTurtles' => $updated_turtles
]);