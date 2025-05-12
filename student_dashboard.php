<?php
session_start();
require_once 'config.php';



// Nodrošini, ka lietotājs ir pieslēdzies kā students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // student_id saglabāšana no sesijas

// Iegūstam studentu no datubāzes
$sql = "SELECT * FROM students WHERE id = :user_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$student = $stmt->fetch();

// Pārbaudām, vai students tika atrasts
if (!$student) {
    die("Studenta dati netika atrasti.");
}

// Profila attēla atjaunināšana
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $image = $_FILES['profile_image'];
    if ($image['error'] === 0) {
        $targetDir = 'uploads/';
        $imageName = uniqid('profile_') . '_' . basename($image['name']);
        $targetFile = $targetDir . $imageName;

        if (move_uploaded_file($image['tmp_name'], $targetFile)) {
            $update = "UPDATE students SET profile_image = :profile_image WHERE id = :user_id";
            $update_stmt = $conn->prepare($update);
            $update_stmt->execute([
                ':profile_image' => $targetFile,
                ':user_id' => $user_id
            ]);
            // Pārstartējam lapu, lai atjauninātu attēlu
            header("Location: student_dashboard.php");
            exit;
        }
    }
}

// Iegūstam atzīmes un priekšmetus
$sql = "
    SELECT sub.subject_name, g.grade 
    FROM subjects sub
    LEFT JOIN grades g ON sub.id = g.subject_id
    WHERE g.student_id = :user_id
    ORDER BY sub.subject_name
";
$stmt = $conn->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .btn { padding: 10px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        .profile { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; }
        .hidden { display: none; }
    </style>
</head>
<body>

<!-- Izrakstīšanās poga -->
<div style="text-align:right;">
    <a href="logout.php" class="btn">Izrakstīties</a>
</div>

<!-- Sveiciens un profila attēls -->
<h1>Sveiki, <?php echo htmlspecialchars($student['first_name']) . ' ' . htmlspecialchars($student['last_name']); ?>!</h1>

<div>
    <h3>Jūsu profila attēls:</h3>
    <?php if ($student['profile_image']) { ?>
        <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile Image" class="profile">
    <?php } else { ?>
        <p>Profila attēls nav pievienots.</p>
    <?php } ?>
</div>

<!-- Forma attēla mainīšanai -->
<form method="POST" enctype="multipart/form-data">
    <label>Pievienot vai mainīt profila attēlu:</label><br>
    <input type="file" name="profile_image" accept="image/*" required><br>
    <button type="submit" class="btn">Augšupielādēt</button>
</form>

<!-- Tabula ar atzīmēm un priekšmetiem -->
<h2>Jūsu atzīmes un priekšmeti:</h2>
<table>
    <thead>
        <tr>
            <th>Priekšmets</th>
            <th>Atzīme</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($grades)): ?>
            <tr>
                <td colspan="2">Nav piešķirtu atzīmju.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($grades as $grade): ?>
                <tr>
                    <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
