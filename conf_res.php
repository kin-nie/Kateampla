<?php
session_start();

// Ensure the user is logged in and the booking details are set
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2 || !isset($_SESSION['booking_details'])) {
    header("Location: login.php");
    exit();
}

// Database connection
require("./conx/conx_customer.php");

// Ensure the connection is successful
if (!$conx_customer) {
    die("Database connection failed");
}

$user_id = $_SESSION['user_id'];
$booking_details = $_SESSION['booking_details'];

// Fetch the room name
$stmt = $conx_customer->prepare("SELECT room_name FROM room WHERE room_id = :room_id");
$stmt->bindParam(':room_id', $booking_details['room_id'], PDO::PARAM_INT);
$stmt->execute();
$room = $stmt->fetch(PDO::FETCH_ASSOC);
$room_name = $room['room_name'];

// Function to convert time to 12-hour format with AM/PM
function convertTo12HourFormat($time)
{
    return date("g:i A", strtotime($time));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .card-custom {
        max-width: 600px;
    }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card card-custom mx-auto">
                    <div class="card-header">
                        <h3 class="text-center">Booking Confirmation</h3>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-center">Booking Details</h5>
                        <p><strong>Room Name:</strong> <?php echo htmlspecialchars($room_name); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($booking_details['res_date']); ?></p>
                        <p><strong>Start Time:</strong>
                            <?php echo convertTo12HourFormat(htmlspecialchars($booking_details['res_start_time'])); ?>
                        </p>
                        <p><strong>End Time:</strong>
                            <?php echo convertTo12HourFormat(htmlspecialchars($booking_details['res_end_time'])); ?></p>
                        <p><strong>Total Price:</strong>
                            <?php echo htmlspecialchars($booking_details['res_total'])  . "php"; ?></p>
                        <p><strong>Reservation Fee:</strong>
                            <?php echo htmlspecialchars($booking_details['res_price']) . "php"; ?></p>

                        <div class="d-flex justify-content-around mt-4">
                            <a href="customerPage.php" class="btn btn-secondary">Go Back</a>
                            <form action="gcash.php" method="post">
                                <button type="submit" class="btn btn-primary">Pay with GCash</button>
                            </form>
                            <form action="paymaya.php" method="post">
                                <button type="submit" class="btn btn-primary">Pay with PayMaya</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>