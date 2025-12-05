<?php
// Ensure Composer dependencies are loaded

require ("vendor/autoload.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

$pdfName = "Invoice";

$options = new Options();
$options->setChroot(__DIR__);
$dompdf = new Dompdf();
$dompdf->setBasePath(__DIR__ ."/Views/css/bootstrap.css");
$html = file_get_contents(__DIR__ ."/Views/template/template.phtml");
$html = str_replace("{{name}}", $pdfName, $html);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfString = $dompdf->output();
// --- CONFIGURATION / BEST PRACTICE ---
// CRITICAL SECURITY WARNING: Do NOT use your primary Gmail password here.
// Instead, you must generate an App Password in your Google Account security settings
// if you are using 2FA, or ensure "Less secure app access" is enabled (NOT recommended).
//
// For production code, these values MUST be loaded securely from environment variables
// or a secure configuration file, NOT hardcoded.
$smtp_username = "jumbonzerhema@gmail.com"; // Set to your account as requested
$smtp_password = "dyju lihd avdr ilky"; // <<< IMPORTANT: REPLACE THIS WITH YOUR GENERATED APP PASSWORD

// -------------------------------------

$mail = new PHPMailer(true);

try {
    // Server settings for Gmail SMTP
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment for detailed debug output

    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS for port 587

    // Authentication
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;

    // Recipients
    $sender_name = "Salford Flooring";

    // Set the From address. This MUST match the authenticated $smtp_username when using Gmail.
    $mail->setFrom($smtp_username, $sender_name);

    // The actual recipient
    $mail->addAddress("jumbonzerhema@gmail.com", "Jumbo");

    // Content
    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = 'New Quotation Invoice from Salford Flooring';
    $mail->Body    = 'This is the HTML body of the email. Here is your **new invoice**.';
    $mail->AltBody = 'This is the plain text version of the email for non-HTML mail clients.';

    $mail->AddStringAttachment(
        $pdfString,
        'Quotation-Invoice-42.pdf', // The file name the recipient will see
        'base64',                   // Encoding (always base64 for binary attachments)
        'application/pdf'           // Mime type
    );
    // Send the email
    $mail->send();
    echo "Message has been sent successfully!";


} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}