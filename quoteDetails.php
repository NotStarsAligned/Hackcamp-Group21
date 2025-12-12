<?php
session_start();
require_once 'Model/Auth.php';
require_once 'Model/QuoteDataSet.php';

Authentication::requireLogin();

$view = new StdClass();
$view->title = "Quote Details";

$quoteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quoteDataSet = new QuoteDataSet();

// 1. Fetch Quote Data
$quote = $quoteDataSet->getQuoteById($quoteId);

if (!$quote) {
    echo "Quote not found.";
    exit;
}

// 2. Fetch Related Data
$rooms = $quoteDataSet->getQuoteRooms($quoteId);
$items = $quoteDataSet->getQuoteItems($quoteId);

// 3. Calculate Totals (on the fly for display)
$totalMaterials = 0;
foreach ($items as $item) {
    $totalMaterials += $item['line_total'];
}
$vat = $totalMaterials * 0.20;
$grandTotal = $totalMaterials + $vat;

// 4. Pass to View
$view->quote = $quote;
$view->rooms = $rooms;
$view->items = $items;
$view->totalMaterials = $totalMaterials;
$view->vat = $vat;
$view->grandTotal = $grandTotal;

require_once("Views/quoteDetails.phtml");