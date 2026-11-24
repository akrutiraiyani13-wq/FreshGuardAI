<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
require_once 'includes/db.inc.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, name, password, plan FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_plan'] = $user['plan'];
            syncUserNotifications($pdo, (int)$user['id']);
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FreshGuardAI</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f4f7f6; margin: 0; padding: 16px; }
        .auth-container { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); width: 100%; max-width: 420px; }
        .auth-container h2 { margin-bottom: 20px; color: #1F6B4F; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .error { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; padding: 10px; border-radius: 6px; text-align: center; margin-bottom: 15px; font-size: 0.9rem; }
        .auth-links { margin-top: 18px; text-align: center; font-size: 0.9rem; }
        .auth-links a { color: #1F6B4F; font-weight: 600; text-decoration: none; }
        .auth-links a:hover { text-decoration: underline; }
        .demo-box { margin-top: 15px; padding: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; font-size: 0.85rem; text-align: center; }
    </style>
</head>
<body>

<div class="auth-container">
    <h2>Login to FreshGuardAI</h2>
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn" style="width:100%; margin-top: 8px;">Login</button>
    </form>

    <div class="demo-box">
        <strong>Demo Account:</strong> demo@freshguardai.com / password<br>
        <button type="button" onclick="document.getElementById('email').value='demo@freshguardai.com'; document.getElementById('password').value='password';" style="margin-top:8px; padding:5px 12px; border:1px solid #1F6B4F; background:#fff; color:#1F6B4F; border-radius:999px; cursor:pointer; font-weight:600;">Fill Demo Credentials</button>
    </div>

    <div class="auth-links">
        <p>Don't have an account? <a href="register.php">Register</a></p>
        <p><a href="index.php">Back to Home</a></p>
    </div>
</div>

</body>
</html>
