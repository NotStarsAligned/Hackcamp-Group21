<?php
session_start();
require_once 'Model/Auth.php';
require_once 'Model/QuoteDataSet.php';
// We no longer need require_once 'Model/database.php'; here for the manual lookup.

Authentication::requireLogin();

$view = new StdClass();
$view->title = "Quote Details";

$quoteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quoteDataSet = new QuoteDataSet();

// 1. Fetch Quote Data (FIXED: This now returns customer_name and account_email)
$quote = $quoteDataSet->getQuoteById($quoteId);

if (!$quote) {
    echo "Quote not found.";
    exit;
}

// 2. Fetch Related Data
$rooms = $quoteDataSet->getQuoteRooms($quoteId);
$items = $quoteDataSet->getQuoteItems($quoteId);

// 3. Calculate Totals
$totalMaterials = 0;
foreach ($items as $item) {
    $totalMaterials += $item['line_total'];
}

$labourCost = $quote['total_labour_cost'] ?? 0;
$deliveryCost = $quote['total_delivery_cost'] ?? 0;

$subtotalBeforeVat = $totalMaterials + $labourCost + $deliveryCost;
$vatRate = 0.20;
$vat = $subtotalBeforeVat * $vatRate;
$grandTotal = $subtotalBeforeVat + $vat;

// 4. Pass to View
$view->quote = $quote; // All necessary customer fields are now in $quote
$view->rooms = $rooms;
$view->items = $items;
$view->totalMaterials = $totalMaterials;
$view->labourCost = $labourCost;
$view->deliveryCost = $deliveryCost;
$view->subtotalBeforeVat = $subtotalBeforeVat;
$view->vat = $vat;
$view->grandTotal = $grandTotal;

// 5. Load the View
require_once("Views/quoteDetails.phtml");