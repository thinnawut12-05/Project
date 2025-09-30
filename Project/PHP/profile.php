<?php   
session_start();
include 'db.php';

if (!isset($_SESSION['email'])) {
    die("ยังไม่ได้เข้าสู่ระบบ");
}

$Email_member = $_SESSION['email'];

// ✅ ดึงข้อมูลผู้ใช้ + เบอร์โทร + จำนวนห้องทั้งหมดที่เคยจอง
$sql = "SELECT m.First_name, m.Last_name, m.Email_member, m.Phone_number,
               COALESCE(SUM(r.Number_of_rooms),0) AS Total_rooms
        FROM member m
        LEFT JOIN reservation r ON m.Email_member = r.Email_member
        WHERE m.Email_member = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $Email_member);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $First_name = $row['First_name'];
    $Last_name = $row['Last_name'];
    $Email_member = $row['Email_member'];
    $Phone_number = $row['Phone_number'];
    $Total_rooms = $row['Total_rooms'];
} else {
    die("ไม่พบข้อมูลผู้ใช้");
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>โปรไฟล์ของคุณ</title>
<link rel="icon" type="image/png" href="../src/images/logo.png" />
<link rel="stylesheet" href="../CSS/css/profile.css">
<style>
/* ปุ่มและการจัดวางเพิ่มเติม */
.navigation-links {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
}
.logout-link {
    display: inline-block;
    margin-top: 25px;
    padding: 10px 20px;
    background-color: #dc3545;
    color: #ffffff;
    text-decoration: none;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}
.logout-link:hover {
    background-color: #c82333;
}
/* เพิ่มสไตล์สำหรับ .label-fixed-width ใน <style> block หรือในไฟล์ CSS ของคุณ */
.label-fixed-width {
    color: #555;
    width: 100px;
    display: inline-block;
}
</style>
</head>
<body>
<div class="profile-container">
    <h1>โปรไฟล์ของฉัน</h1>
    <div class="profile-info">
        <p><strong class="label-fixed-width">ชื่อจริง:</strong> <?= htmlspecialchars($First_name) ?></p>
        <p><strong class="label-fixed-width">นามสกุล:</strong> <?= htmlspecialchars($Last_name) ?></p>
        <p><strong class="label-fixed-width">Email:</strong> <?= htmlspecialchars($Email_member) ?></p>
        <!-- ส่วนที่แก้ไข: ลบคลาส label-fixed-width ออกจาก strong ของ เบอร์โทรศัพท์ และ จำนวนห้องที่จอง -->
        <p>
            <strong>เบอร์โทรศัพท์:</strong> <?= htmlspecialchars($Phone_number) ?>
            &nbsp;&nbsp;&nbsp;&nbsp; <!-- ใช้ &nbsp; เพื่อเพิ่มระยะห่าง -->
            <strong>จำนวนห้องที่จอง:</strong> <?= htmlspecialchars($Total_rooms) ?>
        </p>
    </div>
    
    <div class="navigation-links">
        <a href="home.php" class="back-link">กลับสู่หน้าหลัก</a>
        <a href="./index.php" class="logout-link">ออกจากระบบ</a>
    </div>
</div>
</body>
</html>