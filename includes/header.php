<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_id']);
$unread_notifs = 0;
if ($is_logged_in) {
    require_once __DIR__ . '/db.inc.php';
    require_once __DIR__ . '/functions.php';
    syncUserNotifications($pdo, (int)$_SESSION['user_id']);
    $unread_notifs = getUnreadNotificationCount($pdo, (int)$_SESSION['user_id']);
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshGuardAI - Smart Food Management</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1F6B4F">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
<header class="navbar">
    <div class="container">
        <a href="index.php" class="logo">FreshGuardAI</a>
        <nav>
            <ul>
                <?php if ($is_logged_in): ?>
                    <li><a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'nav-active' : ''; ?>">Dashboard</a></li>
                    <li><a href="inventory.php" class="<?php echo $current_page === 'inventory.php' ? 'nav-active' : ''; ?>">My Food</a></li>
                    <li><a href="use-soon.php" class="<?php echo $current_page === 'use-soon.php' ? 'nav-active' : ''; ?>">Use Soon</a></li>
                    <li><a href="scan.php" class="<?php echo $current_page === 'scan.php' ? 'nav-active' : ''; ?>">Scan Food</a></li>
                    <li><a href="recipes.php" class="<?php echo $current_page === 'recipes.php' ? 'nav-active' : ''; ?>">Recipes</a></li>
                    <li><a href="replacement-list.php" class="<?php echo $current_page === 'replacement-list.php' ? 'nav-active' : ''; ?>">Replacements</a></li>
                    <li>
                        <a href="notifications.php" class="<?php echo $current_page === 'notifications.php' ? 'nav-active' : ''; ?>" title="Notifications" style="display:flex;align-items:center;gap:4px;">
                            🔔
                            <?php if ($unread_notifs > 0): ?>
                                <span class="notif-badge"><?php echo $unread_notifs; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="profile.php" style="display: flex; align-items: center; gap: 8px;">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=1F6B4F&color=fff&size=24&rounded=true" alt="Profile" style="border-radius: 50%; width: 24px; height: 24px;">
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                    </li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="about.php">About</a></li>
                    <li><a href="how-it-works.php">How It Works</a></li>
                    <li><a href="features.php">Features</a></li>
                    <li><a href="pricing.php">Pricing</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main>
