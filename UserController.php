<?php
session_start(); // 添加：启动 Session，以便 Authentication 类可以访问用户数据

// Include the model file
// 包含模型文件
require_once 'Model/UserModel.php';
require_once 'Model/Auth.php';
require_once 'Model/database.php'; // 添加：包含 Database 类

// 保护页面：确保只有已登录用户可以访问此控制器
Authentication::requireLogin();

class UserController {
    private $userModel;

    // Constructor to initialize model and database connection
    // 构造函数：初始化模型和数据库连接
    public function __construct($dbConnection) {
        $this->userModel = new UserModel($dbConnection);
    }

    /**
     * Handles the request for the user profile page.
     * 处理用户资料页面的请求。
     */
    public function showProfile() {
        // --- 1. Get the authenticated user ID (使用 Authentication 类获取当前用户) ---
        $currentUser = Authentication::getCurrentUser(); // 获取当前用户数据
        $currentUserId = $currentUser['id'] ?? null;     // 从用户数据中提取 ID

        if (!$currentUserId) {
            // 如果认证失败，重定向或终止
            die("Authentication Error: Failed to retrieve current user ID.");
        }

        // --- 2. Call the model to fetch data ---
        // --- 2. 调用模型抓取数据 ---
        $profileData = $this->userModel->getUserProfileData($currentUserId);

        // --- 3. Handle data fetching failure (e.g., user not found) ---
        // --- 3. 处理数据抓取失败（例如：用户未找到） ---
        if (!$profileData) {
            // Error handling: Redirect or show 404
            // 错误处理：重定向或显示 404
            die("Error: User profile not found for ID: " . $currentUserId);
        }

        // --- 4. Pass data to the view (userinfo.phtml) ---
        // --- 4. 将数据传递给视图 (userinfo.phtml) ---
        extract($profileData); // $name, $user_type, $email, $phone, $address, $member_since will be available

        // Include the view file (In a simple setup)
        // 包含视图文件
        include 'Views/userinfo.phtml';
    }
}

// --- EXAMPLE USAGE (Database Connection setup) ---
// --- 示例用法（数据库连接设置） ---
// 使用 Database::getInstance() 连接到数据库
try {
    // 使用单例模式获取数据库连接
    $db = Database::getInstance()->getConnection();
    // 注意：Database::getInstance() 内部应该已经设置了 PDO::ATTR_ERRMODE

    // Instantiate the controller
    // 实例化控制器
    $controller = new UserController($db);

    // Call the method to display the profile page
    // 调用方法来显示用户资料页
    $controller->showProfile();
} catch (PDOException $e) {
    // 处理数据库连接错误
    die("Database Connection Error: " . $e->getMessage());
} catch (Exception $e) {
    // 处理其他可能的应用错误（如文件包含错误等）
    die("Application Error: " . $e->getMessage());
}