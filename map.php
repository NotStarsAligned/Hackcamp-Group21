<?php
require("Model/StaffDataSet.php");
$view = new StdClass();
$view->Title ="Live Map";

$class = new StaffDataSet();
$view->latitude = $class->getLat();
require_once("Views/map.phtml");