<?php
// browse.php – controller for the product browsing page

session_start();

// use the existing authentication helper so we can find the logged-in user
require_once __DIR__ . '/Model/Auth.php';
require_once __DIR__ . '/Model/database.php';

$view = new stdClass();
$view->username = null;   // you can wire this up to session later
$view->isLogged = Authentication::isLoggedIn();  // reflect actual logged-in state

// if logged in, store the display name (useful for header templates etc.)
if ($view->isLogged) {
    $view->username = Authentication::full_name();
}

// simple flash message system so we can show "Added to wish list" etc.
// (used as a non-JavaScript fallback – AJAX will usually show messages on the client side)
$view->flashMessage = $_SESSION['flash_message'] ?? null;
$view->flashType    = $_SESSION['flash_type'] ?? 'success'; // success or error
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// --- DB SETUP ---
// FIX: Added the missing slash before database.sqlite
$dbPath = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
}

// --- HANDLE "ADD TO WISHLIST" FROM BROWSING PAGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_wishlist'])) {

    // detect if this is an AJAX request (we explicitly send ajax=1 from JS)
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

    // IMPORTANT: redirects do not work nicely with fetch/AJAX, so return JSON instead
    if (!Authentication::isLoggedIn()) {

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'login',
                'message' => 'Please log in to add items to your wish list.',
            ]);
            exit;
        }

        // Non-AJAX fallback should still redirect properly
        Authentication::requireLogin(); // redirects to login.php and exits if not logged in
    }

    $currentUser = Authentication::getCurrentUser();

    $message = '';
    $type    = 'success'; // success or error

    if ($currentUser !== null && isset($_POST['variant_id'])) {
        $userId    = (int)$currentUser['id'];
        $variantId = (int)$_POST['variant_id'];

        if ($variantId > 0) {

            // First check if this item is already in the wishlist for this user
            $checkStmt = $pdo->prepare("
                SELECT id
                FROM wishlist_items
                WHERE user_id = :user_id
                  AND product_variant_id = :variant_id
            ");
            $checkStmt->execute([
                ':user_id'    => $userId,
                ':variant_id' => $variantId,
            ]);

            if ($checkStmt->fetch()) {
                // Already exists -> show a red error message instead of inserting again
                $message = 'This item is already in your wish list';
                $type    = 'error';
            } else {
                // Not in wishlist yet -> insert a new row
                $stmt = $pdo->prepare("
                    INSERT INTO wishlist_items (user_id, product_variant_id, added_at)
                    VALUES (:user_id, :variant_id, :added_at)
                ");
                $stmt->execute([
                    ':user_id'    => $userId,
                    ':variant_id' => $variantId,
                    ':added_at'   => date('Y-m-d H:i:s'),   // current time when added
                ]);

                $message = 'Added to wish list';
                $type    = 'success';
            }
        } else {
            // No variant selected (e.g. user didn't choose all three options)
            $message = 'Please choose a Colour, Finish and Polish before adding to your wish list.';
            $type    = 'error';
        }
    } else {
        $message = 'User not recognised';
        $type    = 'error';
    }

    if ($isAjax) {
        // For AJAX requests we return JSON instead of reloading the whole page
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => $type,    // "success" or "error"
            'message' => $message,
        ]);
        exit;
    }

    // Fallback: non-JS behaviour still uses the traditional redirect with flash message
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;

    // keep paging and search term when we redirect back to browsing
    $page  = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $query = isset($_POST['q']) ? trim($_POST['q']) : '';

    $redirectUrl = 'browse.php?page=' . max(1, $page);
    if ($query !== '') {
        $redirectUrl .= '&q=' . urlencode($query);
    }

    header('Location: ' . $redirectUrl);
    exit;
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

    // NOTE: adjust "finish" if your schema uses a different column name
    $variantStmt = $pdo->prepare("
        SELECT id, product_id, colour, finish, polish, price_trade
        FROM product_variants
        WHERE product_id IN ($placeholders)
          AND is_active = 1
        ORDER BY colour, finish, polish
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
// FIX: Added the missing slash before Views
require_once __DIR__ . "/Views/browse.phtml";
?>