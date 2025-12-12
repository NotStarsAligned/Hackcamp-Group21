<?php

// Who wrote this incoming mess? Oh right, it was Stars. This is... be the Index page and as you can imagine, it does INDEX THINGS.
// I'm sure you all know by now that this is what automatically ...grin. Also you can choose to follow my structure if you so wish.

//Session management logic, primarily useful for tracking logins and ensuring everything isn't stateless
session_start();
require_once __DIR__ . "/Model/Auth.php";
require_once __DIR__ . "/Model/database.php";

$view = new stdClass();
$view->username = null;
$view->isLogged = false;

/*
    Basic router:
    - Keeps your current behaviour (home works even if other pages don't exist)
    - Allows index.php?page=home / siteinformation / survey etc
    - If a view file doesn't exist, it falls back to Views/index.phtml
*/
$page = $_GET["page"] ?? "home";
$page = strtolower(trim($page));
$page = preg_replace("/[^a-z0-9_-]/", "", $page);

$view->page = $page;

/*
    Define known routes if you have them.
    Add more entries here as you create more pages.
*/
$routes = [
    "home" => "Views/index.phtml",
];

/*
    Resolve view file in a safe way:
    1) Use explicit routes if defined
    2) Otherwise try Views/<page>.phtml
    3) Otherwise try Views/pages/<page>.phtml
    4) Otherwise fall back to home (Views/index.phtml)
*/
$viewFile = $routes[$page] ?? null;

if ($viewFile === null) {
    $candidate1 = "Views/" . $page . ".phtml";
    $candidate2 = "Views/pages/" . $page . ".phtml";

    if (file_exists($candidate1)) {
        $viewFile = $candidate1;
    } elseif (file_exists($candidate2)) {
        $viewFile = $candidate2;
    } else {
        $viewFile = "Views/index.phtml";
    }
}

// Views get output here
require_once($viewFile);
