<?php
session_start();
$userId = $_SESSION['user_id'];
require_once __DIR__ . "/Model/Auth.php";
require_once __DIR__ . "/Model/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST"){
     $email = trim($_POST["email"]);
     $password = $_POST["password"];
/*
 * //prepare database and execute
    $stmt =$conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bindParam(":account_email", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($db_password);
        $stmt->fetch();

        if ($password === $db_password) {
            $message = "Login successful";
 * */



     if (Authentication::login($email, $password)) {
         header("Location: Login.php");//refresh the page after logging in
         exit;
     } else {
         $message = "Wrong email or password";
     }
}
//log out
if (isset($_GET['logout'])){
  Authentication::logout();
  header("Location: index.php");
   exit;
}
include __DIR__ . "/Views/template/header.phtml";
?>

<main class="login-section"> <link href="/Views/css/login.css" rel="stylesheet">

    <h2>Log In</h2>
    <?php if (!Authentication::isLoggedIn()): ?>
        <form method="POST" action="Login.php" class="login-form">

            <label for="email">Email:</label>
            <label for="account_email"></label>
            <input type="text" id="email" name="email" required> <br>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required> <br>

            <button type="submit">Login</button>

            <?php if ($message): ?>
                <p class="error"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>
        </form>

    <?php else: ?>
        <div class="logout-container">
            <p>Welcome: <?= htmlspecialchars(Authentication::full_name()) ?> </p>
            <a href="/Login.php?logout=1" class="logout-btn">Logout</a>
        </div>
    <?php endif; ?>
    <p class="register-link">
        Don't have an account?
        <a href="Registration.php">Register here</a>
    </p>

</main>
<?php include __DIR__ . "/Views/template/footer.phtml"; ?>
