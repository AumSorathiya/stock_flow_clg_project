<?php
require 'db.php';

// Calculate overall aggregate percentages per student
$query = "
    SELECT 
        student_roll_no, 
        student_name,
        COUNT(*) as total_classes,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as attended_classes,
        ROUND((SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
    FROM attendance_details
    GROUP BY student_roll_no, student_name
";
$reportData = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Defaulter Analytics Status</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">Attendance Portal</a>
        <div class="navbar-nav">
            <a class="nav-link" href="index.php">Mark Attendance</a>
            <a class="nav-link" href="view.php">View History</a>
            <a class="nav-link active" href="report.php">Defaulter Report</a>
        </div>
    </div>
</nav>

<div class="container bg-white p-4 rounded shadow-sm">
    <h2 class="mb-2">Attendance Analytics Report</h2>
    <p class="text-muted mb-4">Flagging records below the mandatory 75% system compliance benchmark.</p>

    <table class="table table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>Roll Number</th>
                <th>Student Name</th>
                <th>Total Classes</th>
                <th>Attended</th>
                <th>Percentage</th>
                <th>System Evaluation Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reportData)): ?>
                <tr><td colspan="6" class="text-center text-muted">No attendance batches tracked yet.</td></tr>
            <?php else: ?>
                <?php foreach ($reportData as $row): 
                    $isDefaulter = $row['attendance_percentage'] < 75.00;
                ?>
                <tr class="<?= $isDefaulter ? 'table-danger' : 'table-success' ?>">
                    <td><?= htmlspecialchars($row['student_roll_no']) ?></td>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= $row['total_classes'] ?></td>
                    <td><?= $row['attended_classes'] ?></td>
                    <td><strong><?= $row['attendance_percentage'] ?>%</strong></td>
                    <td>
                        <?php if ($isDefaulter): ?>
                            <span class="badge bg-danger">Low Attendance (Defaulter)</span>
                        <?php else: ?>
                            <span class="badge bg-success">Eligible</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>