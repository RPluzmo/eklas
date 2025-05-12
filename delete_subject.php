<?php
require_once 'config.php';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['subject_id'])){
    $id = $_POST['subject_id'];
    // Dzēš priekšmetu un visas atzīmes (ON DELETE CASCADE)
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: teacher_dashboard.php");
    exit;
}
