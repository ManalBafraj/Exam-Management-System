<!-- exam.php -->
<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "student") {
    header("Location: index.php");
    exit();
}

$student_id = (int)$_SESSION['user_id'];

if (!isset($_SESSION['attempt_id'])) {
    die("خطأ: لم يتم العثور على attempt_id. ابدأ الامتحان من جديد.");
}
$attempt_id = (int)$_SESSION['attempt_id'];

$host = "localhost";
$user = "root";
$pass = "";
$db   = "exam_system";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ============ إعدادات الامتحان ============ */
$settingsSql    = "SELECT * FROM exam_settings WHERE id = 1";
$settingsResult = $conn->query($settingsSql);
if (!$settingsResult || $settingsResult->num_rows == 0) {
    die("خطأ: لم يتم العثور على إعدادات الامتحان.");
}
$settings                   = $settingsResult->fetch_assoc();
$totalQuestionsFromSettings = (int)$settings['total_questions'];
$time_limit_minutes         = (int)$settings['time_limit_minutes'];

/* ============ تحميل الأسئلة ============ */
if (!isset($_SESSION['exam_questions']) ||
    count($_SESSION['exam_questions']) != $totalQuestionsFromSettings) {

    $questionsSql    = "SELECT * FROM questions ORDER BY RAND() LIMIT $totalQuestionsFromSettings";
    $questionsResult = $conn->query($questionsSql);
    if (!$questionsResult || $questionsResult->num_rows == 0) {
        die("خطأ: لا توجد أسئلة في قاعدة البيانات.");
    }
    $questions = [];
    while ($row = $questionsResult->fetch_assoc()) {
        $questions[] = $row;
    }
    $_SESSION['exam_questions'] = $questions;
    $_SESSION['question_index'] = 0;
}

$questions      = $_SESSION['exam_questions'];
$totalQuestions = count($questions);
$questionIndex  = $_SESSION['question_index'];

/* ============ حفظ الإجابة أولاً (قبل التنقل) ============ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    $selected_option = $conn->real_escape_string($_POST['answer']);

    if (isset($questions[$questionIndex])) {
        $question_id = (int)$questions[$questionIndex]['id'];

        $checkSql    = "SELECT id FROM answers WHERE attempt_id = $attempt_id AND question_id = $question_id";
        $checkResult = $conn->query($checkSql);

        if ($checkResult && $checkResult->num_rows > 0) {
            $updateSql = "UPDATE answers 
                          SET selected_option = '$selected_option' 
                          WHERE attempt_id = $attempt_id AND question_id = $question_id";
            $conn->query($updateSql);
        } else {
            $insertSql = "INSERT INTO answers (attempt_id, question_id, selected_option) 
                          VALUES ($attempt_id, $question_id, '$selected_option')";
            $conn->query($insertSql);
        }
    }
}

/* ============ التنقل (بعد الحفظ) ============ */
if (isset($_POST['next']) && $questionIndex < $totalQuestions - 1) {
    $questionIndex++;
}
if (isset($_POST['prev']) && $questionIndex > 0) {
    $questionIndex--;
}
if (isset($_POST['goto'])) {
    $go = (int)$_POST['goto'];
    if ($go >= 0 && $go < $totalQuestions) {
        $questionIndex = $go;
    }
}
$_SESSION['question_index'] = $questionIndex;

/* ============ إنهاء الاختبار ============ */
if (isset($_POST['submit_exam'])) {
    unset($_SESSION['exam_end_time'], $_SESSION['question_index'], $_SESSION['exam_questions']);
    $_SESSION['exam_success'] = "تم استلام الاختبار بنجاح ✅";
    header("Location: start_exam.php");
    exit();
}

/* ============ خريطة الأسئلة المُجابة ============ */
$answeredMap = [];
$ids = array_map(fn($q)=> (int)$q['id'], $questions);
if (!empty($ids)) {
    $idsIn = implode(',', $ids);
    $ansRes = $conn->query("SELECT question_id FROM answers WHERE attempt_id = $attempt_id AND question_id IN ($idsIn)");
    if ($ansRes) {
        while($r = $ansRes->fetch_assoc()){
            $answeredMap[(int)$r['question_id']] = true;
        }
    }
}

/* ============ جلب السؤال والإجابة السابقة ============ */
$question = $questions[$questionIndex] ?? null;
$studentAnswer = '';
if ($question) {
    $question_id  = (int)$question['id'];
    $answerSql    = "SELECT selected_option FROM answers WHERE attempt_id = $attempt_id AND question_id = $question_id";
    $answerResult = $conn->query($answerSql);
    if ($answerResult && $answerResult->num_rows > 0) {
        $answerRow     = $answerResult->fetch_assoc();
        $studentAnswer = $answerRow['selected_option'];
    }
}

/* ============ المؤقت (الحل) ============ */
$elapsedSql = $conn->prepare("
    SELECT TIMESTAMPDIFF(SECOND, attempt_date, NOW()) AS elapsed
    FROM exam_attempts
    WHERE id = ? LIMIT 1
");
$elapsedSql->bind_param("i", $attempt_id);
$elapsedSql->execute();
$elapsedRes = $elapsedSql->get_result();

if ($elapsedRes && $elapsedRes->num_rows > 0) {
    $rowElapsed     = $elapsedRes->fetch_assoc();
    $elapsedSeconds = max(0, (int)$rowElapsed['elapsed']);
    $remainingTime  = max(0, ($time_limit_minutes * 60) - $elapsedSeconds);
} else {
    die("خطأ: محاولة الامتحان غير موجودة. ابدأ الاختبار من البداية.");
}

$remainingMinutes = floor($remainingTime / 60);
$remainingSeconds = $remainingTime % 60;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Page</title>
    <link rel="stylesheet" href="../css/styleExam.css">

    <style>
        body { font-family: Arial, sans-serif; margin: 30px; direction: rtl; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
        .profile-menu { position: relative; display: inline-block; }
        .dropdown { display: none; position: absolute; right: 0; background-color: #f1f1f1; min-width: 140px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); z-index: 1; }
        .dropdown a, .dropdown form button { padding: 10px 14px; display: block; text-decoration: none; color: #000; width: 100%; text-align: left; border: none; background: none; cursor: pointer; }
        .dropdown a:hover, .dropdown form button:hover { background-color: #ddd; }
        .profile-menu:hover .dropdown { display: block; }
        .btn-group button { margin-right: 10px; }
    </style>
</head>
<body>
<div class="container">

  <div class="topbar">
    <div class="timer-card">
      <span>Time Left:</span>
      <span id="timer">
        <?= str_pad($remainingMinutes, 2, "0", STR_PAD_LEFT) . ":" . str_pad($remainingSeconds, 2, "0", STR_PAD_LEFT) ?>
      </span>
    </div>

    <div class="profile-menu">
      <button>Profile⮟</button>
      <div class="dropdown">
        <a href="profile.php">My Account</a>
        <form action="logout.php" method="POST" style="margin:0">
          <button type="submit"> Logout</button>
        </form>
      </div>
    </div>
  </div>

  <?php if ($question): ?>
  <!-- نموذج واحد يحوي الشريط + السؤال + الأزرار -->
  <form method="POST" class="exam-form">

    <!-- شريط التقدّم + عدّاد "السؤال X من N" -->
    <div class="question-progress">
      <div class="steps">
        <?php for($i=0; $i<$totalQuestions; $i++):
              $qid = (int)$questions[$i]['id'];
              $classes = ['step'];
              if ($i === $questionIndex) $classes[] = 'step--active';
              elseif (isset($answeredMap[$qid])) $classes[] = 'step--answered';
              else $classes[] = 'step--upcoming';
        ?>
          <button class="<?= implode(' ', $classes) ?>" type="submit" name="goto" value="<?= $i ?>">
            <?= $i+1 ?>
          </button>
        <?php endfor; ?>
      </div>

      <div class="counter-chip">
        Question:<?= ($questionIndex + 1) ?> of <?= $totalQuestions ?>
      </div>
    </div>

    <!-- كرت السؤال -->
    <div class="card">
      <p style="font-weight:700; margin-top:0; margin-bottom:10px;">
        <span>Question: <?= ($questionIndex + 1) ?>:</span>
        <?= htmlspecialchars($question['question_text']) ?>
      </p>

      <div class="options">
        <label class="choice">
          <input type="radio" name="answer" value="A" <?= ($studentAnswer === 'A') ? 'checked' : '' ?>>
          <span class="letter">A</span>
          <span class="text"><?= htmlspecialchars($question['option_a']) ?></span>
        </label>

        <label class="choice">
          <input type="radio" name="answer" value="B" <?= ($studentAnswer === 'B') ? 'checked' : '' ?>>
          <span class="letter">B</span>
          <span class="text"><?= htmlspecialchars($question['option_b']) ?></span>
        </label>

        <label class="choice">
          <input type="radio" name="answer" value="C" <?= ($studentAnswer === 'C') ? 'checked' : '' ?>>
          <span class="letter">C</span>
          <span class="text"><?= htmlspecialchars($question['option_c']) ?></span>
        </label>

        <label class="choice">
          <input type="radio" name="answer" value="D" <?= ($studentAnswer === 'D') ? 'checked' : '' ?>>
          <span class="letter">D</span>
          <span class="text"><?= htmlspecialchars($question['option_d']) ?></span>
        </label>
      </div>

      <div class="btns">
        <div>
          <?php if ($questionIndex > 0): ?>
            <button class="btn" type="submit" name="prev">Previous</button>
          <?php endif; ?>
        </div>

        <div style="display:flex; gap:10px">
          <?php if ($questionIndex < $totalQuestions - 1): ?>
            <button class="btn btn-primary" type="submit" name="next">Next</button>
          <?php else: ?>
            <button class="btn btn-success" type="submit" name="submit_exam">Finish Exam</button>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </form>
  <?php else: ?>
    <div class="card">No question found.</div>
  <?php endif; ?>

</div>

<script>
let timeLeft = <?php echo (int)$remainingTime; ?>;

function updateTimer() {
  if (timeLeft <= 0) {
    document.getElementById("timer").textContent = "00:00";
    alert("Time is over!");
    document.querySelector(".exam-form")?.submit();
    return;
  }
  let m = Math.floor(timeLeft / 60);
  let s = timeLeft % 60;
  document.getElementById("timer").textContent =
    (m < 10 ? "0" : "") + m + ":" + (s < 10 ? "0" : "") + s;
  timeLeft--;
}
setInterval(updateTimer, 1000);
updateTimer();
</script>

</body>
</html>
