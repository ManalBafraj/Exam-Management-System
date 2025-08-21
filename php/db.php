<!--db.php-->
 <?php
/**$servername = "sql206.infinityfree.com";
$username = "if0_39732748";
$password = "ExamPro1234567";
$dbname = "if0_39732748_XXX";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// فحص الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}**/

$host = "localhost";
$user = "root";
$pass = "";
$db   = "exam_system";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
  ?>