<?php
require("Model/StaffDataSet.php");

require_once __DIR__ . "/Model/Auth.php";
require_once __DIR__ . "/Model/database.php";


//D.D

//protects the page from customer/ only staff admin operator role have access
Authentication::requireStaff();
Authentication::requireLogin();//protects the page/only customer role have access

$view = new StdClass();
$view->Title = "Live Map";

$class = new StaffDataSet();
$view->staffLocations = [];

$staffInfo = $class->getAllStaffLocations();

foreach ($staffInfo as $staff) {
    $view->staffLocations[] = [
        "lat" => $staff->last_latitude,   // Changed key name
        "lng" => $staff->last_longitude,  // Changed key name
    ];
}
require_once("Views/map.phtml");