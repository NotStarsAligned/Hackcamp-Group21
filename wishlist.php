<?php
require 'Model/database.php';
require_once __DIR__ . "/Model/Auth.php";
require_once __DIR__ . "/Model/WishlistRepository.php";

session_start();

// keep the "logged-in only" behaviour your teammate started
Authentication::requireLogin();

// get the pdo connection
$pdo = Database::getInstance()->getConnection();

$repo = new WishlistRepository($pdo);

$view = new stdClass();

// simple flash message system so we can show "Removed from wish list" etc.
// (used as a non-JavaScript fallback – AJAX will usually show messages on the client side)
$view->flashMessage = $_SESSION['flash_message'] ?? null;
$view->flashType    = $_SESSION['flash_type'] ?? 'success'; // success or error
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// get logged-in user id (using your Auth helper)
$currentUser = Authentication::getCurrentUser();
$userId = isset($currentUser['id']) ? (int)$currentUser['id'] : (int)($_SESSION['user_id'] ?? 0);

if ($userId === 0) {
    // This should not happen because requireLogin() above, but safe fallback
    Authentication::requireLogin();
}

// --- HANDLE "REMOVE FROM WISHLIST" ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_wishlist'])) {

    $variantId = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;

    if ($variantId > 0) {
        $removed = $repo->removeItem($userId, $variantId);

        $message = $removed ? 'Removed from wish list' : 'Item not found in your wish list';
        $type    = $removed ? 'success' : 'error';
    } else {
        $message = 'Invalid item';
        $type    = 'error';
    }

    // detect if this is an AJAX request (we explicitly send ajax=1 from JS)
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

    if ($isAjax) {
        // For AJAX requests we return JSON instead of reloading the whole page
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => $type,
            'message' => $message,
        ]);
        exit;
    }

    // Fallback: non-JS behaviour still uses the traditional redirect with flash message
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;

    $page  = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $query = isset($_POST['q']) ? trim($_POST['q']) : '';

    $redirectUrl = 'wishlist.php?page=' . max(1, $page);
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

// Get page data from model
$pager = $repo->getPagedItems($userId, $searchTerm, $page, $itemsPerPage);

$view->items      = $pager['items'];
$view->totalItems = $pager['totalItems'];
$view->totalPages = $pager['totalPages'];
$view->page       = $pager['page'];

// --- Render the view ---
require_once __DIR__ . "/Views/wishlist.phtml";
