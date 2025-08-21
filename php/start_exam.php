<?php
session_start();

// عرض إشعار النجاح إذا وجد
if (isset($_SESSION['exam_success'])) {
  echo '<div class="alert success">'.htmlspecialchars($_SESSION['exam_success']).'</div>';
  unset($_SESSION['exam_success']);
}



// التحقق من تسجيل الدخول كطالب
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "student") {
    header("Location: index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "exam_system");
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جلب مدة الامتحان من الإعدادات
    $rs = $conn->query("SELECT time_limit_minutes FROM exam_settings WHERE id = 1 LIMIT 1");
    $row = $rs && $rs->num_rows ? $rs->fetch_assoc() : null;
    $time_limit_minutes = $row ? (int)$row['time_limit_minutes'] : 30;

    // إنشاء محاولة جديدة
    $sql = "INSERT INTO exam_attempts (user_id, score) VALUES ($user_id, 0)";
    if ($conn->query($sql)) {
        $attempt_id = $conn->insert_id;
        $_SESSION['attempt_id'] = $attempt_id;

        // حساب وقت النهاية بناءً على attempt_date
        $rq = $conn->query("SELECT attempt_date FROM exam_attempts WHERE id = $attempt_id LIMIT 1");
        $start_ts = ($rq && $rq->num_rows) 
            ? strtotime($rq->fetch_assoc()['attempt_date']) 
            : time();

        $_SESSION['exam_end_time'] = $start_ts + ($time_limit_minutes * 60);
        $_SESSION['time_limit_minutes'] = $time_limit_minutes;

        // إعادة التوجيه لصفحة الامتحان
        header("Location: exam.php");
        exit();
    } else {
        die("خطأ في إنشاء محاولة الاختبار: " . $conn->error);
    }
}

// بعد الاتصال بقاعدة البيانات
$cfg = $conn->query("SELECT time_limit_minutes FROM exam_settings WHERE id=1 LIMIT 1");
$time_limit_minutes = ($cfg && $cfg->num_rows) ? (int)$cfg->fetch_assoc()['time_limit_minutes'] : 30;
?>




<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Start Exam</title>
  <link rel="stylesheet" href="../css/styleStart.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<body>
<div class="start-card">
  <h2>Welcome to the Exam</h2>
  <p class="lead">This exam consists of multiple choice questions.<br> Time limit: <?= htmlspecialchars($time_limit_minutes) ?> minutes.</p>


    <div class="top-bar">
    <div class="profile-menu">
        <button>Profile ⮟</button>
        <div class="dropdown">
            <a href="profile.php">My Account</a>
            <form action="logout.php" method="POST" style="margin: 0;">
                <button type="submit" style="width: 100%; border: none; background: none; padding: 10px 14px; text-align: left;">Logout</button>
            </form>
        </div>
    </div>
</div>    
  <form method="POST">
    <button type="submit" class="btn-primary">Start Exam</button>
  </form>
</div>
    
    
    
</body>
</html>




