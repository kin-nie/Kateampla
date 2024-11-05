<?php
session_start();
require("./conx/conx_admin.php");
require_once('tcpdf/tcpdf.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: logout.php");
    exit();
}

$export_all = isset($_GET['export_all']) ? (bool)$_GET['export_all'] : false;
$filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filterValue = isset($_GET['filter_value']) ? $_GET['filter_value'] : '';
$sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';

$sql = "SELECT res.*, users.user_id, users.user_fname, users.user_lname, room.room_name,
               p1.payment_ref AS payment_ref1, p1.payment_amount AS payment_amount1, p1.payment_method AS payment_method1,
               p2.payment_ref AS payment_ref2, p2.payment_amount AS payment_amount2, p2.payment_method AS payment_method2
        FROM res
        JOIN users ON res.user_id = users.user_id
        JOIN room ON res.room_id = room.room_id
        LEFT JOIN payments p1 ON res.res_id = p1.res_id AND p1.payment_status = 'successful'
        LEFT JOIN payments p2 ON res.res_id = p2.res_id AND p2.payment_id > p1.payment_id AND p2.payment_status = 'successful'
        WHERE 1 = 1";

if ($filterType && $filterValue) {
    switch ($filterType) {
        case 'date':
            $sql .= " AND res.res_date = :filter_value";
            break;
        case 'month':
            $sql .= " AND MONTH(res.res_date) = :filter_value";
            break;
        case 'year':
            $sql .= " AND YEAR(res.res_date) = :filter_value";
            break;
        case 'user_id':
            $sql .= " AND res.user_id = :filter_value";
            break;
        case 'room_id':
            $sql .= " AND res.room_id = :filter_value";
            break;
    }
}

if (!$export_all) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $sql .= " ORDER BY YEAR(res.res_date) $sortOrder, MONTH(res.res_date) $sortOrder LIMIT :limit OFFSET :offset";
} else {
    $sql .= " ORDER BY YEAR(res.res_date) $sortOrder, MONTH(res.res_date) $sortOrder";
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
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$organizedReservations = [];
foreach ($reservations as $reservation) {
    $year = date('Y', strtotime($reservation['res_date']));
    $month = date('F', strtotime($reservation['res_date']));
    if (!isset($organizedReservations[$year])) {
        $organizedReservations[$year] = [];
    }
    if (!isset($organizedReservations[$year][$month])) {
        $organizedReservations[$year][$month] = [];
    }
    $organizedReservations[$year][$month][] = $reservation;
}

$pdf = new TCPDF('L', 'mm', 'A3', true, 'UTF-8', false);
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 12, 'Reservations Report', 0, 1, 'C');
$pdf->Ln(6);

$columnWidths = [
    'rID' => 10,
    'uID' => 10,
    'User' => 45,
    'Room' => 35,
    'Date' => 25,
    'Start' => 20,
    'End' => 20,
    'Check-in' => 22, // New column
    'Check-out' => 22, // New column
    'Total' => 17,
    'Downpay' => 23,
    'Ref No. 1' => 30,
    'Method 1' => 23,
    'Comp. Pay' => 25,
    'Ref No. 2' => 23,
    'Method 2' => 25,
    'Status' => 25,
];

foreach ($organizedReservations as $year => $months) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $year, 1, 1, 'C');
    foreach ($months as $month => $reservations) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, $month, 1, 1, 'C');
        $pdf->SetFont('helvetica', '', 11);

        foreach ($columnWidths as $header => $width) {
            $pdf->Cell($width, 10, $header, 1);
        }
        $pdf->Ln();

        foreach ($reservations as $reservation) {
            $pdf->Cell($columnWidths['rID'], 10, $reservation['res_id'], 1);
            $pdf->Cell($columnWidths['uID'], 10, htmlspecialchars($reservation['user_id']), 1);
            $pdf->Cell($columnWidths['User'], 10, htmlspecialchars($reservation['user_fname'] . ' ' . $reservation['user_lname']), 1);
            $pdf->Cell($columnWidths['Room'], 10, htmlspecialchars($reservation['room_name']), 1);
            $pdf->Cell($columnWidths['Date'], 10, htmlspecialchars($reservation['res_date']), 1);
            $pdf->Cell($columnWidths['Start'], 10, htmlspecialchars(date('h:i A', strtotime($reservation['res_start_time']))), 1);
            $pdf->Cell($columnWidths['End'], 10, htmlspecialchars(date('h:i A', strtotime($reservation['res_end_time']))), 1);
            $pdf->Cell($columnWidths['Check-in'], 10, htmlspecialchars(date('h:i A', strtotime($reservation['res_checkin']))), 1); // New cell
            $pdf->Cell($columnWidths['Check-out'], 10, htmlspecialchars(date('h:i A', strtotime($reservation['res_checkout']))), 1); // New cell
            $pdf->Cell($columnWidths['Total'], 10, htmlspecialchars($reservation['res_total']), 1);
            $pdf->Cell($columnWidths['Downpay'], 10, htmlspecialchars($reservation['payment_amount1']), 1);
            $pdf->Cell($columnWidths['Ref No. 1'], 10, htmlspecialchars($reservation['payment_ref1']), 1);
            $pdf->Cell($columnWidths['Method 1'], 10, htmlspecialchars($reservation['payment_method1']), 1);
            $pdf->Cell($columnWidths['Comp. Pay'], 10, htmlspecialchars($reservation['payment_amount2']), 1);
            $pdf->Cell($columnWidths['Ref No. 2'], 10, htmlspecialchars($reservation['payment_ref2']), 1);
            $pdf->Cell($columnWidths['Method 2'], 10, htmlspecialchars($reservation['payment_method2']), 1);
            $pdf->Cell($columnWidths['Status'], 10, htmlspecialchars($reservation['res_status']), 1);
            $pdf->Ln();
        }
    }
}

$pdf->Output('reservations_report.pdf', 'D');
