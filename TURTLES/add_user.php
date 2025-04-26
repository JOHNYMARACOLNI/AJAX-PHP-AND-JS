<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['imie']) && isset($_POST['wiek'])) {
        
        if (isset($_SESSION['room_id'])) {
            header("Location: mistake.html"); 
            exit(); 
        }

        $imie = trim($_POST['imie']);
        $wiek = (int)$_POST['wiek'];

        $kolory = ['red', 'blue', 'green', 'yellow', 'purple'];

        // Szukamy pokoju z mniej niż 5 graczami
        $stmt = $conn->prepare("SELECT rooms.id FROM rooms 
            LEFT JOIN users ON rooms.id = users.room_id 
            GROUP BY rooms.id 
            HAVING COUNT(users.id) < 5 
            LIMIT 1");

        if (!$stmt) {
            die("Błąd przygotowania zapytania: " . $conn->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $room_id = $row['id'];
        } else {
            // Tworzymy nowy pokój jeśli wszystkie są pełne
            $stmt = $conn->prepare("INSERT INTO rooms (name) VALUES (?)");
            if (!$stmt) {
                die("Błąd przygotowania zapytania: " . $conn->error);
            }

            $room_name = 'Pokój #' . uniqid();
            $stmt->bind_param('s', $room_name);
            if ($stmt->execute()) {
                $room_id = $conn->insert_id;
            } else {
                die("Błąd podczas tworzenia pokoju: " . $stmt->error);
            }
        }

        // Pobieramy zajęte kolory w pokoju
        $stmt = $conn->prepare("SELECT color FROM users WHERE room_id = ?");
        if (!$stmt) {
            die("Błąd przygotowania zapytania: " . $conn->error);
        }

        $stmt->bind_param('i', $room_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $zajete_kolory = [];
        while ($row = $result->fetch_assoc()) {
            $zajete_kolory[] = $row['color'];
        }

        // Wybieramy pierwszy dostępny kolor
        $dostepne_kolory = array_diff($kolory, $zajete_kolory);
        $kolor = reset($dostepne_kolory);

        // Dodajemy użytkownika z domyślnie ustawionym is_ready na 0 (false)
        $stmt = $conn->prepare("INSERT INTO users (name, age, room_id, color, is_ready) VALUES (?, ?, ?, ?, 0)");
        if (!$stmt) {
            die("Błąd przygotowania zapytania: " . $conn->error);
        }

        $stmt->bind_param('siis', $imie, $wiek, $room_id, $kolor);
        if ($stmt->execute()) {
            $id_gracza = $conn->insert_id;
            
            // Zapisujemy wszystkie potrzebne dane do sesji
            $_SESSION = [
                'room_id' => $room_id,
                'kolor' => $kolor,
                'user_id' => $id_gracza,
                'is_ready' => false, // Domyślnie gracz nie jest gotowy
                'imie' => $imie,
                'wiek' => $wiek
            ];
            
            header("Location: pokoj.php?room_id=".$room_id);
            exit();
        } else {
            echo "Błąd podczas dodawania użytkownika: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Nie podano wymaganych danych (imię lub wiek).";
    }

    $conn->close();
} else {
    echo "Formularz nie został wysłany metodą POST.";
}
?>