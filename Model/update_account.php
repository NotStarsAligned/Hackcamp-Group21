<?php
// ------------------------------
// CONFIG & DB CONNECTION
// ------------------------------
$db = new PDO('sqlite: database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fake user ID for example – replace with session value
$userId = 1;

// ------------------------------
// HANDLE FORM SUBMISSION
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email      = trim($_POST['email']);
    $name       = trim($_POST['full_name']);
    $phone      = trim($_POST['phone']);
    $password   = trim($_POST['password']);

    // Build base SQL + parameters
    $sql = "UPDATE users  SET account_email = :email, full_name = :fullname, account_phone = :phone";

    $params = [
        ":email"   => $email,
        ":full_name"=> $name,
        ":phone"   => $phone,
        ":id"      => $userId
    ];

    // If password is filled, update it
    if (!empty($password)) {
        $sql .= ", password_hashed = :password_hashed";
        $params[":password_hashed"] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = :id";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $message = "Account details updated successfully!";
}

// ------------------------------
// LOAD CURRENT USER INFO
// ------------------------------
$stmt = $db->prepare("SELECT account_email, full_name, account_phone FROM users WHERE id = :id");
$stmt->execute([":id" => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);