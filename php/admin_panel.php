
<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "supervisor") {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "exam_system"); if ($conn->connect_error)
 { die("Connection failed: " . $conn->connect_error);

}

// Add new question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $text = $_POST['question_text'];
    $a = $_POST['option_a'];
    $b = $_POST['option_b'];
    $c = $_POST['option_c'];
    $d = $_POST['option_d'];
    $correct = $_POST['correct_option'];

    $stmt = $conn->prepare("INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_option)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $text, $a, $b, $c, $d, $correct);
    $stmt->execute();
    $stmt->close();
}

// Delete question
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM questions WHERE id = $id");
}

// Update exam settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $total = (int)$_POST['total_questions'];
    $minutes = (int)$_POST['time_limit'];

    $stmt = $conn->prepare("UPDATE exam_settings SET total_questions = ?, time_limit_minutes = ? WHERE id = 1");
    $stmt->bind_param("ii", $total, $minutes);
    $stmt->execute();
    $stmt->close();
}

// Fetch settings
$settingsResult = $conn->query("SELECT * FROM exam_settings WHERE id = 1");
$settings = $settingsResult->fetch_assoc();

// Fetch questions
$questions = $conn->query("SELECT * FROM questions ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supervisor Dashboard</title>
    <link rel="stylesheet" href="../css/styleAdmin.css">
</head>
<body>

    <div class="container">
        <h2 class="page-title">📋 Supervisor Dashboard</h2>

        <!-- Exam Settings -->
        <div class="card">
            <h3 class="section-title">⚙️ Exam Settings</h3>
            <form method="POST">
                <label for="total_questions">Number of Questions:</label>
                <input id="total_questions" type="number" name="total_questions" 
                       value="<?= htmlspecialchars($settings['total_questions']) ?>" required>

                <label for="time_limit">Time Limit (minutes):</label>
                <input id="time_limit" type="number" name="time_limit" 
                       value="<?= htmlspecialchars($settings['time_limit_minutes']) ?>" required>

                <button type="submit" name="update_settings">💾 Save Settings</button>
            </form>
        </div>

        <!-- Add Question -->
        <div class="card">
            <h3 class="section-title">➕ Add New Question</h3>
            <form method="POST">
                <label for="question_text">Question Text:</label>
                <textarea id="question_text" name="question_text" required></textarea>

                <label for="option_a">Option A:</label>
                <input id="option_a" type="text" name="option_a" required>

                <label for="option_b">Option B:</label>
                <input id="option_b" type="text" name="option_b" required>

                <label for="option_c">Option C:</label>
                <input id="option_c" type="text" name="option_c" required>

                <label for="option_d">Option D:</label>
                <input id="option_d" type="text" name="option_d" required>

                <label for="correct_option">Correct Answer:</label>
                <select id="correct_option" name="correct_option" required>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>

                <button type="submit" name="add_question">➕ Add Question</button>
            </form>
        </div>

        <!-- All Questions -->
        <div class="card">
            <h3 class="section-title">📚 All Questions</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Question</th>
                        <th>Correct Answer</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $questions->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['question_text']) ?></td>
                        <td><?= htmlspecialchars($row['correct_option']) ?></td>
                        <td>
                            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this question?')">
                                <button type="button" class="delete-btn">🗑️ Delete</button>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
