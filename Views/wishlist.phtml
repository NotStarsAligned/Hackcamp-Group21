<?php
require 'wishlist.php';
$userId = $_SESSION['user_id'] ?? 0;

if ($userId === 0) {
    die("Please log in to view your wishlist.");
}

$wishlistItems = getUserWishlist($pdo, $userId);
?>
<?php if (empty($wishlistItems)): ?>
    <p>Your wishlist is empty.</p>
<?php else: ?>
    <?php foreach ($wishlistItems as $item): ?>
        <div class="product">
            <strong><?= htmlspecialchars($item['product_name']) ?> - <?= htmlspecialchars($item['variant_name']) ?></strong><br>
            Price: £<?= number_format($item['price'], 2) ?>
            <form method="POST" action="/wishlist.php">
                <input type="hidden" name="variant_id" value="<?= $item['variant_id'] ?>">
                <button type="submit" name="remove_wishlist" class="btn btn-remove">Remove</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
