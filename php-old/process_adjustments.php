<?php
session_start();
include 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php");
    exit();
}

$officer_email = $_SESSION['Email_Officer'];
$current_province_id = $_SESSION['Province_id']; // ID สาขาของเจ้าหน้าที่

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? null;

    $conn->begin_transaction();
    try {
        switch ($action) {
            case 'apply_penalty':
                $reservation_id = $_POST['reservation_id'] ?? null;
                $penalty_amount = $_POST['penalty_amount'] ?? 0.00;
                $penalty_reason = $_POST['penalty_reason'] ?? '';

                if (empty($reservation_id) || !is_numeric($penalty_amount) || empty($penalty_reason)) {
                    throw new Exception("ข้อมูลไม่สมบูรณ์สำหรับการแจ้งปรับ.");
                }

                // กำหนดสถานะใหม่เป็น 'ไม่มาเช็คอิน/ถูกปรับ' (Booking_status_Id = 8)
                $status_id_no_show_penalized = 8;

                $sql_update_reservation = "UPDATE reservation 
                                           SET Booking_status_Id = ?, Penalty_amount = ?, Penalty_reason = ?
                                           WHERE Reservation_Id = ? AND Province_Id = ?";
                $stmt_update_reservation = $conn->prepare($sql_update_reservation);
                if (!$stmt_update_reservation) {
                    throw new Exception("Failed to prepare penalty update statement: " . $conn->error);
                }
                $stmt_update_reservation->bind_param("idssi", 
                    $status_id_no_show_penalized, 
                    $penalty_amount, 
                    $penalty_reason, 
                    $reservation_id, 
                    $current_province_id
                );
                $stmt_update_reservation->execute();

                if ($stmt_update_reservation->affected_rows === 0) {
                    throw new Exception("ไม่พบการจองที่ระบุ หรือไม่สามารถอัปเดตสถานะ/ค่าปรับได้.");
                }
                $stmt_update_reservation->close();

                $_SESSION['message'] = "แจ้งปรับการจอง #" . htmlspecialchars($reservation_id) . " สำเร็จแล้ว. (มูลค่า: ฿" . number_format($penalty_amount, 2) . ")";
                break;

            case 'record_damage':
                $stay_id = $_POST['stay_id'] ?? null;
                $room_id = $_POST['room_id'] ?? null;
                $damage_item = $_POST['damage_item'] ?? '';
                $damage_description = $_POST['damage_description'] ?? '';
                $damage_value = $_POST['damage_value'] ?? 0.00;

                if (empty($stay_id) || empty($room_id) || empty($damage_item) || !is_numeric($damage_value)) {
                    throw new Exception("ข้อมูลไม่สมบูรณ์สำหรับการบันทึกความเสียหาย.");
                }

                $sql_insert_damage = "INSERT INTO room_damages 
                                      (Stay_Id, Room_Id, Damage_item, Damage_description, Damage_value, Officer_Email)
                                      VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert_damage = $conn->prepare($sql_insert_damage);
                if (!$stmt_insert_damage) {
                    throw new Exception("Failed to prepare damage insert statement: " . $conn->error);
                }
                $stmt_insert_damage->bind_param("sssdss", 
                    $stay_id, 
                    $room_id, 
                    $damage_item, 
                    $damage_description, 
                    $damage_value, 
                    $officer_email
                );
                $stmt_insert_damage->execute();

                if ($stmt_insert_damage->affected_rows === 0) {
                    throw new Exception("ไม่สามารถบันทึกข้อมูลความเสียหายได้.");
                }
                $stmt_insert_damage->close();

                $_SESSION['message'] = "บันทึกความเสียหายสำหรับรายการเข้าพัก #" . htmlspecialchars($stay_id) . " (ห้อง " . htmlspecialchars($room_id) . ") มูลค่า ฿" . number_format($damage_value, 2) . " สำเร็จแล้ว.";
                break;

            default:
                throw new Exception("การดำเนินการไม่ถูกต้อง.");
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        error_log("ERROR: process_adjustments.php - " . $e->getMessage());
    } finally {
        $conn->close();
    }

    // Redirect กลับไปหน้าเดิม
    header("Location: officerindex.php?section=" . ($_POST['redirect_section'] ?? 'confirmed_bookings')); // redirect_section สามารถส่งมาเพื่อกลับไป section ที่ถูกต้อง
    exit();
} else {
    header("Location: officerindex.php");
    exit();
}
?>