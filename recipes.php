<?php
require_once 'includes/auth.php';
require_auth();
require_once 'includes/db.inc.php';
require_once 'includes/functions.php';

$user_id = (int)$_SESSION['user_id'];
$flash = '';

// Allow adding missing recipe ingredients directly to Replacement List
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_missing_ingredient'])) {
    $ingName = trim($_POST['add_missing_ingredient']);
    if ($ingName !== '') {
        $ins = $pdo->prepare("INSERT INTO replacement_list (user_id, food_name, quantity, category, notes, status) VALUES (?, ?, '1', 'Recipe Ingredient', 'Added from Smart Recipes', 'Pending')");
        $ins->execute([$user_id, $ingName]);
        $flash = "Added \"" . htmlspecialchars($ingName) . "\" to your Replacement List.";
    }
}

$showAll = isset($_GET['all']) && $_GET['all'] == '1';

// Fetch ONLY non-expired user food items
$stmt = $pdo->prepare("
    SELECT id, name, category, DATEDIFF(expiry_date, CURDATE()) AS days_left
    FROM food_items
    WHERE user_id = ? AND DATEDIFF(expiry_date, CURDATE()) >= 0
    ORDER BY days_left ASC
");
$stmt->execute([$user_id]);
$usable_items = $stmt->fetchAll();

// Fetch recipes and recipe_food_mapping
$db_recipes = $pdo->query("SELECT * FROM recipes ORDER BY id ASC")->fetchAll();
$mapStmt = $pdo->query("SELECT recipe_id, ingredient_information FROM recipe_food_mapping");
$recipeMappings = [];
foreach ($mapStmt->fetchAll() as $m) {
    $recipeMappings[(int)$m['recipe_id']][] = $m['ingredient_information'];
}

$matched_recipes = [];
foreach ($db_recipes as $r) {
    $ingredients_list = array_filter(array_map('trim', explode(',', $r['ingredients'] ?? '')));
    if (empty($ingredients_list) && isset($recipeMappings[(int)$r['id']])) {
        $ingredients_list = $recipeMappings[(int)$r['id']];
    }

    $match_count = 0;
    $uses_soon = false;
    $score = 0;
    $matched_ings = [];
    $missing_ings = [];

    foreach ($ingredients_list as $ing) {
        $isMatched = false;
        foreach ($usable_items as $ui) {
            if (ingredientMatchesUserItem($ing, $ui['name'])) {
                $isMatched = true;
                $match_count++;
                $score += 2;
                if ((int)$ui['days_left'] <= 3) {
                    $uses_soon = true;
                    $score += 5;
                }
                break;
            }
        }
        if ($isMatched) {
            $matched_ings[] = $ing;
        } else {
            $missing_ings[] = $ing;
        }
    }

    if ($showAll || $match_count > 0) {
        $r['score'] = $score;
        $r['match_count'] = $match_count;
        $r['uses_soon'] = $uses_soon;
        $r['parsed_ing'] = $ingredients_list;
        $r['matched_ings'] = $matched_ings;
        $r['missing_ings'] = $missing_ings;
        $matched_recipes[] = $r;
    }
}

usort($matched_recipes, function ($a, $b) {
    return $b['score'] <=> $a['score'];
});

include 'includes/header.php';
?>

<div class="container section">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:15px;">
        <div>
            <h2 style="margin:0;">Smart Recipe Suggestions</h2>
            <p style="margin:4px 0 0; color:var(--text-muted);">Recipes matched against your Fresh &amp; Use Soon inventory (expired items excluded).</p>
        </div>
        <div>
            <?php if ($showAll): ?>
                <a href="recipes.php" class="btn">Show Matching Only</a>
            <?php else: ?>
                <a href="recipes.php?all=1" class="btn" style="background:#475569;">Browse All Recipes</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?php echo $flash; ?></div>
    <?php endif; ?>

    <div class="alert alert-warning" style="margin-bottom: 30px;">
        <strong>Safety First:</strong> Expired ingredients are automatically excluded from recommendations. Recipes using items expiring in &le; 3 days are prioritized at the top.
    </div>

    <div class="grid">
        <?php if (count($matched_recipes) > 0): ?>
            <?php foreach ($matched_recipes as $recipe): ?>
                <div class="card" style="<?php echo $recipe['uses_soon'] ? 'border-top: 4px solid var(--warning-yellow);' : ''; ?>">
                    <?php if (!empty($recipe['image'])): ?>
                        <img src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="<?php echo htmlspecialchars($recipe['name']); ?>" style="width:100%; height:140px; object-fit:cover; border-radius:10px; margin-bottom:14px;">
                    <?php endif; ?>

                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                        <h3 style="color: var(--primary-green); margin:0;"><?php echo htmlspecialchars($recipe['name']); ?></h3>
                        <?php if ($recipe['uses_soon']): ?>
                            <span class="badge-usesoon">Uses Soon Item</span>
                        <?php elseif ($recipe['match_count'] > 0): ?>
                            <span class="badge-fresh"><?php echo $recipe['match_count']; ?> Match</span>
                        <?php endif; ?>
                    </div>

                    <p style="color: #666; font-size: 0.92rem; margin:10px 0;"><?php echo htmlspecialchars($recipe['description']); ?></p>
                    <hr style="border:none; border-top:1px solid #edf2f7;">

                    <strong style="font-size:0.9rem;">Ingredients (<?php echo count($recipe['matched_ings']); ?>/<?php echo count($recipe['parsed_ing']); ?> in pantry):</strong>
                    <ul style="padding-left: 18px; font-size: 0.9rem; margin: 8px 0 12px;">
                        <?php foreach ($recipe['parsed_ing'] as $ing): ?>
                            <?php $inPantry = in_array($ing, $recipe['matched_ings'], true); ?>
                            <li style="margin-bottom: 5px;">
                                <?php if ($inPantry): ?>
                                    <span style="color: var(--primary-green); font-weight: 700;">✔ <?php echo htmlspecialchars($ing); ?></span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;"><?php echo htmlspecialchars($ing); ?></span>
                                    <form method="POST" style="display:inline; margin-left:6px;">
                                        <input type="hidden" name="add_missing_ingredient" value="<?php echo htmlspecialchars($ing); ?>">
                                        <button type="submit" style="border:none; background:#f1f5f9; color:#334155; font-size:0.72rem; padding:2px 7px; border-radius:999px; cursor:pointer;">+ Buy</button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <hr style="border:none; border-top:1px solid #edf2f7;">
                    <p style="font-size: 0.9rem; margin-bottom:0;"><strong>Instructions:</strong> <?php echo htmlspecialchars($recipe['instructions']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card">
                <p>No recipe matches found for your current non-expired inventory. <a href="recipes.php?all=1" style="color:var(--primary-green); font-weight:700;">Browse all recipes</a> or <a href="manage-food.php" style="color:var(--primary-green); font-weight:700;">add more ingredients</a>.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
