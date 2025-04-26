<?php
session_start(); // Rozpocznij sesję
header('Content-Type: application/json'); // Ustaw nagłówek JSON
require 'db.php';

// Odbierz dane z fetch
$data = json_decode(file_get_contents("php://input"), true);

// Sprawdź, czy podano imię
if (!isset($data['name']) || empty(trim($data['name']))) {
    echo json_encode(["success" => false, "error" => "Nie podano imienia."]);
    exit;
}

$name = trim($data['name']); // Pobierz imię

// Sprawdź, czy gracz już istnieje w bazie (na podstawie sesji)
$session_id = session_id(); // Unikalny identyfikator sesji
$result = $conn->query("SELECT id FROM users WHERE session_id = '$session_id'");
if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "Możesz dodać tylko jednego gracza na przeglądarkę."]);
    exit;
}

// Sprawdź, czy istnieje aktywny pokój
$result = $conn->query("SELECT id FROM rooms ORDER BY id DESC LIMIT 1");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $room_id = $row['id']; // Pobierz ID ostatniego pokoju
} else {
    // Jeśli nie ma pokoju, stwórz nowy
    $conn->query("INSERT INTO rooms (name) VALUES ('Pokój #1')");
    $room_id = $conn->insert_id; // Pobierz ID nowego pokoju
}

// Dodaj gracza do bazy
$stmt = $conn->prepare("INSERT INTO users (name, room_id, session_id) VALUES (?, ?, ?)");
$stmt->bind_param("sis", $name, $room_id, $session_id); // Wiąż parametry
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "room_id" => $room_id]); // Sukces
} else {
    echo json_encode(["success" => false, "error" => "Błąd dodawania gracza."]); // Błąd
}

$stmt->close();
$conn->close();
?>