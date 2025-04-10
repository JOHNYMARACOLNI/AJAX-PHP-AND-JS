<?php
session_start();
require 'db.php'; 

$room_id = $_SESSION['room_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odliczanie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: rgb(178, 198, 195);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }
        h1 {
            margin-bottom: 20px;
        }
        .link {
            display: inline-block;
            font-size: 20px;
            color: rgb(13, 13, 59);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
        }
        .link:hover {
            background-color: #7d9fb7;
            color: white;
        }
    </style>
</head>
<body>
    <h1>GRA ROZPOCZNIE SIE ZA: </h1>
    <h1 id="counter"> </h1>
    <button class="link" id="przerwij">PRZERWIJ</button>
</body>
<script>
    let number = 3;
    let counterEl = document.getElementById("counter");
    let stopper = document.getElementById("przerwij");

    stopper.addEventListener('click', () => {


        // przekierowanie do pokoju z cancelem gry
        window.location.href = 'pokoj.php?room_id=<?php echo $room_id; ?>&cancel=1';
    });

    setInterval(() => {
        if(number >= 0) {
            counterEl.innerHTML = String(number);
            number = number - 1;
        } else { 
            fetch('activate_game.php?room_id=<?php echo $room_id; ?>')
            .then(() => {
                window.location.href = 'game.php?room_id=<?php echo $room_id; ?>';
            })
        }
    }, 1000);



    //sprawdzam czy ktorys z graczy nie anulowal gry co 500ms
    function checkIfCancelled() {
    fetch(`check_cancelled.php?room_id=<?php echo $room_id; ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.cancelled) {
                window.location.href = 'pokoj.php?room_id=<?php echo $room_id; ?>';
            }
        });
    }

    
    setInterval(checkIfCancelled, 500);


</script>
</html>