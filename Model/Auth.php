<?php


class Authentication {
//for logging user checking the email phone number and password.

    public static function login(string $email,string $password):bool{
        //path to database
        require_once __DIR__ . "/database.php";
        $db = Database::getInstance()->getConnection();

        // Prepare a SQL query to fetch a user with the given email, but only if the account is active.
// LIMIT 1 ensures that only one row is returned even if, due to some error, multiple users exist with the same email.
        $stmt = $db->prepare("SELECT * FROM users WHERE account_email = :email AND is_active = 1 LIMIT 1");
        //execute the query with the username used
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user){
            echo "Email not found";
            return false; //email not found in db
        }
        if (!password_verify($password, $user['password_hashed'])) {
            echo "Password does not match";
            return false; //wrong password
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['account_email'];
        $_SESSION['username'] = $user['full_name'];
        $_SESSION['role'] = strtolower($user['role']); // admin, staff, etc.aaaaaaaaaaaaa
        return true;
    }
    // ckeck the user role to allow them to see specific pages depending on role admin staff operator can see all pages
    //customers only few
    public static function getUserRole(): ?string {
        if (!self::isLoggedIn()) return null;
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT role FROM users WHERE role = :role LIMIT 1 ");
        $stmt->execute(['role' => $_SESSION['role']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['role'] ?? null;
    }
    public static function requireRole(array $allowedRoles): void {
        $role = self::getUserRole();

        if ($role === null || !in_array($role, $allowedRoles)) {
            // not allowed
            http_response_code(403);
            die("Access denied.");
        }
    }
    //only the user with role: admin operator and staff. have acces to the page
    public static function requireStaff(): void {
        self::requireRole(['admin', 'operator', 'staff']);
    }
    public static function requireCustomer(): void {
        self::requireRole(['customer']);
    }


//check if user is logged in, only logged in user can have access by
    public static function isLoggedIn(): bool {
        return isset($_SESSION['email']);
    }
    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header("Location: login.php");
            exit;
        }
    }

    //get user by id
    public static function getCurrentUser(){
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        require_once __DIR__ . '/database.php';
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT id, full_name, account_email, account_phone, role FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    // show logged-in username
    public static function full_name(): string {
        return $_SESSION['username'] ?? '';
    }

    public static function logout(): void
    {
        session_unset();
        session_destroy();
    }
}

