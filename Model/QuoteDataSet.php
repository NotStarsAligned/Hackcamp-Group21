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

    public function getWishlistItemsForUser(int $userId): array
    {
        if ($userId <= 0) return [];
        // Fetching category as well to help calculation logic [cite: 62]
        $sql = "SELECT pv.id, p.name, pv.colour, pv.finish, p.category 
                FROM wishlist_items wi
                JOIN product_variants pv ON wi.product_variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                WHERE wi.user_id = ?";

        $stmt = $this->_dbHandle->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createFullQuote(string $title, string $clientName, string $description, array $rooms, array $wishlistItems): ?int
    {
        try {
            $this->_dbHandle->beginTransaction();

            $customerId = $this->getOrCreateCustomer($clientName);
            $quoteId = $this->insertQuote($customerId, $title, $description);

            // --- 1. Calculate Total Job Dimensions ---
            $totalAreaSqm = 0.0;
            $totalPerimeterM = 0.0;
            $firstRoomId = null;

            foreach ($rooms as $index => $room) {
                $roomId = $this->insertRoom($quoteId, $room);
                if ($index === 0) $firstRoomId = $roomId;

                $length = (float)$room['length'];
                $width = (float)$room['width'];

                $totalAreaSqm += ($length * $width);
                $totalPerimeterM += (2 * ($length + $width));
            }

            if (!$firstRoomId || empty($wishlistItems)) {
                $this->_dbHandle->commit();
                return $quoteId;
            }

            // --- 2. Process Implicit Wishlist Items ---
            foreach ($wishlistItems as $item) {
                $variantId = (int)$item['id'];

                // Fetch full details including dimensions for calculation
                $product = $this->getProductDetails($variantId);

                if ($product) {
                    $quantity = 1;
                    $unit = 'unit';
                    $lineTotal = 0.00;
                    $price = (float)($product['price_retail'] ?? 0.00);

                    // --- CALCULATION LOGIC ---
                    if ($product['category'] === 'tile') {
                        // 1. Get Tile Dimensions (Try Variant first, then Product)
                        $lenMM = !empty($product['length_mm']) ? $product['length_mm'] : ($product['tile_length_mm'] ?? 300);
                        $widMM = !empty($product['width_mm']) ? $product['width_mm'] : ($product['tile_width_mm'] ?? 300);

                        // Convert to Meters
                        $lenM = $lenMM / 1000;
                        $widM = $widMM / 1000;
                        $areaPerTile = $lenM * $widM;

                        if ($areaPerTile > 0) {
                            // Calculate Tiles needed + 10% wastage
                            $rawQty = $totalAreaSqm / $areaPerTile;
                            $quantity = ceil($rawQty * 1.10);
                            $unit = 'tile';
                        }
                    }
                    elseif ($product['category'] === 'trim') {
                        // Standard Trim length usually 2.5m
                        $trimLen = 2.5;
                        $quantity = ceil($totalPerimeterM / $trimLen);
                        $unit = 'length';
                    }
                    elseif ($product['category'] === 'consumable') {
                        // Rough estimate: 1 unit per 10 sqm
                        $quantity = ceil($totalAreaSqm / 10);
                    }

                    $lineTotal = $quantity * $price;

                    // Insert and Remove
                    $this->insertQuoteItemWithQty($quoteId, $firstRoomId, $variantId, $product, $quantity, $unit, $price, $lineTotal);
                    $this->removeFromWishlist($variantId);
                }
            }

            $this->_dbHandle->commit();
            return $quoteId;

        } catch (Exception $e) {
            $this->_dbHandle->rollBack();
            error_log("Quote Creation Error: " . $e->getMessage());
            return null;
        }
    }

    private function getProductDetails(int $variantId)
    {
        // Fetches product dimensions from both tables
        $sql = "SELECT p.id as product_id, p.name, p.category, 
                       p.tile_length_mm, p.tile_width_mm,
                       pv.price_retail, pv.colour, pv.finish,
                       pv.length_mm, pv.width_mm
                FROM product_variants pv
                JOIN products p ON p.id = pv.product_id
                WHERE pv.id = ?";
        $stmt = $this->_dbHandle->prepare($sql);
        $stmt->execute([$variantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function insertQuoteItemWithQty(int $quoteId, int $roomId, int $variantId, array $product, float $qty, string $unit, float $price, float $total): void
    {
        $desc = $product['name'] . " - " . ($product['colour'] ?? '') . " (" . $unit . ")";

        $sql = "INSERT INTO quote_items (quote_id, room_id, product_id, product_variant_id, item_type, description, quantity, unit_price, line_total) 
                VALUES (:qid, :rid, :pid, :vid, :cat, :desc, :qty, :price, :total)";

        $stmt = $this->_dbHandle->prepare($sql);
        $stmt->execute([
            ':qid' => $quoteId, ':rid' => $roomId,
            ':pid' => $product['product_id'], ':vid' => $variantId,
            ':cat' => $product['category'] ?? 'other',
            ':desc' => $desc, ':qty' => $qty, ':price' => $price, ':total' => $total
        ]);
    }

    // ... (keep getOrCreateCustomer, insertQuote, insertRoom, removeFromWishlist same as previous) ...
    private function getOrCreateCustomer(string $fullName): int {
        $stmt = $this->_dbHandle->prepare("SELECT c.id FROM customers c JOIN users u ON c.user_id = u.id WHERE u.full_name = ?");
        $stmt->execute([$fullName]);
        if ($id = $stmt->fetchColumn()) return (int)$id;

        // Quick Create logic
        $email = strtolower(str_replace(' ', '.', $fullName)) . rand(100,999) . '@example.com';
        $this->_dbHandle->prepare("INSERT INTO users (full_name, account_email, password_hashed, role) VALUES (?, ?, ?, 'customer')")
            ->execute([$fullName, $email, password_hash('x', PASSWORD_DEFAULT)]);
        $uid = $this->_dbHandle->lastInsertId();
        $this->_dbHandle->prepare("INSERT INTO customers (user_id) VALUES (?)")->execute([$uid]);
        return (int)$this->_dbHandle->lastInsertId();
    }

    private function insertQuote(int $cid, string $title, string $notes): int {
        $uid = $_SESSION['user_id'] ?? 1;
        $ref = 'Q-' . date('Ymd') . '-' . rand(100,999);
        $this->_dbHandle->prepare("INSERT INTO quotes (customer_id, created_by_user_id, reference_code, status, notes_internal, created_at) VALUES (?, ?, ?, 'draft', ?, datetime('now'))")
            ->execute([$cid, $uid, $ref, $title . "\n" . $notes]);
        return (int)$this->_dbHandle->lastInsertId();
    }

    private function insertRoom(int $qid, array $r): int {
        $this->_dbHandle->prepare("INSERT INTO quote_rooms (quote_id, name, length, width, unit, area_sqm, area_sqft) VALUES (?, ?, ?, ?, 'm', ?, ?)")
            ->execute([$qid, $r['name'], $r['length'], $r['width'], $r['length']*$r['width'], $r['length']*$r['width']*10.76]);
        return (int)$this->_dbHandle->lastInsertId();
    }

    private function removeFromWishlist(int $vid): void {
        $uid = $_SESSION['user_id'] ?? 0;
        $this->_dbHandle->prepare("DELETE FROM wishlist_items WHERE user_id = ? AND product_variant_id = ?")->execute([$uid, $vid]);
    }
    // ... inside Model/QuoteDataSet.php ...

    public function getQuoteById(int $id)
    {
        $sql = "SELECT q.*, u.full_name as customer_name, u.account_email, 
                       a.line1, a.postcode, a.city
                FROM quotes q
                JOIN customers c ON q.customer_id = c.id
                JOIN users u ON c.user_id = u.id
                LEFT JOIN addresses a ON q.site_address_id = a.id
                WHERE q.id = ?";
        $stmt = $this->_dbHandle->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
}