<?php
/**
 * Shared helper functions for FreshGuardAI.
 */

function computeFoodStatus(string $expiryDate): array {
    $today = new DateTime('today');
    try {
        $exp = new DateTime($expiryDate);
    } catch (Exception $e) {
        return ['status' => 'Fresh', 'days_left' => 0, 'badge' => 'badge-fresh'];
    }

    $diff = $today->diff($exp);
    $daysLeft = $diff->invert ? -$diff->days : $diff->days;

    if ($daysLeft < 0) {
        return ['status' => 'Expired', 'days_left' => $daysLeft, 'badge' => 'badge-expired'];
    }
    if ($daysLeft <= 3) {
        return ['status' => 'Use Soon', 'days_left' => $daysLeft, 'badge' => 'badge-usesoon'];
    }
    return ['status' => 'Fresh', 'days_left' => $daysLeft, 'badge' => 'badge-fresh'];
}

function guessFoodImage(string $name, string $category = ''): string {
    $haystack = strtolower($name . ' ' . $category);
    $map = [
        'milk'       => 'https://images.unsplash.com/photo-1563636619-e9143da7973b?auto=format&fit=crop&w=500&q=80',
        'yogurt'     => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=500&q=80',
        'cheese'     => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?auto=format&fit=crop&w=500&q=80',
        'egg'        => 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=500&q=80',
        'tomato'     => 'https://images.unsplash.com/photo-1546094096-0df4bcaaa337?auto=format&fit=crop&w=500&q=80',
        'spinach'    => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?auto=format&fit=crop&w=500&q=80',
        'salad'      => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?auto=format&fit=crop&w=500&q=80',
        'potato'     => 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=500&q=80',
        'chicken'    => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=500&q=80',
        'beef'       => 'https://images.unsplash.com/photo-1603048297172-c92544798d5a?auto=format&fit=crop&w=500&q=80',
        'cookie'     => 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=500&q=80',
        'bread'      => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=500&q=80',
        'apple'      => 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=500&q=80',
        'strawberr'  => 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?auto=format&fit=crop&w=500&q=80',
        'juice'      => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=500&q=80',
        'noodle'     => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=500&q=80',
        'chip'       => 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?auto=format&fit=crop&w=500&q=80',
        // Generic category fallbacks
        'dairy'      => 'https://images.unsplash.com/photo-1563636619-e9143da7973b?auto=format&fit=crop&w=500&q=80',
        'meat'       => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=500&q=80',
        'bakery'     => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=500&q=80',
        'fruit'      => 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?auto=format&fit=crop&w=500&q=80',
        'beverage'   => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=500&q=80',
        'snack'      => 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?auto=format&fit=crop&w=500&q=80',
        'vegetable'  => 'https://images.unsplash.com/photo-1546094096-0df4bcaaa337?auto=format&fit=crop&w=500&q=80',
    ];
    foreach ($map as $keyword => $path) {
        if (strpos($haystack, $keyword) !== false) {
            return $path;
        }
    }
    return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=500&q=80';
}

function syncUserNotifications(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare("
        SELECT id, name, DATEDIFF(expiry_date, CURDATE()) AS days_left
        FROM food_items
        WHERE user_id = ? AND DATEDIFF(expiry_date, CURDATE()) <= 3
    ");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();

    $checkStmt = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND food_id = ? AND type = ? LIMIT 1");
    $insertStmt = $pdo->prepare("INSERT INTO notifications (user_id, food_id, message, type, is_read) VALUES (?, ?, ?, ?, 0)");

    foreach ($items as $item) {
        $days = (int)$item['days_left'];
        if ($days < 0) {
            $type = 'expired';
            $msg = "{$item['name']} has expired! Please dispose of it safely and consider adding it to your replacement list.";
        } else {
            $type = 'use_soon';
            $when = $days === 0 ? 'today' : ($days === 1 ? 'tomorrow' : "in {$days} days");
            $msg = "{$item['name']} expires {$when}. Check Recipes for ideas before it goes to waste!";
        }

        $checkStmt->execute([$userId, $item['id'], $type]);
        if (!$checkStmt->fetch()) {
            $insertStmt->execute([$userId, $item['id'], $msg, $type]);
        }
    }
}

function getUnreadNotificationCount(PDO $pdo, int $userId): int {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function ingredientMatchesUserItem(string $recipeIngredient, string $userItemName): bool {
    $ing = strtolower(trim($recipeIngredient));
    $item = strtolower(trim($userItemName));
    if ($ing === '' || $item === '') {
        return false;
    }
    if ($ing === $item || strpos($item, $ing) !== false || strpos($ing, $item) !== false) {
        return true;
    }
    $ingNorm = preg_replace('/(es|s)$/', '', $ing);
    $itemNorm = preg_replace('/(es|s)$/', '', $item);
    if ($ingNorm !== '' && strlen($ingNorm) >= 3 && (strpos($itemNorm, $ingNorm) !== false || strpos($ingNorm, $itemNorm) !== false)) {
        return true;
    }
    return false;
}
