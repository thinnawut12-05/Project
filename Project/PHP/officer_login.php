<?php
session_start();
// ตรวจสอบและยกเลิก session เก่าหากไม่มีการล็อกอินจริง หรือต้องการเคลียร์ก่อนหน้า
// ในการใช้งานจริง มักจะมีปุ่ม logout หรือ logic ที่ชัดเจนกว่านี้
// สำหรับตอนนี้ ให้แน่ใจว่าค่า session ถูกตั้งค่าใหม่เมื่อล็อกอิน

include 'db.php'; // ตรวจสอบให้แน่ใจว่าไฟล์ db.php มีการเชื่อมต่อฐานข้อมูล ($conn) อยู่แล้ว

$error = '';
// เคลียร์ค่า session ที่เกี่ยวข้องกับการล็อกอินก่อนพยายามตั้งค่าใหม่
unset($_SESSION['Email_Officer']);
unset($_SESSION['First_name']);
unset($_SESSION['Last_name']);
unset($_SESSION['Province_id']); // เคลียร์ Province_id ด้วย

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['Email_Officer'] ?? ''); // ชื่อ field จาก HTML form
    $password = $_POST['Password'] ?? ''; // ชื่อ field จาก HTML form

    if ($email === '' || $password === '') {
        $error = "กรุณากรอกอีเมลและรหัสผ่านให้ครบถ้วน";
    } else {
        // แก้ไข SELECT query เพื่อดึง Province_id ด้วย
        $stmt = $conn->prepare("SELECT Email_Officer, Password, First_name, Last_name, Province_id FROM officer WHERE Email_Officer = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            // bind ผลลัพธ์ทั้งหมด รวมถึง Province_id
            $stmt->bind_result($emailDB, $hashedPassword, $first, $last, $provinceIdDB);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['Email_Officer'] = $emailDB;
                $_SESSION['First_name'] = $first;
                $_SESSION['Last_name'] = $last;
                $_SESSION['Province_id'] = $provinceIdDB; // เก็บ Province_id ใน session

                header("Location: officer.php"); // ไปยังหน้าแจ้งห้องไม่พร้อมใช้งาน
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

// ตรวจสอบและปิดการเชื่อมต่อฐานข้อมูล หาก db.php ไม่ได้จัดการเอง
// if (isset($conn)) {
//     $conn->close();
// }
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบเจ้าหน้าที่ - Dom Inn Hotel</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="../CSS/css/admin.css"> <!-- ตรวจสอบพาธของไฟล์ CSS ให้ถูกต้อง -->
</head>

<body>
    <div class="login-box">
        <h2>เข้าสู่ระบบเจ้าหน้าที่</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <!-- แก้ไข type ของ input ให้เป็น email และ password -->
            <input type="email" name="Email_Officer" placeholder="อีเมล" required value="<?= htmlspecialchars($email ?? '') ?>">
            <input type="password" name="Password" placeholder="รหัสผ่าน" required>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
        <a href="index.php">⬅ กลับหน้าหลัก</a> <!-- ตรวจสอบพาธของหน้าหลักให้ถูกต้อง -->
    </div>
</body>

</html>