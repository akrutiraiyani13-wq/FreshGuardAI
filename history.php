<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';
require_once 'includes/data.php';

$user_id = (int)$_SESSION['user_id'];
$filterCat = $_GET['category'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');

$cats = $pdo->prepare("SELECT DISTINCT category FROM scan_history WHERE user_id = ? AND category IS NOT NULL AND category != '' ORDER BY category ASC");
$cats->execute([$user_id]);
$categories = $cats->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT * FROM scan_history WHERE user_id = :uid";
$params = [':uid' => $user_id];
if ($filterCat !== '') {
    $sql .= " AND category = :cat";
    $params[':cat'] = $filterCat;
}
if ($searchQuery !== '') {
    $sql .= " AND product_name LIKE :search";
    $params[':search'] = '%' . $searchQuery . '%';
}
$sql .= " ORDER BY scanned_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$filteredHistory = $stmt->fetchAll();

include 'includes/header.php';
?>

<section class="view active" style="align-items: center;">
    <div class="mobile-card" style="width: 100%; max-width: 680px; padding: 2rem;">
        <div class="card-header nav-header">
            <a href="scan.php"><ion-icon name="arrow-back"></ion-icon></a>
            <h2>Scan History</h2>
            <a href="dashboard.php"><ion-icon name="home-outline"></ion-icon></a>
        </div>

        <form method="GET" action="history.php" style="display:flex; gap:10px; margin-bottom: 20px; flex-wrap:wrap;">
            <input type="text" name="search" placeholder="Search scanned product..." style="flex:2; min-width:160px;" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <select name="category" style="flex:1; min-width:140px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($filterCat === $cat) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Filter</button>
        </form>

        <div class="history-list">
            <?php if (empty($filteredHistory)): ?>
                <div class="text-center" style="padding: 20px 0;">
                    <p>No scanning history found yet. <a href="scan.php" style="color:var(--primary-green); font-weight:700;">Scan a product &rarr;</a></p>
                </div>
            <?php else: ?>
                <?php foreach ($filteredHistory as $item): ?>
                    <?php
                    $fData = getFreshnessStatus($item['mfg_date'], $item['exp_date']);
                    $st = $fData['status'];
                    $pillClass = $st === 'fresh' ? 'fresh' : ($st === 'soon' ? 'soon' : 'spoiled');
                    $pillText = $st === 'soon' ? 'Use Soon' : ucfirst($st);
                    ?>
                    <div class="history-item" onclick="window.location.href='results.php?barcode=<?php echo urlencode($item['barcode']); ?>'">
                        <img class="history-item-img" src="<?php echo htmlspecialchars($item['image'] ?: 'assets/img/lays.png'); ?>" alt="Item">
                        <div class="history-item-details">
                            <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                            <p><?php echo htmlspecialchars($item['category']); ?> &bull; Scanned <?php echo htmlspecialchars(date('M j, Y', strtotime($item['scanned_at']))); ?></p>
                        </div>
                        <div class="pill <?php echo $pillClass; ?>"><?php echo $pillText; ?></div>
                        <ion-icon name="chevron-forward-outline"></ion-icon>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
