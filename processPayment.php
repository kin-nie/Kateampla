<?php
require("./conx/conx_admin.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['res_id']) && isset($_POST['payment_method']) && isset($_POST['payment_amount'])) {
    $res_id = $_POST['res_id'];
    $payment_method = $_POST['payment_method'];
    $payment_number = isset($_POST['payment_number']) ? $_POST['payment_number'] : '';
    $payment_amount = $_POST['payment_amount'];
    $payment_ref = isset($_POST['payment_ref']) ? $_POST['payment_ref'] : '';

    try {
        // Insert the payment
        $stmt = $conx_admin->prepare("INSERT INTO payments (res_id, payment_method, payment_number, payment_amount, payment_ref, payment_status) VALUES (:res_id, :payment_method, :payment_number, :payment_amount, :payment_ref, 'successful')");
        $stmt->bindParam(':res_id', $res_id, PDO::PARAM_INT);
        $stmt->bindParam(':payment_method', $payment_method, PDO::PARAM_STR);
        $stmt->bindParam(':payment_number', $payment_number, PDO::PARAM_STR);
        $stmt->bindParam(':payment_amount', $payment_amount, PDO::PARAM_STR);
        $stmt->bindParam(':payment_ref', $payment_ref, PDO::PARAM_STR);

        if ($stmt->execute()) {
            // Update the reservation status
            $stmt = $conx_admin->prepare("UPDATE res SET res_status = 'completed' WHERE res_id = :res_id");
            $stmt->bindParam(':res_id', $res_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update reservation status']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert payment']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
