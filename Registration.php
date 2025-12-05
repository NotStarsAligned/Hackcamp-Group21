<?php

session_start();
require_once __DIR__ . "/Model/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email= trim($_POST["email"]);
    $password =($_POST["password"]);
    $confirmPassword = $_POST['confirm_password'];
    $full_name = trim($_POST["full_name"]);
    $account_phone = trim($_POST["account_phone"]);

    $errors = "" ;

    //email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.<br>";
    }
    //pasword validation
    if ($password !== $confirmPassword) {
        $errors .= "Passwords do not match.<br>";
    }
    if (strlen($password) < 12) {
        $errors .= "Password must be at least 12 characters.<br>";
    }

    if (!preg_match("/[0-9]/", $password)) {
        $errors .= "Password must include at least one number.<br>";
    }
    if (!preg_match("/[A-Z]/", $password)) {
        $errors .= "Password must include at least one uppercase letter.<br>";

    }
    if (!preg_match("/[a-z]/", $password)) {
        $errors .= "Password must include at least one lowercase letter.<br>";
    }

    if (!preg_match("/[\W_]/", $password)) {
        $errors .= "Password must include at least one special character.<br>";
    }


    if(empty($errors)) {
        $db = Database::getInstance()->getConnection();
        //check if email already exists
        $stmt = $db->prepare("SELECT * FROM users WHERE account_email = :email");
        $stmt->execute([":email" => $email]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $message = "Email already exists.<br>";
        } else {
            //hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $db = Database::getInstance()->getConnection();
            //add new user as customer
            $stmt = $db->prepare("
                INSERT INTO users (
                                   account_email,
                                   password_hashed,
                                   full_name, account_phone, 
                                   role,
                                   is_active)

                VALUES (:email, 
                        :password, 
                        :full_name, 
                        :phone, 
                        'customer',
                        1)
            ");
            $stmt->execute([
                ":email" => $email,
                ":password" => $hashed_password,
                ":full_name" => $full_name,
                ":phone" => $account_phone,
            ]);

            $message = "Registration successful! You can now log in.";
        }
    }else{
        $message = $errors;
    }

}
include __DIR__ . "/template/header.phtml";
?>
<main class="registration-section">
    <h2>Register New Account</h2>

    <form method="POST" action="Registration.php" class="registration-form">

        <label for="email">Email:</label>
        <input type="text" id="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <label for="full_name">Full Name:</label>
        <input type="text" id="full_name" name="full_name" required>

        <label for="account_phone">Phone Number:</label>
        <input type="text" id="account_phone" name="account_phone">

        <button type="submit">Register</button>

        <?php if ($message): ?>
            <p class="message"><?= $message ?></p>
        <?php endif; ?>
    </form>
</main>

<?php include __DIR__ . "/template/footer.phtml"; ?>