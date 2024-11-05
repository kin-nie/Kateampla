<?php
session_start(); // Start the session
require './conx/conx_customer.php'; // Include your database connection file

// Ensure the user is logged in and the booking details are set
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2 || !isset($_SESSION['booking_details'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_details = $_SESSION['booking_details'];
$user_connum = $booking_details['user_connum'];
$room_id = $booking_details['room_id'];
$total_price = $booking_details['res_total'];
$res_price = $booking_details['res_price'];
$booking_date = $booking_details['res_date'];
$booking_time = $booking_details['res_start_time'];
$res_end_time = $booking_details['res_end_time'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayMaya Payment</title>
    <link rel="stylesheet" href="style/maya.css">
    <script src="https://kit.fontawesome.com/1fd0899690.js" crossorigin="anonymous"></script>
    <script>
    function handleFormSubmission(event) {
        event.preventDefault(); // Prevent the default form submission

        // Display the confirmation prompt
        if (confirm("Confirm Payment?")) {
            // Submit the form programmatically
            event.target.submit();
            // Display the success alert after form submission
            alert("Successfully Reserved!");
        }
    }
    </script>
</head>

<body>
    <div class="container">
        <div id="paymentlogo">
            <img src="assets/paymaya.png">
            <h2>PayMaya Payment</h2>
        </div>
        <a href="conf_res.php"><i class="fa-solid fa-chevron-left"></i>Back</a>
        <form action="reserved.php" method="post" onsubmit="handleFormSubmission(event)">
            <div class="payment-fields">
                <label for="total_amount">Total Reservation Fee:</label><br>
                <input type="text" id="res_fee" value="<?= htmlspecialchars($res_price); ?>" disabled><br>
                <label for="payment_amount">Payment Amount:</label><br>
                <input type="number" id="payment_amount" name="payment_amount"
                    value="<?= htmlspecialchars($res_price); ?>" disabled><br>
                <input type="hidden" id="payment_amount" name="payment_amount"
                    value="<?= htmlspecialchars($res_price); ?>">

                <span>Please note that reservation fee is non-refundable.</span>
            </div>
            <label for="paymaya_number">PayMaya Number:</label><br>
            <input type="text" id="paymaya_number" name="number" placeholder="09XX XXX XXXX" pattern="(\+63|0)9\d{9}"
                title="Please enter a valid Philippine contact number" required
                value="<?= htmlspecialchars($user_connum); ?>"><br>
            <!-- Hidden inputs to pass reservation details to the record_transaction.php -->
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id); ?>">
            <input type="hidden" name="room_id" value="<?= htmlspecialchars($room_id); ?>">
            <input type="hidden" name="total_price" value="<?= htmlspecialchars($total_price); ?>">
            <input type="hidden" name="res_dp" value="<?= htmlspecialchars($res_price); ?>"><br>
            <input type="hidden" name="booking_date" value="<?= htmlspecialchars($booking_date); ?>">
            <input type="hidden" name="booking_time" value="<?= htmlspecialchars($booking_time); ?>">
            <input type="hidden" name="res_end_time" value="<?= htmlspecialchars($res_end_time); ?>">
            <input type="hidden" name="method" value="paymaya">

            <button type="submit">Pay via PayMaya</button>
        </form>
    </div>
</body>

</html>