<?php
session_start();
include 'db.php';

if (!isset($_SESSION['email'])) {
    die("ยังไม่ได้เข้าสู่ระบบ");
}

$Email_member = $_SESSION['email'];
$message = "";
$password_value = "**********"; // ค่าเริ่มต้นสำหรับแสดงในฟอร์ม
$should_show_password = false; // ตัวแปรเพื่อบอก JavaScript ว่าควรแสดงรหัสผ่านเลยหรือไม่

// ดึงข้อมูลผู้ใช้
$sql = "SELECT First_name, Last_name, Email_member FROM member WHERE Email_member = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $Email_member);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $First_name = $row['First_name'];
    $Last_name = $row['Last_name'];
    $Email_member = $row['Email_member'];
} else {
    die("ไม่พบข้อมูลผู้ใช้");
}

// เปลี่ยนรหัสผ่าน
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_password'])) {
    $new_pass = $_POST['new_password'];

    // ตรวจสอบว่ารหัสผ่านเป็นตัวเลขเท่านั้น
    if (!preg_match('/^[0-9]+$/', $new_pass)) {
        $message = "รหัสผ่านต้องเป็นตัวเลขเท่านั้น ❌";
        $password_value = $new_pass; // แสดงรหัสที่กรอกผิดเพื่อให้ผู้ใช้แก้ไข
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE member SET Password = ? WHERE Email_member = ?");
        $update->bind_param("ss", $hashed_pass, $Email_member);

        if ($update->execute()) {
            $message = "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว ✅";
            $password_value = $new_pass; // กำหนดค่า password ใหม่เพื่อแสดงผล
            $should_show_password = true; // ตั้งค่าให้ JavaScript แสดงรหัสผ่าน
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
        }

        $update->close();
    }
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>โปรไฟล์ของคุณ</title>
<link rel="stylesheet" href="profile.css">
<style>
input[type="password"], input[type="text"] {
    width: 200px;
}
button {
    margin-left: 5px;
}
/* ----- CSS ที่เพิ่มเข้ามา ----- */
.navigation-links {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px; /* ระยะห่างระหว่างลิงก์ */
}
.logout-link {
    display: inline-block;
    padding: 8px 20px;
    background-color: #dc3545; /* สีแดง */
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
}
.logout-link:hover {
    background-color: #c82333; /* สีแดงเข้มขึ้นเมื่อ hover */
}
/* -------------------------- */
</style>
</head>
<body>
<div class="profile-container">
    <h1>โปรไฟล์ของฉัน</h1>
    <div class="profile-info">
        <p><strong>ชื่อจริง:</strong> <?= htmlspecialchars($First_name) ?></p>
        <p><strong>นามสกุล:</strong> <?= htmlspecialchars($Last_name) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($Email_member) ?></p>

        <form method="post">
            <p><strong>รหัสผ่าน:</strong>
                <input type="<?= $should_show_password ? 'text' : 'password' ?>" id="passwordField" name="new_password"
                       value="<?= htmlspecialchars($password_value) ?>" pattern="[0-9]+" title="กรุณากรอกตัวเลขเท่านั้น"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
                <button type="button" id="toggleBtn"><?= $should_show_password ? 'ซ่อน' : 'แสดง' ?></button>
                <button type="submit">บันทึก</button>
            </p>
        </form>

        <?php if($message): ?>
            <p style="color: <?= strpos($message, '✅') !== false ? 'green' : 'red'; ?>;"><?= $message ?></p>
        <?php endif; ?>
    </div>
    
    <!-- ----- HTML ที่แก้ไขและเพิ่มเข้ามา ----- -->
    <div class="navigation-links">
        <a href="home.php" class="back-link">กลับสู่หน้าหลัก</a>
        <a href="./index.php" class="logout-link">ออกจากระบบ</a>
    </div>
    <!-- ------------------------------------ -->

</div>

<script>
const toggleBtn = document.getElementById('toggleBtn');
const passwordField = document.getElementById('passwordField');

toggleBtn.addEventListener('click', () => {
    if (passwordField.type === "password") {
        if (passwordField.value === "**********") {
            passwordField.value = "";
        }
        passwordField.type = "text";
        toggleBtn.textContent = "ซ่อน";
    } else {
        passwordField.type = "password";
        toggleBtn.textContent = "แสดง";
    }
});

passwordField.addEventListener('focus', () => {
    if (passwordField.value === "**********") {
        passwordField.value = "";
    }
});
</script>
</body>
</html>