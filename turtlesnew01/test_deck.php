<?php
require 'db.php';
require 'deck_manager.php';

// Helper function do wyświetlania danych
function print_data($label, $data) {
    echo "<h3>$label</h3><pre>" . print_r($data, true) . "</pre><hr>";
}

// 1. Inicjalizacja DeckManager
$deckManager = new DeckManager($conn);
print_data("DeckManager zainicjalizowany", get_class($deckManager));

// 2. Test createNewDeck()
$newDeck = $deckManager->createNewDeck();
print_data("Nowa talia (createNewDeck)", [
    'count' => count($newDeck),
    'sample' => array_slice($newDeck, 0, 3)
]);

// 3. Test saveDeckToRoom() i getDeckFromRoom()
$room_id = 4; // Testowy pokój
$deckManager->saveDeckToRoom($room_id, $newDeck);
$savedDeck = $deckManager->getDeckFromRoom($room_id);
print_data("Talia zapisana i odczytana z bazy", [
    'count' => count($savedDeck),
    'is_same' => $newDeck == $savedDeck
]);

// 4. Test dealCards()
$testPlayers = [
    ['id' => 101, 'name' => 'Test Player 1'],
    ['id' => 102, 'name' => 'Test Player 2']
];

$dealtCards = $deckManager->dealCards($room_id, $testPlayers, 5); // 3 karty na gracza
print_data("Rozdane karty (dealCards)", $dealtCards);

// // 5. Test zarządzania kartami graczy
// $player_id = 101;
// $playerCards = $deckManager->getPlayerCards($player_id);
// print_data("Karty gracza przed operacjami", $playerCards);

// // Dodaj kartę
// $newCard = ['id' => 'test_card', 'type' => 'test', 'value' => 0, 'used' => true];
// $deckManager->addCardToPlayer($player_id, $newCard);
// print_data("Karty po dodaniu", $deckManager->getPlayerCards($player_id));

// // Usuń kartę
// $removedCard = $deckManager->removeCardFromPlayer($player_id, 0);
// print_data("Usunięta karta", $removedCard);
// print_data("Karty po usunięciu", $deckManager->getPlayerCards($player_id));

// echo "<h2>Testy zakończone</h2>";