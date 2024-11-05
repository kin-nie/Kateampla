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

$sql = "SELECT payments.*, users.user_fname, users.user_lname 
        FROM payments 
        JOIN res ON payments.res_id = res.res_id 
        JOIN users ON res.user_id = users.user_id 
        WHERE 1 = 1";

if ($filterType && $filterValue) {
    switch ($filterType) {
        case 'date':
            $sql .= " AND DATE(payments.payment_datetime) = :filter_value";
            break;
        case 'month':
            $sql .= " AND MONTH(payments.payment_datetime) = :filter_value";
            break;
        case 'year':
            $sql .= " AND YEAR(payments.payment_datetime) = :filter_value";
            break;
        case 'status':
            $sql .= " AND payments.payment_status LIKE :filter_value";
            $filterValue = "%$filterValue%";
            break;
        case 'method':
            $sql .= " AND payments.payment_method = :filter_value";
            break;
        case 'user_id':
            $sql .= " AND users.user_id = :filter_value";
            break;
    }
}

$sql .= " LIMIT :limit OFFSET :offset";
$stmt = $conx_admin->prepare($sql);
if ($filterType && $filterValue) {
    $stmt->bindValue(':filter_value', $filterValue, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSql = "SELECT COUNT(*) FROM payments 
             JOIN res ON payments.res_id = res.res_id 
             JOIN users ON res.user_id = users.user_id 
             WHERE 1 = 1";

if ($filterType && $filterValue) {
    switch ($filterType) {
        case 'date':
            $totalSql .= " AND DATE(payments.payment_datetime) = :filter_value";
            break;
        case 'month':
            $totalSql .= " AND MONTH(payments.payment_datetime) = :filter_value";
            break;
        case 'year':
            $totalSql .= " AND YEAR(payments.payment_datetime) = :filter_value";
            break;
        case 'status':
            $totalSql .= " AND payments.payment_status LIKE :filter_value";
            $filterValue = "%$filterValue%";
            break;
        case 'method':
            $totalSql .= " AND payments.payment_method = :filter_value";
            break;
        case 'user_id':
            $totalSql .= " AND users.user_id = :filter_value";
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

// Organize payments by year and month
$organizedPayments = [];
foreach ($payments as $payment) {
    $year = date('Y', strtotime($payment['payment_datetime']));
    $month = date('F', strtotime($payment['payment_datetime']));
    if (!isset($organizedPayments[$year])) {
        $organizedPayments[$year] = [];
    }
    if (!isset($organizedPayments[$year][$month])) {
        $organizedPayments[$year][$month] = [];
    }
    $organizedPayments[$year][$month][] = $payment;
}

// Calculate previous and next page numbers
$prevPage = max(1, $page - 1);
$nextPage = min($totalPages, $page + 1);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Payments Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css"
        crossorigin="anonymous" />
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
        } else if (filterType == "status") {
            filterValueInput.innerHTML =
                '<input type="text" id="filter_value" name="filter_value" class="form-control">';
        } else if (filterType == "method") {
            filterValueInput.innerHTML =
                '<select id="filter_value" name="filter_value" class="form-control">' +
                '<option value="">Select Method</option>' +
                '<option value="gcash">Gcash</option>' +
                '<option value="cash">Cash</option>' +
                '<option value="paymaya">Paymaya</option>' +
                '</select>';
        } else if (filterType == "user_id") {
            filterValueInput.innerHTML =
                '<input type="number" id="filter_value" name="filter_value" class="form-control" min="1">';
        } else {
            filterValueInput.innerHTML = '';
        }
    }
    </script>
</head>

<body>
    <div class="container mt-5">
        <h2>Payments Report</h2>
        <a href="adminPage.php" class="btn btn-secondary mb-3">Back</a>
        <a href="adminGenInc.php?export_all=1" class="btn btn-primary mb-3">Export All to PDF</a>
        <a href="adminGenInc.php?page=<?= $page ?>&filter_type=<?= htmlspecialchars($filterType) ?>&filter_value=<?= htmlspecialchars($filterValue) ?>"
            class="btn btn-primary mb-3">Export Current Page to PDF</a>
        <form method="GET" action="">
            <div class="form-group">
                <label for="filter_type">Choose Filter:</label>
                <select id="filter_type" name="filter_type" class="form-control" onchange="updateFilterInput()">
                    <option value="">Select Filter</option>
                    <option value="date">Date</option>
                    <option value="month">Month</option>
                    <option value="year">Year</option>
                    <option value="status">Status</option>
                    <option value="method">Method</option>
                    <option value="user_id">User ID</option>
                </select>
            </div>
            <div class="form-group" id="filter_value_input">
                <!-- Filter input will be inserted here based on filter type selection -->
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>User</th>
                    <th>Reservation ID</th>
                    <th>Method</th>
                    <th>Payment Ref</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizedPayments as $year => $months) : ?>
                <tr>
                    <th colspan="8" class="text-center bg-light"><?= $year ?></th>
                </tr>
                <?php foreach ($months as $month => $payments) : ?>
                <tr>
                    <th colspan="8" class="text-center bg-secondary text-white"><?= $month ?></th>
                </tr>
                <?php foreach ($payments as $payment) : ?>
                <tr>
                    <td><?= htmlspecialchars($payment['payment_id']) ?></td>
                    <td><?= htmlspecialchars($payment['user_fname'] . ' ' . $payment['user_lname']) ?></td>
                    <td><?= htmlspecialchars($payment['res_id']) ?></td>
                    <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                    <td><?= htmlspecialchars($payment['payment_ref']) ?></td>
                    <td><?= htmlspecialchars($payment['payment_amount']) ?></td>
                    <td><?= date('m-d-Y, g:i A', strtotime($payment['payment_datetime'])) ?></td>
                    <td><?= htmlspecialchars($payment['payment_status']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <nav>
            <ul class="pagination">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link"
                        href="?page=<?= $prevPage ?>&filter_type=<?= htmlspecialchars($filterType) ?>&filter_value=<?= htmlspecialchars($filterValue) ?>">Previous</a>
                </li>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link"
                        href="?page=<?= $nextPage ?>&filter_type=<?= htmlspecialchars($filterType) ?>&filter_value=<?= htmlspecialchars($filterValue) ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</body>

</html>