<?php
session_start(); // Start the session
require("./conx/conx_admin.php");

// Check if the user is logged in and if the user type is 1 (admin)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: logout.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Prepare and execute the query to get user details
    $stmt = $conx_admin->prepare("SELECT * FROM users WHERE user_id = :user_id");
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

    // Query to get the total number of pending reservations
    $stmt = $conx_admin->prepare("SELECT COUNT(*) AS pending_reservations FROM res WHERE res_status = 'reserved'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pending_reservations = $result['pending_reservations'];

    // Query to get the total number of cancelled reservations
    $stmt = $conx_admin->prepare("SELECT COUNT(*) AS cancelled_reservations FROM res WHERE res_status = 'cancelled'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cancelled_reservations = $result['cancelled_reservations'];

    // Query to get the total number of completed reservations
    $stmt = $conx_admin->prepare("SELECT COUNT(*) AS completed_reservations FROM res WHERE res_status = 'completed'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $completed_reservations = $result['completed_reservations'];

    // Query to get the total number of no show reservations
    $stmt = $conx_admin->prepare("SELECT COUNT(*) AS no_show_reservations FROM res WHERE res_status = 'absent'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $no_show_reservations = $result['no_show_reservations'];


    // Query to get the total number of reservations
    $stmt = $conx_admin->prepare("SELECT COUNT(*) AS total_reservations FROM res");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_reservations = $result['total_reservations'];
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
    <title>Admin Dashboard</title>

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


        <!-- Page Content  -->
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
                <!-- Additional Reservation Stats -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                Pending Reservations
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Total</h5>
                                <p class="card-text display-4"><?= $pending_reservations; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                Cancelled Reservations
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Total</h5>
                                <p class="card-text display-4"><?= $cancelled_reservations; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                Completed Reservations
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Total</h5>
                                <p class="card-text display-4"><?= $completed_reservations; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                Total Reservations
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Total</h5>
                                <p class="card-text display-4"><?= $total_reservations; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                No Show Reservations
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Total</h5>
                                <p class="card-text display-4"><?= $no_show_reservations; ?></p>
                            </div>
                        </div>
                    </div>
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

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script type="text/javascript">
    $(document).ready(function() {
        $("#sidebarCollapse").on("click", function() {
            console.log("Sidebar button clicked");
            $("#sidebar").toggleClass("active");
            console.log($("#sidebar")); // Check if the sidebar element is being targeted
        });
    });
    </script>
</body>

</html>