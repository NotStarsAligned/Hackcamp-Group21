<?php
// Model/WishlistRepository.php

class WishlistRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Remove a variant from a user's wishlist.
     * Returns true if a row was removed.
     */
    public function removeItem(int $userId, int $variantId): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM wishlist_items
            WHERE user_id = :user_id
              AND product_variant_id = :variant_id
        ");
        $stmt->execute([
            ':user_id'    => $userId,
            ':variant_id' => $variantId,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Fetch wishlist items (each row is a product variant card), with search + pagination.
     * Returns:
     *  - items (array)
     *  - totalItems (int)
     *  - totalPages (int)
     *  - page (int)
     */
    public function getPagedItems(int $userId, string $searchTerm, int $page, int $perPage): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $termLike = '%' . $searchTerm . '%';

        // --- COUNT ---
        if ($searchTerm !== '') {
            $countSql = "
                SELECT COUNT(*)
                FROM wishlist_items wi
                INNER JOIN product_variants pv ON pv.id = wi.product_variant_id
                INNER JOIN products p ON p.id = pv.product_id
                WHERE wi.user_id = :user_id
                  AND (
                      p.name           LIKE :term
                      OR p.description LIKE :term
                      OR pv.colour     LIKE :term
                      OR pv.finish     LIKE :term
                      OR pv.polish     LIKE :term
                  )
            ";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $countStmt->bindValue(':term', $termLike, PDO::PARAM_STR);
            $countStmt->execute();
        } else {
            $countStmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM wishlist_items wi
                WHERE wi.user_id = :user_id
            ");
            $countStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $countStmt->execute();
        }

        $totalItems = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalItems / $perPage));

        // --- FETCH ITEMS ---
        if ($searchTerm !== '') {
            $itemsSql = "
                SELECT
                    wi.product_variant_id AS variant_id,
                    wi.added_at,
                    p.id AS product_id,
                    p.name,
                    p.description,
                    p.thumbnail_url,
                    pv.colour,
                    pv.finish,
                    pv.polish
                FROM wishlist_items wi
                INNER JOIN product_variants pv ON pv.id = wi.product_variant_id
                INNER JOIN products p ON p.id = pv.product_id
                WHERE wi.user_id = :user_id
                  AND (
                      p.name           LIKE :term
                      OR p.description LIKE :term
                      OR pv.colour     LIKE :term
                      OR pv.finish     LIKE :term
                      OR pv.polish     LIKE :term
                  )
                ORDER BY wi.added_at DESC
                LIMIT :limit OFFSET :offset
            ";
            $itemsStmt = $this->pdo->prepare($itemsSql);
            $itemsStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $itemsStmt->bindValue(':term', $termLike, PDO::PARAM_STR);
        } else {
            $itemsSql = "
                SELECT
                    wi.product_variant_id AS variant_id,
                    wi.added_at,
                    p.id AS product_id,
                    p.name,
                    p.description,
                    p.thumbnail_url,
                    pv.colour,
                    pv.finish,
                    pv.polish
                FROM wishlist_items wi
                INNER JOIN product_variants pv ON pv.id = wi.product_variant_id
                INNER JOIN products p ON p.id = pv.product_id
                WHERE wi.user_id = :user_id
                ORDER BY wi.added_at DESC
                LIMIT :limit OFFSET :offset
            ";
            $itemsStmt = $this->pdo->prepare($itemsSql);
            $itemsStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }

        $itemsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $itemsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $itemsStmt->execute();

        return [
            'items'      => $itemsStmt->fetchAll(PDO::FETCH_ASSOC),
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'page'       => $page,
        ];
    }
}
