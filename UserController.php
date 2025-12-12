<?php
session_start(); // Start Session // 启动 Session

// Include model files // 包含模型文件
require_once 'Model/UserModel.php';
require_once 'Model/Auth.php'; // Assume Auth class handles user authentication // 假设 Auth 类处理用户认证
require_once 'Model/database.php'; // Assume Database class handles database connection // 假设 Database 类处理数据库连接

// Protect page: Ensure only logged-in users can access this controller // 保护页面：确保只有已登录用户可以访问此控制器
Authentication::requireLogin();

class UserController {
    private $userModel;

    // Constructor: Initialize model and database connection // 构造函数：初始化模型和数据库连接
    public function __construct($dbConnection) {
        $this->userModel = new UserModel($dbConnection);
    }

    // Helper method for setting Session messages (flash messages) // 用于设置 Session 消息（闪存消息）的辅助方法
    private function setMessage(string $text, string $type = 'success') {
        $_SESSION['message'] = ['text' => $text, 'type' => $type];
    }

    // Helper method for redirecting to the profile page (POST-Redirect-GET pattern) // 用于重定向到资料页的辅助方法 (POST-Redirect-GET 模式)
    private function redirectToProfile() {
        header("Location: UserController.php?page=userinfo");
        exit;
    }

    /**
     * Handles the request for the user profile page. // 处理用户资料页面的请求。
     */
    public function showProfile() {
        // --- Message handling --- // --- 消息处理 ---
        global $message;
        if (isset($_SESSION['message'])) {
            $msg = $_SESSION['message'];
            // Use Bootstrap alert style // 使用 Bootstrap alert 样式
            $message = "<div class='alert alert-" . htmlspecialchars($msg['type']) . "'>" . htmlspecialchars($msg['text']) . "</div>";
            unset($_SESSION['message']);
        } else {
            $message = '';
        }

        // --- 1. Get current user ID --- // --- 1. 获取当前用户 ID ---
        $currentUser = Authentication::getCurrentUser();
        $currentUserId = $currentUser['id'] ?? null;

        if (!$currentUserId) {
            die("Authentication Error: Failed to retrieve current user ID.");
        }

        // --- 2. Call the model to fetch data --- // --- 2. 调用模型抓取数据 ---
        $profileData = $this->userModel->getUserProfileData($currentUserId);

        if (!$profileData) {
            die("Error: User profile not found for ID: " . $currentUserId);
        }

        // --- 3. Pass data to the view --- // --- 3. 将数据传递给视图 ---
        extract($profileData); // $name, $user_type, $email, $phone, $address, $member_since will be available

        // Include view file // 包含视图文件
        include 'Views/userinfo.phtml';
    }

    /**
     * Handles the POST request for updating user profile (name and phone). // 处理用于更新用户资料（姓名和电话）的 POST 请求。
     */
    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToProfile();
            return;
        }

        // 2. Get current user ID // 2. 获取当前用户 ID
        $currentUser = Authentication::getCurrentUser();
        $currentUserId = $currentUser['id'] ?? null;

        if (!$currentUserId) {
            $this->setMessage("Error: Authentication failed.", 'danger');
            $this->redirectToProfile();
            return;
        }

        // 3. Get and sanitize input data // 3. 获取并清理输入数据
        $newName = trim($_POST['name'] ?? ''); // **Correction: Use the correct form field name** // **修正点：使用正确的表单字段名**
        $newPhone = trim($_POST['phone'] ?? ''); // **Correction: Use the correct form field name** // **修正点：使用正确的表单字段名**

        // Validation // 验证
        if (empty($newName) || empty($newPhone)) {
            $this->setMessage("Error: Name and Phone cannot be empty.", 'danger');
            $this->redirectToProfile();
            return;
        }

        // 4. Call the model to update data // 4. 调用模型更新数据
        $success = $this->userModel->updateUserProfile($currentUserId, $newName, $newPhone);

        // 5. Handle result and redirect // 5. 处理结果并重定向
        //if ($success) {
            //$this->setMessage("Profile updated successfully!", 'success');
        //} else {
            //$this->setMessage("Error: Failed to update profile. Check server logs. (错误：更新资料失败。请检查服务器日志。)", 'danger');
       // }

        $this->redirectToProfile();
    }
}


// --- Routing and execution logic --- // --- 路由和执行逻辑 ---
try {
    // Ensure Database class and getInstance() method are available and return a PDO connection // 确保 Database 类和 getInstance() 方法可用，并且返回一个 PDO 连接
    $db = Database::getInstance()->getConnection();
    $controller = new UserController($db);

    $action = $_GET['action'] ?? null;
    $requestMethod = $_SERVER['REQUEST_METHOD'];

    // The form submission in the view did not specify an action, but used the POST method. // 视图中的表单提交没有指定 action，但使用了 POST 方法。
    // We need to ensure that when the form is submitted, the action is set to 'updateProfile', for example, by modifying the action via JavaScript, or adding a hidden field in the POST request. // 我们需要在表单提交时，将 action 设置为 'updateProfile'，例如通过 JavaScript 修改 action，或者在 POST 请求中添加一个隐藏字段。
    // Assuming that in your routing/frontend mechanism, a POST request defaults to triggering updateProfile or is identified by other means. // 假设在您的路由/前端机制中，POST 请求默认会触发 updateProfile 或通过其他方式识别。
    // To make your code work, I assume you need to set the action to 'updateProfile' on the frontend. // 为了让您的代码能够工作，我假设您需要在前端将 action 设置为 'updateProfile'。

    // If the frontend is correctly set to `index.php?page=userinfo&action=updateProfile`, then this logic applies. // 如果前端已正确设置为 `index.php?page=userinfo&action=updateProfile`，则此逻辑适用。
    // Otherwise, we need to ensure the view's form action points to the correct URL. // 否则，我们需要确保视图的表单 action 指向正确的 URL。
    // **Note: Since I cannot modify the view, the controller assumes the routing is correctly configured.** // **注：由于我不能修改视图，控制器假定路由已正确配置。**

    if ($requestMethod === 'POST') {
        // If it's a POST request and it's a profile update request // 如果是 POST 请求，并且是资料更新请求
        // The form action in the view is empty, meaning it submits to the current URL (index.php?page=userinfo). // 视图中的表单 action 为空，这意味着它会提交给当前 URL (index.php?page=userinfo)。
        // To ensure updateProfile is called, we force its call on a POST request, as showProfile does not handle POST. // 为了确保 updateProfile 被调用，我们需要在 POST 请求时强制调用它，因为 showProfile 不处理 POST。
        $controller->updateProfile();
    } else {
        // GET request, used to display the page // GET 请求，用于显示页面
        $controller->showProfile();
    }

} catch (\Throwable $e) {
    // Display detailed error in debug mode, only show generic error in production environment // 调试模式下显示详细错误，生产环境应只显示通用错误
    error_log("Controller error: " . $e->getMessage());
    // Restore to generic error message (if debugging is complete) // 恢复到通用错误信息（如果已完成调试）
    die("An unexpected error occurred.");
    // Use for debugging: die("An unexpected error occurred. <br><strong>Debugging Info:</strong> " . $e->getMessage()); // 调试时使用：die("An unexpected error occurred. <br><strong>Debugging Info:</strong> " . $e->getMessage());
}