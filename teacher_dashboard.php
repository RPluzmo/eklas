<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, profile_image FROM teachers WHERE id = ?");
$stmt->execute([$user_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

$edit_id = $_GET['edit'] ?? null;
$edit_student_id = $_GET['edit_student'] ?? null;
$studentFilter = $_GET['student'] ?? '';
$subjectFilter = $_GET['subject'] ?? '';

$students = $conn->query("SELECT id, first_name, last_name, profile_image FROM students ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);

$sql = "
  SELECT 
    s.id AS student_id, s.first_name, s.last_name, s.profile_image,
    sub.id AS subject_id, sub.subject_name,
    g.id AS grade_id, g.grade
  FROM students s
  LEFT JOIN grades g ON s.id = g.student_id
  LEFT JOIN subjects sub ON g.subject_id = sub.id
  WHERE 1=1
";
$params = [];
if ($studentFilter) {
    $sql .= " AND s.id = ?";
    $params[] = $studentFilter;
}
if ($subjectFilter) {
    $sql .= " AND sub.id = ?";
    $params[] = $subjectFilter;
}
$sql .= " ORDER BY s.last_name, sub.subject_name";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach ($rows as $r) {
    $sid = $r['student_id'];
    if (!isset($data[$sid])) {
        $data[$sid] = [
            'first_name' => $r['first_name'],
            'last_name' => $r['last_name'],
            'profile_image' => $r['profile_image'],
            'grades' => []
        ];
    }
    if ($r['grade_id'] !== null) {
        $data[$sid]['grades'][] = $r;
    }
}
?>

<!DOCTYPE html>

<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Skolotāja Panelis</title>
    <style>
        body { font-family: Arial; padding:20px; }
        .btn { padding:5px 10px; margin:2px; cursor:pointer; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th,td { border:1px solid #ccc; padding:8px; text-align:center; }
        .hidden { display:none; }
        .profile { width:40px; height:40px; border-radius:50%; object-fit:cover; vertical-align:middle; margin-right:8px; }
    </style>
</head>
<body>

<!-- Izrakstīšanās poga -->
<div style="text-align:right;">
    <a href="logout.php" class="btn">Izrakstīties</a>
</div>

<h2>Sveiki, <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?>!</h2>
<?php if (!empty($teacher['profile_image'])): ?>
    <img src="<?= htmlspecialchars($teacher['profile_image']) ?>" class="profile">
<?php endif; ?>
<button class="btn" onclick="toggle('teacherAvatarForm')">Nomainīt avataru</button>
<button class="btn" onclick="location.href='remove_avatar.php'">Noņemt avataru</button>

<form id="teacherAvatarForm" class="hidden" method="post" action="upload_avatar.php" enctype="multipart/form-data">
    <input type="file" name="avatar" accept="image/*" required>
    <button class="btn">Augšupielādēt</button>
    <button class="btn" type="button" onclick="toggle('teacherAvatarForm')">Atcelt</button>
</form>

<hr>

<button class="btn" onclick="toggle('addSubjectForm')">Pievienot priekšmetu</button> <button class="btn" onclick="toggle('delSubjectForm')">Dzēst priekšmetu</button>

<form id="addSubjectForm" class="hidden" method="post" action="insert_subject.php">
    <input name="subject_name" placeholder="Priekšmeta nosaukums" required>
    <button class="btn">Pievienot</button>
    <button class="btn" type="button" onclick="toggle('addSubjectForm')">Atcelt</button>
</form>

<form id="delSubjectForm" class="hidden" method="post" action="delete_subject.php">
    <select name="subject_id" required>
        <option value="">— izvēlies priekšmetu —</option>
        <?php foreach ($subjects as $sub): ?>
            <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn" onclick="return confirm('Tiešām dzēst priekšmetu ar visām atzīmēm?')">Dzēst</button>
    <button class="btn" type="button" onclick="toggle('delSubjectForm')">Atcelt</button>
</form>

<hr>

<button class="btn" onclick="toggle('addStudentForm')">Pievienot skolēnu</button>

<form id="addStudentForm" class="hidden section" method="post" action="insert_student.php">
    <input name="first_name" placeholder="Vārds" required>
    <input name="last_name" placeholder="Uzvārds" required>
    <input name="email" placeholder="E-pasts" required>
    <!-- Noņemts profila attēls -->
    <button class="btn">Pievienot</button>
    <button class="btn" type="button" onclick="toggle('addStudentForm')">Atcelt</button>
</form>

<hr>

<form method="get">
    <label>Skolēns:
        <select name="student">
            <option value="">— visi —</option>
            <?php foreach ($students as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $studentFilter == $s['id'] ? 'selected' : '' ?>>
                    <?= $s['first_name'] . ' ' . $s['last_name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Priekšmets:
        <select name="subject">
            <option value="">— visi —</option>
            <?php foreach ($subjects as $sub): ?>
                <option value="<?= $sub['id'] ?>" <?= $subjectFilter == $sub['id'] ? 'selected' : '' ?>>
                    <?= $sub['subject_name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="btn">Filtrēt</button>
    <a class="btn" href="teacher_dashboard.php">Notīrīt</a>
</form>

<?php if (empty($data)): ?>

<p>❗ Nav datu.</p>

<?php else: ?>

<?php foreach ($data as $sid => $info): ?>
    <div id="student-<?= $sid ?>" style="margin-top:30px;">
        <!-- STUDENT HEADER -->
        <?php if ($edit_student_id == $sid): ?>
            <form method="post" action="update_or_delete.php">
                <input type="hidden" name="student_id" value="<?= $sid ?>">
                <?php if (!empty($info['profile_image'])): ?>
                    <img src="<?= htmlspecialchars($info['profile_image']) ?>" class="profile">
                <?php endif; ?>
                <input type="text" name="first_name" value="<?= htmlspecialchars($info['first_name']) ?>" required>
                <input type="text" name="last_name" value="<?= htmlspecialchars($info['last_name']) ?>" required>
                <button class="btn" name="action" value="update_student">💾 Saglabāt</button>
                <a class="btn" href="teacher_dashboard.php?student=<?= $studentFilter ?>&subject=<?= $subjectFilter ?>#student-<?= $sid ?>">Atcelt</a>
            </form>
        <?php else: ?>
            <form method="post" action="update_or_delete.php">
                <input type="hidden" name="student_id" value="<?= $sid ?>">
                <?php if (!empty($info['profile_image'])): ?>
                    <img src="<?= htmlspecialchars($info['profile_image']) ?>" class="profile">
                <?php endif; ?>
                <strong><?= $info['first_name'] . ' ' . $info['last_name'] ?></strong>
                <a class="btn" href="teacher_dashboard.php?edit_student=<?= $sid ?>#student-<?= $sid ?>">Rediģēt</a>
                <button class="btn" name="action" value="delete_student" onclick="return confirm('Dzēst skolēnu?')">Dzēst</button>
            </form>
        <?php endif; ?>

        <!-- GRADES TABLE -->
        <table>
<thead>
    <tr>
        <th>Priekšmets</th>
        <th>Atzīme</th>
        <th>Rediģēt</th>
        <th>Dzēst</th>
    </tr>
</thead>
<tbody>
<?php foreach ($info['grades'] as $g): ?>
    <?php if ($edit_id == $g['grade_id']): ?>
        <tr>
            <form method="post" action="update_or_delete.php">
                <td>
                    <select name="subject_id">
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $g['subject_id'] ? 'selected' : '' ?>>
                                <?= $sub['subject_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" name="grade" value="<?= $g['grade'] ?>" min="1" max="10"></td>
                <td colspan="2">
                    <input type="hidden" name="grade_id" value="<?= $g['grade_id'] ?>">
                    <input type="hidden" name="student_id" value="<?= $sid ?>">
                    <button class="btn" name="action" value="update_grade">💾 Saglabāt</button>
                    <a class="btn" href="teacher_dashboard.php?student=<?= $studentFilter ?>&subject=<?= $subjectFilter ?>#student-<?= $sid ?>">Atcelt</a>
                </td>
            </form>
        </tr>
    <?php else: ?>
        <tr>
            <td><?= $g['subject_name'] ?></td>
            <td><?= $g['grade'] ?></td>
            <td>
                <a class="btn" href="teacher_dashboard.php?edit=<?= $g['grade_id'] ?>#student-<?= $sid ?>">Rediģēt</a>
            </td>
            <td>
                <form method="post" action="update_or_delete.php" onsubmit="return confirm('Dzēst atzīmi?')">
                    <input type="hidden" name="grade_id" value="<?= $g['grade_id'] ?>">
                    <input type="hidden" name="student_id" value="<?= $sid ?>">
                    <button class="btn" name="action" value="delete_grade">Dzēst</button>
                </form>
            </td>
        </tr>
    <?php endif; ?>
<?php endforeach; ?>
</tbody>
</table>

        <!-- Assign new subject -->
        <form method="post" action="assign_subject.php" style="margin-top:8px;">
            <input type="hidden" name="student_id" value="<?= $sid ?>">
            <select name="subject_id" required>
                <option value="">— izvēlies priekšmetu —</option>
                <?php foreach ($subjects as $sub): ?>
                    <option value="<?= $sub['id'] ?>"><?= $sub['subject_name'] ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="grade" min="1" max="10" placeholder="atzīme" required>
            <button class="btn">Piešķirt priekšmetu</button>
        </form>
    </div>
<?php endforeach; ?>

<?php endif; ?>

<script>
    function toggle(id) {
        var e = document.getElementById(id);
        e.classList.toggle('hidden');
    }
</script>

</body>
</html>
