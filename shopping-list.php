<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';
require_once 'includes/data.php';

$user_id = (int)$_SESSION['user_id'];
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_name'])) {
    $foodName = trim($_POST['add_name']);
    $category = trim($_POST['add_category'] ?? '');
    if ($foodName !== '') {
        $ins = $pdo->prepare("INSERT INTO replacement_list (user_id, food_name, quantity, category, notes, status) VALUES (?, ?, '1', ?, 'Added from Restock Suggestions', 'Pending')");
        $ins->execute([$user_id, $foodName, $category ?: null]);
        $flash = "\"" . htmlspecialchars($foodName) . "\" added to your Replacement List!";
    }
}

$urgentList = [];
$soonList = [];

// Pull from user's food inventory (expired or expiring soon)
$invStmt = $pdo->prepare("SELECT name, category, image, DATEDIFF(expiry_date, CURDATE()) AS days_left FROM food_items WHERE user_id = ? AND DATEDIFF(expiry_date, CURDATE()) <= 3 ORDER BY expiry_date ASC");
$invStmt->execute([$user_id]);
foreach ($invStmt->fetchAll() as $row) {
    $key = strtolower($row['name']);
    if ((int)$row['days_left'] < 0) {
        $urgentList[$key] = ['name' => $row['name'], 'category' => $row['category'], 'image' => $row['image'] ?: 'assets/img/bread.png'];
    } else {
        $soonList[$key] = ['name' => $row['name'], 'category' => $row['category'], 'image' => $row['image'] ?: 'assets/img/milk.png'];
    }
}

// Pull from user's scan history
$scanStmt = $pdo->prepare("SELECT product_name, category, image, mfg_date, exp_date FROM scan_history WHERE user_id = ? ORDER BY scanned_at DESC");
$scanStmt->execute([$user_id]);
foreach ($scanStmt->fetchAll() as $s) {
    $f = getFreshnessStatus($s['mfg_date'], $s['exp_date']);
    $key = strtolower($s['product_name']);
    if ($f['status'] === 'spoiled' && !isset($urgentList[$key])) {
        $urgentList[$key] = ['name' => $s['product_name'], 'category' => $s['category'], 'image' => $s['image'] ?: 'assets/img/lays.png'];
    } elseif ($f['status'] === 'soon' && !isset($soonList[$key]) && !isset($urgentList[$key])) {
        $soonList[$key] = ['name' => $s['product_name'], 'category' => $s['category'], 'image' => $s['image'] ?: 'assets/img/lays.png'];
    }
}

include 'includes/header.php';
?>

<section class="view active" style="align-items: center;">
    <div class="mobile-card" style="width: 100%; max-width: 680px; padding: 2rem;">
        <div class="card-header nav-header">
            <a href="dashboard.php"><ion-icon name="arrow-back"></ion-icon></a>
            <h2>Smart Restock Suggestions</h2>
            <a href="replacement-list.php" title="Replacement List"><ion-icon name="list-outline"></ion-icon></a>
        </div>

        <p style="color:var(--text-muted); text-align:center; margin-bottom: 20px;">Automatically suggested from your Expired &amp; Use Soon items and Scan History.</p>

        <?php if ($flash): ?>
            <div class="alert alert-success"><?php echo $flash; ?> <a href="replacement-list.php" style="font-weight:700; text-decoration:underline; margin-left:6px;">Open List &rarr;</a></div>
        <?php endif; ?>

        <div class="history-list">
            <?php if (empty($urgentList) && empty($soonList)): ?>
                <div class="text-center" style="padding: 2rem 0;">
                    <ion-icon name="happy-outline" style="font-size: 3rem; color: var(--primary-green);"></ion-icon>
                    <p style="margin-top: 15px;">Your pantry looks great! Nothing urgent to restock.</p>
                </div>
            <?php else: ?>

                <?php if (!empty($urgentList)): ?>
                    <h3 style="color:var(--danger-red); font-size: 1.05rem; margin:15px 0 8px; border-bottom: 1px solid #edf2f7; padding-bottom: 6px;">
                        Urgent to Buy (Expired / Spoiled)
                    </h3>
                    <?php foreach ($urgentList as $item): ?>
                        <div class="history-item" style="cursor:default;">
                            <img class="history-item-img" src="<?php echo htmlspecialchars($item['image']); ?>" alt="Item">
                            <div class="history-item-details">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p><?php echo htmlspecialchars($item['category']); ?></p>
                            </div>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="add_name" value="<?php echo htmlspecialchars($item['name']); ?>">
                                <input type="hidden" name="add_category" value="<?php echo htmlspecialchars($item['category']); ?>">
                                <button type="submit" class="btn" style="padding: 6px 14px; font-size: 0.82rem; background: var(--danger-red);">+ Add</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($soonList)): ?>
                    <h3 style="color:var(--warning-yellow); font-size: 1.05rem; margin:22px 0 8px; border-bottom: 1px solid #edf2f7; padding-bottom: 6px;">
                        Buy Soon (Nearing Expiry)
                    </h3>
                    <?php foreach ($soonList as $item): ?>
                        <div class="history-item" style="cursor:default;">
                            <img class="history-item-img" src="<?php echo htmlspecialchars($item['image']); ?>" alt="Item">
                            <div class="history-item-details">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p><?php echo htmlspecialchars($item['category']); ?></p>
                            </div>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="add_name" value="<?php echo htmlspecialchars($item['name']); ?>">
                                <input type="hidden" name="add_category" value="<?php echo htmlspecialchars($item['category']); ?>">
                                <button type="submit" class="btn-outline-full" style="padding: 6px 14px; font-size: 0.82rem;">Plan It</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
