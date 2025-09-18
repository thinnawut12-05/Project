<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยินดีต้อนรับ</title>
    <link rel="stylesheet" href="./sss.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="welcome-wrapper">
        <div class="welcome-card">
            <h1>ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>คุณได้เข้าสู่ระบบเรียบร้อยแล้ว</p>
            <a href="logout.php" class="logout-button">ออกจากระบบ</a>
        </div>
    </div>
</body>
</html>
