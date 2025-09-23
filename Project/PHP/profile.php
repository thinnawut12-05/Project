<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['email'])) {
    die("ยังไม่ได้เข้าสู่ระบบ");
}

$Email_member = $_SESSION['email'];
$message = "";
$password_value = "**********"; 
$should_show_password = false; 

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

// ---------------- เปลี่ยนรหัสผ่าน ----------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_password'])) {
    $new_pass = $_POST['new_password'];

    if (!preg_match('/^[0-9]+$/', $new_pass)) {
        $message = "รหัสผ่านต้องเป็นตัวเลขเท่านั้น ❌";
        $password_value = $new_pass; 
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE member SET Password = ? WHERE Email_member = ?");
        $update->bind_param("ss", $hashed_pass, $Email_member);

        if ($update->execute()) {
            $message = "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว ✅";
            $password_value = $new_pass;
            $should_show_password = true;
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
 <link rel="icon" type="image/png" href="../src/images/logo.png" />
<link rel="stylesheet" href="../CSS/css/profile.css">
<style>
input[type="password"], input[type="text"] {
    width: 200px;
}
button {
    margin-left: 5px;
}
.navigation-links {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
}
.logout-link {
    display: inline-block;
    padding: 8px 20px;
    background-color: #dc3545;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
}
.logout-link:hover {
    background-color: #c82333;
}
</style>
</head>
<body>
<div class="profile-container">
    <h1>โปรไฟล์ของฉัน</h1>
    <div class="profile-info">
        <p><strong>ชื่อจริง:</strong> <?= htmlspecialchars($First_name) ?></p>
        <p><strong>นามสกุล:</strong> <?= htmlspecialchars($Last_name) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($Email_member) ?></p>
        <p><strong>เบอร์โทรศัพท์:</strong> <?= htmlspecialchars($Phone_number) ?></p>
        <p><strong>จำนวนห้องที่จอง:</strong> <?= htmlspecialchars($Total_rooms) ?></p>

        <form method="post">
            <p><strong>รหัสผ่าน:</strong>
                <input type="<?= $should_show_password ? 'text' : 'password' ?>" 
                       id="passwordField" name="new_password"
                       value="<?= htmlspecialchars($password_value) ?>" 
                       pattern="[0-9]+" 
                       title="กรุณากรอกตัวเลขเท่านั้น"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
                <button type="button" id="toggleBtn"><?= $should_show_password ? 'ซ่อน' : 'แสดง' ?></button>
                <button type="submit">บันทึก</button>
            </p>
        </form>

        <?php if($message): ?>
            <p style="color: <?= strpos($message, '✅') !== false ? 'green' : 'red'; ?>;">
                <?= $message ?>
            </p>
        <?php endif; ?>
    </div>
    
    <div class="navigation-links">
        <a href="home.php" class="back-link">กลับสู่หน้าหลัก</a>
        <a href="./index.php" class="logout-link">ออกจากระบบ</a>
    </div>
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
