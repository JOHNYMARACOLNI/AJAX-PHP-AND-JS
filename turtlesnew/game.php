<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['room_id'])) {
    header("Location: newform.html");
    exit();
}

$room_id = $_SESSION['room_id'];
$current_user_id = $_SESSION['user_id'];

// INFO O POKOJU
$stmt = $conn->prepare("
    SELECT r.name, r.game_active, r.current_player_id, r.turn_ends_at,
           u.name as current_player_name
    FROM rooms r
    LEFT JOIN users u ON r.current_player_id = u.id
    WHERE r.id = ?
");
$stmt->bind_param('i', $room_id);
$stmt->execute();
$room_result = $stmt->get_result();
$room = $room_result->fetch_assoc();

// POBIERAM GRACZY, INFO O GRACZACH DO ITERACJI PRZEZ TABLICE ASOCAJCYJNA
$stmt = $conn->prepare("SELECT id, name, age, color FROM users WHERE room_id = ?");
$stmt->bind_param('i', $room_id);
$stmt->execute();
$gracze = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    
    <style>
        body {
            background-color: #121212;
            color: white;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .header {
            margin-bottom: 30px;
        }
        .gracze-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        .gracz {
            background-color: #2c2c2c;
            border-radius: 10px;
            padding: 15px;
            flex: 1;
            min-width: 200px;
            max-width: 300px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            position: relative;
        }
        .gracz:hover {
            transform: translateY(-5px);
            background-color: #383838;
        }
        .gracz h3 {
            margin: 0 0 10px 0;
            color: #f8f9fa;
            font-size: 1.2rem;
        }
        .gracz p {
            margin: 5px 0;
            color: #adb5bd;
        }
        .room-info {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #444;
            color: #6c757d;
            font-style: italic;
        }
        h1 {
            color: #f8f9fa;
            margin-bottom: 20px;
            text-align: center;
        }
        .content {
            background-color: #1e1e1e;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .ready-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #28a745;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .toggle-container {
            background-color: #2c2c2c;
            padding: 10px;
            border-radius: 10px;
            max-width: 300px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            margin-left: 0;
        }

        .gracz-ja {
            border: 2px solid gold;
            box-shadow: 0 0 10px gold;
        }
        .status-ready {
            color: #28a745;
            font-weight: bold;
        }
        .status-not-ready {
            color: #dc3545;
            font-weight: bold;
        }
        #readyStatus {
            margin-right: 7px;
        }
        .timer-container {
            position: absolute;
            top: -15px;
            right: -15px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 0 5px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 id="gameinfocontainer">
            <?php echo $room['game_active'] ? 'TRWA GRA' : 'GRA ZAKOŃCZONA'; ?>
        </h1>
    </div>
    

    <div class="gracze-container">
        <?php foreach ($gracze as $gracz): ?>
            <div class="gracz <?php echo ($gracz['id'] == $current_user_id) ? 'gracz-ja' : ''; ?>"
                 style="position: relative;">
                <?php if ($gracz['id'] == $room['current_player_id'] && $room['game_active']): ?>
                    <div class="timer-container" id="timer-<?php echo $gracz['id']; ?>"></div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($gracz['name']); ?></h3>
                <p>Wiek: <?php echo htmlspecialchars($gracz['age']); ?></p>
                <p>Kolor: <span style="color: <?php echo $gracz['color']; ?>"><?php echo $gracz['color']; ?></span></p>
                <p class="room-info">ID: <?php echo $gracz['id']; ?></p>
            </div>
        <?php endforeach; ?>
    </div>

<script>
    // Zmienna do śledzenia czy timer osiągnął zero
    let timerExpired = false;

    function updateTimers() {
        fetch('get_game_state.php?room_id=<?php echo $room_id; ?>')
            .then(response => response.json())
            .then(data => {
                // Aktualizacja stanu gry
                console.log(data);
                document.getElementById('gameinfocontainer').innerText = 
                    data.game_active ? 'TRWA GRA' : 'GRA ZAKOŃCZONA';
                
                if (data.game_active && data.current_player_id) {
                    const now = new Date().getTime();
                    const endTime = new Date(data.turn_ends_at).getTime();
                    const secondsLeft = Math.max(0, Math.floor((endTime - now) / 1000));
                    
                    // Znajdź element timera
                    const timerElement = document.getElementById(`timer-${data.current_player_id}`);
                    
                    if (timerElement) {
                        // Jeśli czas się skończył
                        if (secondsLeft <= 0) {
                            timerElement.innerText = '0';
                            
                            // Jeśli timer jeszcze nie został oznaczony jako wygasły
                            if (!timerExpired) {
                                timerExpired = true;
                                
                                // Ukryj timer po 1 sekundzie
                                setTimeout(() => {
                                    timerElement.style.display = 'none';
                                    
                                    // Odśwież stronę po kolejnej sekundzie
                                    setTimeout(() => {
                                        location.reload();
                                    }, 1000);
                                }, 1000);
                            }
                        } 
                        else {
                            // Normalne odliczanie
                            timerElement.innerText = secondsLeft;
                            timerElement.style.display = 'flex';
                            timerExpired = false;
                        }
                    }
                    
                    // Ukryj timery innych graczy
                    document.querySelectorAll('.timer-container').forEach(timer => {
                        if (!timer.id.includes(data.current_player_id)) {
                            timer.style.display = 'none';
                        }
                    });
                }
            });
    }

    // Uruchom timer
    setInterval(updateTimers, 1000);
    updateTimers();
</script>
</body>
</html>