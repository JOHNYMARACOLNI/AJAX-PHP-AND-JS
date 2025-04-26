<?php
require 'db.php';

class DeckManager {
    private  $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function createNewDeck() {
        $fullDeck = json_decode(file_get_contents('full_deck.json'), true);
        $deck = [];

        foreach ($fullDeck['cards'] as $card) {
            for ($i=0; $i < $card['count']; $i++) { 
                $deck[] = [
                    'id' => $card['id'],
                    'type' => $card['type'],
                    'value' => $card['value'],
                    'used' => false
                ];
            }
        }

        shuffle($deck);
        return $deck;
        
    }




    public function saveDeckToRoom($room_id, $deck) {
        $deckJson = json_encode($deck);
        $stmt = $this->conn->prepare("UPDATE rooms SET game_deck = ? WHERE id = ?");;
        $stmt->bind_param('si', $deckJson, $room_id);
        return $stmt->execute();
    }


    public function getDeckFromRoom($room_id){
        $stmt = $this->conn->prepare("SELECT game_deck FROM rooms WHERE id= ? ");
        $stmt->bind_param('i', $room_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows >0){
            $row = $result->fetch_assoc();
            return json_decode($row['game_deck'], true);

        }
    }

    public function dealCards($room_id, $players, $cards_per_player) {
        $deck = $this->getDeckFromRoom($room_id);
        $playersWithCards = [];

        foreach ($players as $player) {
            $playerCards = [];
            
            for ($i=0; $i < $cards_per_player; $i++) { 
                if(!empty($deck)) {
                    $card = array_shift($deck);
                    $card['used'] = true;
                    $playerCards[] = $card;
                }
            }

            // Zapisz karty gracza do jego rekordu w users
            $this->savePlayerCards($player['id'], $playerCards);
            
            $playersWithCards[$player['id']] = [
                'player' => $player,
                'cards' => $playerCards
            ];
        }

        $this->saveDeckToRoom($room_id, $deck);
        return $playersWithCards;
    }

    public function savePlayerCards($user_id, $cards) {
        $stmt = $this->conn->prepare("UPDATE users SET player_cards = ? WHERE id = ?");
        $json_cards = json_encode($cards);
        $stmt->bind_param('si', $json_cards, $user_id);
        return $stmt->execute();
    }

    public function getPlayerCards($user_id) {
        $stmt = $this->conn->prepare("SELECT player_cards FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return json_decode($row['player_cards'], true) ?: [];
        }
        return [];
    }

    public function addCardToPlayer($user_id, $room_id) {
        $deck = $this->getDeckFromRoom($room_id);
    
        if (empty($deck)) {
            return false; 
        }
        
        
        $playerCards = $this->getPlayerCards($user_id);
        
        
        $newCard = array_shift($deck);
        $newCard['used'] = true;
        
        
        $playerCards[] = $newCard;
        
        
        $this->saveDeckToRoom($room_id, $deck);
        
        
        $this->savePlayerCards($user_id, $playerCards);
        
        return $newCard;
    }



    public function removeCardFromPlayer($user_id, $card_id) {
        $playerCards = $this->getPlayerCards($user_id);
        
        $cardIndex = null;
        foreach ($playerCards as $index => $card) {
            if ($card['id'] == $card_id) {
                $cardIndex = $index;
                break;
            }
        }
        
        
        if ($cardIndex !== null) {
            array_splice($playerCards, $cardIndex, 1);
            $this->savePlayerCards($user_id, $playerCards);
            return true;
        }
        
        return false;
    }
    

}





