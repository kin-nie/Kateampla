<?php
session_start(); // Start the session
require("./conx/conx_customer.php");

// Check if the user is logged in and if the user type is 2 (customer)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: logout.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $res_id = $_POST['res_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Prepare and execute the query to update the reservation status
        $stmt = $conx_customer->prepare("UPDATE res SET res_status = 'cancelled' WHERE res_id = :res_id AND user_id = :user_id");
        $stmt->bindParam(':res_id', $res_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Insertion to audit trail
            $action = "cancel reservation";
            $audit_datetime = date("Y-m-d H:i:s");
            $stmt_audit = $conx_customer->prepare("INSERT INTO audit_trail (user_id, action, audit_datetime) VALUES (:user_id, :action, :audit_datetime)");
            $stmt_audit->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_audit->bindParam(':action', $action, PDO::PARAM_STR);
            $stmt_audit->bindParam(':audit_datetime', $audit_datetime, PDO::PARAM_STR);
            $stmt_audit->execute();

            // Redirect to the same page to see the changes
            header("Location: custPaidRes.php");
            exit();
        } else {
            // Handle case where reservation is not found or user is not authorized
            echo "Failed to cancel the reservation.";
        }
    } catch (PDOException $e) {
        // Handle database errors
        error_log($e->getMessage());
        echo "An error occurred. Please try again later.";
    }
} else {
    // Handle invalid request method
    echo "Invalid request.";
}
