<?php
session_start(); // Start the session
require("./conx/conx_customer.php");

// Check if the user is logged in and if the user type is 2 (customer)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: logout.php");
    exit();
}

$user_id = $_SESSION['user_id'];

function formatDate($date)
{
    return date("F j, Y", strtotime($date));
}

function formatTime($time)
{
    return date("g:i A", strtotime($time));
}

// Set up pagination for Cancelled Reservations
$cancelled_limit = 5;
$cancelled_page = isset($_GET['cancelled_page']) ? (int)$_GET['cancelled_page'] : 1;
$cancelled_offset = ($cancelled_page - 1) * $cancelled_limit;

// Set up pagination for No Shows
$no_show_limit = 5;
$no_show_page = isset($_GET['no_show_page']) ? (int)$_GET['no_show_page'] : 1;
$no_show_offset = ($no_show_page - 1) * $no_show_limit;

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

    // Fetch the cancelled reservations for the user
    $resQuery = $conx_customer->prepare("
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
        WHERE r.user_id = :user_id AND r.res_status = 'cancelled'
        GROUP BY r.res_id
        LIMIT :cancelled_limit OFFSET :cancelled_offset
    ");
    $resQuery->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $resQuery->bindParam(':cancelled_limit', $cancelled_limit, PDO::PARAM_INT);
    $resQuery->bindParam(':cancelled_offset', $cancelled_offset, PDO::PARAM_INT);
    $resQuery->execute();
    $reservations = $resQuery->fetchAll(PDO::FETCH_ASSOC);

    // Fetch the "No Shows" reservations for the user
    $noShowQuery = $conx_customer->prepare("
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
        WHERE r.user_id = :user_id AND r.res_status = 'absent'
        GROUP BY r.res_id
        LIMIT :no_show_limit OFFSET :no_show_offset
    ");
    $noShowQuery->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $noShowQuery->bindParam(':no_show_limit', $no_show_limit, PDO::PARAM_INT);
    $noShowQuery->bindParam(':no_show_offset', $no_show_offset, PDO::PARAM_INT);
    $noShowQuery->execute();
    $no_shows = $noShowQuery->fetchAll(PDO::FETCH_ASSOC);
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
                            <a href="custCanRes.php">Cancelled Reservations</a>
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
                <h2>Cancelled Reservations</h2>
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
                            <th>Paid At</th>
                            <th>Status</th>
                            <th>Fee Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation) : ?>
                        <tr>
                            <td><?= htmlspecialchars($reservation['res_id']) ?></td>
                            <td><?= htmlspecialchars($reservation['room_name']) ?></td>
                            <td><?= htmlspecialchars(formatDate($reservation['res_date'])) ?></td>
                            <td><?= htmlspecialchars(formatTime($reservation['res_start_time'])) ?></td>
                            <td><?= htmlspecialchars(formatTime($reservation['res_end_time'])) ?></td>
                            <td><?= htmlspecialchars($reservation['payment_method']) ?></td>
                            <td><?= htmlspecialchars($reservation['payment_ref']) ?></td>
                            <td><?= htmlspecialchars(formatDate($reservation['payment_datetime']) . ' ' . formatTime($reservation['payment_datetime'])) ?>
                            </td>
                            <td><?= htmlspecialchars($reservation['res_status']) ?></td>
                            <td><?= htmlspecialchars($reservation['payment_amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Pagination Controls for Cancelled Reservations -->
                <nav aria-label="Cancelled Reservations Pagination">
                    <ul class="pagination">
                        <?php if ($cancelled_page > 1) : ?>
                        <li class="page-item">
                            <a class="page-link" href="?cancelled_page=<?= $cancelled_page - 1 ?>">Prev</a>
                        </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?cancelled_page=<?= $cancelled_page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="container">
                <h2>No Shows</h2>
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
                            <th>Paid At</th>
                            <th>Status</th>
                            <th>Fee Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($no_shows as $no_show) : ?>
                        <tr>
                            <td><?= htmlspecialchars($no_show['res_id']) ?></td>
                            <td><?= htmlspecialchars($no_show['room_name']) ?></td>
                            <td><?= htmlspecialchars(formatDate($no_show['res_date'])) ?></td>
                            <td><?= htmlspecialchars(formatTime($no_show['res_start_time'])) ?></td>
                            <td><?= htmlspecialchars(formatTime($no_show['res_end_time'])) ?></td>
                            <td><?= htmlspecialchars($no_show['payment_method']) ?></td>
                            <td><?= htmlspecialchars($no_show['payment_ref']) ?></td>
                            <td><?= htmlspecialchars(formatDate($no_show['payment_datetime']) . ' ' . formatTime($no_show['payment_datetime'])) ?>
                            </td>
                            <td><?= htmlspecialchars($no_show['res_status']) ?></td>
                            <td><?= htmlspecialchars($no_show['payment_amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Pagination Controls for No Shows -->
                <nav aria-label="No Shows Pagination">
                    <ul class="pagination">
                        <?php if ($no_show_page > 1) : ?>
                        <li class="page-item">
                            <a class="page-link" href="?no_show_page=<?= $no_show_page - 1 ?>">Prev</a>
                        </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?no_show_page=<?= $no_show_page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
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

    <script type="text/javascript">
    $(document).ready(function() {
        $("#sidebarCollapse").on("click", function() {
            $("#sidebar").toggleClass("active");
        });
    });
    </script>
</body>

</html>