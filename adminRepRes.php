<?php
session_start();
require("./conx/conx_admin.php");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: logout.php");
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filterValue = isset($_GET['filter_value']) ? $_GET['filter_value'] : '';
$sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';

$sql = "SELECT res.*, users.user_id, users.user_fname, users.user_lname, room.room_name,
               p1.payment_ref AS payment_ref1, p1.payment_amount AS payment_amount1, p1.payment_method AS payment_method1,
               p2.payment_ref AS payment_ref2, p2.payment_amount AS payment_amount2, p2.payment_method AS payment_method2
        FROM res
        JOIN users ON res.user_id = users.user_id
        JOIN room ON res.room_id = room.room_id
        LEFT JOIN payments p1 ON res.res_id = p1.res_id AND p1.payment_status = 'successful'
        LEFT JOIN payments p2 ON res.res_id = p2.res_id AND p2.payment_id > p1.payment_id AND p2.payment_status = 'successful'
        WHERE 1 = 1";

if ($filterType && $filterValue) {
    switch ($filterType) {
        case 'date':
            $sql .= " AND res.res_date = :filter_value";
            break;
        case 'month':
            $sql .= " AND MONTH(res.res_date) = :filter_value";
            break;
        case 'year':
            $sql .= " AND YEAR(res.res_date) = :filter_value";
            break;
        case 'user_id':
            $sql .= " AND res.user_id = :filter_value";
            break;
        case 'room_id':
            $sql .= " AND res.room_id = :filter_value";
            break;
    }
} else {
    $sql .= " ORDER BY YEAR(res.res_date) $sortOrder, MONTH(res.res_date) $sortOrder";
}

$sql .= " LIMIT :limit OFFSET :offset";
$stmt = $conx_admin->prepare($sql);
if ($filterType && $filterValue) {
    $stmt->bindValue(':filter_value', $filterValue, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSql = "SELECT COUNT(*) FROM res
             JOIN users ON res.user_id = users.user_id
             JOIN room ON res.room_id = room.room_id
             LEFT JOIN payments p1 ON res.res_id = p1.res_id AND p1.payment_status = 'successful'
             WHERE 1 = 1";

if ($filterType && $filterValue) {
    switch ($filterType) {
        case 'date':
            $totalSql .= " AND res.res_date = :filter_value";
            break;
        case 'month':
            $totalSql .= " AND MONTH(res.res_date) = :filter_value";
            break;
        case 'year':
            $totalSql .= " AND YEAR(res.res_date) = :filter_value";
            break;
        case 'user_id':
            $totalSql .= " AND res.user_id = :filter_value";
            break;
        case 'room_id':
            $totalSql .= " AND res.room_id = :filter_value";
            break;
    }
}
$totalStmt = $conx_admin->prepare($totalSql);
if ($filterType && $filterValue) {
    $totalStmt->bindValue(':filter_value', $filterValue, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRecords = $totalStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Organize reservations by year and month
$organizedReservations = [];
foreach ($reservations as $reservation) {
    $year = date('Y', strtotime($reservation['res_date']));
    $month = date('F', strtotime($reservation['res_date']));
    if (!isset($organizedReservations[$year])) {
        $organizedReservations[$year] = [];
    }
    if (!isset($organizedReservations[$year][$month])) {
        $organizedReservations[$year][$month] = [];
    }
    $organizedReservations[$year][$month][] = $reservation;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Reservations Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" crossorigin="anonymous" />
    <script>
        function updateFilterInput() {
            var filterType = document.getElementById("filter_type").value;
            var filterValueInput = document.getElementById("filter_value_input");

            if (filterType == "date") {
                filterValueInput.innerHTML =
                    '<input type="date" id="filter_value" name="filter_value" class="form-control">';
            } else if (filterType == "month") {
                filterValueInput.innerHTML =
                    '<input type="number" id="filter_value" name="filter_value" class="form-control" min="1" max="12">';
            } else if (filterType == "year") {
                filterValueInput.innerHTML =
                    '<input type="number" id="filter_value" name="filter_value" class="form-control" min="2000" max="2100">';
            } else if (filterType == "user_id") {
                filterValueInput.innerHTML =
                    '<input type="number" id="filter_value" name="filter_value" class="form-control">';
            } else if (filterType == "room_id") {
                filterValueInput.innerHTML =
                    '<input type="number" id="filter_value" name="filter_value" class="form-control">';
            } else {
                filterValueInput.innerHTML = '';
            }
        }
    </script>
</head>

<body>
    <div class="container mt-5">
        <h2>Reservations Report</h2>
        <a href="adminPage.php" class="btn btn-secondary mb-3">Back</a>
        <a href="adminGenRes.php?export_all=1&filter_type=<?= htmlspecialchars($filterType) ?>&filter_value=<?= htmlspecialchars($filterValue) ?>&sort_order=<?= htmlspecialchars($sortOrder) ?>" class="btn btn-primary mb-3">Export All to PDF</a>
        <a href="adminGenRes.php?page=<?= $page ?>&filter_type=<?= htmlspecialchars($filterType) ?>&filter_value=<?= htmlspecialchars($filterValue) ?>&sort_order=<?= htmlspecialchars($sortOrder) ?>" class="btn btn-primary mb-3">Export Current Page to PDF</a>

        <form method="GET" action="">
            <div class="form-group">
                <label for="filter_type">Choose Filter:</label>
                <select id="filter_type" name="filter_type" class="form-control" onchange="updateFilterInput()">
                    <option value="">Select Filter</option>
                    <option value="date">Date</option>
                    <option value="month">Month</option>
                    <option value="year">Year</option>
                    <option value="user_id">User ID</option>
                    <option value="room_id">Room ID</option>
                </select>
            </div>
            <div class="form-group" id="filter_value_input">
                <!-- Filter input will be inserted here based on filter type selection -->
            </div>
            <div class="form-group">
                <label for="sort_order">Sort Order:</label>
                <select id="sort_order" name="sort_order" class="form-control">
                    <option value="ASC">Ascending</option>
                    <option value="DESC">Descending</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>User ID</th>
                    <th>User</th>
                    <th>Room</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Check-in Time</th>
                    <th>Check-out Time</th>
                    <th>Total</th>
                    <th>Downpayment Amount</th>
                    <th>Downpayment Ref No.</th>
                    <th>Completion Payment Amount</th>
                    <th>Completion Payment Ref No.</th>
                    <th>Completion Payment Method</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizedReservations as $year => $months) : ?>
                    <tr>
                        <th colspan="16" class="text-center bg-light"><?= $year ?></th>
                    </tr>
                    <?php foreach ($months as $month => $reservations) : ?>
                        <tr>
                            <th colspan="16" class="text-center bg-secondary text-white"><?= $month ?></th>
                        </tr>
                        <?php foreach ($reservations as $reservation) : ?>
                            <tr>
                                <td><?= htmlspecialchars($reservation['res_id']) ?></td>
                                <td><?= htmlspecialchars($reservation['user_id']) ?></td>
                                <td><?= htmlspecialchars($reservation['user_fname'] . ' ' . $reservation['user_lname']) ?></td>
                                <td><?= htmlspecialchars($reservation['room_name']) ?></td>
                                <td><?= htmlspecialchars($reservation['res_date']) ?></td>
                                <td><?= htmlspecialchars(date('h:i A', strtotime($reservation['res_start_time']))) ?></td>
                                <td><?= htmlspecialchars(date('h:i A', strtotime($reservation['res_end_time']))) ?></td>
                                <td><?= htmlspecialchars(date('h:i A', strtotime($reservation['res_checkin']))) ?></td>
                                <td><?= htmlspecialchars(date('h:i A', strtotime($reservation['res_checkout']))) ?></td>
                                <td><?= htmlspecialchars($reservation['res_total']) ?></td>
                                <td><?= htmlspecialchars($reservation['payment_amount1']) ?></td>
                                <td><?= htmlspecialchars($reservation['payment_ref1']) ?></td>
                                <td><?= htmlspecialchars($reservation['payment_amount2']) ?></td>
                                <td><?= htmlspecialchars($reservation['payment_ref2']) ?></td>
                                <td><?= htmlspecialchars($reservation['payment_method2']) ?></td>
                                <td><?= htmlspecialchars($reservation['res_status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>

        </table>
        <nav>
            <ul class="pagination">
                <?php if ($page > 1) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&filter_type=<?= htmlspecialchars($filterType) ?>&filter_value=<?= htmlspecialchars($filterValue) ?>&sort_order=<?= htmlspecialchars($sortOrder) ?>">Prev</a>
                    </li>
                <?php endif; ?>
                <?php if ($page < $totalPages) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&filter_type=<?= htmlspecialchars($filterType) ?>&filter_value=<?= htmlspecialchars($filterValue) ?>&sort_order=<?= htmlspecialchars($sortOrder) ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</body>

</html>