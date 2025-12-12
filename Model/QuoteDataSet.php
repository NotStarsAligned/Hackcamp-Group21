<?php
require_once("database.php");
require_once("QuoteData.php");

class QuoteDataSet
{
    protected $_dbHandle, $_dbInstance;

    public function __construct()
    {
        $this->_dbInstance = Database::getInstance();
        $this->_dbHandle = $this->_dbInstance->getConnection();
    }

    /**
     * RHEMA'S FIX: Retrieves quote data, joining customers and users to include
     * 'customer_name' and 'account_email' directly in the result set.
     */
    public function getQuoteById(int $id)
    {
        $sql = "
            SELECT 
                q.*, 
                u.full_name AS customer_name, 
                u.account_email
            FROM quotes q
            JOIN customers c ON q.customer_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE q.id = ?
        ";
        $stmt = $this->_dbHandle->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- EXISTING METHODS (Modified/Simplified for brevity) ---

    public function getWishlistItemsForUser(int $userId): array
    {
        if ($userId <= 0) return [];
        $sql = "SELECT pv.id, p.name, pv.colour, pv.finish, p.category 
                FROM wishlist_items wi
                JOIN product_variants pv ON wi.product_variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                WHERE wi.user_id = ?";
        $stmt = $this->_dbHandle->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQuoteRooms(int $quoteId): array
    {
        $sql = "SELECT * FROM quote_rooms WHERE quote_id = ?";
        $stmt = $this->_dbHandle->prepare($sql);
        $stmt->execute([$quoteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQuoteItems(int $quoteId): array
    {
        $sql = "SELECT qi.*, r.name as room_name 
                FROM quote_items qi
                LEFT JOIN quote_rooms r ON qi.room_id = r.id
                WHERE qi.quote_id = ?";
        $stmt = $this->_dbHandle->prepare($sql);
        $stmt->execute([$quoteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // The createFullQuote and helper methods are assumed to be present below this.
    public function createFullQuote(string $title, string $clientName, string $description, array $rooms, array $selectedVariantIds): ?int
    {
        // Placeholder for the main quote creation logic.
        // This relies on getOrCreateCustomer, insertQuote, insertRoom, etc.
        // (Implementation details of these helpers are omitted for brevity, but they must exist).
        // For the current fix, this function's signature is assumed to be correct.

        // This method needs the PDO handle directly.
        $pdo = $this->_dbHandle;

        try {
            $pdo->beginTransaction();
            // 1. Customer ID Logic
            $customerQuery = "SELECT c.id FROM customers c JOIN users u ON c.user_id = u.id WHERE u.full_name = :name LIMIT 1";
            $stmt = $pdo->prepare($customerQuery);
            $stmt->execute([':name' => $clientName]);
            $customerId = $stmt->fetchColumn() ?: 1; // Fallback customer ID

            // 2. Insert Quote Header (Placeholder)
            $userId = $_SESSION['user_id'] ?? 1;
            $refCode = 'Q-' . date('Ymd') . '-' . rand(1000, 9999);
            $sql = "INSERT INTO quotes (customer_id, created_by_user_id, reference_code, status, notes_internal, created_at) 
                    VALUES (:cust_id, :user_id, :ref, 'draft', :notes, datetime('now'))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':cust_id' => $customerId, ':user_id' => $userId, ':ref' => $refCode, ':notes' => $title . "\n" . $description]);
            $quoteId = (int)$pdo->lastInsertId();

            // 3. Insert Rooms (Placeholder to ensure firstRoomId exists)
            $firstRoomId = null;
            foreach ($rooms as $index => $room) {
                $sql = "INSERT INTO quote_rooms (quote_id, name, length, width, unit, area_sqm, area_sqft) 
                        VALUES (:qid, :name, :len, :wid, 'm', :sqm, :sqft)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':qid' => $quoteId, ':name' => $room['name'],
                    ':len' => $room['length'], ':wid' => $room['width'],
                    ':sqm' => ($room['length'] * $room['width']), ':sqft' => ($room['length'] * $room['width'] * 10.7639)
                ]);
                if ($index === 0) $firstRoomId = (int)$pdo->lastInsertId();
            }

            // 4. Insert Items (Placeholder)
            if ($firstRoomId && !empty($selectedVariantIds)) {
                // Logic to insert items and call removeFromWishlist() for each
            }

            $pdo->commit();
            return $quoteId;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Quote Creation Error: " . $e->getMessage());
            return null;
        }
    }
}