<?php
require 'db.php';
require 'deck_manager.php';

session_start();
$room_id = $_GET['room_id'];
$current_user_id = $_SESSION['user_id'] ?? 0;


// SPRAWDZAM CZY GRA JEST AKTYWNA / ISTNIEJE
$stmt = $conn->prepare('SELECT game_active, game_deck FROM rooms WHERE id = ?');
$stmt->bind_param('i', $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if(!$room) {
    die(json_encode(['success' => false, 'error' => 'Pokój nie istnieje']));
}

if(!$room['game_active']) {
    die(json_encode(['success' => false, 'error' => 'Gra nie jest aktywna']));
}

$deckManager = new DeckManager($conn);

try {
    // SPRAWDZAM CZY TALIA ISTNIEJE 
    if(empty($room['game_deck'])) {
        $newDeck = $deckManager->createNewDeck();
        $deckManager->saveDeckToRoom($room_id, $newDeck);
    }

    // POBIERAM PLAYERS 
    $stmt = $conn->prepare('SELECT id, name FROM users WHERE room_id = ?');
    $stmt->bind_param('i', $room_id);
    $stmt->execute();
    $players = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


    $playerHasCards = true;

    foreach($players as $player) {
        $existingCards = $deckManager -> getPlayerCards($player['id']);
        if(empty($existingCards)){
            $playerHasCards = false;
            break;
        }
    }

    // ROZDANIE KART JESLI GRACZ ICH NIE MA LUB MA W ZMIENNEJ KTORA POTEM IDZIE DO JSONA
    if(!$playerHasCards){ 
        $playersWithCards = $deckManager->dealCards($room_id, $players, 5);
    }else {
        $playersWithCards = [];
        

        
        foreach ($players as $player) {

            $cards = $deckManager->getPlayerCards($player['id']);

            if(!is_array($cards)){
                $cards = (array)$cards;
            }

            $playersWithCards[$player['id']] = [
                'player' => $player,
                'cards' => $cards
            ];
        }
    }
    
    
    echo json_encode([
        'success' => true,
        'playersWithCards' => $playersWithCards,
        'isNewDeal' => !$playerHasCards
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}