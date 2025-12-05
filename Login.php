<?php
session_start();
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
  header("Location: Login.php");
   exit;
}
include __DIR__ . "/Views/template/header.phtml";
?>

<main class="login-section">
    <h2>Log In</h2>
    <?php if (!Authentication::isLoggedIn()): ?>
        <form method="POST" action="Login.php" class="login-form">

            <label for="email">Email:</label>
            <label for="account_email"></label>
            <input type="text" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>

            <?php if ($message): ?>
                <p class="error"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>
        </form>

    <?php else: ?>
        <div class="logout-container">
            <p><?= htmlspecialchars(Authentication::full_name()) ?> is logged in.</p>
            <a href="/Login.php?logout=1" class="logout-btn">Logout</a>
        </div>
    <?php endif; ?>

</main>
<?php include __DIR__ . "/Views/template/footer.phtml"; ?>
