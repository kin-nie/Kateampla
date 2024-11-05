<?php
session_start(); // Start the session
require("./conx/conx_customer.php");

if (isset($_SESSION['payment_ref'])) {
    unset($_SESSION['payment_ref']);
}

// Ensure session data is unset only when necessary
if (isset($_SESSION['receipt_details'])) {
    unset($_SESSION['receipt_details']);
}

// Check if the user is logged in and if the user type is 2 (customer)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: logout.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Prepare and execute the query to get user details
    $stmt = $conx_customer->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $res_Uname = $user['user_fname'] . ' ' . $user['user_lname'];
        $res_Email = $user['user_email'];
        $res_Age = $user['user_age'];
        $user_pic_base64 = base64_encode($user['user_pic']);
        $user_connum = $user['user_connum'];
    } else {
        // Handle case where user details are not found
        echo "User details not found.";
        exit();
    }

    // Fetch room options with room_status of 'available'
    $stmt_room = $conx_customer->prepare("SELECT * FROM room WHERE room_status = 'available'");
    $stmt_room->execute();
    $room_options = $stmt_room->fetchAll(PDO::FETCH_ASSOC);

    // Set initial room price
    $initial_price = !empty($room_options) ? $room_options[0]['room_price'] : '';

    // Fetch closed dates
    $stmt_closed_dates = $conx_customer->prepare("SELECT close_date FROM close WHERE close_status = 'closed'");
    $stmt_closed_dates->execute();
    $closed_dates = $stmt_closed_dates->fetchAll(PDO::FETCH_COLUMN);

    function convertTo12HourFormat($time)
    {
        return date("g:i A", strtotime($time));
    }

    // Fetch available time slots
    $stmt_time_slots = $conx_customer->prepare("SELECT * FROM time");
    $stmt_time_slots->execute();
    $time_slots = $stmt_time_slots->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database errors
    error_log($e->getMessage());
    echo "An error occurred. Please try again later.";
    exit();
}

// Check for existing reservation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reserve'])) {
    $room_id = $_POST['room-options'];
    $res_date = $_POST['booking-date'];
    $res_start_time = $_POST['booking-time'];

    // Check if the selected date is closed
    if (in_array($res_date, $closed_dates)) {
        echo "<script>alert('The selected date is closed for reservations. Please choose another date.');</script>";
    } else {
        // Calculate end time based on selected start time
        $stmt_get_end_time = $conx_customer->prepare("SELECT time_end FROM time WHERE time_start = :time_start");
        $stmt_get_end_time->bindParam(':time_start', $res_start_time, PDO::PARAM_STR);
        $stmt_get_end_time->execute();
        $time = $stmt_get_end_time->fetch(PDO::FETCH_ASSOC);
        $res_end_time = $time['time_end'];

        $stmt_check = $conx_customer->prepare("SELECT * FROM res WHERE room_id = :room_id AND res_date = :res_date AND res_start_time = :res_start_time AND res_status = 'reserved'");
        $stmt_check->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt_check->bindParam(':res_date', $res_date, PDO::PARAM_STR);
        $stmt_check->bindParam(':res_start_time', $res_start_time, PDO::PARAM_STR);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            echo "<script>alert('There is already a reservation at that time.');</script>";
        } else {
            // Redirect to process_booking.php for further processing
            $_SESSION['booking_details'] = [
                'user_connum' => $user_connum,
                'room_id' => $room_id,
                'res_date' => $res_date,
                'res_start_time' => $res_start_time,
                'res_end_time' => $res_end_time,
                'res_total' => $_POST['res-total'],
                'res_price' => $_POST['res-price']
            ];
            header("Location: conf_res.php");
            exit();
        }
    }
}


// Pre-fill form fields with session data if available
$room_id = isset($_SESSION['booking_details']['room_id']) ? $_SESSION['booking_details']['room_id'] : '';
$res_date = isset($_SESSION['booking_details']['res_date']) ? $_SESSION['booking_details']['res_date'] : '';
$res_start_time = isset($_SESSION['booking_details']['res_start_time']) ? $_SESSION['booking_details']['res_start_time'] : '';
$res_total = isset($_SESSION['booking_details']['res_total']) ? $_SESSION['booking_details']['res_total'] : $initial_price;
$res_price = isset($_SESSION['booking_details']['res_price']) ? $_SESSION['booking_details']['res_price'] : $initial_price * 0.3;
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Customer Page</title>

    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css"
        integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous" />
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js"
        integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ" crossorigin="anonymous">
    </script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js"
        integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous">
    </script>
    <link rel="stylesheet" href="style/page.css" />

    <!-- Add this PHP block to embed the closed dates array in the JavaScript -->
    <script>
    const closedDates = <?php echo json_encode($closed_dates); ?>;
    </script>

</head>

<body>
    <div class="wrapper">
        <!-- Sidebar  -->
        <nav id="sidebar">
            <div class="sidebar-header text-center">
                <img src="data:image/jpeg;base64,<?= $user_pic_base64; ?>" class="profile-image" alt="Profile Image">
                <h2 class="mt-1"><?php echo $res_Uname; ?></h2>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="customerPage.php">Make a Reservation</a>
                </li>
                <li>
                    <a href="#pageSubmenu" data-toggle="collapse" aria-expanded="false"
                        class="dropdown-toggle">Reservations</a>
                    <ul class="collapse list-unstyled" id="pageSubmenu">
                        <li>
                            <a href="custPaidRes.php">Pending and Completed Reservations</a>
                        </li>
                        <li>
                            <a href="custCanRes.php">Cancelled and No Show Reservations</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="profCustomer.php">View Profile</a>
                </li>
            </ul>
            <ul class="list-unstyled CTAs">
                <li>
                    <a href="updCustomer.php" class="btn btn-block btn-primary">Update
                        Profile</a>
                </li>
                <li>
                    <a href="logout.php" class="btn btn-block btn-primary">Log-Out</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content  -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light">
                <div class=" container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info btn-dark">
                        <i class="fas fa-align-left"></i>
                        <span>Menu</span>
                    </button>
                    <button class="btn btn-dark d-inline-block d-lg-none ml-auto" type="button" data-toggle="collapse"
                        data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation">
                        <i class="fas fa-align-justify"></i>
                    </button>
                </div>
            </nav>

            <div class="container">
                <div class="form-box">
                    <h1>Make a Reservation</h1>
                    <form id="reservation-form" action="customerPage.php" method="POST">
                        <div class="form-group">
                            <label for="room-options">Choose a Room:</label>
                            <select class="form-control" id="room-options" name="room-options" required>
                                <?php foreach ($room_options as $option) : ?>
                                <option value="<?php echo $option['room_id']; ?>"
                                    data-price="<?php echo $option['room_price']; ?>"
                                    data-room-pic="data:image/jpeg;base64,<?php echo base64_encode($option['room_pic']); ?>"
                                    <?php echo $room_id == $option['room_id'] ? 'selected' : ''; ?>>
                                    <?php echo $option['room_name']; ?> (₱<?php echo $option['room_price']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="room-preview">Room Preview:</label>
                            <img id="room-preview" src="" alt="Room Preview"
                                style="width: 100%; max-width: 510px; display: block; margin-top: 10px;">
                        </div>
                        <div class="form-group">
                            <label for="booking-date">Choose a Date:</label>
                            <input type="date" class="form-control" id="booking-date" name="booking-date"
                                min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                                value="<?php echo $res_date; ?>" required>
                        </div>


                        <div class="form-group">
                            <label for="booking-time">Choose a Time:</label>
                            <select class="form-control" id="booking-time" name="booking-time" required>
                                <option value="">Select a time slot</option>
                                <?php foreach ($time_slots as $slot) : ?>
                                <option value="<?php echo $slot['time_start']; ?>"
                                    data-end-time="<?php echo $slot['time_end']; ?>"
                                    <?php echo $res_start_time == $slot['time_start'] ? 'selected' : ''; ?>>
                                    <?php echo convertTo12HourFormat($slot['time_start']); ?> -
                                    <?php echo convertTo12HourFormat($slot['time_end']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="res-total">Total Price (₱):</label>
                            <input type="text" class="form-control" id="res-total" name="res-total"
                                value="<?php echo $res_total; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="res-price">Reservation Price (30%) (₱):</label>
                            <input type="text" class="form-control" id="res-price" name="res-price"
                                value="<?php echo $res_price; ?>" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block" name="reserve">Reserve</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery CDN - Slim version (without AJAX) -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" crossorigin="anonymous"></script>

    <!-- Popper.JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"
        integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous">
    </script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"
        integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous">
    </script>

    <!-- Custom JS -->
    <script>
    $(document).ready(function() {
        $("#sidebarCollapse").on("click", function() {
            console.log("Sidebar button clicked");
            $("#sidebar").toggleClass("active");
            console.log($("#sidebar")); // Check if the sidebar element is being targeted
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        const roomOptions = document.getElementById('room-options');
        const roomPreview = document.getElementById('room-preview');
        const totalPriceInput = document.getElementById('res-total');
        const reservationPriceInput = document.getElementById('res-price');
        const bookingDateInput = document.getElementById('booking-date');
        const bookingTimeSelect = document.getElementById('booking-time');

        // Function to format time to 12-hour format
        function convertTo12HourFormat(time) {
            const [hour, minute] = time.split(':');
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const formattedHour = hour % 12 || 12;
            return `${formattedHour}:${minute} ${ampm}`;
        }

        // Calculate the reservation price based on the selected room price
        function calculateReservationPrice() {
            const selectedOption = roomOptions.options[roomOptions.selectedIndex];
            const roomPrice = parseFloat(selectedOption.getAttribute('data-price'));
            const reservationPrice = roomPrice * 0.3;
            totalPriceInput.value = roomPrice.toFixed(2);
            reservationPriceInput.value = reservationPrice.toFixed(2);
        }

        // Populate available time slots based on the selected date and room
        function populateTimeSlots() {
            const selectedDate = bookingDateInput.value;
            const selectedRoom = roomOptions.value;
            bookingTimeSelect.innerHTML = '<option value="">Select a time slot</option>';

            if (!selectedDate || !selectedRoom) return;

            fetch('fetch_time_slots.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        selectedDate: selectedDate,
                        roomId: selectedRoom
                    })
                })
                .then(response => response.json())
                .then(data => {
                    data.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.time_start;
                        option.dataset.endTime = slot.time_end;
                        option.textContent =
                            `${convertTo12HourFormat(slot.time_start)} - ${convertTo12HourFormat(slot.time_end)}`;
                        bookingTimeSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching time slots:', error));
        }

        // Show room preview based on the selected room
        function showRoomPreview() {
            const selectedOption = roomOptions.options[roomOptions.selectedIndex];
            const roomPic = selectedOption.getAttribute('data-room-pic');
            roomPreview.src = roomPic;
        }

        roomOptions.addEventListener('change', () => {
            calculateReservationPrice();
            populateTimeSlots();
            showRoomPreview();
        });

        bookingDateInput.addEventListener('change', () => {
            populateTimeSlots();

            // Check if the selected date is closed
            const selectedDate = bookingDateInput.value;
            if (closedDates.includes(selectedDate)) {
                alert('The selected date is closed for reservations. Please choose another date.');
                bookingDateInput.value = ''; // Clear the input field
            }
        });

        // Pre-fill the form with session data if available
        <?php if ($room_id) : ?>
        calculateReservationPrice();
        populateTimeSlots();
        showRoomPreview();
        <?php endif; ?>

        showRoomPreview();
    });
    </script>

</body>

</html>