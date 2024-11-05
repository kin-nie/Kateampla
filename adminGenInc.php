<?php
session_start();
require('./conx/conx_admin.php');
require_once('tcpdf/tcpdf.php'); // Adjust the path to your TCPDF installation

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: logout.php");
    exit();
}

$export_all = isset($_GET['export_all']) ? (bool)$_GET['export_all'] : false;
$filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filterValue = isset($_GET['filter_value']) ? $_GET['filter_value'] : '';

$sql = "SELECT payments.*, users.user_fname, users.user_lname 
        FROM payments 
        JOIN res ON payments.res_id = res.res_id 
        JOIN users ON res.user_id = users.user_id 
        WHERE 1 = 1";

if ($filterType && $filterValue) {
    switch ($filterType) {
        case 'date':
            $sql .= " AND DATE(payments.payment_datetime) = :filter_value";
            break;
        case 'month':
            $sql .= " AND MONTH(payments.payment_datetime) = :filter_value";
            break;
        case 'year':
            $sql .= " AND YEAR(payments.payment_datetime) = :filter_value";
            break;
        case 'status':
            $sql .= " AND payments.payment_status LIKE :filter_value";
            $filterValue = "%$filterValue%";
            break;
        case 'method':
            $sql .= " AND payments.payment_method LIKE :filter_value";
            $filterValue = "%$filterValue%";
            break;
    }
}

if (!$export_all) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $sql .= " ORDER BY payments.payment_id DESC LIMIT :limit OFFSET :offset";
} else {
    $sql .= " ORDER BY payments.payment_id DESC";
}

$stmt = $conx_admin->prepare($sql);

if ($filterType && $filterValue) {
    $stmt->bindValue(':filter_value', $filterValue, PDO::PARAM_STR);
}

if (!$export_all) {
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
}

$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize payments by year and month
$organizedPayments = [];
foreach ($payments as $payment) {
    $year = date('Y', strtotime($payment['payment_datetime']));
    $month = date('F', strtotime($payment['payment_datetime']));
    if (!isset($organizedPayments[$year])) {
        $organizedPayments[$year] = [];
    }
    if (!isset($organizedPayments[$year][$month])) {
        $organizedPayments[$year][$month] = [];
    }
    $organizedPayments[$year][$month][] = $payment;
}

// Create new PDF document
$pdf = new TCPDF('L', 'mm', 'A4');
$pdf->AddPage();

// Set title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Payments Report', 0, 1, 'C');
$pdf->Ln(10);

// Set header
$pdf->SetFont('helvetica', 'B', 10);
$headerWidths = [20, 50, 30, 30, 40, 25, 35, 25];
$headerTitles = ['Payment ID', 'User', 'Reservation ID', 'Method', 'Payment Ref', 'Amount', 'Date', 'Status'];
foreach ($headerTitles as $i => $title) {
    $pdf->Cell($headerWidths[$i], 10, $title, 1);
}
$pdf->Ln();

// Set data
$pdf->SetFont('helvetica', '', 10);
foreach ($organizedPayments as $year => $months) {
    $pdf->Cell(array_sum($headerWidths), 10, $year, 1, 1, 'C', false);
    foreach ($months as $month => $payments) {
        $pdf->Cell(array_sum($headerWidths), 10, $month, 1, 1, 'C', false);
        foreach ($payments as $payment) {
            $datetimeFormatted = date('m-d-Y, g:i A', strtotime($payment['payment_datetime']));
            $pdf->Cell($headerWidths[0], 10, $payment['payment_id'], 1);
            $pdf->Cell($headerWidths[1], 10, $payment['user_fname'] . ' ' . $payment['user_lname'], 1);
            $pdf->Cell($headerWidths[2], 10, $payment['res_id'], 1);
            $pdf->Cell($headerWidths[3], 10, $payment['payment_method'], 1);
            $pdf->Cell($headerWidths[4], 10, $payment['payment_ref'], 1);
            $pdf->Cell($headerWidths[5], 10, $payment['payment_amount'], 1);
            $pdf->Cell($headerWidths[6], 10, $datetimeFormatted, 1);
            $pdf->Cell($headerWidths[7], 10, $payment['payment_status'], 1);
            $pdf->Ln();
        }
    }
}

// Output PDF
$pdf->Output('payments_report.pdf', 'D');
