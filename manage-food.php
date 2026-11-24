<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';
require_once 'includes/functions.php';

$user_id = (int)$_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$item = [
    'name' => '',
    'category' => '',
    'quantity' => '1',
    'purchase_date' => date('Y-m-d'),
    'expiry_date' => date('Y-m-d', strtotime('+7 days')),
    'storage_location' => 'Fridge',
    'notes' => '',
    'image' => '',
];
$error = '';
$success = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM food_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $item = $fetched;
    } else {
        die("Item not found or unauthorized.");
    }
}

if (!$id && isset($_GET['scan_name'])) {
    $item['name'] = trim($_GET['scan_name']);
    $item['category'] = trim($_GET['scan_category'] ?? '');
    $item['quantity'] = trim($_GET['scan_quantity'] ?? '1');
    $item['storage_location'] = trim($_GET['scan_storage'] ?? 'Fridge');
    if (!empty($_GET['scan_expiry'])) {
        $item['expiry_date'] = trim($_GET['scan_expiry']);
    }
    if (!empty($_GET['scan_image'])) {
        $item['image'] = trim($_GET['scan_image']);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && $id) {
        if (isset($_POST['add_replacement'])) {
            $rep_stmt = $pdo->prepare("INSERT INTO replacement_list (user_id, food_name, quantity, category, notes, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $rep_stmt->execute([
                $user_id,
                $item['name'],
                $item['quantity'] ?: '1',
                $item['category'] ?: null,
                'Auto-added from discarded inventory'
            ]);
        }
        $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        header("Location: inventory.php");
        exit();
    }

    if ($action === 'save') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $quantity = trim($_POST['quantity'] ?? '');
        $purchase_date = $_POST['purchase_date'] ?: date('Y-m-d');
        $expiry_date = $_POST['expiry_date'] ?? '';
        $storage_location = trim($_POST['storage_location'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $imageUrlInput = trim($_POST['image_url'] ?? '');

        $imagePath = $item['image'] ?? '';
        if ($imageUrlInput !== '' && filter_var($imageUrlInput, FILTER_VALIDATE_URL)) {
            $imagePath = $imageUrlInput;
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed, true)) {
                $filename = uniqid('food_', true) . '.' . $ext;
                $target = 'assets/img/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $imagePath = $target;
                }
            } else {
                $error = "Invalid image format. Please upload JPG, PNG, GIF, or WEBP.";
            }
        }

        if (empty($imagePath)) {
            $imagePath = guessFoodImage($name, $category);
        }

        if (empty($name) || empty($expiry_date)) {
            $error = "Food Name and Expiry Date are required.";
        } elseif (empty($error)) {
            $computed = computeFoodStatus($expiry_date);
            $status = $computed['status'];

            if ($id) {
                $stmt = $pdo->prepare("UPDATE food_items SET name=?, category=?, quantity=?, purchase_date=?, expiry_date=?, storage_location=?, notes=?, image=?, status=? WHERE id=? AND user_id=?");
                $stmt->execute([$name, $category, $quantity, $purchase_date, $expiry_date, $storage_location, $notes, $imagePath, $status, $id, $user_id]);
                $success = "Item updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO food_items (user_id, name, category, quantity, purchase_date, expiry_date, storage_location, notes, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $name, $category, $quantity, $purchase_date, $expiry_date, $storage_location, $notes, $imagePath, $status]);
                $id = (int)$pdo->lastInsertId();
                $success = "Item added successfully.";
            }

            syncUserNotifications($pdo, $user_id);

            $stmt = $pdo->prepare("SELECT * FROM food_items WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $item = $stmt->fetch();
        }
    }
}

include 'includes/header.php';
?>

<div class="container section">
    <div style="max-width: 620px; margin: 0 auto 15px; display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin:0;"><?php echo $id ? 'Edit Food Item' : 'Add New Food Item'; ?></h2>
        <a href="inventory.php" style="color:var(--primary-green); font-weight:600;">&larr; Back to Inventory</a>
    </div>

    <div style="background: #fff; padding: 30px; border-radius: 12px; max-width: 620px; margin: 0 auto; box-shadow: var(--shadow-md);">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">

            <div style="margin-bottom: 15px;">
                <label>Product Photo (Upload or Paste Online Image URL)</label>
                <?php if (!empty($item['image'])): ?>
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Preview" style="width:95px; height:95px; object-fit:cover; border-radius:10px; margin-bottom:10px; display:block;">
                <?php endif; ?>
                <input type="url" name="image_url" value="<?php echo (strpos($item['image'] ?? '', 'http') === 0) ? htmlspecialchars($item['image']) : ''; ?>" placeholder="https://images.unsplash.com/... (optional)" style="margin-bottom:8px;">
                <input type="file" name="image" accept="image/*">
                <small style="color:var(--text-muted);">Leave both empty to automatically pick a matching online food photo.</small>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Food Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required placeholder="e.g. Farm Fresh Milk">
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap:wrap;">
                <div style="flex:1; min-width:200px;">
                    <label>Category</label>
                    <input type="text" name="category" list="category-options" value="<?php echo htmlspecialchars($item['category'] ?? ''); ?>" placeholder="e.g. Dairy, Vegetables, Meat">
                    <datalist id="category-options">
                        <option value="Dairy">
                        <option value="Vegetables">
                        <option value="Fruits">
                        <option value="Meat">
                        <option value="Bakery">
                        <option value="Beverages">
                        <option value="Snacks">
                    </datalist>
                </div>
                <div style="flex:1; min-width:200px;">
                    <label>Quantity</label>
                    <input type="text" name="quantity" value="<?php echo htmlspecialchars($item['quantity'] ?? ''); ?>" placeholder="e.g. 1 Gallon, 500g">
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap:wrap;">
                <div style="flex:1; min-width:200px;">
                    <label>Purchase Date</label>
                    <input type="date" name="purchase_date" value="<?php echo htmlspecialchars($item['purchase_date'] ?? date('Y-m-d')); ?>">
                </div>
                <div style="flex:1; min-width:200px;">
                    <label>Expiry Date *</label>
                    <input type="date" name="expiry_date" value="<?php echo htmlspecialchars($item['expiry_date']); ?>" required>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Storage Location</label>
                <input type="text" name="storage_location" list="location-options" value="<?php echo htmlspecialchars($item['storage_location'] ?? ''); ?>" placeholder="e.g. Fridge, Pantry, Freezer, Counter">
                <datalist id="location-options">
                    <option value="Fridge">
                    <option value="Freezer">
                    <option value="Pantry">
                    <option value="Counter">
                </datalist>
            </div>

            <div style="margin-bottom: 20px;">
                <label>Notes</label>
                <textarea name="notes" rows="3" placeholder="Usage notes or recipe ideas..."><?php echo htmlspecialchars($item['notes'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn" style="width: 100%; margin-bottom: 10px;">Save Food Item</button>
        </form>

        <?php if ($id): ?>
            <hr style="margin: 22px 0; border:none; border-top:1px solid #e2e8f0;">
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this food item?');">
                <input type="hidden" name="action" value="delete">
                <label style="display:flex; align-items:center; gap:10px; margin-bottom: 15px; font-weight:500;">
                    <input type="checkbox" name="add_replacement" value="1" checked style="width:auto;">
                    Add to Replacement List when deleted
                </label>
                <button type="submit" class="btn" style="width: 100%; background: var(--danger-red);">Delete / Discard Item</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
