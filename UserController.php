<?php

// UserController.php

// Include the model file (assuming you have a proper autoloader in a real framework)
// 包含模型文件
require_once 'Model/UserModel.php';

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
        // --- 1. Get the authenticated user ID (Example: hardcoded 1, replace with session/auth logic) ---
        // --- 1. 获取已认证的用户 ID（示例：硬编码为 1，请替换为实际的 Session/认证逻辑） ---
        $currentUserId = 1; // Replace this with $_SESSION['user_id'] or similar

        // --- 2. Call the model to fetch data ---
        // --- 2. 调用模型抓取数据 ---
        $profileData = $this->userModel->getUserProfileData($currentUserId);

        // --- 3. Handle data fetching failure (e.g., user not found) ---
        // --- 3. 处理数据抓取失败（例如：用户未找到） ---
        if (!$profileData) {
            // Error handling: Redirect or show 404
            // 错误处理：重定向或显示 404
            die("Error: User profile not found.");
        }

        // --- 4. Pass data to the view (userinfo.phtml) ---
        // --- 4. 将数据传递给视图 (userinfo.phtml) ---
        // In a real framework, this would be a render call. Here, we extract variables.
        // 在实际框架中，这将是一个渲染调用。这里，我们解包变量。
        extract($profileData); // $name, $user_type, $email, $phone, $address, $member_since will be available

        // Include the view file (In a simple setup)
        // 包含视图文件
        include 'Views/userinfo.phtml';
    }
}

// --- EXAMPLE USAGE (Database Connection setup) ---
// --- 示例用法（数据库连接设置） ---
// Replace with your actual database connection logic
try {
    // Connect to the SQLite database (database.sqlite should be in the root)
    // 连接到 SQLite 数据库（database.sqlite 应在根目录）
    $db = new PDO('sqlite:database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Instantiate the controller
    // 实例化控制器
    $controller = new UserController($db);

    // Call the method to display the profile page
    // 调用方法来显示用户资料页
    $controller->showProfile();
} catch (PDOException $e) {
    // Handle database connection error
    // 处理数据库连接错误
    die("Database Connection Error: " . $e->getMessage());
}