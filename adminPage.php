<?php
session_start(); // Start the session
require("./conx/conx_admin.php");

// Check if the user is logged in and if the user type is 1 (admin)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: logout.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get today's date
$today_date = date("Y-m-d");

try {
    // Prepare and execute the query to get user details
    $stmt = $conx_admin->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $res_Uname = $user['user_fname'] . ' ' . $user['user_lname'];
        $user_pic_base64 = base64_encode($user['user_pic']);
    } else {
        // Handle case where user details are not found
        echo "User details not found.";
        exit();
    }

    // Prepare and execute the query to count today's reservations
    $stmt = $conx_admin->prepare("SELECT COUNT(*) AS today_reservations FROM res WHERE res_date = :today_date AND res_status = 'reserved'");
    $stmt->bindParam(':today_date', $today_date, PDO::PARAM_STR);
    $stmt->execute();
    $today_reservations = $stmt->fetch(PDO::FETCH_ASSOC)['today_reservations'];

    // Prepare and execute the query to get details of today's reservations with status "reserved"
    $stmt = $conx_admin->prepare("
        SELECT 
            res.res_id, 
            CONCAT(users.user_fname, ' ', users.user_lname) AS user_name,
            room.room_name, 
            res.res_start_time, 
            res.res_end_time
        FROM 
            res
        JOIN users ON res.user_id = users.user_id
        JOIN room ON res.room_id = room.room_id
        WHERE 
            res.res_date = :today_date AND res.res_status = 'reserved'
    ");
    $stmt->bindParam(':today_date', $today_date, PDO::PARAM_STR);
    $stmt->execute();
    $reservations_reserved = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conx_admin->prepare("
    SELECT 
        res.res_id, 
        CONCAT(users.user_fname, ' ', users.user_lname) AS user_name,
        room.room_name, 
        res.res_start_time, 
        res.res_end_time,
        res.res_checkin,
        res.res_checkout
    FROM 
        res
    JOIN users ON res.user_id = users.user_id
    JOIN room ON res.room_id = room.room_id
    WHERE 
        res.res_date = :today_date AND res.res_status = 'completed'
");
    $stmt->bindParam(':today_date', $today_date, PDO::PARAM_STR);
    $stmt->execute();
    $reservations_completed = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt->bindParam(':today_date', $today_date, PDO::PARAM_STR);
    $stmt->execute();
    $reservations_completed = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare and execute the query to get details of today's reservations with status "completed"
    $stmt = $conx_admin->prepare("
        SELECT 
            res.res_id, 
            CONCAT(users.user_fname, ' ', users.user_lname) AS user_name,
            room.room_name, 
            res.res_start_time, 
            res.res_end_time
        FROM 
            res
        JOIN users ON res.user_id = users.user_id
        JOIN room ON res.room_id = room.room_id
        WHERE 
            res.res_date = :today_date AND res.res_status = 'absent' 
    ");

    $stmt->bindParam(':today_date', $today_date, PDO::PARAM_STR);
    $stmt->execute();
    $reservations_absent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle the AJAX request to update reservation status or time
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['res_id']) && isset($_POST['action'])) {
        $res_id = $_POST['res_id'];
        $action = $_POST['action'];

        if ($action === 'no_show') {
            $new_status = 'absent';
        } elseif ($action === 'completed') {
            $new_status = 'completed';
        } elseif ($action === 'checkin' || $action === 'checkout') {
            $time_input = $_POST['input_time'];
            $time_field = ($action === 'checkin') ? 'res_checkin' : 'res_checkout';

            $stmt = $conx_admin->prepare("UPDATE res SET $time_field = :time_input WHERE res_id = :res_id");
            $stmt->bindParam(':time_input', $time_input, PDO::PARAM_STR);
            $stmt->bindParam(':res_id', $res_id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['status' => 'success']);
            exit();
        } else {
            throw new Exception('Invalid action');
        }

        $stmt = $conx_admin->prepare("UPDATE res SET res_status = :new_status WHERE res_id = :res_id");
        $stmt->bindParam(':new_status', $new_status, PDO::PARAM_STR);
        $stmt->bindParam(':res_id', $res_id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['status' => 'success']);
        exit();
    }
} catch (PDOException $e) {
    // Handle database errors
    error_log($e->getMessage());
    echo "An error occurred. Please try again later.";
    exit();
} catch (Exception $e) {
    // Handle general errors
    error_log($e->getMessage());
    echo "An error occurred. Please try again later.";
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Admin Page</title>
    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css"
        crossorigin="anonymous" />
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js" crossorigin="anonymous"></script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="style/page.css" />
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
                <li><a href="adminPage.php">Reservation Module</a></li>
                <li>
                    <a href="#pageSubmenu" data-toggle="collapse" aria-expanded="false"
                        class="dropdown-toggle">Statistics</a>
                    <ul class="collapse list-unstyled" id="pageSubmenu">
                        <li><a href="adminIncome.php">Payment Statistics</a></li>
                        <li><a href="adminRes.php">Reservation Statistics</a></li>
                        <li><a href="adminRooms.php">Room Statistics</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#pageSubmenu2" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">Manage
                        Offerings</a>
                    <ul class="collapse list-unstyled" id="pageSubmenu2">
                        <li><a href="adminAddRoom.php">Manage Rooms</a></li>
                        <li><a href="adminClose.php">Close Dates</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#pageSubmenu3" data-toggle="collapse" aria-expanded="false"
                        class="dropdown-toggle">Generate Reports</a>
                    <ul class="collapse list-unstyled" id="pageSubmenu3">
                        <li><a href="adminRepRes.php">Reservations Report</a></li>
                        <li><a href="adminRepInc.php">Payments Report</a></li>
                        <li><a href="adminRepAct.php">User Activity Report</a></li>
                        <li><a href="adminRepAudit.php">Audit Trail</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="list-unstyled CTAs">
                <li><a href="profAdmin.php" class="btn btn-block btn-primary">Manage Users</a></li>
                <li>
                    <a href="logout.php" class="btn btn-block btn-primary">Log-Out</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light">
                <div class="container-fluid">
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

            <div class="container mt-5">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Today (<?= date("F d Y"); ?>)</h5>
                        <p class="card-text">Pending Reservations: <?= $today_reservations; ?></p>
                    </div>
                </div>

                <h3 class="mt-4">List</h3>
                <table class="table table-bordered mt-4">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Room</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations_reserved as $reservation) : ?>
                        <tr>
                            <td><?= $reservation['user_name']; ?></td>
                            <td><?= $reservation['room_name']; ?></td>
                            <td><?= date("g:i A", strtotime($reservation['res_start_time'])); ?></td>
                            <td><?= date("g:i A", strtotime($reservation['res_end_time'])); ?></td>
                            <td>
                                <button class="btn btn-danger btn-sm no-show-btn"
                                    data-res-id="<?= $reservation['res_id']; ?>">No Show</button>
                                <button class="btn btn-success btn-sm checkin-btn"
                                    data-res-id="<?= $reservation['res_id']; ?>">Check-In</button>
                                <button class="btn btn-danger btn-sm checkout-btn"
                                    data-res-id="<?= $reservation['res_id']; ?>">Check-Out</button>
                                <button class="btn btn-success btn-sm completed-btn"
                                    data-res-id="<?= $reservation['res_id']; ?>">Completed</button>
                            </td>

                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3 class="mt-4">Completed Reservations</h3>
                <table class="table table-bordered mt-4">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Room</th>
                            <th>Start Time</th>
                            <th>Check-In Time</th>
                            <th>End Time</th>
                            <th>Check-Out Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations_completed as $reservation) : ?>
                        <tr>
                            <td><?= $reservation['user_name']; ?></td>
                            <td><?= $reservation['room_name']; ?></td>
                            <td><?= date("g:i A", strtotime($reservation['res_start_time'])); ?></td>
                            <td><?= $reservation['res_checkin'] ? date("g:i A", strtotime($reservation['res_checkin'])) : 'N/A'; ?>
                            </td>
                            <td><?= date("g:i A", strtotime($reservation['res_end_time'])); ?></td>
                            <td><?= $reservation['res_checkout'] ? date("g:i A", strtotime($reservation['res_checkout'])) : 'N/A'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <h3 class="mt-4">No Shows</h3>
                <table class="table table-bordered mt-4">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Room</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations_absent as $reservation) : ?>
                        <tr>
                            <td><?= $reservation['user_name']; ?></td>
                            <td><?= $reservation['room_name']; ?></td>
                            <td><?= date("g:i A", strtotime($reservation['res_start_time'])); ?></td>
                            <td><?= date("g:i A", strtotime($reservation['res_end_time'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal for Completing Payment -->
        <div class="modal fade" id="completePaymentModal" tabindex="-1" role="dialog"
            aria-labelledby="completePaymentModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="completePaymentModalLabel">Complete Payment</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="completePaymentForm">
                            <div class="form-group">
                                <label for="paymentMethod">Payment Method</label>
                                <select class="form-control" id="paymentMethod" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="gcash">Gcash</option>
                                    <option value="paymaya">Paymaya</option>
                                </select>
                            </div>
                            <div class="form-group" id="paymentNumberGroup">
                                <label for="paymentNumber">Payment Number</label>
                                <input type="text" class="form-control" id="paymentNumber" name="payment_number">
                            </div>
                            <div class="form-group" id="paymentRefGroup">
                                <label for="paymentRef">Payment Reference</label>
                                <input type="text" class="form-control" id="paymentRef" name="payment_ref">
                            </div>
                            <div class="form-group">
                                <label for="paymentAmount">Payment Amount</label>
                                <input type="number" class="form-control" id="paymentAmount" name="payment_amount"
                                    step="0.01" required readonly>
                            </div>
                            <input type="hidden" id="resId" name="res_id">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="submitPayment">Submit Payment</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Check-In and Check-Out -->
        <div class="modal fade" id="timeInputModal" tabindex="-1" role="dialog" aria-labelledby="timeInputModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="timeInputModalLabel">Enter Time</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="timeInputForm">
                            <div class="form-group">
                                <label for="inputTime">Time</label>
                                <input type="time" class="form-control" id="inputTime" name="input_time" required>
                            </div>
                            <input type="hidden" id="resIdTime" name="res_id">
                            <input type="hidden" id="timeAction" name="action">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="submitTime">Submit</button>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js" crossorigin="anonymous"></script>
    <!-- Popper.JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" crossorigin="anonymous">
    </script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" crossorigin="anonymous">
    </script>

    <script type="text/javascript">
    $(document).ready(function() {
        $("#sidebarCollapse").on("click", function() {
            $("#sidebar").toggleClass("active");
        });

        $(".no-show-btn").on("click", function() {
            var resId = $(this).data("res-id");
            $.post("adminPage.php", {
                res_id: resId,
                action: "no_show"
            }, function(data) {
                var response = JSON.parse(data);
                if (response.status === 'success') {
                    location.reload();
                } else {
                    alert("Failed to update the reservation status.");
                }
            });
        });

        $(".completed-btn").on("click", function() {
            var resId = $(this).data("res-id");
            $.post("getReservationAmount.php", {
                res_id: resId
            }, function(data) {
                var response = JSON.parse(data);
                if (response.status === 'success') {
                    $("#paymentAmount").val(response.res_total - response.payment_amount);
                    $("#resId").val(resId);
                    $("#completePaymentModal").modal('show');
                } else {
                    alert("Failed to retrieve reservation amount.");
                }
            });
        });

        $("#submitPayment").on("click", function() {
            var formData = $("#completePaymentForm").serialize();
            $.post("processPayment.php", formData, function(data) {
                var response = JSON.parse(data);
                if (response.status === 'success') {
                    location.reload();
                } else {
                    alert("Failed to process payment: " + response.message);
                }
            });
        });

        $("#paymentMethod").on("change", function() {
            var paymentMethod = $(this).val();
            if (paymentMethod === 'cash') {
                $("#paymentNumberGroup").hide();
                $("#paymentNumber").val('');
                $("#paymentRefGroup").hide();
                $("#paymentRef").val('');
            } else {
                $("#paymentNumberGroup").show();
                $("#paymentNumber").val('');
                $("#paymentRefGroup").show();
                $("#paymentRef").val('');
            }
        });

        // Initialize the modal with correct field visibility
        $("#paymentMethod").trigger("change");

        // Handle Check-In and Check-Out buttons
        $(".checkin-btn, .checkout-btn").on("click", function() {
            var resId = $(this).data("res-id");
            var action = $(this).hasClass("checkin-btn") ? "checkin" : "checkout";
            $("#resIdTime").val(resId);
            $("#timeAction").val(action);
            $("#timeInputModal").modal('show');
        });

        // Handle time input submission
        $("#submitTime").on("click", function() {
            var formData = $("#timeInputForm").serialize();
            $.post("adminPage.php", formData, function(data) {
                var response = JSON.parse(data);
                if (response.status === 'success') {
                    location.reload();
                } else {
                    alert("Failed to update the time.");
                }
            });
        });
    });
    </script>
</body>

</html>