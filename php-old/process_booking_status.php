<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่าไฟล์ db.php มีการเชื่อมต่อฐานข้อมูลที่ถูกต้อง

// เปิดการแสดงข้อผิดพลาดทั้งหมดสำหรับการดีบัก (สามารถปิดได้เมื่อใช้งานจริง)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reservation_id = $_POST['reservation_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$reservation_id || !$action) {
        $_SESSION['error'] = "ข้อมูลไม่สมบูรณ์สำหรับการดำเนินการ";
        header("Location: officerindex.php?section=pending_bookings"); // เปลี่ยนเส้นทางเริ่มต้นเมื่อเกิดข้อผิดพลาด
        exit();
    }

    $new_status_id = null;
    $success_message = "";
    $redirect_section = "";

    switch ($action) {
        case 'confirm':
            $new_status_id = 2; // สถานะ "ยืนยันแล้ว"
            $success_message = "ยืนยันการจอง #" . htmlspecialchars($reservation_id) . " เรียบร้อยแล้ว";
            $redirect_section = "confirmed_bookings"; // ไปยังส่วนการจองที่ยืนยันแล้ว
            break;
        case 'cancel':
            $new_status_id = 3; // สถานะ "ยกเลิกแล้ว"
            $success_message = "ยกเลิกการจอง #" . htmlspecialchars($reservation_id) . " เรียบร้อยแล้ว";
            $redirect_section = "confirmed_bookings"; // อาจมาจากหน้าการจองที่ยืนยันแล้ว หรือรอดำเนินการก็ได้
            break;
        default:
            $_SESSION['error'] = "การดำเนินการไม่ถูกต้อง";
            header("Location: officerindex.php?section=pending_bookings");
            exit();
    }

    // เริ่มต้น Transaction เพื่อให้การทำงานในฐานข้อมูลเป็นไปอย่างครบถ้วน
    $conn->begin_transaction();
    try {
        $sql_update_status = "UPDATE reservation SET Booking_status_Id = ? WHERE Reservation_Id = ?";
        $stmt_update = $conn->prepare($sql_update_status);

        if (!$stmt_update) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        $stmt_update->bind_param("is", $new_status_id, $reservation_id); // 'i' สำหรับ int, 's' สำหรับ string (Reservation_Id เป็น VARCHAR)

        if (!$stmt_update->execute()) {
            throw new Exception("Error updating booking status: " . $stmt_update->error);
        }

        $conn->commit(); // ยืนยัน Transaction
        $_SESSION['message'] = $success_message;
        header("Location: officerindex.php?section=" . $redirect_section);
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // ยกเลิก Transaction หากเกิดข้อผิดพลาด
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการดำเนินการ: " . $e->getMessage();
        header("Location: officerindex.php?section=pending_bookings");
        exit();
    } finally {
        // ปิด statement และ connection
        if (isset($stmt_update)) $stmt_update->close();
        $conn->close();
    }
} else {
    // หากไม่ใช่คำขอ POST ให้เปลี่ยนเส้นทางกลับไปที่หน้าหลัก
    header("Location: officerindex.php");
    exit();
}
?>