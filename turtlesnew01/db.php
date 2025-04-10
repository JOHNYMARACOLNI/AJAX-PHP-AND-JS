<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "moja_baza";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Połączenie nieudane: " . $conn->connect_error);
}
?>