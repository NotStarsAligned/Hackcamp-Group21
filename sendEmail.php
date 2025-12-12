<?php
// sendEmail.php
// Ensure Composer dependencies are loaded
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

// --- CONFIGURATION ---
// IMPORTANT: Use environment variables in production!
$smtp_username = "jumbonzerhema222@gmail.com";
$smtp_password = "dyju lihd avdr ilky"; // App Password
// ---------------------

/**
 * Generates a PDF from HTML content and emails it.
 */
function sendPdfInvoice(string $htmlContent, string $pdfFilename, string $toEmail, string $toName): array
{
    global $smtp_username, $smtp_password;

    // 1. Generate PDF using Dompdf
    try {
        $options = new Options();
        $options->setChroot(__DIR__); // Allow accessing files in project root
        $options->setIsRemoteEnabled(true); // Allow remote images if needed

        $dompdf = new Dompdf($options);

        // Set base path for CSS/Images
        $dompdf->setBasePath(__DIR__ . "/Views/");

        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfString = $dompdf->output();

    } catch (Exception $e) {
        return ['status' => 'error', 'message' => "PDF Generation Error: " . $e->getMessage()];
    }

    // 2. Send Email with PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;

        $sender_name = "Salford Flooring";
        $mail->setFrom($smtp_username, $sender_name);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'New Quotation Invoice from Salford Flooring';
        $mail->Body    = 'Dear ' . htmlspecialchars($toName) . ',<br><br>Please find your official quotation attached.<br><br>Kind Regards,<br>Salford Flooring.';
        $mail->AltBody = 'Please find your official quotation attached.';

        $mail->AddStringAttachment($pdfString, $pdfFilename, 'base64', 'application/pdf');

        $mail->send();
        return ['status' => 'success', 'message' => "Invoice sent to {$toEmail}"];

    } catch (Exception $e) {
        return ['status' => 'error', 'message' => "Mailer Error: " . $mail->ErrorInfo];
    }
}