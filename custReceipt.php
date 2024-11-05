<?php
session_start(); // Start the session
require_once('tcpdf/tcpdf.php'); // Adjust the path as needed

// Check if receipt details are set in the session
if (!isset($_SESSION['receipt_details'])) {
    die("Error: Receipt details not found.");
}

// Retrieve receipt details from the session
$receipt_details = $_SESSION['receipt_details'];

// Create new PDF document with custom size for receipt (58mm width, variable height)
$receiptWidth = 50; // 50mm
$receiptHeight = 70; // Variable height, but needs to be set to a valid number for TCPDF initialization
$pdf = new TCPDF('P', 'mm', array($receiptWidth, $receiptHeight), true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Company');
$pdf->SetTitle('Reservation Receipt');
$pdf->SetSubject('Reservation Receipt');
$pdf->SetKeywords('TCPDF, PDF, reservation, receipt');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins (small margins for receipt printing)
$pdf->SetMargins(2, 2, 2);
$pdf->SetAutoPageBreak(TRUE, 2);

// Add a page
$pdf->AddPage();

// Set font (smaller font for receipt printing)
$pdf->SetFont('helvetica', '', 8);

// Add content
$html = <<<EOD
<h3>Reservation Receipt</h3>
<p><strong>Sender:</strong> {$receipt_details['user_fname']} {$receipt_details['user_lname']}</p>
<p><strong>Room Name:</strong> {$receipt_details['room_name']}</p>
<p><strong>Reservation Date:</strong> {$receipt_details['booking_date']}</p>
<p><strong>Reservation Time:</strong> {$receipt_details['booking_time']} - {$receipt_details['res_end_time']}</p>
<p><strong>Payment Method:</strong> {$receipt_details['payment_method']}</p>
<p><strong>Payment Recipient:</strong> 09xxxxxxxxx</p>
<p><strong>Payment Amount:</strong> {$receipt_details['payment_amount']}php</p>
<p><strong>Payment Reference:</strong> {$receipt_details['payment_ref']}</p>
EOD;

$pdf->writeHTML($html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('reservation_receipt.pdf', 'D');

exit();
