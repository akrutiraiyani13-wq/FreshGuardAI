<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.inc.php';

function formatBarcodeProductRow(array $row): array {
    $today = new DateTime('today');
    $mfgDaysAgo = (int)($row['mfg_days_ago'] ?? 5);
    $shelfDays = max(1, (int)($row['shelf_life_days'] ?? 30));
    $mfgDate = (clone $today)->modify("-{$mfgDaysAgo} days")->format('Y-m-d');
    $expDate = (clone $today)->modify('-' . $mfgDaysAgo . ' days')->modify("+{$shelfDays} days")->format('Y-m-d');

    return [
        'id' => (int)$row['id'],
        'barcode' => $row['barcode'],
        'name' => $row['name'],
        'brand' => $row['brand'],
        'category' => $row['category'],
        'weight' => $row['weight'],
        'mfgDate' => $mfgDate,
        'expDate' => $expDate,
        'shelfLifeMonths' => round($shelfDays / 30, 1),
        'image' => $row['image'] ?: 'assets/img/lays.png',
        'storage' => $row['storage_location'] ?: 'Pantry',
        'price' => (float)$row['price'],
        'nutrition' => [
            'calories' => $row['calories'] ?: 'N/A',
            'sugar' => $row['sugar'] ?: 'N/A',
            'protein' => $row['protein'] ?: 'N/A',
        ],
    ];
}

function getAllBarcodeProducts(?PDO $pdoConn = null): array {
    global $pdo;
    $db = $pdoConn ?? $pdo;
    $rows = $db->query("SELECT * FROM barcode_products ORDER BY id ASC")->fetchAll();
    return array_map('formatBarcodeProductRow', $rows);
}

function getProductByBarcode(string $code, ?PDO $pdoConn = null): ?array {
    global $pdo;
    $db = $pdoConn ?? $pdo;
    $stmt = $db->prepare("SELECT * FROM barcode_products WHERE barcode = ? LIMIT 1");
    $stmt->execute([trim($code)]);
    $row = $stmt->fetch();
    return $row ? formatBarcodeProductRow($row) : null;
}

function getFreshnessStatus(string $mfgStr, string $expStr): array {
    $currentDate = new DateTime('today');
    $mfgDate = new DateTime($mfgStr);
    $expDate = new DateTime($expStr);

    $totalDays = max(1, $mfgDate->diff($expDate)->days);
    $remaining = $currentDate->diff($expDate);
    $daysLeft = $remaining->invert ? -$remaining->days : $remaining->days;

    $status = 'fresh';
    $score = max(0, min(100, (int)round(($daysLeft / $totalDays) * 100)));

    if ($daysLeft < 0) {
        $status = 'spoiled';
        $score = 0;
        $daysLeft = 0;
    } elseif ($daysLeft <= 3 || $daysLeft < ($totalDays * 0.35)) {
        $status = 'soon';
    }

    return [
        'daysLeft' => $daysLeft,
        'status' => $status,
        'score' => $score,
    ];
}

function addToHistory(array $product, string $status, int $score = 0, ?PDO $pdoConn = null, ?int $userId = null): void {
    global $pdo;
    $db = $pdoConn ?? $pdo;
    $uid = $userId ?? ($_SESSION['user_id'] ?? null);

    if ($uid) {
        $del = $db->prepare("DELETE FROM scan_history WHERE user_id = ? AND barcode = ?");
        $del->execute([$uid, $product['barcode']]);

        $ins = $db->prepare("
            INSERT INTO scan_history (user_id, barcode, product_name, category, image, mfg_date, exp_date, price, freshness_status, freshness_score)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $uid,
            $product['barcode'],
            $product['name'],
            $product['category'],
            $product['image'],
            $product['mfgDate'],
            $product['expDate'],
            $product['price'],
            $status,
            $score,
        ]);
    }

    if (!isset($_SESSION['history'])) {
        $_SESSION['history'] = [];
    }
    $item = [
        'barcode' => $product['barcode'],
        'name' => $product['name'],
        'category' => $product['category'],
        'image' => $product['image'],
        'mfgDate' => $product['mfgDate'],
        'expDate' => $product['expDate'],
        'price' => $product['price'],
        'status' => $status,
        'score' => $score,
        'added_at' => date('Y-m-d H:i:s'),
    ];
    $_SESSION['history'] = array_values(array_filter($_SESSION['history'], function ($h) use ($item) {
        return $h['barcode'] !== $item['barcode'];
    }));
    array_unshift($_SESSION['history'], $item);
}
