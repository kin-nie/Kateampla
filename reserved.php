<?php
session_start(); // Start the session
require './conx/conx_customer.php'; // Include your database connection file

// Check if the payment reference number is already set in the session
if (!isset($_SESSION['payment_ref'])) {
    // If not set, generate a new random number
    $_SESSION['payment_ref'] = mt_rand(100000000, 999999999);
}

// Retrieve reservation details passed from the previous page
$user_id = $_POST['user_id'];
$room_id = $_POST['room_id'];
$booking_date = $_POST['booking_date'];
$booking_time = $_POST['booking_time'];
$res_end_time = $_POST['res_end_time'];
$total_price = $_POST['total_price'];

// Retrieve payment information from the form
$payment_number = $_POST['number'];
$payment_amount = $_POST['payment_amount'];
$payment_method = $_POST['method']; // Get payment method dynamically

// Retrieve payment reference number from session
$stored_payment_ref = $_SESSION['payment_ref'];

function convertTo12HourFormat($time)
{
    $formattedTime = date("g:i A", strtotime($time));
    return $formattedTime;
}

try {
    // Start a transaction
    $conx_customer->beginTransaction();

    // Check if a reservation with the same details already exists
    $stmt_check_duplicate = $conx_customer->prepare("
        SELECT COUNT(*) FROM res
        WHERE user_id = :user_id
        AND room_id = :room_id
        AND res_date = :booking_date
        AND res_start_time = :booking_time
        AND res_end_time = :res_end_time
    ");
    $stmt_check_duplicate->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check_duplicate->bindParam(':room_id', $room_id, PDO::PARAM_INT);
    $stmt_check_duplicate->bindParam(':booking_date', $booking_date, PDO::PARAM_STR);
    $stmt_check_duplicate->bindParam(':booking_time', $booking_time, PDO::PARAM_STR);
    $stmt_check_duplicate->bindParam(':res_end_time', $res_end_time, PDO::PARAM_STR);
    $stmt_check_duplicate->execute();
    $duplicate_count = $stmt_check_duplicate->fetchColumn();

    if ($duplicate_count > 0) {
        // Rollback the transaction
        $conx_customer->rollBack();
        // Notify the user that the transaction didn't go through
        echo "Error: Duplicate reservation detected. Your transaction did not go through.";
    } else {
        // Insert reservation details
        $stmt_reservation = $conx_customer->prepare("
            INSERT INTO res (user_id, room_id, res_date, res_start_time, res_end_time, res_total, res_status)
            VALUES (:user_id, :room_id, :booking_date, :booking_time, :res_end_time, :total_price, 'reserved')
        ");
        $stmt_reservation->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_reservation->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt_reservation->bindParam(':booking_date', $booking_date, PDO::PARAM_STR);
        $stmt_reservation->bindParam(':booking_time', $booking_time, PDO::PARAM_STR);
        $stmt_reservation->bindParam(':res_end_time', $res_end_time, PDO::PARAM_STR);
        $stmt_reservation->bindParam(':total_price', $total_price, PDO::PARAM_STR);
        $stmt_reservation->execute();

        // Get the last inserted reservation ID
        $reservation_id = $conx_customer->lastInsertId();

        // Insert payment details
        $stmt_payment = $conx_customer->prepare("
            INSERT INTO payments (res_id, payment_method, payment_number, payment_amount, payment_datetime, payment_ref, payment_status)
            VALUES (:reservation_id, :payment_method, :payment_number, :payment_amount, CURRENT_TIMESTAMP(), :payment_ref, 'successful')
        ");
        $stmt_payment->bindParam(':reservation_id', $reservation_id, PDO::PARAM_INT);
        $stmt_payment->bindParam(':payment_method', $payment_method, PDO::PARAM_STR);
        $stmt_payment->bindParam(':payment_number', $payment_number, PDO::PARAM_STR);
        $stmt_payment->bindParam(':payment_amount', $payment_amount, PDO::PARAM_STR);
        $stmt_payment->bindParam(':payment_ref', $stored_payment_ref, PDO::PARAM_STR); // Use the stored value
        $stmt_payment->execute();

        // Insert into audit_trail table
        $stmt_audit = $conx_customer->prepare("
            INSERT INTO audit_trail (user_id, action, audit_datetime)
            VALUES (:user_id, 'reserve room', CURRENT_TIMESTAMP())
        ");
        $stmt_audit->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_audit->execute();

        // Commit the transaction
        $conx_customer->commit();

        // Fetch room details for display in the receipt
        $stmt_room = $conx_customer->prepare("
            SELECT room_name FROM room WHERE room_id = :room_id
        ");
        $stmt_room->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt_room->execute();
        $room = $stmt_room->fetch(PDO::FETCH_ASSOC);

        // Fetch user details for display in the receipt
        $stmt_user = $conx_customer->prepare("
            SELECT user_fname, user_lname FROM users WHERE user_id = :user_id
        ");
        $stmt_user->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_user->execute();
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        // Store receipt details in the session with converted times
        $_SESSION['receipt_details'] = [
            'user_fname' => $user['user_fname'],
            'user_lname' => $user['user_lname'],
            'room_name' => $room['room_name'],
            'booking_date' => $booking_date,
            'booking_time' => convertTo12HourFormat($booking_time),
            'res_end_time' => convertTo12HourFormat($res_end_time),
            'total_price' => $total_price,
            'payment_number' => $payment_number,
            'payment_amount' => $payment_amount,
            'payment_method' => $payment_method,
            'payment_ref' => $stored_payment_ref
        ];

        // Display receipt
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Receipt</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header">
                        <h5 class="card-title text-center">Reservation Receipt</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Sender:</strong> <?php echo $user['user_fname'] . ' ' . $user['user_lname']; ?></p>
                        <p><strong>Room Name:</strong> <?php echo $room['room_name']; ?></p>
                        <p><strong>Reservation Date:</strong> <?php echo $booking_date; ?></p>
                        <p><strong>Reservation Time:</strong>
                            <?php echo convertTo12HourFormat($booking_time) . ' - ' . convertTo12HourFormat($res_end_time); ?>
                        </p>
                        <p><strong>Payment Method:</strong> <?php echo $payment_method; ?></p>
                        <p><strong>Payment Recipient:</strong> <?php echo "09xxxxxxxxx"; ?></p>
                        <p><strong>Payment Amount:</strong> <?php echo $payment_amount; ?>php</p>
                        <p><strong>Payment Reference:</strong> <?php echo $stored_payment_ref; ?></p>

                        <!-- Download PDF button -->
                        <div class="text-center mb-2">
                            <a href="custReceipt.php" class="btn btn-primary">Print Receipt</a>
                        </div>

                        <!-- Close button -->
                        <div class="text-center">
                            <a href="customerPage.php" class="btn btn-dark"
                                style="background-color:rgb(243, 142, 41);">Close</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

<?php
        // Ensure session data is available before unsetting
        if (isset($_SESSION['booking_details'])) {
            unset($_SESSION['booking_details']);
        }

        // Stop execution after displaying receipt
        exit();
    }
} catch (PDOException $e) {
    // Rollback the transaction on error
    $conx_customer->rollBack();
    // Display an error message
    echo "Error: " . $e->getMessage();
}
?>