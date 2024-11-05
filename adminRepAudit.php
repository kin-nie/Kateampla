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

$sql = "SELECT audit_trail.*, users.user_fname, users.user_lname 
        FROM audit_trail 
        JOIN users ON audit_trail.user_id = users.user_id 
        WHERE 1 = 1";

if ($filterType && $filterValue) {
    switch ($filterType) {
        case 'date':
            $sql .= " AND DATE(audit_trail.audit_datetime) = :filter_value";
            break;
        case 'month':
            $sql .= " AND MONTH(audit_trail.audit_datetime) = :filter_value";
            break;
        case 'year':
            $sql .= " AND YEAR(audit_trail.audit_datetime) = :filter_value";
            break;
        case 'keyword':
            $sql .= " AND audit_trail.action LIKE :filter_value";
            $filterValue = "%$filterValue%";
            break;
        case 'user_id':
            $sql .= " AND audit_trail.user_id = :filter_value";
            break;
    }
}

$sql .= " ORDER BY audit_trail.audit_id DESC LIMIT :limit OFFSET :offset";

$stmt = $conx_admin->prepare($sql);

if ($filterType && $filterValue) {
    $stmt->bindValue(':filter_value', $filterValue, PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$audits = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSql = "SELECT COUNT(*) FROM audit_trail 
             JOIN users ON audit_trail.user_id = users.user_id 
             WHERE 1 = 1";

if ($filterType && $filterValue) {
    switch ($filterType) {
        case 'date':
            $totalSql .= " AND DATE(audit_trail.audit_datetime) = :filter_value";
            break;
        case 'month':
            $totalSql .= " AND MONTH(audit_trail.audit_datetime) = :filter_value";
            break;
        case 'year':
            $totalSql .= " AND YEAR(audit_trail.audit_datetime) = :filter_value";
            break;
        case 'keyword':
            $totalSql .= " AND audit_trail.action LIKE :filter_value";
            $filterValue = "%$filterValue%";
            break;
        case 'user_id':
            $totalSql .= " AND audit_trail.user_id = :filter_value";
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

$prevPage = max(1, $page - 1);
$nextPage = min($totalPages, $page + 1);

// Organize audits by year and month
$organizedAudits = [];
foreach ($audits as $audit) {
    $year = date('Y', strtotime($audit['audit_datetime']));
    $month = date('F', strtotime($audit['audit_datetime']));
    if (!isset($organizedAudits[$year])) {
        $organizedAudits[$year] = [];
    }
    if (!isset($organizedAudits[$year][$month])) {
        $organizedAudits[$year][$month] = [];
    }
    $organizedAudits[$year][$month][] = $audit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Audit Trail Report</title>
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
            } else if (filterType == "keyword") {
                filterValueInput.innerHTML =
                    '<input type="text" id="filter_value" name="filter_value" class="form-control">';
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
        <h2>Audit Trail Report</h2>
        <a href="adminPage.php" class="btn btn-secondary mb-3">Back</a>
        <a href="adminGenAudit.php?export_all=1" class="btn btn-primary mb-3">Export All to PDF</a>
        <a href="adminGenAudit.php?page=<?= $page ?>&filter_type=<?= htmlspecialchars($filterType) ?>&filter_value=<?= htmlspecialchars($filterValue) ?>" class="btn btn-primary mb-3">Export Current Page to PDF</a>
        <form method="GET" action="">
            <div class="form-group">
                <label for="filter_type">Choose Filter:</label>
                <select id="filter_type" name="filter_type" class="form-control" onchange="updateFilterInput()">
                    <option value="">Select Filter</option>
                    <option value="date" <?= $filterType == 'date' ? 'selected' : '' ?>>Date</option>
                    <option value="month" <?= $filterType == 'month' ? 'selected' : '' ?>>Month</option>
                    <option value="year" <?= $filterType == 'year' ? 'selected' : '' ?>>Year</option>
                    <option value="keyword" <?= $filterType == 'keyword' ? 'selected' : '' ?>>Keyword</option>
                    <option value="user_id" <?= $filterType == 'user_id' ? 'selected' : '' ?>>User ID</option>
                </select>
            </div>
            <div class="form-group" id="filter_value_input">
                <?php if ($filterType == 'date') : ?>
                    <input type="date" id="filter_value" name="filter_value" class="form-control" value="<?= htmlspecialchars($filterValue) ?>">
                <?php elseif ($filterType == 'month') : ?>
                    <input type="number" id="filter_value" name="filter_value" class="form-control" min="1" max="12" value="<?= htmlspecialchars($filterValue) ?>">
                <?php elseif ($filterType == 'year') : ?>
                    <input type="number" id="filter_value" name="filter_value" class="form-control" min="2000" max="2100" value="<?= htmlspecialchars($filterValue) ?>">
                <?php elseif ($filterType == 'keyword') : ?>
                    <input type="text" id="filter_value" name="filter_value" class="form-control" value="<?= htmlspecialchars($filterValue) ?>">
                <?php elseif ($filterType == 'user_id') : ?>
                    <input type="number" id="filter_value" name="filter_value" class="form-control" min="1" value="<?= htmlspecialchars($filterValue) ?>">
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Audit ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizedAudits as $year => $months) : ?>
                    <tr>
                        <th colspan="4" class="text-center bg-light"><?= $year ?></th>
                    </tr>
                    <?php foreach ($months as $month => $audits) : ?>
                        <tr>
                            <th colspan="4" class="text-center bg-secondary text-white"><?= $month ?></th>
                        </tr>
                        <?php foreach ($audits as $audit) : ?>
                            <tr>
                                <td><?= htmlspecialchars($audit['audit_id']) ?></td>
                                <td><?= htmlspecialchars($audit['user_fname'] . ' ' . $audit['user_lname']) ?></td>
                                <td><?= htmlspecialchars($audit['action']) ?></td>
                                <td><?= date('m-d-Y, g:i A', strtotime($audit['audit_datetime'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <nav>
            <ul class="pagination">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $prevPage ?>&filter_type=<?= htmlspecialchars($filterType) ?>&filter_value=<?= htmlspecialchars($filterValue) ?>">Previous</a>
                </li>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $nextPage ?>&filter_type=<?= htmlspecialchars($filterType) ?>&filter_value=<?= htmlspecialchars($filterValue) ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</body>

</html>