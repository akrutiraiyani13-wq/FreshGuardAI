<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("DROP DATABASE IF EXISTS freshguardai;");
    echo "Database successfully dropped. Please visit the homepage (index.php) and the system will exactly recreate it holding the new schema!";
} catch (PDOException $e) {
    die("Error dropping database: " . $e->getMessage());
}
?>
