<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';
require_once 'includes/functions.php';
require_once 'includes/data.php';

$user_id = (int)$_SESSION['user_id'];
$barcode = trim($_GET['barcode'] ?? $_POST['barcode'] ?? '');
if ($barcode === '') {
    header("Location: scan.php");
    exit();
}

$product = getProductByBarcode($barcode, $pdo);
if (!$product) {
    header("Location: scan.php?error=notfound");
    exit();
}

$fData = getFreshnessStatus($product['mfgDate'], $product['expDate']);
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_inventory') {
        $computed = computeFoodStatus($product['expDate']);
        $ins = $pdo->prepare("
            INSERT INTO food_items (user_id, name, category, quantity, purchase_date, expiry_date, storage_location, notes, image, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $user_id,
            $product['name'],
            $product['category'],
            $product['weight'] ?: '1',
            $product['mfgDate'],
            $product['expDate'],
            $product['storage'],
            'Added via Barcode Scanner (' . $product['barcode'] . ')',
            $product['image'],
            $computed['status'],
        ]);
        syncUserNotifications($pdo, $user_id);
        $flash = "\"" . htmlspecialchars($product['name']) . "\" added to your Food Inventory!";
    } elseif ($_POST['action'] === 'add_replacement') {
        $ins = $pdo->prepare("INSERT INTO replacement_list (user_id, food_name, quantity, category, notes, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $ins->execute([
            $user_id,
            $product['name'],
            $product['weight'] ?: '1',
            $product['category'],
            'Added from Scanner Results',
        ]);
        $flash = "\"" . htmlspecialchars($product['name']) . "\" added to your Replacement List!";
    }
} else {
    addToHistory($product, $fData['status'], $fData['score'], $pdo, $user_id);
}

if ($fData['status'] === 'fresh') {
    $cardClass = 'card-status-fresh';
    $statusText = 'Fresh';
    $iconType = 'checkmark-circle';
    $msgText = 'Fresh and safe to consume.';
} elseif ($fData['status'] === 'soon') {
    $cardClass = 'card-status-soon';
    $statusText = 'Use Soon';
    $iconType = 'warning';
    $msgText = 'Nearing expiry. Prioritize using soon!';
} else {
    $cardClass = 'card-status-spoiled';
    $statusText = 'Spoiled';
    $iconType = 'close-circle';
    $msgText = 'Expired. Do not consume — replace safely.';
}

include 'includes/header.php';
?>

<div class="container" style="padding-top:25px;">
    <?php if ($flash): ?>
        <div class="alert alert-success"><?php echo $flash; ?> <a href="inventory.php" style="font-weight:700; text-decoration:underline; margin-left:8px;">View Inventory &rarr;</a></div>
    <?php endif; ?>
</div>

<section id="results-view" class="view active">
    <div class="results-container">

        <!-- Details -->
        <div class="mobile-card">
            <div class="card-header nav-header">
                <a href="scan.php" title="Back to Scanner"><ion-icon name="arrow-back"></ion-icon></a>
                <h2>Product Details</h2>
                <a href="dashboard.php" title="Dashboard"><ion-icon name="home-outline"></ion-icon></a>
            </div>

            <div class="product-image-large"><img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product"></div>
            <h3 class="product-name-title"><?php echo htmlspecialchars($product['name']); ?></h3>

            <div class="detail-list" style="margin-bottom: 20px;">
                <div class="detail-row"><span class="label">Brand</span><span class="value"><?php echo htmlspecialchars($product['brand']); ?></span></div>
                <div class="detail-row"><span class="label">Category</span><span class="value"><?php echo htmlspecialchars($product['category']); ?></span></div>
                <div class="detail-row"><span class="label">Weight</span><span class="value"><?php echo htmlspecialchars($product['weight']); ?></span></div>
                <div class="detail-row"><span class="label">Storage</span><span class="value"><?php echo htmlspecialchars($product['storage']); ?></span></div>
            </div>

            <div style="background:#f8fafc; padding:15px; border-radius:8px;">
                <h4 style="margin:0 0 10px; font-size:0.9rem;">Nutritional Summary</h4>
                <div style="display:flex; justify-content:space-between; text-align:center;">
                    <div><b style="color:var(--primary-dark)"><?php echo htmlspecialchars($product['nutrition']['calories']); ?></b><br><small style="color:var(--text-muted)">Calories</small></div>
                    <div><b style="color:var(--primary-dark)"><?php echo htmlspecialchars($product['nutrition']['sugar']); ?></b><br><small style="color:var(--text-muted)">Sugar</small></div>
                    <div><b style="color:var(--primary-dark)"><?php echo htmlspecialchars($product['nutrition']['protein']); ?></b><br><small style="color:var(--text-muted)">Protein</small></div>
                </div>
            </div>
        </div>

        <div class="flow-arrow"><ion-icon name="arrow-forward"></ion-icon></div>

        <!-- Mfg & Exp -->
        <div class="mobile-card">
            <div class="card-header nav-header">
                <a href="scan.php"><ion-icon name="arrow-back"></ion-icon></a>
                <h2>Expiry Stats</h2>
                <span></span>
            </div>
            <div class="info-blocks">
                <div class="info-block">
                    <div class="ib-label">Mfg Date</div>
                    <div class="ib-value"><ion-icon name="calendar-outline"></ion-icon> <span><?php echo htmlspecialchars($product['mfgDate']); ?></span></div>
                </div>
                <div class="info-block">
                    <div class="ib-label">Exp Date</div>
                    <div class="ib-value"><ion-icon name="calendar-outline"></ion-icon> <span><?php echo htmlspecialchars($product['expDate']); ?></span></div>
                </div>
            </div>
            <div class="info-block" style="margin-bottom:20px;">
                <div class="ib-label">Days Remaining</div>
                <div class="ib-value">
                    <ion-icon name="<?php echo $fData['status'] === 'spoiled' ? 'close-circle-outline' : 'checkmark-circle-outline'; ?>"></ion-icon>
                    <span><?php echo $fData['daysLeft']; ?> Days</span>
                </div>
            </div>

            <form method="POST" style="margin-bottom:10px;">
                <input type="hidden" name="barcode" value="<?php echo htmlspecialchars($product['barcode']); ?>">
                <input type="hidden" name="action" value="add_inventory">
                <button type="submit" class="btn" style="width:100%;">+ Add Directly to My Food</button>
            </form>
            <a href="manage-food.php?scan_name=<?php echo urlencode($product['name']); ?>&scan_category=<?php echo urlencode($product['category']); ?>&scan_quantity=<?php echo urlencode($product['weight']); ?>&scan_storage=<?php echo urlencode($product['storage']); ?>&scan_expiry=<?php echo urlencode($product['expDate']); ?>&scan_image=<?php echo urlencode($product['image']); ?>" class="btn-outline-full" style="width:100%; text-align:center;">Customize Before Saving</a>
        </div>

        <div class="flow-arrow"><ion-icon name="arrow-forward"></ion-icon></div>

        <!-- Status -->
        <div class="mobile-card <?php echo $cardClass; ?>">
            <div class="card-header nav-header">
                <a href="scan.php"><ion-icon name="arrow-back"></ion-icon></a>
                <h2>Freshness Status</h2>
                <span></span>
            </div>
            <div class="status-visual-container">
                <div class="status-circle">
                    <div class="status-inner"><?php echo $statusText; ?></div>
                    <div class="status-score-label">Score</div>
                    <div class="status-score-val"><?php echo $fData['score']; ?>/100</div>
                </div>
            </div>
            <div class="status-message-box">
                <ion-icon name="<?php echo $iconType; ?>"></ion-icon>
                <div><?php echo $msgText; ?></div>
            </div>

            <?php if ($fData['status'] === 'soon' || $fData['status'] === 'spoiled'): ?>
                <div style="margin-top: 15px; padding: 15px; background:#f8fafc; border-radius: 8px; border: 1px dashed var(--primary-green); text-align:center;">
                    <p style="font-size:0.85rem; margin:0 0 10px;"><b><?php echo $fData['status'] === 'spoiled' ? 'Need a fresh replacement?' : 'Running low on time!'; ?></b></p>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="barcode" value="<?php echo htmlspecialchars($product['barcode']); ?>">
                        <input type="hidden" name="action" value="add_replacement">
                        <button type="submit" class="btn" style="width:100%; font-size:0.88rem; background:var(--warning-yellow);">+ Add to Replacement List</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php include 'includes/footer.php'; ?>
