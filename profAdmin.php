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
    // Prepare and execute the query to get admin user details
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
        echo "User details not found.";
        exit();
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo "An error occurred. Please try again later.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unlock_user_id'])) {
    $unlock_user_id = (int)$_POST['unlock_user_id'];
    $default_password = password_hash("@Default1", PASSWORD_BCRYPT);

    try {
        // Update the user's password and status
        $stmt = $conx_admin->prepare("UPDATE users SET user_pass = :user_pass, user_status = 'activated' WHERE user_id = :user_id");
        $stmt->bindParam(':user_pass', $default_password, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $unlock_user_id, PDO::PARAM_INT);
        $stmt->execute();

        // Reset the fail counter
        resetFailCounter($conx_admin, $unlock_user_id);

        header("Location: profAdmin.php");
        exit();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo "An error occurred while updating the user. Please try again later.";
    }
}

function fetch_users($conx_admin, $status, $offset, $limit)
{
    $stmt = $conx_admin->prepare("SELECT * FROM users WHERE user_type = 2 AND user_status = :status LIMIT :offset, :limit");
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function count_users($conx_admin, $status)
{
    $stmt = $conx_admin->prepare("SELECT COUNT(*) FROM users WHERE user_type = 2 AND user_status = :status");
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function resetFailCounter($conx_admin, $user_id)
{
    $stmt = $conx_admin->prepare("UPDATE fail_logs SET fail_counter = 0 WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
}

$statuses = ['activated', 'pending', 'compromised'];
$limit = 5;
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Admin Page</title>

    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" />
    <!-- Font Awesome JS -->
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/solid.js"></script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.13/js/fontawesome.js"></script>
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
                        <li><a href="adminIncome.php">Downpayment Statistics</a></li>
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
                <div class=" container-fluid">
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
                <?php foreach ($statuses as $status) : ?>
                    <?php
                    $page = isset($_GET[$status . '_page']) ? (int)$_GET[$status . '_page'] : 1;
                    $offset = ($page - 1) * $limit;
                    $users = fetch_users($conx_admin, $status, $offset, $limit);
                    $total_users = count_users($conx_admin, $status);
                    $total_pages = ceil($total_users / $limit);
                    ?>

                    <h3><?php echo ucfirst($status); ?> Users</h3>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Profile Picture</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                                <th>Update Date</th>
                                <th>Status</th>
                                <?php if ($status != 'activated') : ?>
                                    <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user) : ?>
                                <tr>
                                    <td><img src="data:image/jpeg;base64,<?= base64_encode($user['user_pic']); ?>" class="profile-image" style="width: 50px; height: 50px;" alt="Profile Picture"></td>
                                    <td><?= $user['user_fname'] . ' ' . $user['user_lname']; ?></td>
                                    <td><?= $user['user_email']; ?></td>
                                    <td><?= $user['user_regdate']; ?></td>
                                    <td><?= $user['user_upddate']; ?></td>
                                    <td><?= $user['user_status']; ?></td>
                                    <?php if ($status != 'activated') : ?>
                                        <td>
                                            <form method="post" action="">
                                                <input type="hidden" name="unlock_user_id" value="<?= $user['user_id']; ?>">
                                                <button type="submit" class="btn btn-success">Unlock Account</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <nav>
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?= $status; ?>_page=<?= $i; ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- jQuery CDN - Slim version (without AJAX) -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" crossorigin="anonymous"></script>
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
        });
    </script>
</body>

</html>