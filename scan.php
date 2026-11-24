<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';
require_once 'includes/data.php';

$products = getAllBarcodeProducts($pdo);
$error = isset($_GET['error']) && $_GET['error'] === 'notfound' ? 'Barcode not found in catalog. Try one of the sample barcodes below.' : '';

include 'includes/header.php';
?>

<div class="container section">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
        <div>
            <h2 style="margin:0;">Barcode Food Scanner</h2>
            <p style="margin:4px 0 0; color:var(--text-muted);">Enter a barcode or pick a sample product to check freshness score, nutrition, and add to your pantry.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="history.php" class="btn" style="background:#6366f1;">Scan History</a>
            <a href="shopping-list.php" class="btn" style="background:#475569;">Restock Suggestions</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Manual Barcode Input -->
    <div style="max-width: 600px; margin: 0 auto 35px; padding: 30px; background: #fff; border: 2px dashed var(--primary-green); border-radius: 16px; text-align:center; box-shadow: var(--shadow-sm);">
        <div style="font-size: 2.5rem; margin-bottom: 10px;">📷</div>
        <h3 style="margin-top:0;">Enter or Scan Barcode</h3>
        <form action="results.php" method="GET" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:15px;">
            <input type="text" name="barcode" placeholder="e.g. 8901234567891" required style="flex:1; min-width:200px;">
            <button type="submit" class="btn">Check Freshness</button>
        </form>
    </div>

    <!-- Product Barcode Catalog -->
    <h3>Sample Barcode Catalog (Click to Scan)</h3>
    <div class="grid">
        <?php foreach ($products as $p): ?>
            <?php $f = getFreshnessStatus($p['mfgDate'], $p['expDate']); ?>
            <div class="card">
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:12px;">
                    <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" style="width:60px; height:60px; object-fit:contain; background:#f8fafc; border-radius:10px; padding:4px;">
                    <div>
                        <h3 style="margin:0; font-size:1.05rem;"><?php echo htmlspecialchars($p['name']); ?></h3>
                        <small style="color:var(--text-muted);"><?php echo htmlspecialchars($p['brand']); ?> &bull; <?php echo htmlspecialchars($p['category']); ?></small>
                    </div>
                </div>
                <p style="font-size:0.85rem; margin:4px 0;"><strong>Barcode:</strong> <code><?php echo htmlspecialchars($p['barcode']); ?></code></p>
                <p style="font-size:0.85rem; margin:4px 0;"><strong>Weight:</strong> <?php echo htmlspecialchars($p['weight']); ?> &bull; <strong>Storage:</strong> <?php echo htmlspecialchars($p['storage']); ?></p>
                <div style="display:flex; gap:8px; margin-top:14px;">
                    <a href="results.php?barcode=<?php echo urlencode($p['barcode']); ?>" class="btn" style="flex:1; padding:8px 12px; font-size:0.85rem;">Scan Product</a>
                    <a href="manage-food.php?scan_name=<?php echo urlencode($p['name']); ?>&scan_category=<?php echo urlencode($p['category']); ?>&scan_quantity=<?php echo urlencode($p['weight']); ?>&scan_storage=<?php echo urlencode($p['storage']); ?>&scan_expiry=<?php echo urlencode($p['expDate']); ?>&scan_image=<?php echo urlencode($p['image']); ?>" class="btn-outline-full" style="padding:8px 12px; font-size:0.85rem;">+ Pantry</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
