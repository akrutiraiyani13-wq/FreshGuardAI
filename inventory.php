<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';
require_once 'includes/functions.php';

$user_id = (int)$_SESSION['user_id'];
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['item_id'])) {
    $itemId = (int)$_POST['item_id'];
    $stmt = $pdo->prepare("SELECT * FROM food_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$itemId, $user_id]);
    $targetItem = $stmt->fetch();

    if ($targetItem) {
        if ($_POST['action'] === 'replace') {
            $repStmt = $pdo->prepare("INSERT INTO replacement_list (user_id, food_name, quantity, category, notes, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $repStmt->execute([
                $user_id,
                $targetItem['name'],
                $targetItem['quantity'] ?: '1',
                $targetItem['category'] ?: null,
                'Moved from inventory'
            ]);
            $pdo->prepare("DELETE FROM food_items WHERE id = ? AND user_id = ?")->execute([$itemId, $user_id]);
            $flash = htmlspecialchars($targetItem['name']) . " moved to Replacement List.";
        } elseif ($_POST['action'] === 'consume') {
            $pdo->prepare("DELETE FROM food_items WHERE id = ? AND user_id = ?")->execute([$itemId, $user_id]);
            $flash = htmlspecialchars($targetItem['name']) . " marked as consumed and removed.";
        }
    }
}

$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
$search = trim($_GET['search'] ?? '');

$catStmt = $pdo->prepare("SELECT DISTINCT category FROM food_items WHERE user_id = ? AND category IS NOT NULL AND category != '' ORDER BY category ASC");
$catStmt->execute([$user_id]);
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT *, DATEDIFF(expiry_date, CURDATE()) AS days_left FROM food_items WHERE user_id = :user_id";
$params = [':user_id' => $user_id];

if ($status_filter === 'fresh') {
    $sql .= " AND DATEDIFF(expiry_date, CURDATE()) > 3";
} elseif ($status_filter === 'usesoon') {
    $sql .= " AND DATEDIFF(expiry_date, CURDATE()) BETWEEN 0 AND 3";
} elseif ($status_filter === 'expired') {
    $sql .= " AND DATEDIFF(expiry_date, CURDATE()) < 0";
}

if ($category_filter !== '') {
    $sql .= " AND category = :category";
    $params[':category'] = $category_filter;
}

if ($search !== '') {
    $sql .= " AND (name LIKE :search OR category LIKE :search OR storage_location LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY expiry_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container section">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px;">
        <div>
            <h2 style="margin:0;">Your Food Inventory</h2>
            <p style="margin:4px 0 0; color:var(--text-muted);">Track storage locations, quantities, and live freshness status.</p>
        </div>
        <a href="manage-food.php" class="btn">+ Add New Food</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?php echo $flash; ?></div>
    <?php endif; ?>

    <div style="background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: var(--shadow-sm);">
        <form method="GET" action="inventory.php" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="text" name="search" placeholder="Search by name, category, or location..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 2; min-width:200px;">
            <select name="status" style="flex: 1; min-width:140px;">
                <option value="">All Statuses</option>
                <option value="fresh" <?php echo $status_filter === 'fresh' ? 'selected' : ''; ?>>Fresh</option>
                <option value="usesoon" <?php echo $status_filter === 'usesoon' ? 'selected' : ''; ?>>Use Soon</option>
                <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
            </select>
            <select name="category" style="flex: 1; min-width:140px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Filter</button>
            <a href="inventory.php" class="btn" style="background:#6c757d;">Reset</a>
        </form>
    </div>

    <div style="overflow-x: auto; background: #fff; border-radius: 12px; padding: 15px; box-shadow: var(--shadow-md);">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $st = computeFoodStatus($item['expiry_date']);
                            $days = $st['days_left'];
                            $img = !empty($item['image']) ? $item['image'] : guessFoodImage($item['name'], $item['category'] ?? '');
                        ?>
                        <tr style="<?php echo $days < 0 ? 'background-color:#fff5f6;' : ''; ?>">
                            <td>
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="Product Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <?php if (!empty($item['notes'])): ?>
                                    <br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($item['notes']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['category'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity'] ?? ''); ?></td>
                            <td>
                                <?php echo htmlspecialchars($item['expiry_date']); ?><br>
                                <small style="color:var(--text-muted);">
                                    <?php
                                    if ($days < 0) echo abs($days) . ' day(s) ago';
                                    elseif ($days === 0) echo 'Today';
                                    else echo $days . ' day(s) left';
                                    ?>
                                </small>
                            </td>
                            <td><span class="<?php echo $st['badge']; ?>"><?php echo $st['status']; ?></span></td>
                            <td><?php echo htmlspecialchars($item['storage_location'] ?? ''); ?></td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <a href="manage-food.php?id=<?php echo $item['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.78rem; background: #0ea5e9;">Edit</a>
                                    <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Mark <?php echo htmlspecialchars(addslashes($item['name'])); ?> as used and remove from pantry?');">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="action" value="consume">
                                        <button type="submit" class="btn" style="padding: 5px 10px; font-size: 0.78rem; background: #16a34a;">Used</button>
                                    </form>
                                    <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Remove and add <?php echo htmlspecialchars(addslashes($item['name'])); ?> to Replacement List?');">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="action" value="replace">
                                        <button type="submit" class="btn" style="padding: 5px 10px; font-size: 0.78rem; background: #d97706;">Replace</button>
                                    </form>
                                </div>
                                <?php if ($days < 0): ?>
                                    <span style="color:#dc2626; font-size:0.75rem; font-weight:bold; display:block; margin-top:5px;">⚠️ DO NOT CONSUME</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No food items found. <a href="manage-food.php" style="color:var(--primary-green); font-weight:600;">Add food now</a>.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
