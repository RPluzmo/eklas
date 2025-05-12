<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

$dir = 'uploads/';
foreach (glob($dir . "teacher_$user_id.*") as $file) {
    if (file_exists($file)) unlink($file);
}
$upd = $conn->prepare("UPDATE teachers SET profile_image=NULL WHERE id=?");
$upd->execute([$user_id]);

header("Location: teacher_dashboard.php");
exit;
