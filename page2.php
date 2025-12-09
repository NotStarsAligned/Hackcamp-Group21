<?php
session_start();
require_once __DIR__ . "/Model/Auth.php";
require_once __DIR__ . "/Model/database.php";
require_once __DIR__ . "/Views/template/header.phtml";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page 2</title>
</head>
<body>

<h1>Page 2 (Empty Base Page)</h1>
<p>This page is intentionally empty and will be filled later.</p>

</body>
</html>

<?php
require_once __DIR__ . "/Views/template/footer.phtml";
?>
