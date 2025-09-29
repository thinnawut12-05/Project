<?php
session_start();
include 'db.php'; // ตรวจสอบว่า db.php เชื่อมต่อฐานข้อมูลเรียบร้อย

$error = '';
// ไม่ควรตั้งค่า $_SESSION['First_name'] หรือ $_SESSION['Last_name'] เป็นค่าว่างที่นี่
// เพราะมันจะล้างค่าเก่าทิ้งหากมีการเรียกใช้หน้านี้ซ้ำ
// $_SESSION['First_name'] = "";
// $_SESSION['Last_name'] = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST['Email_Admin'] ?? '');
  $password = $_POST['Password'] ?? '';

  if ($email === '' || $password === '') {
    $error = "กรุณากรอกอีเมลและรหัสผ่านให้ครบถ้วน";
  } else {
    $stmt = $conn->prepare("SELECT Email_Admin, Password, First_name, Last_name FROM admin WHERE Email_Admin = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        // bind ทั้งหมดจาก statement เดียว
        $stmt->bind_result($emailDB, $hashedPassword, $first, $last);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            // เข้าสู่ระบบสำเร็จ
            $_SESSION['admin_logged_in'] = true; // เพิ่มบรรทัดนี้เพื่อตั้งค่าสถานะการเข้าสู่ระบบ
            $_SESSION['Email_Admin'] = $emailDB;
            $_SESSION['First_name'] = $first;
            $_SESSION['Last_name'] = $last;
            $_SESSION['admin_name'] = $first . " " . $last; // เพิ่มบรรทัดนี้เพื่อตั้งค่าชื่อเต็ม

            header("Location: admin-home.php");
            exit;
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบอีเมลในระบบ";
    }

    $stmt->close();
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
</head>

<body>
    <div class="login-box">
        <h2>เข้าสู่ระบบแอดมิน</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="Email_Admin" name="Email_Admin" placeholder="อีเมล" required>
            <input type="Password" name="Password" placeholder="รหัสผ่าน" required>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
        <a href="index.php">⬅ กลับหน้าหลัก</a>
    </div>
</body>

</html>