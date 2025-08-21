<!-- index.php -->
<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// استدعاء ملف الاتصال
require_once "db.php";   // حسب مسار الملف عندك

$error = "";

// عند إرسال الفورم
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role     = trim($_POST['role']); // الدور المختار من المستخدم

    // البحث عن المستخدم حسب الإيميل
    $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // التحقق من كلمة المرور
        if (password_verify($password, $user['password'])) {

            // التحقق من الدور إذا تحب تمنع الدخول الخاطئ
            if ($user['role'] !== $role) {
                $error = "You selected the wrong role.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];

                // تحويل حسب الدور
                if ($user['role'] === "student") {
                    header("Location: start_exam.php");
                } else {
                    header("Location: admin_panel.php");
                }
                exit();
            }

        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../css/styleSign.css">
    
    <meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<body>
    <h2>Login</h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST" action="">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Login as:</label><br>
        <select name="role" required>
            <option value="student">Student</option>
            <option value="supervisor">supervisor</option>
        </select><br><br>

        <button type="submit">Login</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>
