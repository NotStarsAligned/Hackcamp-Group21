<?php

// Who wrote this incoming mess? Oh right, it was Stars. This is meant to be the Index page and as you can imagine, it does INDEX THINGS.
// I'm sure you all know by now that this is what automatically loads, grin. Also you can choose to follow my structure if you so wish.

//Session management logic, primarily useful for tracking logins and ensuring everything isn't stateless
session_start();

$view = new stdClass();
$view->username = null;
$view->isLogged = false;
header("Location: sendEmail.php");

// Views get output here
require_once("Views/index.phtml");