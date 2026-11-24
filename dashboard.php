<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';
require_once 'includes/functions.php';

$user_id = (int)$_SESSION['user_id'];
syncUserNotifications($pdo, $user_id);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM food_items WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_items = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN DATEDIFF(expiry_date, CURDATE()) > 3 THEN 1 ELSE 0 END) AS fresh_count,
        SUM(CASE WHEN DATEDIFF(expiry_date, CURDATE()) BETWEEN 0 AND 3 THEN 1 ELSE 0 END) AS use_soon_count,
        SUM(CASE WHEN DATEDIFF(expiry_date, CURDATE()) < 0 THEN 1 ELSE 0 END) AS expired_count
    FROM food_items WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM replacement_list WHERE user_id = ?");
$stmt->execute([$user_id]);
$replacement_count = (int)$stmt->fetchColumn();

$unread_count = getUnreadNotificationCount($pdo, $user_id);

$upcomingStmt = $pdo->prepare("
    SELECT id, name, category, quantity, expiry_date, storage_location, image, DATEDIFF(expiry_date, CURDATE()) AS days_left
    FROM food_items
    WHERE user_id = ?
    ORDER BY expiry_date ASC
    LIMIT 5
");
$upcomingStmt->execute([$user_id]);
$upcoming_items = $upcomingStmt->fetchAll();

include 'includes/header.php';
?>

<div class="container section">
    <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
    <p>Here is your live FreshGuardAI kitchen &amp; pantry overview.</p>

    <!-- Stat Cards -->
    <div class="grid" style="margin-bottom: 35px;">
        <a href="inventory.php" class="card" style="border-left: 4px solid #64748b; display:block;">
            <h3>Total Items</h3>
            <p class="score-number"><?php echo $total_items; ?></p>
        </a>
        <a href="inventory.php?status=fresh" class="card" style="border-left: 4px solid var(--primary-green); display:block;">
            <h3 class="text-success">Fresh</h3>
            <p class="score-number"><?php echo (int)($stats['fresh_count'] ?? 0); ?></p>
        </a>
        <a href="use-soon.php" class="card" style="border-left: 4px solid var(--warning-yellow); display:block;">
            <h3 class="text-warning">Use Soon</h3>
            <p class="score-number"><?php echo (int)($stats['use_soon_count'] ?? 0); ?></p>
        </a>
        <a href="inventory.php?status=expired" class="card" style="border-left: 4px solid var(--danger-red); display:block;">
            <h3 class="text-danger">Expired</h3>
            <p class="score-number"><?php echo (int)($stats['expired_count'] ?? 0); ?></p>
        </a>
        <a href="replacement-list.php" class="card" style="border-left: 4px solid #0284c7; display:block;">
            <h3>Replacements</h3>
            <p class="score-number"><?php echo $replacement_count; ?></p>
        </a>
        <a href="notifications.php" class="card" style="border-left: 4px solid var(--accent); display:block;">
            <h3>Unread Alerts</h3>
            <p class="score-number"><?php echo $unread_count; ?></p>
        </a>
    </div>

    <!-- Quick Actions -->
    <div style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom: 30px;">
        <a href="manage-food.php" class="btn">+ Add Food Manually</a>
        <a href="scan.php" class="btn" style="background: #0ea5e9;">Barcode Scanner</a>
        <a href="recipes.php" class="btn" style="background: #d97706;">Smart Recipes</a>
        <a href="history.php" class="btn" style="background: #6366f1;">Scan History</a>
        <a href="shopping-list.php" class="btn" style="background: #475569;">Restock Suggestions</a>
    </div>

    <?php if ((int)($stats['expired_count'] ?? 0) > 0): ?>
        <div class="alert alert-danger">
            <div>
                <strong>Warning!</strong> You have <?php echo (int)$stats['expired_count']; ?> expired food item(s).
                <a href="inventory.php?status=expired" style="text-decoration:underline; font-weight:700;">Review &amp; move to replacement list &rarr;</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ((int)($stats['use_soon_count'] ?? 0) > 0): ?>
        <div class="alert alert-warning">
            <div>
                <strong>Use Soon!</strong> You have <?php echo (int)$stats['use_soon_count']; ?> item(s) expiring within 3 days.
                <a href="recipes.php" style="text-decoration:underline; font-weight:700;">View recipe suggestions &rarr;</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Expiring Next Table -->
    <div style="background: #fff; padding: 25px; border-radius: 16px; box-shadow: var(--shadow-md); margin-top: 25px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <h3 style="margin:0;">Expiring Soonest</h3>
            <a href="inventory.php" style="color: var(--primary-green); font-weight: 600;">View Full Inventory &rarr;</a>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Qty</th>
                        <th>Location</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($upcoming_items) > 0): ?>
                        <?php foreach ($upcoming_items as $ui): ?>
                            <?php $st = computeFoodStatus($ui['expiry_date']); ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ui['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($ui['category']); ?></td>
                                <td><?php echo htmlspecialchars($ui['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($ui['storage_location'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($ui['expiry_date']); ?></td>
                                <td><span class="<?php echo $st['badge']; ?>"><?php echo $st['status']; ?></span></td>
                                <td><a href="manage-food.php?id=<?php echo $ui['id']; ?>" class="btn" style="padding:5px 12px; font-size:0.8rem;">Manage</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No items in inventory yet. <a href="manage-food.php" style="color:var(--primary-green); font-weight:600;">Add your first item!</a></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
