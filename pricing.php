<?php
session_start();
require_once 'includes/db.inc.php';

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'], $_POST['select_plan'])) {
    $plan = $_POST['select_plan'];
    if (in_array($plan, ['Free', 'Premium', 'Yearly'], true)) {
        $stmt = $pdo->prepare("UPDATE users SET plan = ? WHERE id = ?");
        $stmt->execute([$plan, (int)$_SESSION['user_id']]);
        $_SESSION['user_plan'] = $plan;
        $flash = "Your subscription plan has been updated to " . htmlspecialchars($plan) . "!";
    }
}

$currentPlan = $_SESSION['user_plan'] ?? null;
include 'includes/header.php';
?>

<div class="container section text-center">
    <h2>Subscription Plans (Projected Pricing)</h2>
    <p>FreshGuardAI offers tiered options for any household size.</p>

    <?php if ($flash): ?>
        <div class="alert alert-success" style="max-width:600px; margin:20px auto;"><?php echo $flash; ?></div>
    <?php endif; ?>

    <div class="grid" style="margin-top: 40px;">
        <div class="card">
            <h3>FREE</h3>
            <p style="font-size: 2rem; font-weight: bold; color: var(--primary-green);">₹0<span style="font-size:1rem;">/month</span></p>
            <ul style="text-align: left; list-style: none; padding: 0; margin-bottom:20px;">
                <li>✔️ Basic food tracking</li>
                <li>✔️ Basic expiry reminders</li>
                <li>✔️ Pantry inventory management</li>
            </ul>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST">
                    <input type="hidden" name="select_plan" value="Free">
                    <button type="submit" class="btn" style="width:100%;" <?php echo $currentPlan === 'Free' ? 'disabled' : ''; ?>>
                        <?php echo $currentPlan === 'Free' ? 'Current Plan' : 'Switch to Free'; ?>
                    </button>
                </form>
            <?php else: ?>
                <a href="register.php" class="btn" style="width:100%;">Get Started Free</a>
            <?php endif; ?>
        </div>

        <div class="card" style="border: 2px solid var(--primary-green);">
            <h3>PREMIUM</h3>
            <p style="font-size: 2rem; font-weight: bold; color: var(--primary-green);">₹199<span style="font-size:1rem;">/month</span></p>
            <ul style="text-align: left; list-style: none; padding: 0; margin-bottom:20px;">
                <li>✔️ Unlimited food items</li>
                <li>✔️ Smart recipe suggestions</li>
                <li>✔️ Automated replacement list</li>
                <li>✔️ Barcode scanner &amp; history</li>
                <li>✔️ Real-time expiry alerts</li>
            </ul>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST">
                    <input type="hidden" name="select_plan" value="Premium">
                    <button type="submit" class="btn" style="width:100%;" <?php echo $currentPlan === 'Premium' ? 'disabled' : ''; ?>>
                        <?php echo $currentPlan === 'Premium' ? 'Current Plan' : 'Upgrade to Premium'; ?>
                    </button>
                </form>
            <?php else: ?>
                <a href="register.php" class="btn" style="width:100%;">Start Premium</a>
            <?php endif; ?>
        </div>

        <div class="card" style="border: 2px solid var(--primary-green);">
            <h3>YEARLY</h3>
            <p style="font-size: 2rem; font-weight: bold; color: var(--primary-green);">₹1,999<span style="font-size:1rem;">/year</span></p>
            <ul style="text-align: left; list-style: none; padding: 0; margin-bottom:20px;">
                <li>✔️ All premium features</li>
                <li>✔️ Save 2 months every year</li>
                <li>✔️ Priority feature updates</li>
            </ul>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST">
                    <input type="hidden" name="select_plan" value="Yearly">
                    <button type="submit" class="btn" style="width:100%;" <?php echo $currentPlan === 'Yearly' ? 'disabled' : ''; ?>>
                        <?php echo $currentPlan === 'Yearly' ? 'Current Plan' : 'Choose Yearly'; ?>
                    </button>
                </form>
            <?php else: ?>
                <a href="register.php" class="btn" style="width:100%;">Choose Yearly</a>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-top: 60px; text-align: left; max-width: 800px; margin-left: auto; margin-right: auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: var(--shadow-sm);">
        <h3 style="color: var(--primary-green); margin-top:0;">Projected Financials</h3>
        <p style="color: #777; margin-bottom: 20px;">*The following figures are strictly projected financials based on expected growth targets.</p>
        <p><strong>Year 1:</strong><br>Revenue: ₹23,88,000 | Expenses: ₹8,00,000 | Profit: ₹15,88,000</p>
        <p><strong>Year 2:</strong><br>Revenue: ₹35,82,000 | Expenses: ₹10,40,000 | Profit: ₹25,42,000</p>
        <p><strong>Year 3:</strong><br>Revenue: ₹50,14,800 | Expenses: ₹13,00,000 | Profit: approximately ₹37,14,800</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
