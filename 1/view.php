<?php
require 'db.php';

// Setup pagination configuration variables
$limit = 5; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Setup search configurations
$search = $_GET['search'] ?? '';

if ($search !== '') {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM lectures WHERE subject_name LIKE ? OR faculty_name LIKE ?");
    $countStmt->execute(["%$search%", "%$search%"]);
    $totalRows = $countStmt->fetchColumn();

    $dataStmt = $pdo->prepare("SELECT * FROM lectures WHERE subject_name LIKE ? OR faculty_name LIKE ? ORDER BY lecture_date DESC LIMIT ? OFFSET ?");
    $dataStmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    $dataStmt->bindValue(2, "%$search%", PDO::PARAM_STR);
    $dataStmt->bindValue(3, $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(4, $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $lectures = $dataStmt->fetchAll();
} else {
    $totalRows = $pdo->query("SELECT COUNT(*) FROM lectures")->fetchColumn();
    $dataStmt = $pdo->prepare("SELECT * FROM lectures ORDER BY lecture_date DESC LIMIT ? OFFSET ?");
    $dataStmt->bindValue(1, $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(2, $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $lectures = $dataStmt->fetchAll();
}

$totalPages = ceil($totalRows / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lecture History Log</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">Attendance Portal</a>
        <div class="navbar-nav">
            <a class="nav-link" href="index.php">Mark Attendance</a>
            <a class="nav-link active" href="view.php">View History</a>
            <a class="nav-link" href="report.php">Defaulter Report</a>
        </div>
    </div>
</nav>

<div class="container bg-white p-4 rounded shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Past Attendance Entries</h2>
        <form class="d-flex" method="GET" action="view.php">
            <input class="form-control me-2" type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search subject or faculty...">
            <button class="btn btn-outline-dark" type="submit">Search</button>
        </form>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-secondary">
            <tr>
                <th>ID</th>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Date</th>
                <th>Faculty</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($lectures)): ?>
                <tr><td colspan="5" class="text-center text-muted">No records found.</td></tr>
            <?php else: ?>
                <?php foreach ($lectures as $lec): ?>
                <tr>
                    <td><?= $lec['lecture_id'] ?></td>
                    <td><?= htmlspecialchars($lec['subject_code']) ?></td>
                    <td><?= htmlspecialchars($lec['subject_name']) ?></td>
                    <td><?= htmlspecialchars($lec['lecture_date']) ?></td>
                    <td><?= htmlspecialchars($lec['faculty_name']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination Render Blocks -->
    <?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                    <a class="page-link" href="view.php?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
</body>
</html>