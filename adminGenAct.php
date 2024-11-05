<?php
session_start();
require('./conx/conx_admin.php');
require_once('tcpdf/tcpdf.php'); // Adjust path as necessary

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: logout.php");
    exit();
}

$export_all = isset($_GET['export_all']) ? (bool)$_GET['export_all'] : false;

if ($export_all) {
    $sql = "SELECT users.user_id, users.user_fname, users.user_lname, 
            SUM(res.res_total) as total_spent, 
            COUNT(res.res_id) as total_reservations, 
            MAX(res.res_total) as max_spent
            FROM users 
            JOIN res ON users.user_id = res.user_id 
            GROUP BY users.user_id 
            ORDER BY total_spent DESC";
} else {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT users.user_id, users.user_fname, users.user_lname, 
            SUM(res.res_total) as total_spent, 
            COUNT(res.res_id) as total_reservations, 
            MAX(res.res_total) as max_spent
            FROM users 
            JOIN res ON users.user_id = res.user_id 
            GROUP BY users.user_id 
            ORDER BY total_spent DESC 
            LIMIT :limit OFFSET :offset";
}

$stmt = $conx_admin->prepare($sql);
if (!$export_all) {
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
}
$stmt->execute();
$spendings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create new PDF document
$pdf = new TCPDF();
$pdf->AddPage();

// Set title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'User Spending Report', 0, 1, 'C');
$pdf->Ln(10);

// Set header
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(20, 10, 'User ID', 1);
$pdf->Cell(50, 10, 'User Name', 1);
$pdf->Cell(30, 10, 'Total Spent', 1);
$pdf->Cell(40, 10, 'Total Reservations', 1);
$pdf->Cell(30, 10, 'Max Spent', 1);
$pdf->Ln();

// Set data
$pdf->SetFont('helvetica', '', 12);
foreach ($spendings as $spending) {
    $pdf->Cell(20, 10, $spending['user_id'], 1);
    $pdf->Cell(50, 10, $spending['user_fname'] . ' ' . $spending['user_lname'], 1);
    $pdf->Cell(30, 10, $spending['total_spent'], 1);
    $pdf->Cell(40, 10, $spending['total_reservations'], 1);
    $pdf->Cell(30, 10, $spending['max_spent'], 1);
    $pdf->Ln();
}

// Output PDF
$pdf->Output('user_spending_report.pdf', 'D');
