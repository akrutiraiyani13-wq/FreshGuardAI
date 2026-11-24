<?php
require_once 'includes/db.inc.php';
require_once 'includes/functions.php';

$sql = "
INSERT INTO food_items (user_id, name, category, quantity, purchase_date, expiry_date, storage_location, notes, status, image)
SELECT id, 'Farm Fresh Milk', 'Dairy', '1 Gallon', CURDATE() - INTERVAL 1 DAY, CURDATE() + INTERVAL 2 DAY, 'Fridge', 'For coffee', 'Use Soon', 'assets/img/milk.png'
FROM users WHERE id > 1;

INSERT INTO food_items (user_id, name, category, quantity, purchase_date, expiry_date, storage_location, notes, status, image)
SELECT id, 'Organic Eggs (Dozen)', 'Dairy', '12', CURDATE() - INTERVAL 3 DAY, CURDATE() + INTERVAL 10 DAY, 'Fridge', 'Breakfast', 'Fresh', 'assets/img/eggs.png'
FROM users WHERE id > 1;

INSERT INTO food_items (user_id, name, category, quantity, purchase_date, expiry_date, storage_location, notes, status, image)
SELECT id, 'Cherry Tomatoes', 'Vegetables', '1 Box', CURDATE() - INTERVAL 5 DAY, CURDATE() + INTERVAL 2 DAY, 'Fridge', 'Snacks', 'Use Soon', 'assets/img/tomatoes.png'
FROM users WHERE id > 1;

INSERT INTO food_items (user_id, name, category, quantity, purchase_date, expiry_date, storage_location, notes, status, image)
SELECT id, 'Chicken Breast', 'Meat', '2 lbs', CURDATE() - INTERVAL 1 DAY, CURDATE() + INTERVAL 4 DAY, 'Fridge', '', 'Fresh', 'assets/img/chicken.png'
FROM users WHERE id > 1;

INSERT INTO food_items (user_id, name, category, quantity, purchase_date, expiry_date, storage_location, notes, status, image)
SELECT id, 'Fresh Artisan Bread', 'Bakery', '1 Loaf', CURDATE() - INTERVAL 6 DAY, CURDATE() - INTERVAL 1 DAY, 'Counter', 'Make croutons', 'Expired', 'assets/img/bread.png'
FROM users WHERE id > 1;

INSERT INTO food_items (user_id, name, category, quantity, purchase_date, expiry_date, storage_location, notes, status, image)
SELECT id, 'Raw Potatoes', 'Vegetables', '1 Bag', CURDATE() - INTERVAL 2 DAY, CURDATE() + INTERVAL 14 DAY, 'Pantry', '', 'Fresh', 'assets/img/potatoes.png'
FROM users WHERE id > 1;
";

try {
    $pdo->exec($sql);
    foreach ($pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN) as $uid) {
        syncUserNotifications($pdo, (int)$uid);
    }
    echo "Successfully filled example data and synced notifications!";
} catch (Exception $e) {
    echo "Error inserting data: " . $e->getMessage();
}
