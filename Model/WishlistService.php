<?php
// Model/WishlistService.php

class WishlistService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Try to add a product variant to a user's wishlist.
     * Returns ['type' => 'success'|'error', 'message' => '...']
     */
    public function addItem(int $userId, int $variantId): array
    {
        if ($variantId <= 0) {
            return [
                'type'    => 'error',
                'message' => 'Please choose a Colour, Finish and Polish before adding to your wish list.',
            ];
        }

        // First check if this item is already in the wishlist for this user
        $checkStmt = $this->pdo->prepare("
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
            return [
                'type'    => 'error',
                'message' => 'This item is already in your wish list',
            ];
        }

        // Not in wishlist yet -> insert a new row
        $stmt = $this->pdo->prepare("
            INSERT INTO wishlist_items (user_id, product_variant_id, added_at)
            VALUES (:user_id, :variant_id, :added_at)
        ");
        $stmt->execute([
            ':user_id'    => $userId,
            ':variant_id' => $variantId,
            ':added_at'   => date('Y-m-d H:i:s'),
        ]);

        return [
            'type'    => 'success',
            'message' => 'Added to wish list',
        ];
    }
}
