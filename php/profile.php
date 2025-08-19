<?php
session_start();

// หากยังไม่ได้ล็อกอิน ให้ redirect ไปยังหน้าล็อกอิน (หากมี)
// if (!isset($_SESSION['First_name'])) {
//     header('Location: login.php');
//     exit();
// }

$First_name = $_SESSION['First_name'] ?? 'ไม่ได้ระบุ';
$Last_name = $_SESSION['Last_name'] ?? 'ไม่ได้ระบุ';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>โปรไฟล์ของคุณ</title>
    <link rel="stylesheet" href="profile.css">
</head>
<body>
    <div class="profile-container">
        <h1>โปรไฟล์ของฉัน</h1>
        <div class="profile-info">
            <p><strong>ชื่อจริง:</strong> <?= htmlspecialchars($First_name) ?></p>
            <p><strong>นามสกุล:</strong> <?= htmlspecialchars($Last_name) ?></p>
            <!-- สามารถเพิ่มข้อมูลอื่นๆ ที่ดึงมาจาก Session หรือฐานข้อมูลได้ที่นี่ -->
        </div>
        <a href="home.php" class="back-link">กลับสู่หน้าหลัก</a>
    </div>
</body>
</html>