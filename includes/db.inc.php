<?php
// Use Railway environment variables if available, otherwise fallback to localhost (Laragon)
$host   = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: 'localhost';
$dbname = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'freshguardai';
$user   = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
$pass   = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$port   = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($e->getCode() == 1049) {
        // Unknown database, connect without dbname and create it automatically
        try {
            $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

            $sql = file_get_contents(__DIR__ . '/../database.sql');
            if ($sql) {
                $pdo->exec($sql);
                // Reconnect explicitly with the newly created DB
                $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e2) {
            die("Database connection failed: " . $e2->getMessage());
        }
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>
