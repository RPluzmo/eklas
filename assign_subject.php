<?php
require_once 'config.php';

// Pārbauda, vai ir nosūtīts POST pieprasījums
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $grade      = $_POST['grade'];

    // Pārbauda, vai skolēnam jau nav piešķirts šis priekšmets
    $chk = $conn->prepare("SELECT COUNT(*) FROM grades WHERE student_id = ? AND subject_id = ?");
    $chk->execute([$student_id, $subject_id]);
    if ($chk->fetchColumn() > 0) {
        // Kļūdas paziņojums un atgriešanās
        header("Location: teacher_dashboard.php?error=duplicate#student-" . $student_id);
        exit;
    }

    // Ievieto jaunu ierakstu tabulā "grades"
    $query = "INSERT INTO grades (student_id, subject_id, grade) VALUES (?, ?, ?)";
    $stmt  = $conn->prepare($query);  
    $stmt->execute([$student_id, $subject_id, $grade]);

    // Novirza atpakaļ uz skolotāja paneli
    header("Location: teacher_dashboard.php?student=" . $student_id . "#student-" . $student_id);
    exit();
}
