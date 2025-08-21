<!--register.php -->
<?php include "register_logic.php"; ?>
<!DOCTYPE html>
<html lang="er">
<head>
    <meta charset="UTF-8">
    <title>Sign up </title>
    <link rel="stylesheet" href="../css/styleSign.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<body>
    <h2>Sign up</h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST" action="">
        <label> Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label> Email</label><br>
        <input type="email" name="email" required><br><br>

        <label> password</label><br>
        <input type="password" name="password" required><br><br>

        <label>Role:</label><br>
<select name="role" required>
  <option value="student">Student</option>
  <option value="supervisor">Supervisor</option>
</select><br><br>


        <button type="submit">Submit</button>
    </form>

    <p>Already have an account?<a href="index.php"> Sign in</a></p>
</body>
</html>
