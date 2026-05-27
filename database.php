<?php
session_start(); 

$host = "localhost";
$db_name = "voyagevista";
$username = "root";
$password = "root";
$conn = null;

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    echo "Erreur de connexion : " . $exception->getMessage();
}
?>