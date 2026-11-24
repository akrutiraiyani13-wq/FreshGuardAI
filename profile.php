<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';

$user_id = (int)$_SESSION['user_id'];
$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $plan = $_POST['plan'] ?? 'Free';
    $allowedPlans = ['Free', 'Premium', 'Yearly'];

    if ($name === '') {
        $error = 'Name cannot be empty.';
    } elseif (!in_array($plan, $allowedPlans, true)) {
        $error = 'Invalid subscription plan.';
    } else {
        $upd = $pdo->prepare("UPDATE users SET name = ?, plan = ? WHERE id = ?");
        $upd->execute([$name, $plan, $user_id]);
        $_SESSION['user_name'] = $name;
        $_SESSION['user_plan'] = $plan;
        $flash = 'Profile updated successfully!';
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$itemCountStmt = $pdo->prepare("SELECT COUNT(*) FROM food_items WHERE user_id = ?");
$itemCountStmt->execute([$user_id]);
$itemCount = (int)$itemCountStmt->fetchColumn();

include 'includes/header.php';
?>

<div class="container section">
    <h2 class="text-center">User Profile</h2>
    <div class="card" style="max-width: 540px; margin: 0 auto;">
        <div style="text-align:center;">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=1F6B4F&color=fff&size=110&rounded=true" alt="Profile Logo" style="display: block; margin: 0 auto 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 4px; font-size: 1.45rem;"><?php echo htmlspecialchars($user['name']); ?></h3>
            <p style="color: #666; margin-top: 0;"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div style="background: #f8fafc; padding: 15px; border-radius: 10px; margin: 20px 0;">
            <p style="margin:6px 0;"><strong>Current Plan:</strong> <span class="badge-fresh"><?php echo htmlspecialchars($user['plan']); ?></span></p>
            <p style="margin:6px 0;"><strong>Tracked Food Items:</strong> <?php echo $itemCount; ?></p>
            <p style="margin:6px 0;"><strong>Member Since:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($user['created_at']))); ?></p>
        </div>

        <form method="POST" style="margin-bottom: 18px;">
            <div style="margin-bottom:12px;">
                <label>Full Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div style="margin-bottom:15px;">
                <label>Subscription Plan</label>
                <select name="plan">
                    <option value="Free" <?php echo $user['plan'] === 'Free' ? 'selected' : ''; ?>>Free (₹0/month)</option>
                    <option value="Premium" <?php echo $user['plan'] === 'Premium' ? 'selected' : ''; ?>>Premium (₹199/month)</option>
                    <option value="Yearly" <?php echo $user['plan'] === 'Yearly' ? 'selected' : ''; ?>>Yearly (₹1,999/year)</option>
                </select>
            </div>
            <button type="submit" class="btn" style="width:100%;">Save Profile Changes</button>
        </form>

        <a href="logout.php" class="btn" style="background: var(--danger-red); width: 100%;">Logout from FreshGuardAI</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
