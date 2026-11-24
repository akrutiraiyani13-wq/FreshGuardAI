<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';
require_once 'includes/functions.php';

$user_id = (int)$_SESSION['user_id'];
syncUserNotifications($pdo, $user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_all_read') {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    } elseif ($action === 'mark_read' && isset($_POST['notif_id'])) {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([(int)$_POST['notif_id'], $user_id]);
    } elseif ($action === 'delete' && isset($_POST['notif_id'])) {
        $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")->execute([(int)$_POST['notif_id'], $user_id]);
    } elseif ($action === 'clear_read') {
        $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = 1")->execute([$user_id]);
    }
    header("Location: notifications.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT n.*, f.name AS food_name, f.expiry_date
    FROM notifications n
    LEFT JOIN food_items f ON n.food_id = f.id
    WHERE n.user_id = ?
    ORDER BY n.is_read ASC, n.created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container section">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
        <div>
            <h2 style="margin:0;">Freshness Notifications</h2>
            <p style="margin:4px 0 0; color:var(--text-muted);">Automatic alerts generated when your food enters Use Soon or Expired status.</p>
        </div>
        <div style="display:flex; gap:8px;">
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn" style="padding:8px 16px; font-size:0.85rem;">Mark All Read</button>
            </form>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="clear_read">
                <button type="submit" class="btn" style="background:#64748b; padding:8px 16px; font-size:0.85rem;">Clear Read</button>
            </form>
        </div>
    </div>

    <div style="background:#fff; padding:20px; border-radius:12px; box-shadow:var(--shadow-md);">
        <?php if (count($notifications) > 0): ?>
            <?php foreach ($notifications as $n): ?>
                <?php
                $isExpired = $n['type'] === 'expired';
                $bg = $n['is_read'] ? '#fff' : ($isExpired ? '#fef2f2' : '#fffbeb');
                $border = $isExpired ? 'var(--danger-red)' : 'var(--warning-yellow)';
                ?>
                <div style="display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; padding:16px; margin-bottom:12px; border-radius:10px; background:<?php echo $bg; ?>; border-left:4px solid <?php echo $border; ?>; border-top:1px solid #edf2f7; border-right:1px solid #edf2f7; border-bottom:1px solid #edf2f7;">
                    <div style="flex:1; min-width:220px;">
                        <span class="<?php echo $isExpired ? 'badge-expired' : 'badge-usesoon'; ?>">
                            <?php echo $isExpired ? 'Expired Alert' : 'Use Soon'; ?>
                        </span>
                        <?php if (!$n['is_read']): ?>
                            <span style="margin-left:6px; font-size:0.75rem; font-weight:700; color:var(--primary-green);">NEW</span>
                        <?php endif; ?>
                        <p style="margin:8px 0 4px; font-weight:600;"><?php echo htmlspecialchars($n['message']); ?></p>
                        <small style="color:var(--text-muted);"><?php echo htmlspecialchars($n['created_at']); ?></small>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <?php if (!empty($n['food_id'])): ?>
                            <a href="manage-food.php?id=<?php echo (int)$n['food_id']; ?>" class="btn" style="padding:6px 12px; font-size:0.8rem; background:#0ea5e9;">View Item</a>
                        <?php endif; ?>
                        <?php if (!$n['is_read']): ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="notif_id" value="<?php echo $n['id']; ?>">
                                <button type="submit" class="btn" style="padding:6px 12px; font-size:0.8rem;">Mark Read</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="notif_id" value="<?php echo $n['id']; ?>">
                            <button type="submit" class="btn" style="padding:6px 12px; font-size:0.8rem; background:#64748b;">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="margin:0;">No notifications right now. Everything in your kitchen is looking good!</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
