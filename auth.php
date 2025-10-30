<?php
session_start();
require_once 'config.php';
 
function register($pdo, $username, $email, $password, $role) {
    $password = password_hash(trim($password), PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['role'] = $role;
        header('Location: index.php?page=dashboard');
        exit;
    } catch (PDOException $e) {
        echo "<p class='text-danger'>Registration failed: " . $e->getMessage() . "</p>";
    }
}
 
function login($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        echo "<p class='text-danger'>Invalid login credentials.</p>";
    }
}
 
function logout() {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
