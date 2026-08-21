<?php
require 'db.php';

$message = '';
$alertClass = '';

// Fetch students to populate the entry list
$stmt = $pdo->query("SELECT * FROM students ORDER BY roll_no ASC");
$students = $stmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_code = $_POST['subject_code'];
    $subject_name = $_POST['subject_name'];
    $lecture_date = $_POST['lecture_date'];
    $faculty_name = $_POST['faculty_name'];
    $attendance = $_POST['attendance'] ?? []; // Array mapping roll_no => status

    if (!empty($subject_code) && !empty($subject_name) && !empty($lecture_date) && !empty($faculty_name)) {
        try {
            // Start the transaction
            $pdo->beginTransaction();

            // 1. Insert into Master Table
            $stmtMaster = $pdo->prepare("INSERT INTO lectures (subject_code, subject_name, lecture_date, faculty_name) VALUES (?, ?, ?, ?)");
            $stmtMaster->execute([$subject_code, $subject_name, $lecture_date, $faculty_name]);
            
            // Capture the generated Master primary key ID
            $lecture_id = $pdo->lastInsertId();

            // 2. Insert into Detail Table
            $stmtDetail = $pdo->prepare("INSERT INTO attendance_details (lecture_id, student_roll_no, student_name, status) VALUES (?, ?, ?, ?)");
            
            foreach ($students as $student) {
                $roll = $student['roll_no'];
                // Default to 'Absent' if checkbox isn't explicitly marked
                $status = isset($attendance[$roll]) ? 'Present' : 'Absent';
                
                $stmtDetail->execute([$lecture_id, $roll, $student['name'], $status]);
            }

            // Commit transaction if all records pass safely
            $pdo->commit();
            $message = "Attendance sheet saved successfully!";
            $alertClass = "alert-success";
        } catch (Exception $e) {
            // Rollback changes entirely if an entry fails
            $pdo->rollBack();
            $message = "Transaction failed: " . $e->getMessage();
            $alertClass = "alert-danger";
        }
    } else {
        $message = "Please fill out all master information fields.";
        $alertClass = "alert-warning";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Attendance Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">Attendance Portal</a>
        <div class="navbar-nav">
            <a class="nav-link active" href="index.php">Mark Attendance</a>
            <a class="nav-link" href="view.php">View History</a>
            <a class="nav-link" href="report.php">Defaulter Report</a>
        </div>
    </div>
</nav>

<div class="container bg-white p-4 rounded shadow-sm">
    <h2 class="mb-4">New Attendance Session</h2>
    
    <?php if ($message): ?>
        <div class="alert <?= $alertClass ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form action="index.php" method="POST">
        <!-- Master Form Fields -->
        <h5 class="text-secondary border-bottom pb-2">Lecture Configuration (Master Record)</h5>
        <div class="row mb-4">
            <div class="col-md-3">
                <label class="form-label">Subject Code</label>
                <input type="text" name="subject_code" class="form-control" placeholder="e.g., IMCA16312" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Subject Name</label>
                <input type="text" name="subject_name" class="form-control" placeholder="e.g., Software Project - 2" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Lecture Date</label>
                <input type="date" name="lecture_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Faculty Name</label>
                <input type="text" name="faculty_name" class="form-control" placeholder="e.g., Prof. Sharma" required>
            </div>
        </div>

        <!-- Detail Form Fields -->
        <h5 class="text-secondary border-bottom pb-2">Student Roster (Detail Records)</h5>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th width="15%">Roll No</th>
                        <th width="65%">Student Name</th>
                        <th width="20%" class="text-center">Status (Checked = Present)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['roll_no']) ?></td>
                        <td><?= htmlspecialchars($student['name']) ?></td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" name="attendance[<?= $student['roll_no'] ?>]" value="Present" checked>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-primary mt-3 px-5">Submit Attendance Batch</button>
    </form>
</div>
</body>
</html>