<?php

// UserModel.php

class UserModel {
    private $db; // Database connection handler (e.g., PDO object)

    // Constructor to initialize the database connection
    // 构造函数：初始化数据库连接
    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Fetches all required user profile data for the userinfo page.
     * 抓取 userinfo 页面所需的所有用户资料数据。
     *
     * @param int $userId The ID of the user to fetch. 要抓取的用户 ID。
     * @return array|null An associative array of user data, or null on failure. 用户数据关联数组，失败时返回 null。
     */
    public function getUserProfileData(int $userId): ?array {
        // --- 1. Fetch basic data from the 'users' table ---
        // --- 1. 从 'users' 表抓取基础数据 ---
        // The 'user' table has full_name, role, account_email, account_phone.
        // 'user' 表包含 full_name, role, account_email, account_phone。
        $userSql = "SELECT full_name, role, account_email, account_phone FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($userSql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // If user is not found, return null
        if (!$userData) {
            return null;
        }

        // Initialize variables for optional data
        // 初始化可选数据变量
        $address = 'N/A';
        $memberSince = 'N/A';
        $isCustomer = (strtolower($userData['role']) === 'customer');

        // --- 2. Handle data specific to 'customer' role (Address & Member Since) ---
        // --- 2. 处理 'customer' 角色特定的数据（地址和会员时间） ---
        if ($isCustomer) {

            // 2.1 Get billing_address_id and created_at from the 'customers' table
            // 2.1 从 'customers' 表获取 billing_address_id 和 created_at
            $customerSql = "SELECT billing_address_id, created_at FROM customers WHERE user_id = :user_id";
            $stmt = $this->db->prepare($customerSql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $customerData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customerData) {
                // Set 'Member Since'
                // 设置 'Member Since'
                $memberSince = $customerData['created_at'];

                // 2.2 Use billing_address_id to fetch address details from 'addresses' table
                // 2.2 使用 billing_address_id 从 'addresses' 表抓取地址详情
                $addressId = $customerData['billing_address_id'];

                // NOTE: Assuming the addresses table has columns: id (PK), county, postcode
                // 注意：假设 addresses 表包含字段：id (主键), county, postcode
                if ($addressId) {
                    $addressSql = "SELECT county, postcode FROM addresses WHERE id = :address_id LIMIT 1";
                    $stmt = $this->db->prepare($addressSql);
                    $stmt->bindParam(':address_id', $addressId);
                    $stmt->execute();
                    $addressData = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($addressData) {
                        // Concatenate county and postcode
                        // 合并 county 和 postcode
                        $address = $addressData['county'] . ', ' . $addressData['postcode'];
                    }
                }
            }
        }
        // For non-customer roles, $address and $memberSince remain 'N/A'

        // --- 3. Compile and return the final profile array ---
        // --- 3. 编译并返回最终资料数组 ---
        $profile = [
            'name' => $userData['full_name'],        // full_name -> name
            'user_type' => $userData['role'],        // role -> user type
            'email' => $userData['account_email'],   // account_email -> email
            'phone' => $userData['account_phone'],   // account_phone -> phone
            'address' => $address,                   // county and postcode
            'member_since' => $memberSince           // created_at from customer table
        ];

        return $profile;
    }
}