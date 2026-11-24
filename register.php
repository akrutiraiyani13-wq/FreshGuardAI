<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
require_once 'includes/db.inc.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, plan) VALUES (?, ?, ?, 'Free')");
            if ($stmt->execute([$name, $email, $hashed_password])) {
                $new_id = (int)$pdo->lastInsertId();

                $starterStmt = $pdo->prepare("
                    INSERT INTO food_items (user_id, name, category, quantity, purchase_date, expiry_date, storage_location, notes, image, status)
                    VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?, ?, ?, ?)
                ");
                $starters = [
                    [$new_id, 'Farm Fresh Milk', 'Dairy', '1 Gallon', -1, 2, 'Fridge', 'For coffee & cereal', guessFoodImage('milk', 'Dairy'), 'Use Soon'],
                    [$new_id, 'Organic Eggs (Dozen)', 'Dairy', '12 pcs', -3, 10, 'Fridge', 'Breakfast', guessFoodImage('eggs', 'Dairy'), 'Fresh'],
                    [$new_id, 'Cherry Tomatoes', 'Vegetables', '1 Box', -5, 2, 'Fridge', 'Salad & curry', guessFoodImage('tomatoes', 'Vegetables'), 'Use Soon'],
                    [$new_id, 'Raw Potatoes', 'Vegetables', '1 kg', -2, 14, 'Pantry', 'Store cool and dry', guessFoodImage('potatoes', 'Vegetables'), 'Fresh'],
                    [$new_id, 'Chicken Breast', 'Meat', '2 lbs', -1, 4, 'Fridge', 'Marinate tonight', guessFoodImage('chicken', 'Meat'), 'Fresh'],
                    [$new_id, 'Greek Yogurt Plain', 'Dairy', '400 g', -4, 1, 'Fridge', 'Use before tomorrow', guessFoodImage('yogurt', 'Dairy'), 'Use Soon'],
                    [$new_id, 'Washington Apples', 'Fruits', '6 pcs', -2, 9, 'Fridge', 'Crisper drawer', guessFoodImage('apples', 'Fruits'), 'Fresh'],
                    [$new_id, 'Fresh Artisan Bread', 'Bakery', '1 Loaf', -8, -1, 'Counter', 'Expired - replace soon', guessFoodImage('bread', 'Bakery'), 'Expired'],
                ];
                foreach ($starters as $s) {
                    $starterStmt->execute($s);
                }

                $repStmt = $pdo->prepare("INSERT INTO replacement_list (user_id, food_name, quantity, category, notes, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
                $repStmt->execute([$new_id, 'Fresh Artisan Bread', '1 Loaf', 'Bakery', 'Replace expired bread']);
                $repStmt->execute([$new_id, 'Orange Juice', '1 Carton', 'Beverages', 'Breakfast essential']);
                $repStmt->execute([$new_id, 'Baby Spinach', '1 Bag', 'Vegetables', 'Fresh greens for salad']);

                syncUserNotifications($pdo, $new_id);
                $success = "Registration successful! Starter items with photos have been added to your pantry. You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FreshGuardAI</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f4f7f6; margin: 0; padding: 16px; }
        .auth-container { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); width: 100%; max-width: 420px; }
        .auth-container h2 { margin-bottom: 20px; color: #1F6B4F; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .error { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; padding: 10px; border-radius: 6px; text-align: center; margin-bottom: 15px; font-size: 0.9rem; }
        .success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 10px; border-radius: 6px; text-align: center; margin-bottom: 15px; font-size: 0.9rem; }
        .auth-links { margin-top: 18px; text-align: center; font-size: 0.9rem; }
        .auth-links a { color: #1F6B4F; font-weight: 600; text-decoration: none; }
        .auth-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="auth-container">
    <h2>Register for FreshGuardAI</h2>
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?> <a href="login.php" style="font-weight:700; text-decoration:underline;">Login now &rarr;</a></div>
    <?php endif; ?>
    <form action="register.php" method="POST">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password (min 6 characters)</label>
            <input type="password" id="password" name="password" minlength="6" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
        </div>
        <button type="submit" class="btn" style="width:100%; margin-top: 8px;">Register</button>
    </form>
    <div class="auth-links">
        <p>Already have an account? <a href="login.php">Login</a></p>
        <p><a href="index.php">Back to Home</a></p>
    </div>
</div>

</body>
</html>
