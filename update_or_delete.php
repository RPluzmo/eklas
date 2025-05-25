<?php
require_once 'config.php';
$pdo = $conn;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: teacher_dashboard.php");
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'delete_student') {
    $student_id = $_POST['student_id'] ?? null;
    if ($student_id) {
        $stmt = $pdo->prepare("DELETE FROM grades WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
    }
    header("Location: teacher_dashboard.php");
    exit();
}

if ($action === 'delete_grade') {
    $grade_id = $_POST['grade_id'] ?? null;
    if ($grade_id) {
        $stmt = $pdo->prepare("DELETE FROM grades WHERE id = ?");
        $stmt->execute([$grade_id]);
    }
    header("Location: teacher_dashboard.php");
    exit();
}

if ($action === 'update_grade') {
    $grade_id = $_POST['grade_id'] ?? null;
    $student_id = $_POST['student_id'] ?? null;
    $grade = $_POST['grade'] ?? null;
    $subject_id = $_POST['subject_id'] ?? null;

    if ($grade_id && $student_id && $grade !== null && $subject_id) {
        // Pārbaude vai šis skolēns jau nav saņēmis šo priekšmetu citā atzīmē
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE student_id = ? AND subject_id = ? AND id != ?");
        $stmt->execute([$student_id, $subject_id, $grade_id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            header("Location: teacher_dashboard.php?edit=$grade_id&error=duplicate#student-$student_id");
            exit();
        }

        $stmt = $pdo->prepare("UPDATE grades SET grade = ?, subject_id = ? WHERE id = ?");
        $stmt->execute([$grade, $subject_id, $grade_id]);
    }

    header("Location: teacher_dashboard.php#student-$student_id");
    exit();
}

if ($action === 'update_student') {
    $student_id = $_POST['student_id'] ?? null;
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    $valid_name_regex = '/^[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž]+$/u';

    if (!$student_id || !preg_match($valid_name_regex, $first_name) || !preg_match($valid_name_regex, $last_name)) {
        header("Location: teacher_dashboard.php?error=invalid_name_edit#student-$student_id");
        exit();
    }

    // Pārbaude vai nav cits skolēns ar tādu pašu vārdu un uzvārdu
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) AND id != ?");
    $stmt->execute([$first_name, $last_name, $student_id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        header("Location: teacher_dashboard.php?error=student_name_exists_edit#student-$student_id");
        exit();
    }

    $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ? WHERE id = ?");
    $stmt->execute([$first_name, $last_name, $student_id]);

    header("Location: teacher_dashboard.php#student-$student_id");
    exit();
}

// Ja darbība nav atpazīta
header("Location: teacher_dashboard.php");
exit();
?>
