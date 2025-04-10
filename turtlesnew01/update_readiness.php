<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['is_ready']) && isset($_SESSION['user_id'])) {
    $is_ready = (int)$_POST['is_ready'];
    $user_id = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE users SET is_ready = ? WHERE id = ?");
    $stmt->bind_param('ii', $is_ready, $user_id);

    if ($stmt->execute()) {
        $_SESSION['is_ready'] = (bool)$is_ready;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    
    $stmt->close();

} else {
    echo json_encode(['success' => false, 'error' => 'Nieprawidłowe żądanie']);
}

$conn->close();

?>

