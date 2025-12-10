<?php
session_start();
require_once __DIR__ . "/Model/database.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Settings</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        form { max-width: 400px; }
        input { width: 100%; padding: 10px; margin: 8px 0; }
        button { padding: 10px 15px; }
        .msg { background: #e0ffe0; padding: 10px; border: 1px solid #60c060; }
    </style>
</head>
<body>

<h2>Update Account Settings</h2>

<?php if (!empty($message)) : ?>
    <div class="msg"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST">

    <label>Email</label>
    <input type="email" name="email" required value="<?= htmlspecialchars($user['account_email']) ?>">

    <label>Full Name</label>
    <input type="text" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>">

    <label>Phone Number</label>
    <input type="text" name="phone" required value="<?= htmlspecialchars($user['account_phone']) ?>">

    <label>New Password (leave empty if unchanged)</label>
    <input type="password" name="password">

    <button type="submit">Save Changes</button>
</form>

</body>
</html>