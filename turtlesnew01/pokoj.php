<?php
require 'db.php';
session_start();



if (isset($_GET['cancel'])) {
    $room_id = $_SESSION['room_id'] ?? 0;
    $stmt = $conn->prepare("UPDATE users SET is_ready = 0 WHERE room_id = ?");
    $stmt->bind_param('i', $room_id);
    $stmt->execute();
    
    $_SESSION['message'] = "Gra została anulowana";

    header("Location: pokoj.php?room_id=".$room_id);
    exit();
}



if (!isset($_SESSION['user_id']) || !isset($_SESSION['room_id'])) {
    header("Location: newform.html");
    exit();
}

$room_id = $_SESSION['room_id'];
$current_user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, name, age, color, is_ready FROM users WHERE room_id = ?");
$stmt->bind_param('i', $room_id);
$stmt->execute();
$result = $stmt->get_result();
$gracze = $result->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT name FROM rooms WHERE id = ?");
$stmt->bind_param('i', $room_id);
$stmt->execute();
$room_result = $stmt->get_result();
$room = $room_result->fetch_assoc();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokój <?php echo htmlspecialchars($room['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($room['name']); ?></h1>
    </div>

    <div class="toggle-container" >
        Gra w niepełnym składzie
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="readyToggle" 
                <?php echo ($_SESSION['is_ready'] ?? false) ? 'checked' : ''; ?>>
            
        </div>
        
    </div>

    <div class="gracze-container">
        <?php foreach ($gracze as $gracz): ?>
            <div class="gracz <?php echo ($gracz['id'] == $current_user_id) ? 'gracz-ja' : ''; ?>">
                <?php if ($gracz['is_ready']): ?>
                    <div class="ready-badge">✓</div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($gracz['name']); ?></h3>
                <p>Wiek: <?php echo htmlspecialchars($gracz['age']); ?></p>
                <p>Kolor: <?php echo htmlspecialchars($gracz['color']); ?></p>
                <p>
                    <span class="<?php echo $gracz['is_ready'] ? 'status-ready' : 'status-not-ready'; ?>">
                        <?php echo $gracz['is_ready'] ? 'Gotowy na grę' : 'Niegotowy na grę'; ?>
                    </span>
                </p>
                <p class="room-info">ID: <?php echo htmlspecialchars($gracz['id']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <script>


        document.getElementById('readyToggle').addEventListener('change', function() {
            const isReady = this.checked ? 1 : 0;
            
            fetch('update_readiness.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'is_ready=' + isReady
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => location.reload(), 500);
                }
            });
        });

        //ODSWIERZENIE GOTOWOSCI W ZALEZNOSCI OD ZMIAN INNYCH GRACZY
        let lastCheck = <?php echo time(); ?>;


        function checkForUpdates() {
            fetch(`check_changes.php?room_id=<?php echo $room_id; ?>&last_check=${lastCheck}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data)
                    if (data.changed) {
                        lastCheck = data.new_timestamp;
                        location.reload();
                    }
                    // Sprawdzaj ponownie po krótkim czasie (np. co 1 sekundę)
                    setTimeout(checkForUpdates, 1000);
                })
                .catch(error => {
                    console.error('Błąd:', error);
                    setTimeout(checkForUpdates, 2000); // Przy błędzie czekaj dłużej
                });
        }

        // czy moge rozpocząć gre
        setInterval(() => {
            fetch('check_start_game.php?room_id=<?php echo $room_id; ?>')
                .then(response => response.json())
                .then(data => {
                    console.log(data.can_start)
                    if (data.can_start) {
                        
                        window.location.href = 'odliczanie.php?room_id=<?php echo $room_id; ?>';
                    }
                });
        }, 1000);


        checkForUpdates();

    </script>
</body>
</html>