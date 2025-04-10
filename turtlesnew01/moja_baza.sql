-- Tworzenie bazy danych
CREATE DATABASE IF NOT EXISTS turtle_game;
USE turtle_game;

-- Tabela pokoi
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    game_active BOOLEAN DEFAULT FALSE,
    current_player_id INT DEFAULT NULL,
    turn_ends_at DATETIME DEFAULT NULL,
    game_deck TEXT DEFAULT NULL, -- Przechowuje stan talii jako JSON
    game_state TEXT DEFAULT NULL -- Przechowuje stan gry jako JSON
);

-- Tabela użytkowników
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    room_id INT NOT NULL,
    color VARCHAR(50) NOT NULL,
    is_ready BOOLEAN DEFAULT FALSE,
    player_cards TEXT DEFAULT NULL, -- Dodane zgodnie z DeckManager
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Aktualizacja tabeli rooms po utworzeniu tabeli users
ALTER TABLE rooms
ADD CONSTRAINT fk_current_player
FOREIGN KEY (current_player_id) REFERENCES users(id) ON DELETE SET NULL;

-- Indeksy dla poprawy wydajności
CREATE INDEX idx_room_id ON users(room_id);
CREATE INDEX idx_game_active ON rooms(game_active);