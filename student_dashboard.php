<?php
session_start();
require_once 'config.php';

// Nodrošini, ka lietotājs ir pieslēdzies kā students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Profila attēla noņemšana
if (isset($_POST['remove_profile_image'])) {
    // Iegūstam pašreizējo attēlu, lai izdzēstu failu
    $stmt = $conn->prepare("SELECT profile_image FROM students WHERE id = ?");
    $stmt->execute([$user_id]);
    $currentImage = $stmt->fetchColumn();

    if ($currentImage && file_exists($currentImage)) {
        unlink($currentImage);
    }

    // Dzēšam attēla ceļu no DB
    $stmt = $conn->prepare("UPDATE students SET profile_image = NULL WHERE id = ?");
    $stmt->execute([$user_id]);

    header("Location: student_dashboard.php");
    exit;
}

// Profila attēla atjaunināšana
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $image = $_FILES['profile_image'];
    if ($image['error'] === 0) {
        $targetDir = 'uploads/';
        $imageName = uniqid('profile_') . '_' . basename($image['name']);
        $targetFile = $targetDir . $imageName;

        if (move_uploaded_file($image['tmp_name'], $targetFile)) {
            // Ja iepriekš bija attēls, dzēšam veco failu
            $stmt = $conn->prepare("SELECT profile_image FROM students WHERE id = ?");
            $stmt->execute([$user_id]);
            $oldImage = $stmt->fetchColumn();
            if ($oldImage && file_exists($oldImage)) {
                unlink($oldImage);
            }

            $update = "UPDATE students SET profile_image = :profile_image WHERE id = :user_id";
            $update_stmt = $conn->prepare($update);
            $update_stmt->execute([
                ':profile_image' => $targetFile,
                ':user_id' => $user_id
            ]);
            header("Location: student_dashboard.php");
            exit;
        }
    }
}

// Iegūstam studentu
$sql = "SELECT * FROM students WHERE id = :user_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Studenta dati netika atrasti.");
}

// Iegūstam atzīmes un priekšmetus
$sql = "
    SELECT sub.subject_name, g.grade 
    FROM grades g
    INNER JOIN subjects sub ON g.subject_id = sub.id
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
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding: 20px;
        background: #fff6f9; /* ļoti gaiši rozīga */
        color: #8a3f72; /* maigāks violets */
        line-height: 1.6;
    }
    .btn {
        padding: 10px 18px;
        background: #d77fa1; /* rozīgi violets */
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        font-weight: 600;
        box-shadow: 0 4px 6px rgba(215, 127, 161, 0.4);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        margin-right: 10px;
    }
    .btn:hover {
        background: #a75275; /* tumšāks violets */
        box-shadow: 0 6px 10px rgba(167, 82, 117, 0.6);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
        background: #fff0f4; /* vēl maigāka rozīga */
        box-shadow: 0 0 12px rgba(215, 127, 161, 0.25);
        border-radius: 8px;
        overflow: hidden;
    }
    th, td {
        padding: 14px 16px;
        border-bottom: 1px solid #d79eb3; /* gaišāka roze */
        text-align: center;
        color: #7a3b64;
    }
    th {
        background-color: #f9d9e1; /* ļoti gaiša rozīga */
        color: #6a2e58;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    tr:last-child td {
        border-bottom: none;
    }
    .profile {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        cursor: pointer;
        border: 3px solid #d77fa1;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        box-shadow: 0 0 10px rgba(215, 127, 161, 0.6);
    }
    .profile:hover {
        transform: scale(1.07);
        box-shadow: 0 0 18px rgba(167, 82, 117, 0.8);
    }
    form {
        margin-top: 25px;
    }

    /* Modāla stils */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        padding-top: 60px;
        left: 0; top: 0;
        width: 100%; height: 100%;
        overflow: auto;
        background-color: rgba(167, 82, 117, 0.85); /* puscaurspīdīga tumša rozīga */
    }
    .modal-content {
        margin: auto;
        display: block;
        max-width: 80%;
        max-height: 80vh;
        border-radius: 12px;
        box-shadow: 0 0 20px #a75275;
    }
    .modal-close {
        position: absolute;
        top: 30px;
        right: 30px;
        color: #fff0f0;
        font-size: 34px;
        font-weight: bold;
        cursor: pointer;
        user-select: none;
        transition: color 0.3s ease;
        text-shadow: 0 0 6px #a75275;
    }
    .modal-close:hover {
        color: #ffcad4;
    }
</style>

</head>
<body>

<!-- Izrakstīšanās poga -->
<div style="text-align:right;">
    <a href="logout.php" class="btn">Izrakstīties</a>
</div>

<h1>Sveiki, <?php echo htmlspecialchars($student['first_name']) . ' ' . htmlspecialchars($student['last_name']); ?>!</h1>

<div>
    <?php if ($student['profile_image'] && file_exists($student['profile_image'])): ?>
        <img id="profileImg" src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile Image" class="profile">
    <?php else: ?>
        <p>Profila attēls nav pievienots.</p>
    <?php endif; ?>
</div>

<!-- Poga attēla noņemšanai -->
<?php if ($student['profile_image'] && file_exists($student['profile_image'])): ?>
    <form method="POST" onsubmit="return confirm('Vai tiešām vēlaties noņemt profila attēlu?');">
        <button type="submit" name="remove_profile_image" class="btn">Noņemt profila attēlu</button>
    </form>
<?php endif; ?>

<!-- Modāls attēlam -->
<div id="imgModal" class="modal">
    <span class="modal-close" id="modalClose">&times;</span>
    <img class="modal-content" id="modalImg">
</div>

<!-- Forma attēla mainīšanai -->
<form method="POST" enctype="multipart/form-data">
    <label><strong>Pievienot vai mainīt profila attēlu:</strong></label><br>
    <input type="file" name="profile_image" accept="image/*" required><br><br>
    <button type="submit" class="btn">Augšupielādēt</button>
</form>

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
                    <td><?php echo htmlspecialchars($grade['grade'] ?? '-'); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<script>
    // Modāla atvēršana un aizvēršana
    const modal = document.getElementById('imgModal');
    const img = document.getElementById('profileImg');
    const modalImg = document.getElementById('modalImg');
    const closeBtn = document.getElementById('modalClose');

    if (img) {
        img.onclick = function() {
            modal.style.display = "block";
            modalImg.src = this.src;
        }
    }

    closeBtn.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

</body>
</html>
