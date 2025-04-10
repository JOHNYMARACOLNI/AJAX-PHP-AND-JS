<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['room_id'])) {
    header("Location: newform.html");
    exit();
}

$room_id = $_SESSION['room_id'];
$current_user_id = $_SESSION['user_id'];
$current_kolor = $_SESSION['kolor'];

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
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
            gap: 10px;  
            margin-bottom: 20px;  
            max-width: 800px;  
            margin-left: auto;
            margin-right: auto;
        }
        .gracz {
            margin-left: 5px;
            background-color: #2c2c2c;
            border-radius: 8px;  
            padding: 10px; 
            flex: 1;
            min-width: 150px;  
            max-width: 200px;  
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);  
            transition: transform 0.3s ease;
            position: relative;
        }
        .gracz:hover {
            transform: translateY(-5px);
            background-color: #383838;
        }
        .gracz h3 {
            color: white;
            margin: 0 0 8px 0;  
            font-size: 1rem;  
        }
        .gracz p {
            color: white;
            margin: 3px 0;  
            font-size: 0.85rem;  
        }
        .room-info {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #444;
            color: #6c757d;
            font-style: italic;
            font-size: 0.75rem;
        }
        h3 {
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
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 0 5px rgba(0,0,0,0.5);
            font-size: 0.8rem;
        }
        .cards-container {
            background-color: #1e1e1e;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        .card {
            width: 150px;
            height: 230px;
            background-image: url('turtles.gif');
            background-repeat: no-repeat;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: relative;
        }
        #board {
            margin-left: 40vw;
            width: 660px;
            height: 1328px;
            background-image: url('turtles.gif');
            background-repeat: no-repeat;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: relative;
        }
        #game {
            display: flex;
            
            margin-top: 20px;
        }
        .turtle {
            
            z-index: 10;
        }

        .turtle:hover {
            transform: translateY(-5px);
            filter: drop-shadow(0 0 5px gold);
        }
        #realColor {
            width: 150px;
            height: 150px;
            background-image: url('turtles.gif');
            background-repeat: no-repeat;
            border-radius: 8px;
            position: relative;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3 id="gameinfocontainer">
            <?php echo $room['game_active'] ? 'TRWA GRA' : 'GRA ZAKOŃCZONA'; ?>
        </h3>
    </div>
    

    <div class="gracze-container">
        <?php foreach ($gracze as $gracz): ?>
            <div class="gracz <?php echo ($gracz['id'] == $current_user_id) ? 'gracz-ja' : ''; ?>"
                id="player-<?php echo $gracz['id']; ?>"
                 style="position: relative;">
                <?php if ($gracz['id'] == $room['current_player_id'] && $room['game_active']): ?>
                    <div class="timer-container" id="timer-<?php echo $gracz['id']; ?>"></div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($gracz['name']); ?></h3>
                <p>Wiek: <?php echo htmlspecialchars($gracz['age']); ?></p>
                <p class="room-info">ID: <?php echo $gracz['id']; ?></p>
            </div>
        <?php endforeach; ?>
    </div>


            
   


    <div class="cards-container" id="playerCards">
    </div>

    
    
    <div id="game">
        <div id="realColor"></div>
        <div id="board">
        </div>
    </div>



    <script>
    const roomId = <?php echo $room_id; ?>;
    const currentUserId = <?php echo json_encode($current_user_id ); ?>;
    const kolor = <?php echo json_encode($current_kolor ); ?>;
    let spriteData = {};
    let timerExpired = false;
    let currentPlayerId = null;

    // Inicjalizacja gry po załadowaniu strony
    document.addEventListener('DOMContentLoaded', function() {
        loadSpriteData().then(() => {
            initGame();
            
        });
    });

    // Ładowanie danych sprite'ów
    async function loadSpriteData() {
        try {
            const response = await fetch('card_positions.json');
            spriteData = await response.json();
        } catch (error) {
            console.error('Błąd ładowania danych sprite:', error);
        }
    }

    // Inicjalizacja gry
    function initGame() {
        fetchCards();
        updateTimers();
        drawTurtles();
        showBoard();
        showColor()
        
        // Ustawienie interwałów
        setInterval(updateTimers, 1000);
        setInterval(fetchCards, 5000); // Odświeżanie kart co 5 sekund
    }

    // Pobieranie kart gracza
    let isFetching = false;
    async function fetchCards() {
        if(isFetching) return;
        isFetching = true;
        try {
            const response = await fetch(`deal_cards.php?room_id=${roomId}`);
            const data = await response.json();
            
            if(data.success) {
                showCards(data);
                
                
                if(data.isNewDeal) {
                    drawTurtles();
                    showBoard();
                }
            }
        } catch (error) {
            console.error('Błąd odświeżania kart:', error);
        } finally {
            isFetching = false;
        }
    }

    // wyświetlanie kart gracza
    function showCards(data) {
        const cardsContainer = document.getElementById('playerCards');
        cardsContainer.innerHTML = '';
        
        if(data.playersWithCards && data.playersWithCards[currentUserId]) {
            const playerCards = data.playersWithCards[currentUserId].cards;
            
            playerCards.forEach(card => {
                const cardElement = document.createElement('div');
                cardElement.classList.add('card');

                //WARTOSCI KART ZEBY PO KLIKNIECIU JE WYSLAC DO SERWERA 
                cardElement.dataset.cardId = card.id;
                cardElement.dataset.cardValue = card.value;
                cardElement.dataset.cardType = card.type;
                cardElement.dataset.cardColor = card.color || kolor;

                
                const spriteInfo = spriteData[card.id];
                if (spriteInfo) {
                    cardElement.style.backgroundPosition = `-${spriteInfo.x}px -${spriteInfo.y}px`;
                } else {
                    cardElement.textContent = `${card.value} ${card.type}`;
                    console.warn(`Nie znaleziono pozycji dla karty: ${card.id}`);
                }

                cardElement.addEventListener('click', handleCardClick)

                cardsContainer.appendChild(cardElement);
            });
        }
    }


    let cardProcesing = false;
    function handleCardClick(event) {
        if (cardProcesing) return;
        
        const cardElement = event.currentTarget;
        cardProcesing = true;

        // animacja kliknieciia
        gsap.to(cardElement, {
            scale: 0.9,
            duration: 0.2,
            yoyo: true,
            repeat: 1,
            onComplete: () => {
                // reszta logiki klikniecia
                cardElement.style.opacity = '0.5';
                
                const cardData = {
                    id: cardElement.dataset.cardId,
                    value: cardElement.dataset.cardValue,
                    type: cardElement.dataset.cardType,
                    color: cardElement.dataset.cardColor,
                    roomId: roomId,
                    playerId: currentUserId
                };

                fetch('play_card.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(cardData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchCards();
                    }
                })
                .finally(() => {
                    cardProcesing = false;
                    cardElement.style.opacity = '1';
                });
            }
        });
    }

    // rysowanie planszy
    function showBoard() {
        const boardContainer = document.getElementById('board');
        if(boardContainer && spriteData.board) {
            boardContainer.style.backgroundPosition = `-${spriteData.board.x}px -${spriteData.board.y}px`;
            boardContainer.style.width = `${spriteData.board.width}px`;
            boardContainer.style.height = `${spriteData.board.height}px`;
        }
    }

    // rysowanie żółwi
    function drawTurtles() {
        const board = document.getElementById('board');
        if (!board) return;
        
        board.innerHTML = '';
        const players = <?php echo json_encode($gracze); ?>;
        
        players.forEach((player, index) => {
            const turtleKey = `player_${player.color.toLowerCase()}`;
            const turtleData = spriteData[turtleKey];
            
            if(!turtleData) {
                console.warn(`Brak danych dla żółwia: ${turtleKey}`);
                return;
            }
            
            const turtle = document.createElement('div');
            turtle.className = 'turtle';
            turtle.id = `turtle-${player.id}`;
            turtle.dataset.color = player.color;
            
            Object.assign(turtle.style, {
                position: 'absolute',
                bottom: '0',
                right: `${100 * index}px`,
                width: `${turtleData.width}px`,
                height: `${turtleData.height}px`,
                backgroundImage: "url('turtles.gif')",
                backgroundPosition: `-${turtleData.x}px -${turtleData.y}px`,
                zIndex: 10
            });
            
            board.appendChild(turtle);
        });
    }

    function showColor(){
        console.log(kolor)
        let div = document.getElementById("realColor");
        div.innerText = String(kolor);
        
        const turtleData = spriteData[`color_${kolor}`];
            
        console.log("aaaaaaaaaaaaaaaaaaaaa",turtleData)
        Object.assign(div.style, {
            width: `${turtleData.width}px`,
            height: `${turtleData.height}px`,
            backgroundImage: "url('turtles.gif')",
            backgroundPosition: `-${turtleData.x}px -${turtleData.y}px`,
        });
            
    }

 

    // Aktualizacja timerów
    function updateTimers() {
        fetch(`get_game_state.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                console.log(timerExpired)
                //console.log(data.current_player_id)
                console.log(data)
                document.getElementById('gameinfocontainer').innerText = 
                    data.game_active ? 'TRWA GRA' : 'GRA ZAKOŃCZONA';
                
                    if (data.game_active && data.current_player_id) {
                        timerExpired = false;
                        const now = new Date().getTime();
                        const endTime = new Date(data.turn_ends_at).getTime();
                        const secondsLeft = Math.max(0, Math.floor((endTime - now) / 1000));
                        
                        //Ukryj wszystkie timery
                        // document.querySelectorAll('.timer-container').forEach(timer => {
                        //     timer.style.display = 'none';
                        // });
                        
                        // Pokaż timer dla aktualnego gracza
                        // Usuń wszystkie istniejące timery
                        document.querySelectorAll('.timer-container').forEach(timer => timer.remove());
                        
                        // Znajdź element gracza, który jest aktualnym graczem
                        const playerElement = document.getElementById(`player-${data.current_player_id}`);
                        
                        if (playerElement) {
                            // Stwórz nowy timer
                            const timerElement = document.createElement('div');
                            timerElement.className = 'timer-container';
                            timerElement.id = `timer-${data.current_player_id}`;
                            timerElement.innerText = secondsLeft;
                            
                            // Dodaj timer do elementu gracza
                            playerElement.appendChild(timerElement);
                            
                            if (secondsLeft <= 0 && !timerExpired) {
                                timerExpired = true;
                                
                                fetchCards();
                                
                            } else if (secondsLeft > 0) {
                                timerExpired = false;
                            }
                        }
                    }
            })
            .catch(error => console.error('Błąd aktualizacji timerów:', error));
    }
</script>
</body>
</html>