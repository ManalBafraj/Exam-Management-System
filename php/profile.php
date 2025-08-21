<!--profile.php -->
<?php
session_start();

require_once "db.php";   // حسب مسار الملف عندك

$error = "";

// التأكد من تسجيل الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// جلب بيانات الطالب
$studentSql = "SELECT name, email FROM users WHERE id = ? AND role = 'student'";
$stmt = $conn->prepare($studentSql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("❌ لا يوجد طالب بهذا الرقم.");
}

$student = $result->fetch_assoc();

// إجمالي عدد الأسئلة
$totalSql = "SELECT COUNT(*) as total FROM questions";
$totalResult = $conn->query($totalSql);
$totalQuestions = $totalResult->fetch_assoc()['total'];

// عدد الإجابات الصحيحة
$scoreSql = "
    SELECT COUNT(*) as correct_answers
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    JOIN exam_attempts ea ON a.attempt_id = ea.id
    WHERE ea.user_id = ? 
      AND ea.id = (SELECT MAX(id) FROM exam_attempts WHERE user_id = ?)
      AND a.selected_option = q.correct_option
";
$stmtScore = $conn->prepare($scoreSql);
$stmtScore->bind_param("ii", $student_id, $student_id);
$stmtScore->execute();
$scoreResult = $stmtScore->get_result();
$correctAnswers = $scoreResult->fetch_assoc()['correct_answers'];

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Student Profile</title>
    <link rel="stylesheet" href="../css/styleProfile.css">
</head>
<body>

    <div class="card">
        <h2 class="page-title">👤 Student Profile</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>

        <h3 class="section-title">📊 Exam Result</h3>
        <p><strong>Total Questions:</strong> <?= $totalQuestions ?></p>
        <p><strong>Correct Answers:</strong> <?= $correctAnswers ?></p>
        <p class="score"><strong>Score:</strong> <?= $correctAnswers ?> / <?= $totalQuestions ?></p>

        <div class="back-link">
            <a href="start_exam.php">↩ Back to Home</a>
        </div>
    </div>

</body>
</html>
