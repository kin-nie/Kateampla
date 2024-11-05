<?php
session_start(); // Start the session
require("./conx/conx_customer.php");

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
                <li>
                    <a href="#pageSubmenu" data-toggle="collapse" aria-expanded="false"
                        class="dropdown-toggle">Reservations</a>
                    <ul class="collapse list-unstyled" id="pageSubmenu">
                        <li>
                            <a href="#">Paid Reservations</a>
                        </li>
                        <li>
                            <a href="#">Cancelled Reservations</a>
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
            console.log("Sidebar button clicked");
            $("#sidebar").toggleClass("active");
            console.log($("#sidebar")); // Check if the sidebar element is being targeted
        });
    });
    </script>
</body>

</html>