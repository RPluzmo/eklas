<?php
session_start();
require_once 'config.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher'){
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
    $dir = 'uploads/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $newFile = $dir . 'teacher_' . $user_id . '.' . $ext;

    foreach (glob($dir . "teacher_$user_id.*") as $oldFile) {
        if (file_exists($oldFile)) unlink($oldFile);
    }

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $newFile)) {
        $upd = $conn->prepare("UPDATE teachers SET profile_image=? WHERE id=?");
        $upd->execute([$newFile, $user_id]);
    }
}
header("Location: teacher_dashboard.php");
exit;
