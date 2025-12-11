<?php
require("Model/StaffDataSet.php");
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