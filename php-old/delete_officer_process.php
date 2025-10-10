<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่า db.php อยู่ในตำแหน่งที่ถูกต้องและมีการเชื่อมต่อฐานข้อมูล ($conn)

// ตรวจสอบการล็อกอินของแอดมินหรือเจ้าหน้าที่ที่ได้รับสิทธิ์
if (!isset($_SESSION['Email_Officer'])) {
    // หากไม่มีการล็อกอิน ให้เปลี่ยนเส้นทางไปหน้า login ทันที
    header("Location: login.php");
    exit();
}

$message = ''; // เก็บข้อความสำหรับแจ้งเตือน

if (isset($_GET['email']) && !empty($_GET['email'])) {
    $email_to_delete = $_GET['email'];

    // คุณอาจต้องการตรวจสอบสิทธิ์ของผู้ใช้ก่อนดำเนินการลบเพิ่มเติม เช่น
    // ตรวจสอบว่า Admin ที่กำลังล็อกอินอยู่มีสิทธิ์ลบเจ้าหน้าที่คนนี้หรือไม่
    // และป้องกันการลบบัญชีของตัวเอง
    // if ($_SESSION['Email_Officer'] == $email_to_delete) {
    //     $_SESSION['delete_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> ไม่สามารถลบบัญชีของตัวเองได้!</div>';
    //     header("Location: add_officer.php");
    //     exit();
    // }

    $sql_delete = "DELETE FROM officer WHERE Email_Officer = ?";
    $stmt_delete = $conn->prepare($sql_delete);

    if ($stmt_delete) {
        $stmt_delete->bind_param('s', $email_to_delete);
        if ($stmt_delete->execute()) {
            $_SESSION['delete_message'] = '<div class="alert success"><i class="fas fa-check-circle"></i> ลบเจ้าหน้าที่ <strong>' . htmlspecialchars($email_to_delete) . '</strong> สำเร็จแล้ว!</div>';
        } else {
            $_SESSION['delete_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการลบเจ้าหน้าที่: ' . $stmt_delete->error . '</div>';
        }
        $stmt_delete->close();
    } else {
        $_SESSION['delete_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับลบ: ' . $conn->error . '</div>';
    }
} else {
    $_SESSION['delete_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> ไม่พบอีเมลเจ้าหน้าที่ที่ต้องการลบ.</div>';
}

// ปิดการเชื่อมต่อฐานข้อมูล
if (isset($conn) && $conn->ping()) {
    $conn->close();
}

// เปลี่ยนเส้นทางกลับไปยังหน้าจัดการเจ้าหน้าที่
header("Location: add_officer.php");
exit();
?>