<?php
if (session_status() == PHP_SESSION_NONE){
    session_start();
}

class Authentication {
//for logging user checking the email phone number and password.

    public static function login(string $email,string $password):bool{
        //path to database
        require_once __DIR__ . "/database.php";
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM users WHERE account_email = :email AND is_active = 1 LIMIT = 1");
        //execute the query with the username used
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user){
            return false;
        }
        if (password_verify($password, $user['password_hashed'])) {
            return false; //wrong password
        }

        $_SESSION['email'] = $user['account_email'];
        $_SESSION['username'] = $user['full_name'];
        $_SESSION['role'] = strtolower($user['role']); // admin, staff, etc.aaaaaaaaaaaaa
        return true;
    }

//check if user is logged in, only logged in user can have access
    public static function isLoggedIn(): bool {
        return isset($_SESSION['email']);
    }
    //need to modify  to display what user is logged in
    /*
     * public static function getCurrentUser(){
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        //prepare to fetch the user by id if user has not found returns null

        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, username, role FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
     * */

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

