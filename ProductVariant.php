<?php
//TEK
// ProductVariant.php is in project root
// database.php is in /Model/database.php
require_once __DIR__ . "/Model/database.php";

class ProductVariant
{
    /**
     * Get RETAIL price for a given product_variant id.
     *
     * @param int $variantId
     * @return float|null   Returns price or null if not found
     */
    public static function getRetailPriceById(int $variantId): ?float
    {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT price_retail
                    FROM product_variants
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $variantId]);

            $price = $stmt->fetchColumn();

            if ($price === false) {
                return null; // no row found
            }

            return (float)$price;

        } catch (Exception $e) {
            error_log("Retail price error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get TRADE price for a given product_variant id.
     *
     * @param int $variantId
     * @return float|null   Returns price or null if not found
     */
    public static function getTradePriceById(int $variantId): ?float
    {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT price_trade
                    FROM product_variants
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $variantId]);

            $price = $stmt->fetchColumn();

            if ($price === false) {
                return null; // no row found
            }

            return (float)$price;

        } catch (Exception $e) {
            error_log("Trade price error: " . $e->getMessage());
            return null;
        }
    }
}
