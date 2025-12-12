<?php
session_start();
require_once ('Model/Auth.php');
require_once ('Model/QuoteDataSet.php');

Authentication::requireLogin();

$view = new StdClass();
$view->title = "Quote";

$quoteDataSet = new QuoteDataSet();
$currentUser = Authentication::getCurrentUser();
$userId = $currentUser['id'] ?? ($_SESSION['user_id'] ?? 0);

// Fetch Implicit Wishlist Items (Used for both GET view and POST processing)
$wishlistItems = $quoteDataSet->getWishlistItemsForUser($userId);
$view->wishlistItems = $wishlistItems;

// --- HANDLE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $client = filter_input(INPUT_POST, 'client', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $rooms = $_POST['rooms'] ?? [];

    if (empty($title) || empty($client) || empty($rooms) || empty($wishlistItems)) {
        $_SESSION['error'] = "Missing information. Check client details, rooms, and ensure your wishlist is not empty.";
        header('Location: quote.php');
        exit;
    }

    // Process Room Input
    $validRooms = [];
    foreach ($rooms as $room) {
        if (isset($room['name'], $room['length'], $room['width']) && $room['length'] > 0 && $room['width'] > 0) {
            $validRooms[] = [
                'name' => filter_var($room['name'], FILTER_SANITIZE_STRING),
                'length' => filter_var($room['length'], FILTER_VALIDATE_FLOAT),
                'width' => filter_var($room['width'], FILTER_VALIDATE_FLOAT),
            ];
        }
    }

    if (empty($validRooms)) {
        $_SESSION['error'] = "Please add at least one valid room measurement.";
        header('Location: quote.php');
        exit;
    }

    // Create Quote using Implicit Wishlist Items
    $newQuoteId = $quoteDataSet->createFullQuote($title, $client, $description, $validRooms, $wishlistItems);

    if ($newQuoteId) {
        $_SESSION['success'] = "Quote created! Material quantities calculated based on room dimensions.";
        header('Location: quoteDetails.php?id=' . $newQuoteId);
        exit;
    } else {
        $_SESSION['error'] = "Error saving quote.";
        header('Location: quote.php');
        exit;
    }
}

require_once("Views/quote.phtml");