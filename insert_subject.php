<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['subject_name']);

    // Validācija - vai priekšmets jau eksistē (case insensitive)
    $chk = $conn->prepare("SELECT COUNT(*) FROM subjects WHERE LOWER(subject_name) = LOWER(?)");
    $chk->execute([$name]);
    if ($chk->fetchColumn() > 0) {
        header("Location: teacher_dashboard.php?error=subject_exists");
        exit;
    }

    // Pārbauda vai ir skolotājs ielogojies
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        header("Location: login.php");
        exit;
    }

    $teacher_id = $_SESSION['user_id'];

    $ins = $conn->prepare("INSERT INTO subjects (subject_name, teacher_id) VALUES (?, ?)");
    $ins->execute([$name, $teacher_id]);

    header("Location: teacher_dashboard.php");
    exit;
}
