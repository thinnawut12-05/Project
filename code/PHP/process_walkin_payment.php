<?php
session_start();
include 'db.php';

date_default_timezone_set('Asia/Bangkok');
error_reporting(E_ALL); // เปิดการแสดง error เพื่อช่วยในการ Debug
ini_set('display_errors', 1);

if ($conn) {
    // กำหนด SQL_MODE สำหรับการเชื่อมต่อนี้ให้ผ่อนปรนขึ้น
    $conn->query("SET SESSION sql_mode = '';"); 
}

// ตรวจสอบการเข้าสู่ระบบเจ้าหน้าที่
if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php");
    exit();
}

$officer_email = $_SESSION['Email_Officer'];
$current_province_id = $_SESSION['Province_id'];

// ฟังก์ชันสำหรับสร้าง ID ที่ไม่ซ้ำกัน (คัดลอกมาจาก counter_operations.php)
function generateUniqueId($conn, $table, $idColumn) {
    $isUnique = false;
    $newId = '';
    $maxAttempts = 100;
    for ($i = 0; $i < $maxAttempts && !$isUnique; $i++) {
        $newId = (string)mt_rand(1000000000, 9999999999); 
        $check_sql = "SELECT 1 FROM $table WHERE $idColumn = ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt) {
            $check_stmt->bind_param("s", $newId);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows == 0) { $isUnique = true; }
            $check_stmt->close();
        } else { error_log("ERROR: generateUniqueId - Failed to prepare ID check statement for $table.$idColumn: " . $conn->error); throw new Exception("Error checking for unique ID."); }
    }
    if (!$isUnique) { error_log("CRITICAL ERROR: generateUniqueId - Failed to generate a unique ID for $table.$idColumn after $maxAttempts attempts."); throw new Exception("Error: Could not generate a unique ID."); }
    return $newId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_walkin_payment') {
    $reservation_id = $_POST['reservation_id'] ?? null;
    $amount = (float)($_POST['amount'] ?? 0);
    $selected_room_id = $_POST['selected_room_id'] ?? null;
    $guest_name = $_POST['guest_name'] ?? null;
    $checkin_date = $_POST['checkin_date'] ?? null;
    $checkout_date = $_POST['checkout_date'] ?? null;

    // สถานะ ID จาก counter_operations.php
    $status_id_checked_in = 6;
    $generic_email_member = "walkin@example.com";

    try {
        if (empty($reservation_id) || empty($selected_room_id) || empty($guest_name) || empty($checkin_date) || empty($checkout_date) || $amount <= 0) {
            throw new Exception("ข้อมูลไม่ครบถ้วนสำหรับการยืนยันการชำระเงิน Walk-in.");
        }

        $conn->begin_transaction();

        // 1. อัปเดตสถานะการจองในตาราง reservation เป็น 'Checked In' (6)
        // ตรวจสอบว่าสถานะเดิมเป็น 'รอชำระเงิน' (1) ก่อนอัปเดต
        $stmt_update_reservation = $conn->prepare("
            UPDATE reservation SET Booking_status_Id = ?
            WHERE Reservation_Id = ? AND Province_Id = ? AND Booking_status_Id = 1
        ");
        if ($stmt_update_reservation === false) { throw new Exception("Failed to prepare reservation status update statement: " . $conn->error); }
        $stmt_update_reservation->bind_param("isi", $status_id_checked_in, $reservation_id, $current_province_id);
        $stmt_update_reservation->execute();
        if ($stmt_update_reservation->affected_rows === 0) {
            // อาจหมายความว่าการจองถูกยกเลิกไปแล้ว หรือสถานะไม่ถูกต้อง
            throw new Exception("ไม่สามารถอัปเดตสถานะการจอง Walk-in ได้ (อาจไม่อยู่ในสถานะรอชำระเงินหรือรหัสการจองไม่ถูกต้อง).");
        }
        $stmt_update_reservation->close();

        // 2. สร้างรายการเข้าพักในตาราง stay
        $stay_id = generateUniqueId($conn, 'stay', 'Stay_id');
        $checkin_time = date('H:i:s');
        $stmt_insert_stay = $conn->prepare(
            "INSERT INTO stay (Stay_id, Room_id, Guest_name, Check_in_date, Check_in_time, 
                                Check_out_date, Check_out_time, Reservation_Id, Email_member, Receipt_Id) 
            VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, ?, NULL)" // Check_out_date/Time, Receipt_Id เป็น NULL ในการเช็คอิน
        );
        if ($stmt_insert_stay === false) { throw new Exception("Failed to prepare stay insert statement: " . $conn->error); }
        $stmt_insert_stay->bind_param(
            "sssssss", // Stay_id, Room_id, Guest_name, Check_in_date, Check_in_time, Reservation_Id, Email_member
            $stay_id, $selected_room_id, $guest_name, $checkin_date, $checkin_time,
            $reservation_id, $generic_email_member
        );
        $stmt_insert_stay->execute();
        if ($stmt_insert_stay->affected_rows === 0) { throw new Exception("ไม่สามารถสร้างรายการเข้าพักสำหรับ Walk-in ได้."); }
        $stmt_insert_stay->close();

        // 3. อัปเดตสถานะห้องในตาราง room เป็น 'OCC' (Occupied)
        // ตรวจสอบว่าห้องยังคงเป็น 'AVL' (ว่าง) ก่อนอัปเดต เพื่อป้องกันปัญหา Race Condition
        $stmt_update_room = $conn->prepare("UPDATE room SET Status = 'OCC' WHERE Room_ID = ? AND Province_id = ? AND Status = 'AVL'");
        if ($stmt_update_room === false) { throw new Exception("Failed to prepare room status update statement: " . $conn->error); }
        $stmt_update_room->bind_param("si", $selected_room_id, $current_province_id);
        $stmt_update_room->execute();
        if ($stmt_update_room->affected_rows === 0) { throw new Exception("ห้องพัก " . htmlspecialchars($selected_room_id) . " ไม่ว่างหรือไม่ถูกต้อง (หรือไม่อยู่ในสาขา) ณ เวลาที่ยืนยันการชำระเงิน."); }
        $stmt_update_room->close();

        $conn->commit();
        $_SESSION['message'] = "ยืนยันการชำระเงินและเช็คอินลูกค้า Walk-in สำเร็จแล้ว! รหัสการจอง: #" . htmlspecialchars($reservation_id);
        header("Location: counter_operations.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // ยกเลิกการเปลี่ยนแปลงทั้งหมดใน Transaction หากเกิดข้อผิดพลาด
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการยืนยันการชำระเงิน: " . $e->getMessage();
        error_log("ERROR: process_walkin_payment.php - " . $e->getMessage()); // บันทึก error ลง log
        // เปลี่ยนเส้นทางกลับไปหน้าชำระเงินพร้อมข้อผิดพลาด
        header("Location: payment_walkin.php?reservation_id=" . urlencode($reservation_id) . "&amount=" . urlencode($amount) . "&room_id=" . urlencode($selected_room_id));
        exit();
    }
} else {
    $_SESSION['error'] = "คำขอไม่ถูกต้อง.";
    header("Location: counter_operations.php");
    exit();
}


?>