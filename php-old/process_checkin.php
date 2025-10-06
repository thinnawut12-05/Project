<?php
session_start();
include 'db.php';

// เปิดการแสดงข้อผิดพลาดสำหรับดีบัก
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ตรวจสอบ session เจ้าหน้าที่
if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php");
    exit();
}

// ฟังก์ชันสุ่ม Stay_Id 10 หลักแบบไม่ซ้ำ
function generateUniqueStayId($conn) {
    do {
        $stay_id = 'S' . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT); // S + 9 หลัก = 10
        $stmt = $conn->prepare("SELECT Stay_Id FROM stay WHERE Stay_Id = ?");
        $stmt->bind_param("s", $stay_id);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($exists);
    return $stay_id;
}

// ตรวจสอบ POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $guest_name = trim($_POST['guest_name']);
    $room_id = trim($_POST['room_id']);
    $check_in_date = $_POST['check_in_date'];
    $check_in_time = $_POST['check_in_time'];
    $check_out_date = !empty($_POST['check_out_date']) ? $_POST['check_out_date'] : null;
    $receipt_id = !empty($_POST['receipt_id_input']) ? intval($_POST['receipt_id_input']) : null;
    $reservation_id = $_GET['prefill_reservation_id'] ?? null;
    $email_member = !empty($_POST['email_member']) ? trim($_POST['email_member']) : null;
    $check_out_time = null; // เปลี่ยนจาก '00:00:00' เป็น NULL

    // ตรวจสอบ Room_Id
    if (empty($room_id)) {
        $_SESSION['error'] = "กรุณาเลือกห้องพัก";
        header("Location: officerindex.php?section=checkin");
        exit();
    }

    // ตรวจสอบว่ามีห้องนี้ในฐานข้อมูล
    $stmt_room = $conn->prepare("SELECT Room_ID FROM room WHERE Room_ID = ?");
    $stmt_room->bind_param("s", $room_id);
    $stmt_room->execute();
    $stmt_room->store_result();
    if ($stmt_room->num_rows === 0) {
        $_SESSION['error'] = "Room ID '{$room_id}' ไม่มีอยู่จริงในระบบ";
        $stmt_room->close();
        header("Location: officerindex.php?section=checkin");
        exit();
    }
    $stmt_room->close();

    // สร้าง Stay_Id แบบไม่ซ้ำ
    $stay_id = generateUniqueStayId($conn);

    try {
        $conn->begin_transaction();

        // Insert stay
        $sql = "INSERT INTO stay 
            (Stay_Id, Guest_name, Check_in_date, Check_in_time, Check_out_date, Check_out_time, Room_Id, Receipt_Id, Reservation_Id, Email_member)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare insert stay failed: " . $conn->error);

        $stmt->bind_param(
            "ssssssssss",
            $stay_id,
            $guest_name,
            $check_in_date,
            $check_in_time,
            $check_out_date,
            $check_out_time,
            $room_id,
            $receipt_id,
            $reservation_id,
            $email_member
        );

        if (!$stmt->execute()) throw new Exception("Insert stay failed: " . $stmt->error);
        $stmt->close();

        // อัปเดตสถานะ reservation หากเชื่อมโยง
        if ($reservation_id !== null && $reservation_id !== '-') {
            $stmt_res = $conn->prepare("UPDATE reservation SET Booking_status_Id = 4 WHERE Reservation_Id = ?");
            if (!$stmt_res) throw new Exception("Prepare update reservation failed: " . $conn->error);
            $stmt_res->bind_param("s", $reservation_id);
            if (!$stmt_res->execute()) throw new Exception("Update reservation failed: " . $stmt_res->error);
            $stmt_res->close();
        }

        $conn->commit();
        $_SESSION['message'] = "เช็คอินลูกค้า {$guest_name} เรียบร้อยแล้ว (Stay ID: {$stay_id})";
        header("Location: officerindex.php?section=current_stays");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("Location: officerindex.php?section=checkin");
        exit();
    } finally {
        $conn->close();
    }

} else {
    header("Location: officerindex.php");
    exit();
}
?>
