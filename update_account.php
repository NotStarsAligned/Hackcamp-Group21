<?php
session_start();
require_once __DIR__ . "/Model/Auth.php";
require_once __DIR__ . "/Model/database.php";

$message = "";

// ------------------------------
// GET CURRENT USER
// ------------------------------
$currentUser = Authentication::getCurrentUser();

if (!$currentUser) {
    // Not logged in or user not found
    header("Location: login.php");
    exit;
}

$userId = $currentUser['id'];

// ------------------------------
// DATABASE CONNECTION
// ------------------------------
$db = Database::getInstance()->getConnection();

// ------------------------------
// HANDLE FORM SUBMISSION
// ------------------------------
$userId = $currentUser['id'];
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $name     = trim($_POST['full_name']);
    $phone    = trim($_POST['phone']);
    $password = trim($_POST['password']);


    $sql = "UPDATE users SET account_email = :email, full_name = :fullname, account_phone = :phone";
    $params = [
            ":email"    => $email,
            ":fullname" => $name,
            ":phone"    => $phone,
            ":id"       => $userId
        ];


    if (!empty($password)) {
        $sql .= ", password_hashed = :password_hashed";
        $params[":password_hashed"] = password_hash($password, PASSWORD_DEFAULT);
    }

        $sql .= " WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $db->commit();
        $message = "Account details updated successfully!";

        // Refresh currentUser data
        $currentUser = Authentication::getCurrentUser();

    }

//////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ANDREWS CHANGES:
require_once("Views/update_account.phtml");