<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่าไฟล์ db.php อยู่ใน path ที่ถูกต้อง

// ตั้งค่า default timezone สำหรับ PHP เพื่อให้แน่ใจว่าเวลาถูกต้อง
date_default_timezone_set('Asia/Bangkok'); // กำหนด timezone เป็น Asia/Bangkok (สำคัญ!)

// ตรวจสอบว่ามี session email หรือไม่ หากไม่มี ให้ redirect ไปหน้า login
if (!isset($_SESSION['email'])) {
    $_SESSION['message'] = "กรุณาเข้าสู่ระบบเพื่อดำเนินการ";
    $_SESSION['message_type'] = "danger";
    header('Location: login.php'); // เปลี่ยนเป็นหน้า login ของคุณ
    exit();
}

// ตรวจสอบว่าเป็น method POST และมีข้อมูลที่จำเป็นหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id'], $_POST['stars'])) {
    $reservation_id = $_POST['reservation_id'];
    $stars = $_POST['stars'];
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : NULL; // คอมเมนต์อาจจะว่างเปล่าได้, trim() เพื่อลบช่องว่างหัวท้าย

    // *** เพิ่ม: ดึงเวลาปัจจุบันเพื่อบันทึกเป็น rating_timestamp ***
    $rating_timestamp = date('Y-m-d H:i:s');

    // ตรวจสอบให้แน่ใจว่าค่าดาวอยู่ในช่วงที่ถูกต้อง (1-5) และเป็นตัวเลข
    if (!filter_var($stars, FILTER_VALIDATE_INT, array("options" => array("min_range"=>1, "max_range"=>5)))) {
        $_SESSION['message'] = "ค่าคะแนนไม่ถูกต้อง กรุณาเลือกดาว 1-5 ดาว";
        $_SESSION['message_type'] = "danger";
        header('Location: score.php'); // กลับไปหน้าให้คะแนน
        exit();
    }

    // เตรียมคำสั่ง SQL เพื่ออัปเดตฐานข้อมูล
    // ตรวจสอบว่า `Email_member` ของการจองนั้นตรงกับ `Email` ของผู้ใช้ปัจจุบันใน session เพื่อความปลอดภัย
    // *** แก้ไข: เพิ่ม rating_timestamp = ? เข้าไปใน UPDATE query ***
    $sql = "UPDATE reservation SET stars = ?, comment = ?, rating_timestamp = ? WHERE Reservation_Id = ? AND Email_member = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // ผูกค่าพารามิเตอร์ (i: integer, s: string, s: string (for datetime), s: string (for reservation_id), s: string (for email))
        // *** แก้ไข: เพิ่ม $rating_timestamp เข้าไปใน bind_param ***
        $stmt->bind_param("issss", $stars, $comment, $rating_timestamp, $reservation_id, $_SESSION['email']);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "บันทึกคะแนนเรียบร้อยแล้ว!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "เกิดข้อผิดพลาดในการบันทึกคะแนน: " . $stmt->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    // ปิดการเชื่อมต่อฐานข้อมูล
    $conn->close();

    // Redirect กลับไปหน้าให้คะแนน
    header('Location: score.php');
    exit();

} else {
    // ถ้าไม่มีข้อมูลที่จำเป็น หรือไม่ใช่ method POST
    $_SESSION['message'] = "ไม่มีข้อมูลที่จำเป็นสำหรับการให้คะแนน";
    $_SESSION['message_type'] = "danger";
    header('Location: score.php');
    exit();
}
?>