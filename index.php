<?php

// Who wrote this incoming mess? Oh right, it was Stars. This is meant to be the Index page and as you can imagine, it does INDEX THINGS.
// I'm sure you all know by now that this is what automatically loads, grin. Also you can choose to follow my structure if you so wish.

//Session management logic, primarily useful for tracking logins and ensuring everything isn't stateless
session_start();
require_once __DIR__ . "/Model/Auth.php";
require_once __DIR__ . "/Model/database.php";

$view = new stdClass();
$view->username = null;
$view->isLogged = false;


// Views get output here
require_once("Views/index.phtml");