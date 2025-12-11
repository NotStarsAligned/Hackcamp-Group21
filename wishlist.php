<?php
require 'Model/database.php';
require_once __DIR__ . "/Model/Auth.php";
require_once __DIR__ . "/Views/template/header.phtml";

session_start();
Authentication::requireLogin();
$pdo = Database::getInstance()->getConnection();

// wishlist function
function addToWishlist(PDO $pdo, int $userId, int $variantId): bool
{
    $sql = "INSERT OR IGNORE INTO wishlist_items (user_id, product_variant_id, added_at) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$userId, $variantId, date('Y-m-d H:i:s')]);
}

function removeFromWishlist(PDO $pdo, int $userId, int $variantId): bool
{
    $sql = "DELETE FROM wishlist_items WHERE user_id = ? AND product_variant_id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$userId, $variantId]);
}

function getUserWishlist(PDO $pdo, int $userId): array
{
    $sql = "
        SELECT p.id, p.name, p.description, p.thumbnail_url, p.colour
        FROM wishlist_items w
        JOIN products p ON w.product_variant_id = p.id
        WHERE w.user_id = ?
          AND p.is_active = 1
        ORDER BY w.added_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------- HANDLER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId === 0) {
        die("You must be logged in to manage a wishlist.");
    }

    if (isset($_POST['add_wishlist']) && isset($_POST['variant_id'])) {
        $variantId = (int)$_POST['variant_id'];
        addToWishlist($pdo, $userId, $variantId);
    }

    if (isset($_POST['remove_wishlist']) && isset($_POST['variant_id'])) {
        $variantId = (int)$_POST['variant_id'];
        removeFromWishlist($pdo, $userId, $variantId);
    }

    header("Location: wishlist.phtml");
    exit;
}
include __DIR__ . "/Views/template/footer.phtml";