<?php
// Model/TileRepository.php

class TileRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Fetch a paginated list of tile products with their variants and
     * precomputed colour/finish/polish options for each product.
     */
    public function getPagedTiles(string $searchTerm, int $page, int $perPage): array
    {
        $page      = max(1, $page);
        $offset    = ($page - 1) * $perPage;
        $termLike  = '%' . $searchTerm . '%';

        // --- Count total products for pagination ---
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
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->bindValue(':term', $termLike, PDO::PARAM_STR);
            $countStmt->execute();
        } else {
            $countStmt = $this->pdo->query("
                SELECT COUNT(*)
                FROM products
                WHERE category = 'tile'
                  AND is_active = 1
            ");
        }

        $totalProducts = (int)$countStmt->fetchColumn();
        $totalPages    = max(1, (int)ceil($totalProducts / $perPage));

        // --- Fetch products for this page ---
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

        $productStmt = $this->pdo->prepare($productSql);

        if ($searchTerm !== '') {
            $productStmt->bindValue(':term', $termLike, PDO::PARAM_STR);
        }

        $productStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $productStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $productStmt->execute();

        $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($products)) {
            return [
                'tiles'         => [],
                'totalProducts' => $totalProducts,
                'totalPages'    => $totalPages,
                'page'          => $page,
            ];
        }

        // --- Fetch variants for these products ---
        $productIds   = array_column($products, 'id');
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        // NOTE: adjust "finish" if your schema uses a different column name
        $variantStmt = $this->pdo->prepare("
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

        $variantsByProduct = [];
        foreach ($variants as $variant) {
            $pid = $variant['product_id'];
            if (!isset($variantsByProduct[$pid])) {
                $variantsByProduct[$pid] = [];
            }
            $variantsByProduct[$pid][] = $variant;
        }

        // --- Build tiles with variants + unique option lists ---
        $tiles = [];
        foreach ($products as $product) {
            $pid         = $product['id'];
            $prodVariants = $variantsByProduct[$pid] ?? [];

            $colours  = [];
            $finishes = [];
            $polishes = [];

            foreach ($prodVariants as $v) {
                if (!empty($v['colour']) && !in_array($v['colour'], $colours, true)) {
                    $colours[] = $v['colour'];
                }
                if (!empty($v['finish']) && !in_array($v['finish'], $finishes, true)) {
                    $finishes[] = $v['finish'];
                }
                if (!empty($v['polish']) && !in_array($v['polish'], $polishes, true)) {
                    $polishes[] = $v['polish'];
                }
            }

            $product['variants'] = $prodVariants;
            $product['colours']  = $colours;
            $product['finishes'] = $finishes;
            $product['polishes'] = $polishes;

            $tiles[] = $product;
        }

        return [
            'tiles'         => $tiles,
            'totalProducts' => $totalProducts,
            'totalPages'    => $totalPages,
            'page'          => $page,
        ];
    }
}
