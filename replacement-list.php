<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';
require_once 'includes/functions.php';

$user_id = (int)$_SESSION['user_id'];
$flash = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $food_name = trim($_POST['food_name'] ?? '');
        $quantity = trim($_POST['quantity'] ?? '1');
        $category = trim($_POST['category'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($food_name !== '') {
            $ins = $pdo->prepare("INSERT INTO replacement_list (user_id, food_name, quantity, category, notes, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $ins->execute([$user_id, $food_name, $quantity ?: '1', $category ?: null, $notes ?: null]);
            $flash = "Added \"" . htmlspecialchars($food_name) . "\" to your replacement list.";
        }
    } elseif ($action === 'restock' && isset($_POST['item_id'])) {
        $item_id = (int)$_POST['item_id'];
        $stmt = $pdo->prepare("SELECT * FROM replacement_list WHERE id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
        $rep = $stmt->fetch();
        if ($rep) {
            $img = guessFoodImage($rep['food_name'], $rep['category'] ?? '');
            $ins = $pdo->prepare("
                INSERT INTO food_items (user_id, name, category, quantity, purchase_date, expiry_date, storage_location, notes, image, status)
                VALUES (?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Fridge', ?, ?, 'Fresh')
            ");
            $ins->execute([
                $user_id,
                $rep['food_name'],
                $rep['category'] ?: 'Groceries',
                $rep['quantity'] ?: '1',
                $rep['notes'] ?: 'Restocked from replacement list',
                $img
            ]);
            $pdo->prepare("DELETE FROM replacement_list WHERE id = ? AND user_id = ?")->execute([$item_id, $user_id]);
            $flash = "\"" . htmlspecialchars($rep['food_name']) . "\" marked bought and added back to your inventory!";
        }
    } elseif (isset($_POST['delete_id'])) {
        $del_stmt = $pdo->prepare("DELETE FROM replacement_list WHERE id = ? AND user_id = ?");
        $del_stmt->execute([(int)$_POST['delete_id'], $user_id]);
        $flash = "Item removed from replacement list.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM replacement_list WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container section">
    <h2>Replacement Shopping List</h2>
    <p>Items added here when expired, discarded, or needed for recipes help you plan your next grocery run.</p>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?php echo $flash; ?></div>
    <?php endif; ?>

    <!-- Manual Add Form -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: var(--shadow-sm); margin-bottom: 25px;">
        <h3 style="margin-top:0; font-size:1.05rem;">Add Item to Buy</h3>
        <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
            <input type="hidden" name="action" value="add">
            <div style="flex:2; min-width:180px;">
                <label>Item Name *</label>
                <input type="text" name="food_name" placeholder="e.g. Organic Milk" required>
            </div>
            <div style="flex:1; min-width:120px;">
                <label>Quantity</label>
                <input type="text" name="quantity" placeholder="e.g. 1 Loaf" value="1">
            </div>
            <div style="flex:1; min-width:130px;">
                <label>Category</label>
                <input type="text" name="category" placeholder="e.g. Dairy">
            </div>
            <div style="flex:2; min-width:160px;">
                <label>Notes</label>
                <input type="text" name="notes" placeholder="Optional note">
            </div>
            <button type="submit" class="btn">+ Add Item</button>
        </form>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: var(--shadow-md);">
        <?php if (count($items) > 0): ?>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach ($items as $item): ?>
                    <li style="display: flex; justify-content: space-between; align-items: center; gap:12px; flex-wrap:wrap; padding: 15px; border-bottom: 1px solid #eee;">
                        <div>
                            <strong style="font-size:1.05rem;"><?php echo htmlspecialchars($item['food_name']); ?></strong>
                            <?php if (!empty($item['quantity'])): ?>
                                <span style="background:#f1f5f9; padding:2px 8px; border-radius:999px; font-size:0.8rem; margin-left:6px;"><?php echo htmlspecialchars($item['quantity']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['category'])): ?>
                                <small style="color:#64748b; margin-left:6px;">(<?php echo htmlspecialchars($item['category']); ?>)</small>
                            <?php endif; ?>
                            <?php if (!empty($item['notes'])): ?>
                                <br><i style="font-size: 0.85rem; color:#64748b;"><?php echo htmlspecialchars($item['notes']); ?></i>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="restock">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn" style="padding: 6px 14px; font-size: 0.85rem;">Bought &rarr; Add to Pantry</button>
                            </form>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn" style="background: #64748b; padding: 6px 14px; font-size: 0.85rem;">Remove</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="margin:0;">Your replacement list is empty. You're all caught up!</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
