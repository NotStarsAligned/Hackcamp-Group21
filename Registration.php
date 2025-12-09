<?php

session_start();
require_once __DIR__ . "/Model/Auth.php";
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
    // email must contain domain and TLD (.com, .co.uk, etc.)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/@.+\..+/", $email)) {
        $message = "Invalid email address. Please enter a full email like example@gmail.com.<br>";
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
include __DIR__ . "/Views/template/header.phtml";
?>
<main class="registration-section" xmlns="http://www.w3.org/1999/html">
    <h2>Register New Account</h2>

    <form method="POST" action="Registration.php" class="registration-form">
        <!--Name field-->
        <label for="full_name">Full Name:</label>
        <input type="text" id="full_name" name="full_name" placeholder="John Doe" required><br>
        <!--email field-->
        <label for="email">Email:</label>
        <input type="text" id="email" name="email"  placeholder="example@gmail.com" required> <br>
        <!--phone field-->
        <label for="account_phone">Phone Number:</label>
        <input type="text" id="account_phone" name="account_phone" placeholder="07123456789" required><br>
        <!--password field-->
        <label for="password">Password:</label>

        <input type="password" id="password" name="password" placeholder="Min 12 characters" required>
        <i class="fa fa-eye" id="togglePassword"></i>
        <p id="password-length">Length: 0 / 12</p>

        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-type password" required>
        <i class="fa fa-eye" id="toggleConfirmPassword"></i>

        <p id="confirm-length">Length: 0 / 12</p>



        <button type="submit">Register</button>

        <?php if (!empty($message)): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

    </form>
</main>


    <script>
        // PASSWORD length display
        const passwordField = document.getElementById("password");
        passwordField.value = "";
        const lengthDisplay = document.getElementById("password-length");

        passwordField.addEventListener("input", function () {
            const len = passwordField.value.length;
            lengthDisplay.textContent = "Length: " + len + " / 12";
        });

        // CONFIRM PASSWORD length display
        const confirmField = document.getElementById("confirm_password");
        confirmField.value = "";
        const confirmLengthDisplay = document.getElementById("confirm-length");

        confirmField.addEventListener("input", function () {
            const len = confirmField.value.length;
            confirmLengthDisplay.textContent = "Length: " + len + " / 12";
        });


        // Toggle password visibility
        const togglePassword = document.getElementById("togglePassword");
        togglePassword.addEventListener("click", function () {
            const type = passwordField.getAttribute("type") === "password" ? "text" : "password";
            passwordField.setAttribute("type", type);
            this.classList.toggle("fa-eye-slash");
        });

        // Toggle confirm password visibility
        const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");
        toggleConfirmPassword.addEventListener("click", function () {
            const type = confirmField.getAttribute("type") === "password" ? "text" : "password";
            confirmField.setAttribute("type", type);
            this.classList.toggle("fa-eye-slash");
        });
    </script>

<!-- dsada -->

<?php include __DIR__ . "/Views/template/footer.phtml"; ?>