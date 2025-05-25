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
$sortBy = $_GET['sort_by'] ?? 'last_name';
$order = $_GET['order'] ?? 'asc';
$show = $_GET['show'] ?? '';

$allowedSort = ['first_name', 'last_name'];
$allowedOrder = ['asc', 'desc'];
$sortBy = in_array($sortBy, $allowedSort) ? $sortBy : 'last_name';
$order = in_array($order, $allowedOrder) ? $order : 'asc';

$students = $conn->query("SELECT id, first_name, last_name, profile_image FROM students ORDER BY $sortBy $order")->fetchAll(PDO::FETCH_ASSOC);
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
$sql .= " ORDER BY s.$sortBy $order, sub.subject_name";

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
    <meta charset="UTF-8" />
    <title>Skolotāja Panelis</title>
    <style>
        body { font-family: Arial; padding: 20px; background-color: #fff0f5; color: #333; }
        .btn { padding: 6px 12px; margin: 3px; cursor: pointer; background-color: #f7c4d5; border: 1px solid #e3a8bb; border-radius: 5px; text-decoration: none; }
        .btn:hover { background-color: #f9d3e0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e9b8c6; padding: 8px; text-align: center; background-color: #fff7f9; }
        .profile { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; vertical-align: middle; margin-right: 8px; }
        form.inline { display:inline; }
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            padding-top: 60px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
        }
        .modal-content {
            display: block;
            margin: auto;
            max-width: 80%;
            max-height: 80%;
            border-radius: 10px;
        }
        .modal.show {
            display: block;
        }
    </style>
</head>
<body>

<?php if (isset($_GET['error'])): ?>
    <p style="color:red; font-weight: bold;">
        <?php
            switch ($_GET['error']) {
                case 'duplicate': echo '⚠️ Šim skolēnam jau ir piešķirts šis priekšmets.'; break;
                case 'subject_exists': echo '⚠️ Šāds priekšmets jau eksistē.'; break;
                case 'student_email_exists': echo '⚠️ Šāds e-pasts jau tiek izmantots.'; break;
                case 'student_name_exists':
                case 'student_name_exists_edit': echo '⚠️ Skolēns ar šādu vārdu un uzvārdu jau eksistē.'; break;
                case 'invalid_name':
                case 'invalid_name_edit': echo '⚠️ Vārds un uzvārds drīkst saturēt tikai burtus.'; break;
                default: echo '⚠️ Nezināma kļūda.';
            }
        ?>
    </p>
<?php endif; ?>

<div style="text-align:right;">
    <a href="logout.php" class="btn">Izrakstīties</a>
</div>

<h2>Sveiki, <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?>!</h2>
<?php if (!empty($teacher['profile_image'])): ?>
    <a href="#modal-teacher" class="profile-link">
        <img src="<?= htmlspecialchars($teacher['profile_image']) ?>" class="profile" alt="Avatar" />
    </a>
    <div id="modal-teacher" class="modal">
        <img src="<?= htmlspecialchars($teacher['profile_image']) ?>" class="modal-content" alt="Palielināts skolotāja attēls" />
    </div>
<?php endif; ?>

<a class="btn" href="?show=teacherAvatarForm">Nomainīt avataru</a>
<a class="btn" href="remove_avatar.php">Noņemt avataru</a>

<?php if ($show === 'teacherAvatarForm'): ?>
<form method="post" action="upload_avatar.php" enctype="multipart/form-data">
    <input type="file" name="avatar" accept="image/*" required />
    <button class="btn">Augšupielādēt</button>
    <a class="btn" href="teacher_dashboard.php">Atcelt</a>
</form>
<?php endif; ?>

<hr>

<a class="btn" href="?show=addSubjectForm">Pievienot priekšmetu</a>
<a class="btn" href="?show=delSubjectForm">Dzēst priekšmetu</a>

<?php if ($show === 'addSubjectForm'): ?>
<form method="post" action="insert_subject.php">
    <input name="subject_name" placeholder="Priekšmeta nosaukums" required />
    <button class="btn">Pievienot</button>
    <a class="btn" href="teacher_dashboard.php">Atcelt</a>
</form>
<?php endif; ?>

<?php if ($show === 'delSubjectForm'): ?>
<form method="post" action="delete_subject.php">
    <select name="subject_id" required>
        <option value="">— izvēlies priekšmetu —</option>
        <?php foreach ($subjects as $sub): ?>
            <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn" onclick="return confirm('Tiešām dzēst priekšmetu ar visām atzīmēm?')">Dzēst</button>
    <a class="btn" href="teacher_dashboard.php">Atcelt</a>
</form>
<?php endif; ?>

<hr>

<a class="btn" href="?show=addStudentForm">Pievienot skolēnu</a>

<?php if ($show === 'addStudentForm'): ?>
<form method="post" action="insert_student.php">
    <input name="first_name" placeholder="Vārds" pattern="[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž]+" required />
    <input name="last_name" placeholder="Uzvārds" pattern="[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž]+" required />
    <input name="email" placeholder="E-pasts" required />
    <button class="btn">Pievienot</button>
    <a class="btn" href="teacher_dashboard.php">Atcelt</a>
</form>
<?php endif; ?>

<hr>

<form method="get">
    <label>Skolēns:
        <select name="student">
            <option value="">— visi —</option>
            <?php foreach ($students as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $studentFilter == $s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Priekšmets:
        <select name="subject">
            <option value="">— visi —</option>
            <?php foreach ($subjects as $sub): ?>
                <option value="<?= $sub['id'] ?>" <?= $subjectFilter == $sub['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sub['subject_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Kārtot pēc:
        <select name="sort_by">
            <option value="first_name" <?= $sortBy == 'first_name' ? 'selected' : '' ?>>Vārda</option>
            <option value="last_name" <?= $sortBy == 'last_name' ? 'selected' : '' ?>>Uzvārda</option>
        </select>
    </label>
    <label>Kārtības virziens:
        <select name="order">
            <option value="asc" <?= $order == 'asc' ? 'selected' : '' ?>>A-Z</option>
            <option value="desc" <?= $order == 'desc' ? 'selected' : '' ?>>Z-A</option>
        </select>
    </label>
    <button class="btn">Filtrēt</button>
</form>

<hr>

<?php foreach ($data as $sid => $student): ?>
    <h3 id="student-<?= $sid ?>">
    <?php if (!empty($student['profile_image'])): ?>
        <a href="#modal-student-<?= $sid ?>" class="profile-link">
            <img src="<?= htmlspecialchars($student['profile_image']) ?>" class="profile" alt="Skolēna profils" />
        </a>
        <div id="modal-student-<?= $sid ?>" class="modal">
            <img src="<?= htmlspecialchars($student['profile_image']) ?>" class="modal-content" alt="Palielināts attēls" />
        </div>
    <?php endif; ?>

    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>

    <a class="btn" href="?edit_student=<?= $sid ?>#student-<?= $sid ?>">Rediģēt skolnieku</a>

    <?php if ((int)$edit_student_id === (int)$sid): ?>
        <form method="post" action="update_or_delete.php" style="margin-top:10px;">
            <input type="hidden" name="action" value="update_student" />
            <input type="hidden" name="student_id" value="<?= $sid ?>" />
            <input type="text" name="first_name" value="<?= htmlspecialchars($student['first_name']) ?>" required pattern="[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž]+" />
            <input type="text" name="last_name" value="<?= htmlspecialchars($student['last_name']) ?>" required pattern="[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž]+" />
            <button class="btn" type="submit">Saglabāt</button>
            <a class="btn" href="teacher_dashboard.php#student-<?= $sid ?>">Atcelt</a>
        </form>
    <?php endif; ?>

    <form method="post" action="update_or_delete.php" class="inline" onsubmit="return confirm('Tiešām dzēst skolēnu?')">
        <input type="hidden" name="action" value="delete_student" />
        <input type="hidden" name="student_id" value="<?= $sid ?>" />
        <button class="btn" type="submit">Dzēst skolēnu</button>
    </form>
</h3>

    <table>
    <thead>
        <tr>
            <th>Priekšmets</th>
            <th>Atzīme</th>
            <th>Darbības</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($student['grades']) === 0): ?>
            <tr><td colspan="3">Nav piešķirtu priekšmetu</td></tr>
        <?php else: ?>
            <?php foreach ($student['grades'] as $g): ?>
                <tr>
                    <td>
                        <form method="post" action="update_or_delete.php" style="margin:0;">
                            <input type="hidden" name="action" value="update_grade" />
                            <input type="hidden" name="grade_id" value="<?= $g['grade_id'] ?>" />
                            <input type="hidden" name="student_id" value="<?= $sid ?>" />

                            <select name="subject_id" required>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>" <?= ($sub['id'] == $g['subject_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sub['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                    </td>
                    <td>
                            <input type="number" name="grade" min="1" max="10" value="<?= htmlspecialchars($g['grade']) ?>" required />
                    </td>
                    <td>
                            <button class="btn" type="submit">Saglabāt</button>
                        </form>

                        <form method="post" action="update_or_delete.php" class="inline" style="display:inline;" onsubmit="return confirm('Tiešām dzēst atzīmi?')">
                            <input type="hidden" name="action" value="delete_grade" />
                            <input type="hidden" name="grade_id" value="<?= $g['grade_id'] ?>" />
                            <button class="btn" type="submit">Dzēst atzīmi</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

    <!-- Forma priekšmetu piešķiršanai un atzīmes ievadei konkrētam skolēnam -->
    <form method="post" action="assign_subject.php" style="margin-top:10px;">
        <input type="hidden" name="student_id" value="<?= $sid ?>">
        <select name="subject_id" required>
            <option value="">— izvēlies priekšmetu —</option>
            <?php foreach ($subjects as $sub): ?>
                <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="grade" min="1" max="10" placeholder="Atzīme (1-10)" required />
        <button class="btn" type="submit">Piešķirt priekšmetu</button>
    </form>

    <hr>
<?php endforeach; ?>

<script>
document.querySelectorAll('.profile-link').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        const modal = document.querySelector(link.getAttribute('href'));
        if (modal) modal.classList.add('show');
    });
});

document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', e => {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    });
});
</script>

</body>
</html>
