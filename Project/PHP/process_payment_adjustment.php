<?php
session_start();
include 'db.php';
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบ session เจ้าหน้าที่
if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php");
    exit();
}

$officer_email = $_SESSION['Email_Officer'];
$type = $_POST['type'] ?? '';
$amount = $_POST['amount'] ?? 0;
$stay_id = $_POST['stay_id'] ?? '';
$room_id = $_POST['room_id'] ?? '';
$reservation_id = $_POST['reservation_id'] ?? '';
$action = $_POST['action'] ?? '';

// เพิ่มการ Debugging สำหรับการตรวจสอบค่า
error_log("DEBUG process_payment_adjustment.php: Type = " . $type . ", Reservation_id = " . $reservation_id . ", Stay_id = " . $stay_id . ", Room_id = " . $room_id . ", Amount = " . $amount . ", Action = " . $action);


if ($action !== 'confirm_payment') {
    $_SESSION['error'] = "การกระทำไม่ถูกต้อง";
    header("Location: counter_operations.php");
    exit();
}

$now = date("Y-m-d H:i:s"); // เวลา ณ ปัจจุบันที่ยืนยันการชำระเงิน

try {
    if ($type === 'damage') {
        // อัปเดตค่าเสียหายใน room_damages
        // โค้ดเดิมถูกต้องแล้ว แต่เพิ่มการตรวจสอบ affected_rows เพื่อให้ข้อความแจ้งเตือนแม่นยำขึ้น
        $stmt = $conn->prepare("UPDATE room_damages 
                                SET Damage_value = ?, Officer_Email = ?, Damage_date = ? 
                                WHERE Stay_Id = ? AND Room_Id = ?
                                ORDER BY Damage_date DESC LIMIT 1"); // LIMIT 1 เพื่ออัปเดตรายการล่าสุด
        if (!$stmt) {
            throw new Exception("เกิดข้อผิดพลาดในการเตรียม SQL สำหรับค่าเสียหาย: " . $conn->error);
        }
        $stmt->bind_param("dssss", $amount, $officer_email, $now, $stay_id, $room_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "บันทึกการชำระค่าเสียหายห้องพัก " . htmlspecialchars($room_id) . " เรียบร้อยแล้ว ✅";
            } else {
                throw new Exception("ไม่พบรายการค่าเสียหายที่ตรงกันเพื่ออัปเดต หรือไม่มีการเปลี่ยนแปลง (Stay ID: " . htmlspecialchars($stay_id) . ", Room ID: " . htmlspecialchars($room_id) . ").");
            }
        } else {
            throw new Exception("เกิดข้อผิดพลาดในการอัปเดตค่าเสียหาย: " . $stmt->error);
        }
        $stmt->close();
    } elseif ($type === 'penalty') {
        // อัปเดตค่าปรับใน reservation
        // *** แก้ไขชื่อคอลัมน์ให้ถูกต้องตามโครงสร้างฐานข้อมูล ***
        $stmt = $conn->prepare("UPDATE reservation 
                                SET Penalty_amount = ?, Penalty_officer_email = ?, Penalty_date = ? 
                                WHERE Reservation_Id = ?");
        if (!$stmt) {
            throw new Exception("เกิดข้อผิดพลาดในการเตรียม SQL สำหรับค่าปรับ No-show: " . $conn->error);
        }
        // d: amount (double), s: officer_email (string), s: now (string - date), s: reservation_id (string)
        $stmt->bind_param("dsss", $amount, $officer_email, $now, $reservation_id); 
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "บันทึกการชำระค่าปรับ No-show สำหรับการจอง #" . htmlspecialchars($reservation_id) . " เรียบร้อยแล้ว ✅";
            } else {
                throw new Exception("ไม่พบรายการค่าปรับ No-show ที่ตรงกันเพื่ออัปเดต หรือไม่มีการเปลี่ยนแปลง (Reservation ID: " . htmlspecialchars($reservation_id) . ").");
            }
        } else {
            throw new Exception("เกิดข้อผิดพลาดในการอัปเดตค่าปรับ No-show: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "ประเภทการปรับไม่ถูกต้อง";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดำเนินการชำระเงิน: " . $e->getMessage();
    error_log("ERROR process_payment_adjustment.php: " . $e->getMessage()); // บันทึก error ลง log file
}

$conn->close();
header("Location: counter_operations.php");
exit();
?>