<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่า db.php อยู่ในตำแหน่งที่ถูกต้อง

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stay_id = $_POST['stay_id'] ?? null;
    $room_id = $_POST['room_id'] ?? null; // Room_Id ที่ส่งมาจากฟอร์ม (เป็น VARCHAR)

    if (empty($stay_id) || empty($room_id)) {
        $_SESSION['error'] = "ข้อมูลไม่สมบูรณ์สำหรับการเช็คเอาท์";
        header("Location: officerindex.php?section=checkout");
        exit();
    }

    // ตั้งค่า default timezone สำหรับ PHP เพื่อให้แน่ใจว่าเวลาถูกต้อง
    date_default_timezone_set('Asia/Bangkok'); 
    $checkout_date = date('Y-m-d');
    $checkout_time = date('H:i:s');

    $conn->begin_transaction(); // เริ่มต้น Transaction
    try {
        // 1. อัปเดต Check_out_date และ Check_out_time ในตาราง 'stay'
        $sql_update_stay = "UPDATE stay 
                            SET Check_out_date = ?, Check_out_time = ? 
                            WHERE Stay_id = ? AND Room_id = ? AND Check_out_date IS NULL";
        $stmt_update_stay = $conn->prepare($sql_update_stay);
        if (!$stmt_update_stay) {
            throw new Exception("Failed to prepare stay update statement: " . $conn->error);
        }
        // bind_param: s (checkout_date), s (checkout_time), s (Stay_id), s (Room_id)
        $stmt_update_stay->bind_param("ssss", $checkout_date, $checkout_time, $stay_id, $room_id);
        $stmt_update_stay->execute();

        if ($stmt_update_stay->affected_rows === 0) {
            throw new Exception("ไม่พบรายการเข้าพักที่ยังไม่ได้เช็คเอาท์ หรือข้อมูลไม่ถูกต้อง (Stay_id: " . htmlspecialchars($stay_id) . ", Room_id: " . htmlspecialchars($room_id) . ").");
        }
        $stmt_update_stay->close();

        // 2. อัปเดตสถานะห้องในตาราง 'room' ให้เป็น 'AVL' (Available)
        $sql_update_room = "UPDATE room SET Status = 'AVL' WHERE Room_ID = ? AND Status = 'OCC'";
        $stmt_update_room = $conn->prepare($sql_update_room);
        if (!$stmt_update_room) {
            throw new Exception("Failed to prepare room update statement: " . $conn->error);
        }
        // bind_param: s (Room_Id)
        $stmt_update_room->bind_param("s", $room_id); // Room_ID เป็น VARCHAR
        $stmt_update_room->execute();

        if ($stmt_update_room->affected_rows === 0) {
            // อาจจะไม่ใช่ข้อผิดพลาดร้ายแรงหากห้องนั้นถูกอัปเดตไปแล้ว หรือไม่ได้อยู่ในสถานะ OCC
            error_log("WARNING: ไม่สามารถอัปเดตสถานะห้อง " . htmlspecialchars($room_id) . " เป็น 'AVL' ได้ (อาจไม่พบห้อง, สถานะไม่ใช่ 'OCC' หรือถูกอัปเดตไปแล้ว).");
        }
        $stmt_update_room->close();

        // 3. (Optional) อัปเดตสถานะการจองในตาราง 'reservation' หาก Stay นี้มาจาก Reservation
        // ต้องไปดึง Reservation_Id จากตาราง stay ก่อน
        $sql_get_reservation_id = "SELECT Reservation_Id FROM stay WHERE Stay_id = ?";
        $stmt_get_reservation_id = $conn->prepare($sql_get_reservation_id);
        if ($stmt_get_reservation_id) {
            $stmt_get_reservation_id->bind_param("s", $stay_id);
            $stmt_get_reservation_id->execute();
            $result_get_reservation_id = $stmt_get_reservation_id->get_result();
            $reservation_data = $result_get_reservation_id->fetch_assoc();
            $stmt_get_reservation_id->close();

            if ($reservation_data && !empty($reservation_data['Reservation_Id'])) {
                $reservation_id = $reservation_data['Reservation_Id'];

                // ตรวจสอบว่ามีการจองทั้งหมดเช็คเอาท์หมดแล้วหรือไม่
                $sql_check_all_stays_checked_out = "SELECT COUNT(*) FROM stay WHERE Reservation_Id = ? AND Check_out_date IS NULL";
                $stmt_check_all_stays = $conn->prepare($sql_check_all_stays_checked_out);
                if ($stmt_check_all_stays) {
                    $stmt_check_all_stays->bind_param("s", $reservation_id);
                    $stmt_check_all_stays->execute();
                    $stmt_check_all_stays->bind_result($remaining_stays);
                    $stmt_check_all_stays->fetch();
                    $stmt_check_all_stays->close();

                    if ($remaining_stays == 0) {
                        // ถ้าทุกห้องเช็คเอาท์หมดแล้ว ให้อัปเดตสถานะการจองเป็น 'เสร็จสมบูรณ์' (หรือสถานะที่เหมาะสม)
                        // สมมติว่า 'เสร็จสมบูรณ์' คือ Booking_status_Id = 7 (ตามที่คุณนิยามใน officerindex.php)
                        $status_id_completed = 7; 
                        $sql_update_reservation_status = "UPDATE reservation SET Booking_status_Id = ? WHERE Reservation_Id = ?";
                        $stmt_update_reservation_status = $conn->prepare($sql_update_reservation_status);
                        if ($stmt_update_reservation_status) {
                            $stmt_update_reservation_status->bind_param("is", $status_id_completed, $reservation_id);
                            $stmt_update_reservation_status->execute();
                            $stmt_update_reservation_status->close();
                        } else {
                            error_log("Failed to prepare reservation status update to completed: " . $conn->error);
                        }
                    }
                } else {
                    error_log("Failed to prepare check all stays checked out statement: " . $conn->error);
                }
            }
        } else {
            error_log("Failed to prepare get reservation_id from stay statement: " . $conn->error);
        }
        
        $conn->commit(); // ยืนยัน Transaction
        $_SESSION['message'] = "เช็คเอาท์รายการเข้าพัก #" . htmlspecialchars($stay_id) . " (ห้อง " . htmlspecialchars($room_id) . ") เรียบร้อยแล้ว.";
        header("Location: officerindex.php?section=checkout");
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // ยกเลิก Transaction หากเกิดข้อผิดพลาด
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเช็คเอาท์: " . $e->getMessage();
        header("Location: officerindex.php?section=checkout");
        exit();
    } finally {
        $conn->close();
    }
} else {
    header("Location: officerindex.php");
    exit();
}
?>