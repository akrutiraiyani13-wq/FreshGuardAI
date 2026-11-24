<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';
require_once 'includes/functions.php';

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consume_id'])) {
    $del = $pdo->prepare("DELETE FROM food_items WHERE id = ? AND user_id = ?");
    $del->execute([(int)$_POST['consume_id'], $user_id]);
}

$stmt = $pdo->prepare("
    SELECT *, DATEDIFF(expiry_date, CURDATE()) AS days_left
    FROM food_items
    WHERE user_id = ? AND DATEDIFF(expiry_date, CURDATE()) BETWEEN 0 AND 3
    ORDER BY expiry_date ASC
");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container section">
    <h2 style="color: var(--warning-yellow);">Use Soon Items</h2>
    <p>These items expire within the next 3 days. Use them soon or find matching recipes before they spoil!</p>

    <div class="grid">
        <?php if (count($items) > 0): ?>
            <?php foreach ($items as $item): ?>
                <?php
                $days_left = (int)$item['days_left'];
                $img = !empty($item['image']) ? $item['image'] : guessFoodImage($item['name'], $item['category'] ?? '');
                $days_text = $days_left === 0 ? 'Expires Today!' : ($days_left === 1 ? 'Expires Tomorrow' : "Expires in {$days_left} days");
                ?>
                <div class="card" style="border-left: 4px solid var(--warning-yellow);">
                    <div style="display:flex; align-items:center; gap:14px; margin-bottom:12px;">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="Item" style="width:60px; height:60px; object-fit:cover; border-radius:10px;">
                        <div>
                            <h3 style="margin:0;"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <small style="color:var(--text-muted);"><?php echo htmlspecialchars($item['category']); ?> &bull; <?php echo htmlspecialchars($item['quantity']); ?></small>
                        </div>
                    </div>
                    <p style="color: var(--warning-yellow); font-weight: 700; font-size: 1.15rem; margin: 8px 0;">
                        ⏳ <?php echo $days_text; ?>
                    </p>
                    <p style="margin:4px 0;"><strong>Location:</strong> <?php echo htmlspecialchars($item['storage_location'] ?? 'N/A'); ?></p>
                    <?php if (!empty($item['notes'])): ?>
                        <p style="margin:4px 0; color:var(--text-muted); font-size:0.9rem;"><em><?php echo htmlspecialchars($item['notes']); ?></em></p>
                    <?php endif; ?>
                    <hr style="border:none; border-top:1px solid #edf2f7; margin:15px 0;">
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <a href="recipes.php" class="btn" style="flex:1; font-size:0.85rem; padding:8px 12px;">Find Recipes</a>
                        <a href="manage-food.php?id=<?php echo $item['id']; ?>" class="btn" style="background:#0ea5e9; font-size:0.85rem; padding:8px 12px;">Edit</a>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="consume_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn" style="background:#64748b; font-size:0.85rem; padding:8px 12px;">Mark Used</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card">
                <p style="margin:0;">You have no food items expiring in the next 3 days! Great job managing your food.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
