<?php
require("./conx/conx_admin.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['res_id'])) {
    $res_id = $_POST['res_id'];

    try {
        // Get reservation total
        $stmt = $conx_admin->prepare("SELECT res_total FROM res WHERE res_id = :res_id");
        $stmt->bindParam(':res_id', $res_id, PDO::PARAM_INT);
        $stmt->execute();
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reservation) {
            // Get total payments made for the reservation
            $stmt = $conx_admin->prepare("SELECT SUM(payment_amount) AS payment_amount FROM payments WHERE res_id = :res_id");
            $stmt->bindParam(':res_id', $res_id, PDO::PARAM_INT);
            $stmt->execute();
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            $payment_amount = $payment['payment_amount'] ? $payment['payment_amount'] : 0;

            echo json_encode(['status' => 'success', 'res_total' => $reservation['res_total'], 'payment_amount' => $payment_amount]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Reservation not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
