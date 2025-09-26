<?php
session_start();
include 'db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $sql = "SELECT * FROM admin WHERE Email_Admin=? AND Password=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $row['Email_Admin'];
        $_SESSION['admin_name'] = $row['First_name'] . " " . $row['Last_name'];
        // Redirect ไปหน้า dashboard
        header("Location: admin.php");
        exit();
    } else {
        $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง!";
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบแอดมิน - Dom Inn Hotel</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="../CSS/css/admin.css"> 

<body>
    <div class="login-box">
        <h2>เข้าสู่ระบบแอดมิน</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="อีเมล" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
        <a href="index.php">⬅ กลับหน้าหลัก</a>
    </div>
</body>

</html>