<?php
require("./conx/conx_customer.php");

$data = json_decode(file_get_contents('php://input'), true);
$date = $data['date'];

try {
    $stmt = $conx_customer->prepare("SELECT res_start_time FROM res WHERE res_date = :res_date AND res_status = 'reserved'");
    $stmt->bindParam(':res_date', $date, PDO::PARAM_STR);
    $stmt->execute();

    $reserved_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['reserved_slots' => $reserved_slots]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'An error occurred. Please try again later.']);
}
