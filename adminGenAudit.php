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

$sql = "SELECT audit_trail.*, users.user_fname, users.user_lname 
        FROM audit_trail 
        JOIN users ON audit_trail.user_id = users.user_id 
        WHERE 1 = 1";

if ($filterType && $filterValue) {
    switch ($filterType) {
        case 'date':
            $sql .= " AND DATE(audit_trail.audit_datetime) = :filter_value";
            break;
        case 'month':
            $sql .= " AND MONTH(audit_trail.audit_datetime) = :filter_value";
            break;
        case 'year':
            $sql .= " AND YEAR(audit_trail.audit_datetime) = :filter_value";
            break;
        case 'keyword':
            $sql .= " AND audit_trail.action LIKE :filter_value";
            $filterValue = "%$filterValue%";
            break;
        case 'user_id':
            $sql .= " AND audit_trail.user_id = :filter_value";
            break;
    }
}

if (!$export_all) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $sql .= " ORDER BY audit_trail.audit_id DESC LIMIT :limit OFFSET :offset";
} else {
    $sql .= " ORDER BY audit_trail.audit_id DESC";
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
$audits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize audits by year and month
$organizedAudits = [];
foreach ($audits as $audit) {
    $year = date('Y', strtotime($audit['audit_datetime']));
    $month = date('F', strtotime($audit['audit_datetime']));
    if (!isset($organizedAudits[$year])) {
        $organizedAudits[$year] = [];
    }
    if (!isset($organizedAudits[$year][$month])) {
        $organizedAudits[$year][$month] = [];
    }
    $organizedAudits[$year][$month][] = $audit;
}

// Create new PDF document
$pdf = new TCPDF();
$pdf->AddPage();

// Set title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Audit Trail Report', 0, 1, 'C');
$pdf->Ln(10);

// Set header
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(20, 10, 'Audit ID', 1);
$pdf->Cell(50, 10, 'User', 1);
$pdf->Cell(70, 10, 'Action', 1);
$pdf->Cell(50, 10, 'Date & Time', 1);
$pdf->Ln();

// Set data
$pdf->SetFont('helvetica', '', 12);
foreach ($organizedAudits as $year => $months) {
    $pdf->Cell(0, 10, $year, 1, 1, 'C');
    foreach ($months as $month => $audits) {
        $pdf->Cell(0, 10, $month, 1, 1, 'C');
        foreach ($audits as $audit) {
            $datetimeFormatted = date('m-d-Y, g:i A', strtotime($audit['audit_datetime']));
            $pdf->Cell(20, 10, $audit['audit_id'], 1);
            $pdf->Cell(50, 10, $audit['user_fname'] . ' ' . $audit['user_lname'], 1);
            $pdf->Cell(70, 10, $audit['action'], 1);
            $pdf->Cell(50, 10, $datetimeFormatted, 1);
            $pdf->Ln();
        }
    }
}

// Output PDF
$pdf->Output('audit_trail_report.pdf', 'D');
