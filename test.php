<?php

require_once 'TileCalculator.php';

$calc = new TileCalculator(4.5, 3.2);

print_r($calc->getSummary());