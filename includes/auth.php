<?php
session_start();

function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_auth() {
    check_auth();
}
?>
