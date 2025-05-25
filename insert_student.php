<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);

    // Validācija: tikai burti vārda un uzvārda laukos
    $valid_name_regex = '/^[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž]+$/u';
    if (!preg_match($valid_name_regex, $first_name) || !preg_match($valid_name_regex, $last_name)) {
        header("Location: teacher_dashboard.php?error=invalid_name");
        exit;
    }

    // Pārbaude, vai e-pasts jau eksistē
    $checkEmail = $conn->prepare("SELECT COUNT(*) FROM students WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->fetchColumn() > 0) {
        header("Location: teacher_dashboard.php?error=student_email_exists");
        exit;
    }

    // Pārbaude, vai skolēns ar tādu pašu vārdu un uzvārdu jau eksistē
    $checkName = $conn->prepare("SELECT COUNT(*) FROM students WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?)");
    $checkName->execute([$first_name, $last_name]);
    if ($checkName->fetchColumn() > 0) {
        header("Location: teacher_dashboard.php?error=student_name_exists");
        exit;
    }

    // Datu ievietošana
    $stmt = $conn->prepare("INSERT INTO students (first_name, last_name, email) VALUES (?, ?, ?)");
    $stmt->execute([$first_name, $last_name, $email]);

    header("Location: teacher_dashboard.php");
    exit;
}
?>
