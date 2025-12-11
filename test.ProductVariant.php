<?php


require_once __DIR__ . "/ProductVariant.php";  // this already pulls in database.php

$variantId = 100; // use an id that exists in your product_variants table

$retail = ProductVariant::getRetailPriceById($variantId);
$trade = ProductVariant::getTradePriceById($variantId);

echo "<h2>Testing Variant ID: $variantId</h2>";
echo "<p>Retail Price: " . ($retail !== null ? $retail : "NOT FOUND") . "</p>";
echo "<p>Trade Price: " . ($trade !== null ? $trade : "NOT FOUND") . "</p>";
