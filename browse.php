<?php
// browse.php – controller for the product browsing page

session_start();

$view = new stdClass();
$view->username = null;   // you can wire this up to session later
$view->isLogged = false;

// --- DB SETUP ---
$dbPath = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
}

// --- SEARCH TERM ---
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$view->searchTerm = $searchTerm;

// --- PAGINATION SETTINGS ---
$itemsPerPage = 9; // 3 wide grid x 3 rows
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// --- COUNT TOTAL TILE PRODUCTS (WITH OPTIONAL SEARCH) ---
if ($searchTerm !== '') {
    $countSql = "
        SELECT COUNT(*)
        FROM products
        WHERE category = 'tile'
          AND is_active = 1
          AND (
              name           LIKE :term
              OR description LIKE :term
              OR colour      LIKE :term
          )
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->bindValue(':term', '%' . $searchTerm . '%', PDO::PARAM_STR);
    $countStmt->execute();
} else {
    $countStmt = $pdo->query("
        SELECT COUNT(*)
        FROM products
        WHERE category = 'tile'
          AND is_active = 1
    ");
}

$view->totalProducts = (int)$countStmt->fetchColumn();
$view->totalPages = max(1, (int)ceil($view->totalProducts / $itemsPerPage));
$view->page = $page;

// --- FETCH PRODUCTS FOR CURRENT PAGE (WITH OPTIONAL SEARCH) ---
$productSql = "
    SELECT id, name, description, thumbnail_url, colour
    FROM products
    WHERE category = 'tile'
      AND is_active = 1
";

if ($searchTerm !== '') {
    $productSql .= "
      AND (
          name           LIKE :term
          OR description LIKE :term
          OR colour      LIKE :term
      )
    ";
}

$productSql .= "
    ORDER BY id
    LIMIT :limit OFFSET :offset
";

$productStmt = $pdo->prepare($productSql);

if ($searchTerm !== '') {
    $productStmt->bindValue(':term', '%' . $searchTerm . '%', PDO::PARAM_STR);
}

$productStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$productStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$productStmt->execute();
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

// --- FETCH VARIANTS FOR THESE PRODUCTS ---
$variantsByProduct = [];
if (!empty($products)) {
    $productIds = array_column($products, 'id');
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $variantStmt = $pdo->prepare("
        SELECT id, product_id, colour, polish, price_trade
        FROM product_variants
        WHERE product_id IN ($placeholders)
          AND is_active = 1
        ORDER BY colour, polish
    ");

    foreach ($productIds as $i => $pid) {
        $variantStmt->bindValue($i + 1, $pid, PDO::PARAM_INT);
    }

    $variantStmt->execute();
    $variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($variants as $variant) {
        $pid = $variant['product_id'];
        if (!isset($variantsByProduct[$pid])) {
            $variantsByProduct[$pid] = [];
        }
        $variantsByProduct[$pid][] = $variant;
    }
}

// Build a tiles array with variants attached so the view is simple
$view->tiles = [];
foreach ($products as $product) {
    $pid = $product['id'];
    $product['variants'] = $variantsByProduct[$pid] ?? [];
    $view->tiles[] = $product;
}

// --- Render the view ---
require_once __DIR__ . "/Views/browse.phtml";
