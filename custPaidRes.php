<?php
session_start(); // Start the session
require("./conx/conx_customer.php");

// Check if the user is logged in and if the user type is 2 (customer)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: logout.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$limit = 5; // Number of records per page

function formatDate($date)
{
    return date("F j, Y", strtotime($date));
}

function formatTime($time)
{
    return date("g:i A", strtotime($time));
}

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

    // Fetch pending reservations for the user
    $pendingPage = isset($_GET['pendingPage']) ? (int)$_GET['pendingPage'] : 1;
    $offset = ($pendingPage - 1) * $limit;

    $pendingQuery = $conx_customer->prepare("
        SELECT r.*, 
            ro.room_name, 
            COALESCE(SUM(p.payment_amount), 0) AS total_paid,
            p.payment_datetime,
            p.payment_amount,
            p.payment_method,
            p.payment_ref
        FROM res r
        LEFT JOIN room ro ON r.room_id = ro.room_id
        LEFT JOIN payments p ON r.res_id = p.res_id AND p.payment_status = 'successful'
        WHERE r.user_id = :user_id AND r.res_status = 'reserved' AND r.res_date >= CURDATE()
        GROUP BY r.res_id
        ORDER BY r.res_id 
        LIMIT :limit OFFSET :offset
    ");
    $pendingQuery->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $pendingQuery->bindParam(':limit', $limit, PDO::PARAM_INT);
    $pendingQuery->bindParam(':offset', $offset, PDO::PARAM_INT);
    $pendingQuery->execute();
    $pendingReservations = $pendingQuery->fetchAll(PDO::FETCH_ASSOC);

    // Fetch total number of pending reservations for pagination
    $pendingCountQuery = $conx_customer->prepare("
        SELECT COUNT(*) 
        FROM res 
        WHERE user_id = :user_id AND res_status = 'reserved' AND res_date >= CURDATE()
    ");
    $pendingCountQuery->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $pendingCountQuery->execute();
    $totalPending = $pendingCountQuery->fetchColumn();
    $totalPendingPages = ceil($totalPending / $limit);

    // Fetch completed reservations for the user
    $completedPage = isset($_GET['completedPage']) ? (int)$_GET['completedPage'] : 1;
    $offset = ($completedPage - 1) * $limit;

    $completedQuery = $conx_customer->prepare("
    SELECT r.*, 
        ro.room_name, 
        COALESCE(SUM(p.payment_amount), 0) AS total_paid,
        p.payment_datetime,
        p.payment_amount,
        p.payment_method,
        p.payment_ref,
        r.res_checkin,
        r.res_checkout
    FROM res r
    LEFT JOIN room ro ON r.room_id = ro.room_id
    LEFT JOIN payments p ON r.res_id = p.res_id AND p.payment_status = 'successful'
    WHERE r.user_id = :user_id AND r.res_status = 'completed'
    GROUP BY r.res_id
    ORDER BY r.res_id 
    LIMIT :limit OFFSET :offset
");
    $completedQuery->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $completedQuery->bindParam(':limit', $limit, PDO::PARAM_INT);
    $completedQuery->bindParam(':offset', $offset, PDO::PARAM_INT);
    $completedQuery->execute();
    $completedReservations = $completedQuery->fetchAll(PDO::FETCH_ASSOC);

    // Fetch total number of completed reservations for pagination
    $completedCountQuery = $conx_customer->prepare("
    SELECT COUNT(*) 
    FROM res 
    WHERE user_id = :user_id AND res_status = 'completed'
");
    $completedCountQuery->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $completedCountQuery->execute();
    $totalCompleted = $completedCountQuery->fetchColumn();
    $totalCompletedPages = ceil($totalCompleted / $limit);
} catch (PDOException $e) {
    // Handle database errors
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
    <title>Customer Page</title>

    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css"
        integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous" />
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js"
        integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1jC/AtVX5K0uZtmcHitFZ" crossorigin="anonymous">
    </script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js"
        integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous">
    </script>
    <link rel="stylesheet" href="style/page.css" />
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
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
                    <a href="updCustomer.php" class="btn btn-block btn-primary">Update Profile</a>
                </li>
                <li>
                    <a href="logout.php" class="btn btn-block btn-primary">Log-Out</a>
                </li>
            </ul>
        </nav>
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
            <div class="container">
                <h2>Pending Reservations</h2>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Room Name</th>
                            <th>Reservation Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Payment Method</th>
                            <th>Payment Ref</th>
                            <th>Status</th>
                            <th>Fee Paid</th>
                            <th>Balance</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingReservations as $reservation) : ?>
                        <tr>
                            <td><?= htmlspecialchars($reservation['res_id']) ?></td>
                            <td><?= htmlspecialchars($reservation['room_name']) ?></td>
                            <td><?= htmlspecialchars(formatDate($reservation['res_date'])) ?></td>
                            <td><?= htmlspecialchars(formatTime($reservation['res_start_time'])) ?></td>
                            <td><?= htmlspecialchars(formatTime($reservation['res_end_time'])) ?></td>
                            <td><?= htmlspecialchars($reservation['payment_method']) ?></td>
                            <td><?= htmlspecialchars($reservation['payment_ref']) ?></td>
                            <td><?= htmlspecialchars($reservation['res_status']) ?></td>
                            <td><?= htmlspecialchars($reservation['payment_amount']) ?></td>
                            <td><?= htmlspecialchars($reservation['res_total'] - $reservation['total_paid']) ?></td>
                            <td><button class="btn btn-danger"
                                    onclick="cancelReservation(<?= $reservation['res_id'] ?>)">Cancel</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($pendingPage > 1) : ?>
                        <li class="page-item">
                            <a class="page-link" href="?pendingPage=<?= $pendingPage - 1 ?>">Prev</a>
                        </li>
                        <?php endif; ?>
                        <?php if ($pendingPage < $totalPendingPages) : ?>
                        <li class="page-item">
                            <a class="page-link" href="?pendingPage=<?= $pendingPage + 1 ?>">Next</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <h2>Completed Reservations</h2>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Room Name</th>
                            <th>Reservation Date</th>
                            <th>Start Time</th>
                            <th>Check-In Time</th>
                            <th>End Time</th>
                            <th>Check-Out Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedReservations as $reservation) : ?>
                        <tr>
                            <td><?= htmlspecialchars($reservation['res_id']) ?></td>
                            <td><?= htmlspecialchars($reservation['room_name']) ?></td>
                            <td><?= htmlspecialchars(formatDate($reservation['res_date'])) ?></td>
                            <td><?= htmlspecialchars(formatTime($reservation['res_start_time'])) ?></td>
                            <td><?= htmlspecialchars(formatTime($reservation['res_checkin'])) ?></td>
                            <td><?= htmlspecialchars(formatTime($reservation['res_end_time'])) ?></td>
                            <td><?= htmlspecialchars(formatTime($reservation['res_checkout'])) ?></td>
                            <td><?= htmlspecialchars($reservation['res_status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($completedPage > 1) : ?>
                        <li class="page-item">
                            <a class="page-link" href="?completedPage=<?= $completedPage - 1 ?>">Prev</a>
                        </li>
                        <?php endif; ?>
                        <?php if ($completedPage < $totalCompletedPages) : ?>
                        <li class="page-item">
                            <a class="page-link" href="?completedPage=<?= $completedPage + 1 ?>">Next</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Cancellation -->
    <form id="cancelForm" method="POST" action="cancel_reservation.php" style="display: none;">
        <input type="hidden" name="res_id" id="cancelResId" value="">
    </form>

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

    <script type="text/javascript">
    $(document).ready(function() {
        $("#sidebarCollapse").on("click", function() {
            $("#sidebar").toggleClass("active");
        });
    });

    function cancelReservation(resId) {
        if (confirm("Are you sure you want to cancel this reservation? There is no refund.")) {
            // Set the reservation ID in the hidden form and submit it
            document.getElementById('cancelResId').value = resId;
            document.getElementById('cancelForm').submit();
        }
    }
    </script>
</body>

</html>