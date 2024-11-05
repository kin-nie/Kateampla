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

    // Insert new room with image
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
        $room_name = $_POST['room_name'];
        $room_price = $_POST['room_price'];
        $room_pic = file_get_contents($_FILES['room_pic']['tmp_name']);

        $stmt = $conx_admin->prepare("INSERT INTO room (room_name, room_price, room_pic, room_status, room_creation) VALUES (:room_name, :room_price, :room_pic, 'available', NOW())");
        $stmt->bindParam(':room_name', $room_name, PDO::PARAM_STR);
        $stmt->bindParam(':room_price', $room_price, PDO::PARAM_STR);
        $stmt->bindParam(':room_pic', $room_pic, PDO::PARAM_LOB);
        $stmt->execute();
    }

    // Update room with image
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_room'])) {
        $room_id = $_POST['room_id'];
        $room_name = $_POST['room_name'];
        $room_price = $_POST['room_price'];

        if (!empty($_FILES['update_room_pic']['tmp_name'])) {
            $room_pic = file_get_contents($_FILES['update_room_pic']['tmp_name']);
            $stmt = $conx_admin->prepare("UPDATE room SET room_name = :room_name, room_price = :room_price, room_pic = :room_pic WHERE room_id = :room_id");
            $stmt->bindParam(':room_pic', $room_pic, PDO::PARAM_LOB);
        } else {
            $stmt = $conx_admin->prepare("UPDATE room SET room_name = :room_name, room_price = :room_price WHERE room_id = :room_id");
        }

        $stmt->bindParam(':room_name', $room_name, PDO::PARAM_STR);
        $stmt->bindParam(':room_price', $room_price, PDO::PARAM_STR);
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();
    }


    // Archive room
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['archive_room'])) {
        $room_id = $_POST['room_id'];

        $stmt = $conx_admin->prepare("UPDATE room SET room_status = 'archived' WHERE room_id = :room_id");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Unarchive room
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unarchive_room'])) {
        $room_id = $_POST['room_id'];

        $stmt = $conx_admin->prepare("UPDATE room SET room_status = 'available' WHERE room_id = :room_id");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Get available rooms
    $stmt = $conx_admin->prepare("SELECT * FROM room WHERE room_status = 'available'");
    $stmt->execute();
    $available_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get archived rooms
    $stmt = $conx_admin->prepare("SELECT * FROM room WHERE room_status = 'archived'");
    $stmt->execute();
    $archived_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Admin Page</title>

    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous" />
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js" integrity="sha384-tzzSw1/Vo+0N5UhStP3bvwWPq+uvzCMfrN1fEFe+xBmv1C/AtVX5K0uZtmcHitFZ" crossorigin="anonymous">
    </script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js" integrity="sha384-6OIrr52G08NpOFSZdxxz1xdNSndlD4vdcf/q2myIUVO0VsqaGHJsB0RaBE01VTOY" crossorigin="anonymous">
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
                    <a href="#pageSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">Statistics</a>
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
                    <a href="#pageSubmenu3" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">Generate Reports</a>
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
                    <button class="btn btn-dark d-inline-block d-lg-none ml-auto" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <i class="fas fa-align-justify"></i>
                    </button>
                </div>
            </nav>

            <div class="container">
                <h2>Manage Rooms</h2>

                <!-- Add Room Form -->
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="room_name">Room Name</label>
                        <input type="text" class="form-control" id="room_name" name="room_name" required>
                    </div>
                    <div class="form-group">
                        <label for="room_price">Room Price</label>
                        <input type="number" step="0.01" class="form-control" id="room_price" name="room_price" required>
                    </div>
                    <div class="form-group">
                        <label for="room_pic">Room Picture</label>
                        <input type="file" class="form-control-file" id="room_pic" name="room_pic" accept="image/*" onchange="previewImage(event, 'add_room_preview')">
                        <img id="add_room_preview" src="#" alt="Image Preview" style="display:none; width:100px; height:auto;">
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_room">Add Room</button>
                </form>

                <hr>

                <!-- Available Rooms Table -->
                <h3>Available Rooms</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Room Name</th>
                            <th>Room Price</th>
                            <th>Room Picture</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($available_rooms as $room) : ?>
                            <tr>
                                <td><?= htmlspecialchars($room['room_name']) ?></td>
                                <td><?= htmlspecialchars($room['room_price']) ?></td>
                                <td>
                                    <?php if (!empty($room['room_pic'])) : ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($room['room_pic']) ?>" style="width: 100px; height: auto;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                                        <button type="submit" class="btn btn-warning" name="archive_room">Archive</button>
                                    </form>
                                    <button class="btn btn-info" onclick="openUpdateModal('<?= $room['room_id'] ?>', '<?= htmlspecialchars($room['room_name']) ?>', '<?= htmlspecialchars($room['room_price']) ?>')">Update</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <hr>

                <!-- Archived Rooms Table -->
                <h3>Archived Rooms</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Room Name</th>
                            <th>Room Price</th>
                            <th>Room Picture</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archived_rooms as $room) : ?>
                            <tr>
                                <td><?= htmlspecialchars($room['room_name']) ?></td>
                                <td><?= htmlspecialchars($room['room_price']) ?></td>
                                <td>
                                    <?php if (!empty($room['room_pic'])) : ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($room['room_pic']) ?>" style="width: 100px; height: auto;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                                        <button type="submit" class="btn btn-success" name="unarchive_room">Unarchive</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update Room Modal -->
    <div class="modal fade" id="updateRoomModal" tabindex="-1" role="dialog" aria-labelledby="updateRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateRoomModalLabel">Update Room</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="update_room_id" name="room_id">
                        <div class="form-group">
                            <label for="update_room_name">Room Name</label>
                            <input type="text" class="form-control" id="update_room_name" name="room_name" required>
                        </div>
                        <div class="form-group">
                            <label for="update_room_price">Room Price</label>
                            <input type="number" step="0.01" class="form-control" id="update_room_price" name="room_price" required>
                        </div>
                        <div class="form-group">
                            <label for="update_room_pic">Room Picture</label>
                            <input type="file" class="form-control-file" id="update_room_pic" name="update_room_pic" accept="image/*" onchange="previewImage(event, 'update_room_preview')">
                            <img id="update_room_preview" src="#" alt="Image Preview" style="display:none; width:100px; height:auto;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="update_room">Update Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery CDN - Slim version (without AJAX) -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" crossorigin="anonymous"></script>

    <!-- Popper.JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous">
    </script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous">
    </script>

    <script type="text/javascript">
        function openUpdateModal(room_id, room_name, room_price) {
            $('#update_room_id').val(room_id);
            $('#update_room_name').val(room_name);
            $('#update_room_price').val(room_price);
            $('#updateRoomModal').modal('show');
        }

        function previewImage(event, previewElementId) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById(previewElementId);
                output.src = reader.result;
                output.style.display = 'block';
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        $(document).ready(function() {
            $("#sidebarCollapse").on("click", function() {
                $("#sidebar").toggleClass("active");
            });
        });
    </script>
</body>

</html>