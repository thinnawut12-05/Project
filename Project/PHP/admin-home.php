<?php
session_start(); // ต้องเรียกใช้ session_start() ที่ด้านบนสุดของทุกหน้าที่จะใช้ session

// ตรวจสอบว่ามีค่า First_name และ Last_name ใน Session หรือไม่
$admin_first_name = $_SESSION['First_name'] ?? 'ผู้ดูแลระบบ'; // กำหนดค่าเริ่มต้นหากไม่มีใน Session
$admin_last_name = $_SESSION['Last_name'] ?? '';

// รวมชื่อ-สกุล
$admin_name = "คุณ" . $admin_first_name . " " . $admin_last_name;

// หากต้องการให้แสดงแค่ชื่อต้น (เช่น "คุณสมชาย") สามารถใช้โค้ดนี้แทน:
// $admin_name = "คุณ" . $admin_first_name;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <title>หน้าแอดมิน</title>
    <link rel="stylesheet" href="../CSS/css/admin-home.css">
</head>

<body>
    <div class="header">
        <h1>หน้าผู้ดูแลระบบ</h1>
        <!-- แสดงชื่อผู้ดูแลระบบที่แก้ไขแล้ว -->
        <p class="welcome-message">ยินดีต้อนรับ, <?php echo htmlspecialchars($admin_name); ?>!</p>
    </div>

    <div class="container">
        <div class="admin-menu">
            <a href="admin.php" class="menu-item">
                <div class="icon">💰</div>
                <span>ตรวจสอบจำนวนเงิน</span>
            </a>
            <a href="notify_officer.php" class="menu-item">
                <div class="icon">📢</div>
                <span>แจ้งเจ้าหน้าที่</span>
            </a>
            <a href="add_officer.php" class="menu-item">
                <div class="icon">➕</div>
                <span>เพิ่มเจ้าหน้าที่</span>
            </a>
            <!-- คุณสามารถเพิ่มเมนูอื่นๆ ได้ที่นี่ -->
        </div>


        <div class="footer">
        </div>
</body>

</html>