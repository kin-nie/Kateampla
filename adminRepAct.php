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

$sql = "SELECT users.user_id, users.user_fname, users.user_lname, 
        SUM(res.res_total) as total_spent, 
        COUNT(res.res_id) as total_reservations, 
        MAX(res.res_total) as max_spent
        FROM users 
        JOIN res ON users.user_id = res.user_id 
        GROUP BY users.user_id 
        ORDER BY total_spent DESC 
        LIMIT :limit OFFSET :offset";
$stmt = $conx_admin->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$spendings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSql = "SELECT COUNT(DISTINCT user_id) FROM res";
$totalStmt = $conx_admin->prepare($totalSql);
$totalStmt->execute();
$totalRecords = $totalStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$prevPage = max(1, $page - 1);
$nextPage = min($totalPages, $page + 1);
?>

<!DOCTYPE html>
<html>

<head>
    <title>User Spending Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css"
        crossorigin="anonymous" />
</head>

<body>
    <div class="container mt-5">
        <h2>User Spending Report</h2>
        <a href="adminPage.php" class="btn btn-secondary mb-3">Back</a>
        <a href="adminGenAct.php?export_all=1" class="btn btn-primary mb-3">Export All to PDF</a>
        <a href="adminGenAct.php?page=<?= $page ?>" class="btn btn-primary mb-3">Export Current Page to PDF</a>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>User Name</th>
                    <th>Total Spent</th>
                    <th>Total Reservations</th>
                    <th>Max Spent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spendings as $spending) : ?>
                <tr>
                    <td><?= htmlspecialchars($spending['user_id']) ?></td>
                    <td><?= htmlspecialchars($spending['user_fname'] . ' ' . $spending['user_lname']) ?></td>
                    <td><?= htmlspecialchars($spending['total_spent']) ?></td>
                    <td><?= htmlspecialchars($spending['total_reservations']) ?></td>
                    <td><?= htmlspecialchars($spending['max_spent']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <nav>
            <ul class="pagination">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $prevPage ?>">Previous</a>
                </li>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $nextPage ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</body>

</html>