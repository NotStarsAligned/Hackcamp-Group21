<?php

// UserModel.php

class UserModel {
    private $db; // Database connection handler (e.g., PDO object)

    // 构造函数：初始化数据库连接
    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * 抓取 userinfo 页面所需的所有用户资料数据。
     */
    public function getUserProfileData(int $userId): ?array {
        // --- 1. 从 'users' 表抓取基础数据 ---
        // 注意：不包含 address_id，因为它在 'users' 表中不存在（根据您的错误信息）
        $userSql = "SELECT full_name, role, account_email, account_phone FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($userSql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            return null;
        }

        $address = 'N/A';
        $memberSince = 'N/A';
        $addressId = null;

        // --- 2. 如果适用，抓取客户特定的数据 ---
        if ($userData['role'] === 'customer') {
            // 从 'customers' 表获取注册时间 和 billing_address_id
            $customerSql = "SELECT created_at, billing_address_id FROM customers WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->db->prepare($customerSql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $customerData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customerData) {
                $memberSince = date('F Y', strtotime($customerData['created_at']));
                $addressId = $customerData['billing_address_id']; // **修正点：确保使用正确的字段名**

                // 使用 addressId 获取地址详情 (county, postcode)
                if ($addressId) {
                    $addressSql = "SELECT county, postcode FROM addresses WHERE id = :address_id LIMIT 1";
                    $stmt = $this->db->prepare($addressSql);
                    $stmt->bindParam(':address_id', $addressId);
                    $stmt->execute();
                    $addressData = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($addressData) {
                        $address = $addressData['county'] . ', ' . $addressData['postcode'];
                    }
                }
            }
        }

        $profile = [
            'name' => $userData['full_name'],
            'user_type' => $userData['role'],
            'email' => $userData['account_email'],
            'phone' => $userData['account_phone'],
            'address' => $address,
            'member_since' => $memberSince
        ];

        return $profile;
    }

    /**
     * 在数据库中更新用户的姓名和电话号码。
     */
    public function updateUserProfile(int $userId, string $name, string $phone): bool {
        // 用于更新用户 full_name 和 account_phone 的 SQL 语句
        $sql = "UPDATE users SET full_name = :name, account_phone = :phone WHERE id = :user_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("User profile update failed for ID $userId: " . $e->getMessage());
            return false;
        }
    }
}