<?php
session_start();
require_once 'config.php';

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];

    // Pārbaudīt, vai lietotājs ir skolēns
    $stmt = $conn->prepare("SELECT * FROM students WHERE email = :email AND first_name = :first_name AND last_name = :last_name");
    $stmt->execute([':email' => $email, ':first_name' => $first_name, ':last_name' => $last_name]);
    $student = $stmt->fetch();

    if ($student) {
        $_SESSION['user_id'] = $student['id'];
        $_SESSION['role'] = 'student';
        header("Location: student_dashboard.php");
        exit;
    }

    // Pārbaudīt, vai lietotājs ir skolotājs
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE email = :email AND first_name = :first_name AND last_name = :last_name");
    $stmt->execute([':email' => $email, ':first_name' => $first_name, ':last_name' => $last_name]);
    $teacher = $stmt->fetch();

    if ($teacher) {
        $_SESSION['user_id'] = $teacher['id'];
        $_SESSION['role'] = 'teacher';
        header("Location: teacher_dashboard.php");
        exit;
    }

    $error_message = "Nepareizs lietotājs vai paraksts!";
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Pieslēgšanās</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            width: 320px;
            text-align: center;
        }

        .login-container h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        label {
            font-size: 14px;
            display: block;
            margin-top: 10px;
            text-align: left;
        }

        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            margin-top: 20px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: red;
            margin-top: 10px;
        }

        .note {
            background-color: #fff8b3;
            border: 1px solid #e6d200;
            border-radius: 8px;
            padding: 15px 20px;
            margin-top: 30px;
            width: 300px;
            font-size: 14px;
            box-shadow: 2px 2px 8px rgba(0,0,0,0.1);
        }

        .note h3 {
            margin-top: 0;
            font-size: 16px;
            text-align: center;
            color: #444;
        }

        .note ul {
            padding-left: 18px;
            margin: 10px 0 0 0;
        }

        .note li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Pieslēgšanās</h2>
        <form method="POST" action="login.php">
            <label for="first_name">Vārds:</label>
            <input type="text" name="first_name" required>

            <label for="last_name">Uzvārds:</label>
            <input type="text" name="last_name" required>

            <label for="email">E-pasts:</label>
            <input type="email" name="email" required>

            <button type="submit">Pieslēgties</button>
        </form>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
    </div>

    <div class="note">
        <h3>Piemēra lietotāji</h3>
        <strong>Skolotāji:</strong>
        <ul>
            <li>Skolotājs Viens – skolotajs1@vtdt.edu.lv</li>
            <li>Skolotājs Divi – skolotajs2@vtdt.edu.lv</li>
        </ul>
        <strong>Skolēni:</strong>
        <ul>
            <li>Anna Ozola – anna@vtdt.edu.lv</li>
            <li>Jānis Kalniņš – janis@vtdt.edu.lv</li>
        </ul>
    </div>
</body>
</html>
