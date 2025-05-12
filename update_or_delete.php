<?php
require_once 'config.php';
$pdo = $conn; // nodrošinām PDO pieejamību

// 1. Skolēna dzēšana
if (isset($_POST['action']) && $_POST['action'] == 'delete_student') {
    $student_id = $_POST['student_id'];

    // Dzēst skolēna atzīmes vispirms (sakarā ar ārējo atslēgu)
    $stmt = $pdo->prepare("DELETE FROM grades WHERE student_id = ?");
    $stmt->execute([$student_id]);

    // Dzēst skolēnu
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$student_id]);

    header("Location: teacher_dashboard.php");
    exit();
}

// 2. Atzīmes dzēšana
if (isset($_POST['action']) && $_POST['action'] == 'delete_grade') {
    $grade_id = $_POST['grade_id'];

    $stmt = $pdo->prepare("DELETE FROM grades WHERE id = ?");
    $stmt->execute([$grade_id]);

    header("Location: teacher_dashboard.php");
    exit();
}

// 3. Atzīmes un priekšmeta atjaunināšana
if (isset($_POST['action']) && $_POST['action'] == 'update_grade') {
    $grade_id = $_POST['grade_id'];
    $student_id = $_POST['student_id'];
    $grade = $_POST['grade'];
    $subject_id = $_POST['subject_id'];

    $stmt = $pdo->prepare("UPDATE grades SET grade = ?, subject_id = ? WHERE id = ?");
    $stmt->execute([$grade, $subject_id, $grade_id]);

    header("Location: teacher_dashboard.php#student-" . $student_id);
    exit();
}

// 4. Skolēna vārda/uzvārda atjaunināšana
if (isset($_POST['action']) && $_POST['action'] == 'update_student') {
    $student_id = $_POST['student_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];

    $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ? WHERE id = ?");
    $stmt->execute([$first_name, $last_name, $student_id]);

    header("Location: teacher_dashboard.php#student-" . $student_id);
    exit();
}
?>
