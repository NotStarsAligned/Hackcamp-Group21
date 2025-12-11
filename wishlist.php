<?php
require 'Model/database.php';
session_start();

/**
 * Add a product variant to the user's wishlist
 */
function addToWishlist(PDO $pdo, int $userId, int $variantId) {
    $sql = "INSERT OR IGNORE INTO wishlist_items (user_id, product_variant_id, added_at) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$userId, $variantId, date('Y-m-d H:i:s')]);
}

/**
 * Remove a product variant from the user's wishlist
 */
function removeFromWishlist(PDO $pdo, int $userId, int $variantId) {
    $sql = "DELETE FROM wishlist_items WHERE user_id = ? AND product_variant_id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$userId, $variantId]);
}

/**
 * Get all wishlist items for a user
 */
//need to conect to product table when the browse items are added
function getUserWishlist(PDO $pdo, int $userId) {
    $sql = "SELECT pv.id AS variant_id, p.name AS product_name, pv.name AS variant_name, pv.price
            FROM product_variants pv
            JOIN products p ON pv.product_id = p.id
            JOIN wishlist_items w ON pv.id = w.product_variant_id
            WHERE w.user_id = ?
            ORDER BY w.added_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// HANDLER
$userId = $_SESSION['user_id'] ?? 0;

if ($userId === 0) {
    die("You must be logged in to manage a wishlist.");
}

// Add to wishlist
if (isset($_POST['add_wishlist']) && isset($_POST['variant_id'])) {
    $variantId = (int)$_POST['variant_id'];
    addToWishlist($pdo, $userId, $variantId);
}

// Remove from wishlist
if (isset($_POST['remove_wishlist']) && isset($_POST['variant_id'])) {
    $variantId = (int)$_POST['variant_id'];
    removeFromWishlist($pdo, $userId, $variantId);
}

// Redirect back to wishlist page
header("Location: wishlist.phtml");
exit;