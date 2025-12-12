<?php
// generateInvoice.php
session_start();
require_once 'Model/Auth.php';
require_once 'Model/QuoteDataSet.php';
require_once 'sendEmail.php'; // Include our new function

Authentication::requireLogin();

$quoteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quoteDataSet = new QuoteDataSet();

// 1. Fetch Data
$quote = $quoteDataSet->getQuoteById($quoteId);

if (!$quote) {
    die("Quote not found.");
}

$rooms = $quoteDataSet->getQuoteRooms($quoteId);
$items = $quoteDataSet->getQuoteItems($quoteId);

// 2. Prepare View Data (Same logic as quoteDetails.php)
$view = new StdClass();
$view->quote = $quote;
$view->rooms = $rooms;
$view->items = $items;

$totalMaterials = 0;
foreach ($items as $item) {
    $totalMaterials += $item['line_total'];
}

$view->totalMaterials = $totalMaterials;
$view->labourCost = $quote['labour_cost'] ?? 0;
$view->deliveryCost = $quote['delivery_cost'] ?? 0;

$view->subtotalBeforeVat = $view->totalMaterials + $view->labourCost + $view->deliveryCost;
$view->vat = $view->subtotalBeforeVat * 0.20;
$view->grandTotal = $view->subtotalBeforeVat + $view->vat;

// 3. Render Template to String (Output Buffering)
// This executes the PHP inside template.phtml and captures the HTML result
ob_start();
include __DIR__ . "/Views/template/template.phtml";
$htmlContent = ob_get_clean();

// 4. Send PDF
$pdfName = "Quotation-" . ($quote['reference_code'] ?? '000') . ".pdf";
$result = sendPdfInvoice($htmlContent, $pdfName, $quote['account_email'], $quote['customer_name']);

if ($result['status'] === 'success') {
    $_SESSION['success'] = "Invoice sent successfully!";
} else {
    $_SESSION['error'] = "Failed to send invoice: " . $result['message'];
}

// Return to details page
header('Location: quoteDetails.php?id=' . $quoteId);
exit;