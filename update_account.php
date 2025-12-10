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


    try {
        // Start transaction
        $db->beginTransaction();

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

        // Commit the transaction
        $db->commit();
        $message = "Account details updated successfully!";

        // Refresh currentUser data
        $currentUser = Authentication::getCurrentUser();

    } catch (Exception $e) {
        // Roll back if something went wrong
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $message = "Update failed: " . $e->getMessage();
    }
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ANDREWS CHANGES:
require_once("Views/update_account.phtml");