<?php
session_start();
include 'db.php'; // ตรวจสอบว่า db.php เชื่อมต่อฐานข้อมูลเรียบร้อย

// เปิดการแสดง error เพื่อช่วยในการ Debug (ควรปิดเมื่อใช้งานจริงบน Production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ตั้งค่า default timezone สำหรับ PHP เพื่อให้แน่ใจว่าเวลาถูกต้อง
date_default_timezone_set('Asia/Bangkok'); // กำหนด timezone เป็น Asia/Bangkok (สำคัญ!)

// --- สำคัญ: กำหนด SQL_MODE สำหรับการเชื่อมต่อนี้ให้ผ่อนปรนขึ้น ---
// สิ่งนี้จะช่วยแก้ปัญหา 'NO_ZERO_DATE' หรือ 'STRICT_TRANS_TABLES' ที่อาจทำให้เวลา 00:00:00
if ($conn) {
    // การตั้งค่านี้จะใช้ได้เฉพาะกับ session การเชื่อมต่อปัจจุบันเท่านั้น
    $conn->query("SET SESSION sql_mode = '';"); 
    // เพิ่มการ Log เพื่อตรวจสอบว่า SQL_MODE ถูกตั้งค่าจริงหรือไม่
    $current_sql_mode = $conn->query("SELECT @@session.sql_mode;")->fetch_row()[0];
    error_log("DEBUG: SQL_MODE set for session: " . $current_sql_mode);
}

// ตรวจสอบการเข้าสู่ระบบของเจ้าหน้าที่
if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php"); // ส่งกลับไปหน้า login หากไม่ได้เข้าสู่ระบบ
    exit();
}

// ข้อมูลเจ้าหน้าที่จาก Session
$officer_email = $_SESSION['Email_Officer'];
$officer_fname = $_SESSION['First_name'];
$officer_lname = $_SESSION['Last_name'];
$current_province_id = $_SESSION['Province_id']; // ID สาขาของเจ้าหน้าที่

// *** เพิ่มบรรทัดนี้เพื่อ Debug ค่า officer_email ที่ดึงมาจาก Session ***
error_log("DEBUG: Officer Email loaded from session: " . ($officer_email ?? 'NULL or EMPTY'));
// *******************************************************************

// ดึงชื่อจังหวัด/สาขา
$province_name = '';
$stmt_province = $conn->prepare("SELECT Province_name FROM province WHERE Province_ID = ?");
if ($stmt_province === false) {
    error_log("ERROR: Failed to prepare province name statement: " . $conn->error);
} else {
    $stmt_province->bind_param("i", $current_province_id);
    $stmt_province->execute();
    $result_province = $stmt_province->get_result();
    if ($result_province->num_rows > 0) {
        $province_data = $result_province->fetch_assoc();
        $province_name = $province_data['Province_name'];
    }
    $stmt_province->close();
}

$message = ''; // สำหรับแสดงข้อความแจ้งเตือน
if (isset($_SESSION['message'])) {
    $message = '<div class="alert success">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $message = '<div class="alert error">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

// กำหนด ID ของสถานะต่างๆ (ตามตาราง booking_status ที่คุณมี)
$status_id_pending_payment = 1; // ยืนยันการจองและรอชำระเงิน
$status_id_payment_pending_review = 2; // ชำระเงินสำเร็จรอการตรวจสอบ
$status_id_payment_confirmed = 3; // ชำระเงินสำเร็จ
$status_id_cancelled_timeout = 4; // ยกเลิกการจองเนื่องจากไม่ชำระเงินภายใน 24 ชม.
$status_id_cancelled_incomplete_payment = 5; // ยกเลิกการจองเนื่องจากชำระเงินไม่ครบภายใน 24 ชม.
$status_id_checked_in = 6; // เช็คอินแล้ว
$status_id_completed = 7; // เช็คเอาท์แล้ว
$status_id_no_show_penalized = 8; // ไม่มาเช็คอิน/ถูกปรับ (สถานะใหม่)


// --- ฟังก์ชันสำหรับสร้าง ID ที่ไม่ซ้ำกันและเป็นสตริงตัวเลข/ตัวอักษร 10 หลัก ---
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


// --- ส่วนจัดการ POST Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $redirect_url = "counter_operations.php"; // default redirect

    try {
        switch ($action) {
           case 'walk_in_check_in':
                $guest_name = trim($_POST['guest_name'] ?? '');
                $contact_phone = trim($_POST['contact_phone'] ?? '');
                $checkin_date = $_POST['checkin_date'] ?? date('Y-m-d');
                $checkout_date = $_POST['checkout_date'] ?? date('Y-m-d', strtotime('+1 day'));
                $num_adults = (int)($_POST['num_adults'] ?? 1);
                $num_children = (int)($_POST['num_children'] ?? 0);
                $selected_room_id = $_POST['selected_room_id'] ?? null;
                // room_type_id และ room_price ไม่ได้ใช้ในการคำนวณราคาสำหรับ Walk-in แล้ว แต่ยังคงรับค่าไว้ได้หากต้องการใช้ในส่วนอื่น
                // $room_type_id = $_POST['room_type_id'] ?? null;
                // $room_price = (float)($_POST['room_price'] ?? 0);

                if (empty($guest_name) || empty($contact_phone) || empty($selected_room_id)) {
                    throw new Exception("กรุณากรอกข้อมูลการเช็คอินหน้าเคาน์เตอร์ให้ครบถ้วน.");
                }

                $conn->begin_transaction();

                // คำนวณจำนวนวันเข้าพัก
                $checkin_datetime = new DateTime($checkin_date);
                $checkout_datetime = new DateTime($checkout_date);
                $interval = $checkin_datetime->diff($checkout_datetime);
                $num_days = $interval->days;

                if ($num_days <= 0) {
                    throw new Exception("วันที่เช็คเอาท์ต้องมากกว่าวันที่เช็คอินอย่างน้อย 1 วัน.");
                }

                $daily_rate = 930.00; // อัตราค่าห้องพักต่อคืน
                $calculated_total_price = $num_days * $daily_rate;

                // 1. สร้าง Reservation ID ใหม่สำหรับ Walk-in
                $reservation_id = generateUniqueId($conn, 'reservation', 'Reservation_Id');

                // 2. สร้างรายการจองในตาราง reservation
                $stmt_insert_reservation = $conn->prepare(
                    "INSERT INTO reservation (Reservation_Id, Guest_name, Number_of_rooms, Number_of_adults, Number_of_children,
                                            Booking_date, Check_out_date, Email_member, Province_Id, Booking_status_Id,
                                            Booking_time, Total_price)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)"
                );
                if ($stmt_insert_reservation === false) { throw new Exception("Failed to prepare reservation insert statement: " . $conn->error); }
                
                $generic_email_member = "walkin@example.com"; 
                $num_rooms = 1; // สำหรับ Walk-in ทีละห้อง

                $stmt_insert_reservation->bind_param(
                    "ssiiisssisd",
                    $reservation_id, $guest_name, $num_rooms, $num_adults, $num_children,
                    $checkin_date, $checkout_date, $generic_email_member, $current_province_id, $status_id_pending_payment, // สถานะเป็น รอชำระเงิน (1)
                    $calculated_total_price // ใช้ราคาที่คำนวณ
                );
                
                $stmt_insert_reservation->execute();
                if ($stmt_insert_reservation->affected_rows === 0) { throw new Exception("ไม่สามารถสร้างรายการจอง Walk-in ได้."); }
                $stmt_insert_reservation->close();

                // *** ณ จุดนี้ เราจะยังไม่สร้างรายการเข้าพัก (stay) และยังไม่เปลี่ยนสถานะห้องพัก
                // การดำเนินการเหล่านี้จะเกิดขึ้นหลังจากลูกค้าชำระเงินในหน้า payment_walkin.php

                $conn->commit();
                $_SESSION['message'] = "สร้างรายการจอง Walk-in สำเร็จแล้ว! โปรดดำเนินการชำระเงิน";
                
                // เปลี่ยนเส้นทางไปยังหน้าชำระเงิน Walk-in
                $redirect_url = "payment_walkin.php?reservation_id=" . urlencode($reservation_id) . 
                                "&amount=" . urlencode($calculated_total_price) .
                                "&room_id=" . urlencode($selected_room_id); // ส่ง room_id ไปด้วย
                break;

            case 'record_no_show_adjustment':
                $reservation_id_no_show = trim($_POST['reservation_id_no_show'] ?? '');
                $amount_no_show = (float)($_POST['amount_no_show'] ?? 0);
                $description_no_show = trim($_POST['description_no_show'] ?? 'ผู้เข้าพักไม่มาเช็คอินตามกำหนด');
                $adjustment_date = date('Y-m-d H:i:s'); // วันที่เวลาปัจจุบันที่บันทึกการปรับ

                if (empty($reservation_id_no_show) || $amount_no_show <= 0) {
                    throw new Exception("กรุณากรอกรหัสการจองและค่าปรับไม่มาเช็คอินให้ถูกต้อง.");
                }

                $conn->begin_transaction();

                // 1. อัปเดตสถานะการจองเป็นยกเลิก (no-show) (ใช้ ID 8)
                // และบันทึกข้อมูลการปรับ No-show ในตาราง reservation
                $stmt_update_reservation_no_show = $conn->prepare(
                    "UPDATE reservation SET 
                        Booking_status_Id = ?, 
                        Penalty_amount = ?, 
                        Penalty_reason = ?, 
                        Penalty_officer_email = ?, 
                        Penalty_date = ?
                    WHERE Reservation_Id = ? AND Province_Id = ? AND Booking_status_Id IN (?, ?, ?)" // ID 1, 2, 3
                );
                if ($stmt_update_reservation_no_show === false) { throw new Exception("Failed to prepare no-show reservation status update statement: " . $conn->error); }
                
                $stmt_update_reservation_no_show->bind_param(
                    "idsissiiii", // Booking_status_Id (i), amount (d), reason (s), officer_email (s), date (s), Reservation_Id (s), Province_Id (i), old_status_ids (iii)
                    $status_id_no_show_penalized, // ใช้สถานะใหม่สำหรับ No-Show (ID 8)
                    $amount_no_show, 
                    $description_no_show, 
                    $officer_email, 
                    $adjustment_date,
                    $reservation_id_no_show, 
                    $current_province_id,
                    $status_id_pending_payment, $status_id_payment_pending_review, $status_id_payment_confirmed
                );
                $stmt_update_reservation_no_show->execute();
                if ($stmt_update_reservation_no_show->affected_rows === 0) {
                    throw new Exception("ไม่พบการจอง หรือสถานะไม่ถูกต้องสำหรับ No-show (ต้องเป็นสถานะ 'ยืนยันการจองและรอชำระเงิน', 'ชำระเงินสำเร็จรอการตรวจสอบ' หรือ 'ชำระเงินสำเร็จ').");
                }
                $stmt_update_reservation_no_show->close();

                $conn->commit();
                $_SESSION['message'] = "บันทึกการปรับ No-show สำหรับการจอง #" . htmlspecialchars($reservation_id_no_show) . " สำเร็จแล้ว. ค่าปรับ: ฿" . number_format($amount_no_show, 2);
                
                // --- Redirect ไปหน้าจ่ายเงินค่าปรับ ---
                $redirect_url = "payment_adjustment.php?type=penalty&reservation_id=" . urlencode($reservation_id_no_show) . "&amount=" . urlencode($amount_no_show);
                break;

            case 'record_damage_adjustment':
                $room_id_damage = trim($_POST['room_id_damage'] ?? '');
                $amount_damage = (float)($_POST['amount_damage'] ?? 0);
                $description_damage = trim($_POST['description_damage'] ?? '');
                $damage_item = trim($_POST['damage_item'] ?? 'ของเสียหายทั่วไป'); // เพิ่มฟิลด์รายการของที่เสียหาย
                $adjustment_date = date('Y-m-d H:i:s'); // วันที่เวลาปัจจุบันที่บันทึกการปรับ

                // เพิ่มการตรวจสอบค่าก่อนดำเนินการ
                error_log("DEBUG: Processing damage adjustment for Room ID: " . $room_id_damage . ", Item: " . $damage_item . ", Value: " . $amount_damage . ", Date: " . $adjustment_date);
                // *** เพิ่มบรรทัดนี้เพื่อ Debug ค่า officer_email ก่อนการบันทึก ***
                error_log("DEBUG: Officer Email value for damage adjustment: " . ($officer_email ?? 'NULL or EMPTY'));
                // *******************************************************************


                if (empty($room_id_damage) || $amount_damage <= 0 || empty($description_damage) || empty($damage_item)) {
                    throw new Exception("กรุณากรอกรหัสห้องพัก, รายการ, รายละเอียดความเสียหาย และมูลค่าให้ถูกต้อง.");
                }

                // *** เพิ่มการตรวจสอบว่า officer_email มีค่าหรือไม่ ***
                if (empty($officer_email)) {
                    throw new Exception("ไม่สามารถระบุอีเมลเจ้าหน้าที่ผู้ทำรายการได้. กรุณาลองเข้าสู่ระบบอีกครั้ง.");
                }
                // ***************************************************

                $conn->begin_transaction();

                // 1. ตรวจสอบว่าห้องพักนั้นอยู่ในสาขาของเจ้าหน้าที่และดึง Stay_id ล่าสุดที่ยังไม่เช็คเอาท์
                $stmt_check_room_and_get_stay_id = $conn->prepare("
                    SELECT s.Stay_id 
                    FROM stay s
                    JOIN room r ON s.Room_id = r.Room_ID
                    WHERE r.Room_ID = ? AND r.Province_id = ? AND s.Check_out_date IS NULL
                    ORDER BY s.Check_in_date DESC, s.Check_in_time DESC LIMIT 1
                ");
                if ($stmt_check_room_and_get_stay_id === false) { throw new Exception("Failed to prepare room/stay check statement: " . $conn->error); }
                $stmt_check_room_and_get_stay_id->bind_param("si", $room_id_damage, $current_province_id);
                $stmt_check_room_and_get_stay_id->execute();
                $result_stay_id = $stmt_check_room_and_get_stay_id->get_result();
                $stay_id_for_damage = null;
                if ($row_stay = $result_stay_id->fetch_assoc()) {
                    $stay_id_for_damage = $row_stay['Stay_id'];
                }
                $stmt_check_room_and_get_stay_id->close();

                if (empty($stay_id_for_damage)) {
                    error_log("ERROR: No active stay found for room " . $room_id_damage . " in province " . $current_province_id);
                    throw new Exception("ไม่พบห้องพัก " . htmlspecialchars($room_id_damage) . " ที่กำลังเข้าพักในสาขาของคุณ (ต้องมี Stay ID เพื่อบันทึกความเสียหาย).");
                }
                error_log("DEBUG: Found Stay_Id for damage: " . $stay_id_for_damage);


                // 2. บันทึกข้อมูลการปรับความเสียหายลงในตาราง room_damages
                // เพิ่ม Damage_date เข้าไปในคำสั่ง INSERT
                $stmt_insert_damage = $conn->prepare(
                    "INSERT INTO room_damages (Stay_Id, Room_Id, Damage_item, Damage_description, Damage_value, Damage_date, Officer_Email)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                if ($stmt_insert_damage === false) { 
                    error_log("ERROR: Failed to prepare room damage insert statement: " . $conn->error);
                    throw new Exception("Failed to prepare room damage insert statement: " . $conn->error); 
                }
                
                // เพิ่ม 's' สำหรับ Damage_date ($adjustment_date) ใน bind_param
                $stmt_insert_damage->bind_param(
                    "sssdsss", // Stay_Id (s), Room_Id (s), Damage_item (s), Description (s), Value (d), Damage_date (s), Officer_Email (s)
                    $stay_id_for_damage, 
                    $room_id_damage, 
                    $damage_item,
                    $description_damage, 
                    $amount_damage,
                    $adjustment_date, // เพิ่มตัวแปรนี้เข้ามา
                    $officer_email
                );
                $stmt_insert_damage->execute();
                if ($stmt_insert_damage->affected_rows === 0) { 
                    error_log("ERROR: Could not insert room damage for room " . $room_id_damage . ". MySQL Error: " . $stmt_insert_damage->error);
                    throw new Exception("ไม่สามารถบันทึกการปรับความเสียหายสำหรับห้อง " . htmlspecialchars($room_id_damage) . " ได้. " . $stmt_insert_damage->error); 
                }
                $stmt_insert_damage->close();

                $conn->commit();
                $_SESSION['message'] = "บันทึกการปรับความเสียหายสำหรับห้อง " . htmlspecialchars($room_id_damage) . " สำเร็จแล้ว. มูลค่า: ฿" . number_format($amount_damage, 2);
                
                // --- Redirect ไปหน้าจ่ายเงินค่าเสียหาย ---
                $redirect_url = "payment_adjustment.php?type=damage&stay_id=" . urlencode($stay_id_for_damage) . "&room_id=" . urlencode($room_id_damage) . "&amount=" . urlencode($amount_damage);
                break;
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        error_log("ERROR: counter_operations.php - " . $e->getMessage()); // บันทึก error ลง log file
    }
    header("Location: " . $redirect_url); // Redirect ไปยัง URL ที่กำหนดไว้
    exit();
}


// --- ดึงข้อมูลการเข้าพักปัจจุบัน (Active Stays) สำหรับเจ้าหน้าที่สาขานั้นๆ ---
$active_stays = [];
$sql_active_stays = "SELECT 
                        s.Stay_id, s.Guest_name, s.Room_id, s.Check_in_date, s.Check_in_time, 
                        s.Check_out_date, s.Check_out_time, s.Reservation_Id, 
                        r.Room_number, rt.Room_type_name, bs.Booking_status_name
                     FROM stay s
                     JOIN reservation res ON s.Reservation_Id = res.Reservation_Id
                     JOIN room r ON s.Room_id = r.Room_ID
                     JOIN room_type rt ON r.Room_type_Id = rt.Room_type_Id
                     JOIN booking_status bs ON res.Booking_status_Id = bs.Booking_status_Id
                     WHERE r.Province_id = ? AND s.Check_out_date IS NULL
                     ORDER BY s.Check_in_date ASC, s.Check_in_time ASC";
$stmt_active_stays = $conn->prepare($sql_active_stays);
if ($stmt_active_stays === false) {
    error_log("ERROR: Failed to prepare active stays select statement: " . $conn->error);
} else {
    $stmt_active_stays->bind_param("i", $current_province_id);
    $stmt_active_stays->execute();
    $result_active_stays = $stmt_active_stays->get_result();
    while ($row = $result_active_stays->fetch_assoc()) {
        $active_stays[] = $row;
    }
    error_log("DEBUG: Active Stays fetched: " . json_encode($active_stays));
    $stmt_active_stays->close();
}


// --- ดึงห้องว่างสำหรับ Walk-in Check-in Form ---
$available_rooms = [];
$sql_available_rooms = "SELECT r.Room_ID, r.Room_number, rt.Room_type_name, r.Price AS Room_Type_Price, rt.Room_type_Id
                        FROM room r
                        JOIN room_type rt ON r.Room_type_Id = rt.Room_type_Id
                        WHERE r.Province_id = ? AND r.Status = 'AVL'
                        ORDER BY rt.Room_type_name ASC, r.Room_number ASC";
$stmt_avail_rooms = $conn->prepare($sql_available_rooms);
if ($stmt_avail_rooms === false) {
    error_log("ERROR: Failed to prepare available rooms select statement: " . $conn->error);
} else {
    $stmt_avail_rooms->bind_param("i", $current_province_id);
    $stmt_avail_rooms->execute();
    $result_avail_rooms = $stmt_avail_rooms->get_result();
    while ($row = $result_avail_rooms->fetch_assoc()) {
        $available_rooms[] = $row;
    }
    $stmt_avail_rooms->close();
}

$conn->close();

// กำหนดวันที่ปัจจุบันสำหรับ PHP เพื่อส่งไปใช้ใน JavaScript
$today_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การปฏิบัติงานเคาน์เตอร์ - Dom Inn Hotel (สาขา: <?= htmlspecialchars($province_name) ?>)</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f6;
            color: #333;
            line-height: 1.6;
        }

        header {
            background-color: #007bff;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            margin: 0;
            font-size: 1.8em;
        }

        .user-info {
            font-size: 1em;
        }

        .user-info a {
            color: #ffc107;
            text-decoration: none;
            margin-left: 15px;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .user-info a:hover {
            color: #fff;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 30px;
            font-size: 2em;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: fadeIn 0.5s ease-out;
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* Tabs Styling */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .tab-button {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-bottom: none;
            padding: 12px 20px;
            cursor: pointer;
            font-weight: bold;
            color: #6c757d;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            transition: all 0.3s ease;
        }

        .tab-button:hover:not(.active) {
            background-color: #e2f3ff;
            color: #007bff;
        }

        .tab-button.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
            transform: translateY(2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .tab-content {
            padding: 20px;
            border: 1px solid #e9ecef;
            border-top: none;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            display: none;
            animation: fadeInContent 0.5s;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeInContent {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* Form & Table Base Styles */
        .form-group,
        .form-row {
            margin-bottom: 15px;
        }

        .form-group label,
        .form-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            font-size: 0.95em;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 0.95em;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row>div {
            flex: 1;
        }

        button[type="submit"] {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #218838;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #dee2e6;
            padding: 10px 12px;
            text-align: left;
            vertical-align: middle;
            font-size: 0.9em;
        }

        .data-table th {
            background-color: #e9ecef;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
        }

        .data-table tbody tr:nth-child(odd) {
            background-color: #fcfdfe;
        }

        .data-table tbody tr:hover {
            background-color: #e2f3ff;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.8em;
            white-space: nowrap;
        }

        .status-เช็คอินแล้ว {
            background-color: #007bff;
            color: white;
        }

        .status-เช็คเอาท์แล้ว,
        .status-เสร็จสมบูรณ์ {
            background-color: #6c757d;
            color: white;
        }

        .status-ชำระเงินสำเร็จ {
            background-color: #28a745;
            color: white;
        }

        .status-ยืนยันการจองและรอชำระเงิน {
            background-color: #ffc107;
            color: #333;
        }

        .status-ยกเลิกการจองเนื่องจากไม่ชำระเงินภายใน-24-ชม
        .status-ยกเลิกการจองเนื่องจากชำระเงินไม่ครบภายใน-24-ชม{
            background-color: #dc3545;
            color: white;
        }
        
        /* New Status Styles for No-Show Penalty */
        .status-ไม่มาเช็คอิน-ถูกปรับ { /* ใช้ชื่อ status ที่ถูกต้องที่ replace ' ' ด้วย '-' */
            background-color: #ff6347; /* Tomato red */
            color: white;
        }
    </style>
</head>

<body>
    <header>
        <h1>ระบบจัดการโรงแรม - การปฏิบัติงานเคาน์เตอร์</h1>
        <div class="user-info">
            สวัสดี, <?= htmlspecialchars($officer_fname . " " . $officer_lname); ?>
            (สาขา: <?= htmlspecialchars($province_name); ?>)
            <!-- <a href="index.php">ออกจากระบบ</a> -->
            <a href="officer.php">กลับหน้าหลักเจ้าหน้าที่</a>
        </div>
    </header>

    <main class="container">
        <h2>การจัดการเคาน์เตอร์</h2>

        <?= $message ?>

        <div class="tabs">
            <button class="tab-button active" onclick="openTab(event, 'current_stays')">รายการการเข้าพักปัจจุบัน</button>
            <button class="tab-button" onclick="openTab(event, 'walk_in_check_in')">เช็คอินลูกค้าหน้าเคาน์เตอร์</button>
            <button class="tab-button" onclick="openTab(event, 'adjustments')">แจ้งปรับ</button>
        </div>

        <!-- Tab Content: Current Stays -->
        <div id="current_stays" class="tab-content active">
            <h3>รายการการเข้าพักปัจจุบันและกำลังจะมาถึง</h3>
            <?php if (empty($active_stays)): ?>
                <p style="text-align: center; color: #666;">ไม่พบรายการเข้าพักที่กำลังใช้งานในขณะนี้</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>รหัสเข้าพัก</th>
                                <th>รหัสการจอง</th>
                                <th>ชื่อลูกค้า</th>
                                <th>ห้องพัก</th>
                                <th>ประเภทห้อง</th>
                                <th>เช็คอิน</th>
                                <th>เช็คเอาท์ (คาดการณ์)</th>
                                <th>สถานะการจอง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_stays as $stay): ?>
                                <tr>
                                    <td><?= htmlspecialchars($stay['Stay_id']) ?></td>
                                    <td><?= htmlspecialchars($stay['Reservation_Id']) ?></td>
                                    <td><?= htmlspecialchars($stay['Guest_name']) ?></td>
                                    <td><?= htmlspecialchars($stay['Room_number']) ?></td>
                                    <td><?= htmlspecialchars($stay['Room_type_name']) ?></td>
                                    <td><?= htmlspecialchars($stay['Check_in_date']) ?> <br> <?= htmlspecialchars($stay['Check_in_time']) ?></td>
                                    <td>
                                        <?php if ($stay['Check_out_date']): ?>
                                            <?= htmlspecialchars($stay['Check_out_date']) ?> <br> <?= htmlspecialchars($stay['Check_out_time'] ?? '00:00:00') ?>
                                        <?php else: ?>
                                            ยังไม่เช็คเอาท์
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="status-badge status-<?= str_replace(' ', '-', htmlspecialchars($stay['Booking_status_name'])) ?>"><?= htmlspecialchars($stay['Booking_status_name']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab Content: Walk-in Check-in -->
        <div id="walk_in_check_in" class="tab-content">
            <h3>บันทึกการเช็คอินลูกค้า Walk-in</h3>
            <form action="counter_operations.php" method="POST">
                <input type="hidden" name="action" value="walk_in_check_in">
                <input type="hidden" name="room_type_id" id="walkInRoomTypeId">
                <input type="hidden" name="room_price" id="walkInRoomPrice">

                <div class="form-group">
                    <label for="guest_name">ชื่อลูกค้า: <span style="color:red;">*</span></label>
                    <input type="text" id="guest_name" name="guest_name" required>
                </div>
                <div class="form-group">
                    <label for="contact_phone">เบอร์โทรศัพท์: <span style="color:red;">*</span></label>
                    <input type="tel" id="contact_phone" name="contact_phone" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="checkin_date">วันที่เช็คอิน: <span style="color:red;">*</span></label>
                        <input type="date" id="checkin_date" name="checkin_date" value="<?= $today_date ?>" min="<?= $today_date ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="checkout_date">วันที่เช็คเอาท์ (คาดการณ์): <span style="color:red;">*</span></label>
                        <input type="date" id="checkout_date" name="checkout_date" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="num_adults">จำนวนผู้ใหญ่: <span style="color:red;">*</span></label>
                        <input type="number" id="num_adults" name="num_adults" value="1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="num_children">จำนวนเด็ก:</label>
                        <input type="number" id="num_children" name="num_children" value="0" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label for="selected_room_id">เลือกห้องว่าง: <span style="color:red;">*</span></label>
                    <select id="selected_room_id" name="selected_room_id" required onchange="updateWalkInRoomDetails()">
                        <option value="">-- เลือกห้องพัก --</option>
                        <?php foreach ($available_rooms as $room): ?>
                            <option value="<?= htmlspecialchars($room['Room_ID']) ?>"
                                    data-room-type-id="<?= htmlspecialchars($room['Room_type_Id']) ?>"
                                    data-room-price="<?= htmlspecialchars($room['Room_Type_Price']) ?>">
                                ห้อง <?= htmlspecialchars($room['Room_number']) ?> (<?= htmlspecialchars($room['Room_type_name']) ?>) - ฿<?= number_format($room['Room_Type_Price'], 2) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">บันทึกเช็คอิน Walk-in</button>
            </form>
        </div>

        <!-- Tab Content: Adjustments -->
        <div id="adjustments" class="tab-content">
            <h3>แจ้งปรับ</h3>

            <h4>แจ้งปรับผู้เข้าพักที่มาเช็คอินล่าช้า</h4>
            <form action="counter_operations.php" method="POST">
                <input type="hidden" name="action" value="record_no_show_adjustment">
                <div class="form-group">
                    <label for="reservation_id_no_show">รหัสการจอง (ที่ No-show): <span style="color:red;">*</span></label>
                    <input type="text" id="reservation_id_no_show" name="reservation_id_no_show" required>
                </div>
                <div class="form-group">
                    <label for="amount_no_show">จำนวนเงินค่าปรับ (฿): <span style="color:red;">*</span></label>
                    <input type="number" id="amount_no_show" name="amount_no_show" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="description_no_show">รายละเอียด (ไม่จำเป็น):</label>
                    <textarea id="description_no_show" name="description_no_show" rows="2">ผู้เข้าพักไม่มาเช็คอินตามกำหนด</textarea>
                </div>
                <button type="submit">บันทึกปรับ</button>
            </form>

            <h4 style="margin-top: 30px;">แจ้งปรับความเสียหายในห้องพัก</h4>
            <form action="counter_operations.php" method="POST">
                <input type="hidden" name="action" value="record_damage_adjustment">
                <div class="form-group">
                    <label for="room_id_damage">รหัสห้องพัก (ที่พบเสียหาย): <span style="color:red;">*</span></label>
                    <input type="text" id="room_id_damage" name="room_id_damage" required>
                </div>
                <div class="form-group">
                    <label for="damage_item">รายการของที่เสียหาย: <span style="color:red;">*</span></label>
                    <input type="text" id="damage_item" name="damage_item" required>
                </div>
                <div class="form-group">
                    <label for="description_damage">รายละเอียดความเสียหาย: <span style="color:red;">*</span></label>
                    <textarea id="description_damage" name="description_damage" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="amount_damage">มูลค่าความเสียหาย (฿): <span style="color:red;">*</span></label>
                    <input type="number" id="amount_damage" name="amount_damage" step="0.01" min="0.01" required>
                </div>
                <button type="submit">บันทึกปรับความเสียหาย</button>
            </form>
        </div>
    </main>

    <script>
        // Tab functionality
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove('active');
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add('active');
            evt.currentTarget.classList.add("active");
        }

        // Default open first tab on load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.tab-button').click(); // Clicks the first tab

            // --- Date Picker Logic for Walk-in Check-in ---
            const checkinDateInput = document.getElementById('checkin_date');
            const checkoutDateInput = document.getElementById('checkout_date');

            const today = new Date();
            const todayFormatted = today.toISOString().split('T')[0]; // YYYY-MM-DD

            // Ensure check-in date cannot be in the past
            checkinDateInput.setAttribute('min', todayFormatted);

            // Set initial min for checkout date (at least tomorrow)
            const tomorrow = new Date(today);
            tomorrow.setDate(today.getDate() + 1);
            const tomorrowFormatted = tomorrow.toISOString().split('T')[0];
            checkoutDateInput.setAttribute('min', tomorrowFormatted);

            // Update checkout min date when check-in date changes
            checkinDateInput.addEventListener('change', function() {
                let selectedCheckinDate = new Date(this.value);
                let nextDay = new Date(selectedCheckinDate);
                nextDay.setDate(selectedCheckinDate.getDate() + 1);
                
                const nextDayFormatted = nextDay.toISOString().split('T')[0];
                checkoutDateInput.setAttribute('min', nextDayFormatted);

                // If current checkout date is before the new minimum checkout date, update it
                if (checkoutDateInput.value < nextDayFormatted) {
                    checkoutDateInput.value = nextDayFormatted;
                }
            });
            // --- End Date Picker Logic ---
        });

        // Update hidden room details for walk-in form
        function updateWalkInRoomDetails() {
            var select = document.getElementById('selected_room_id');
            var selectedOption = select.options[select.selectedIndex];
            document.getElementById('walkInRoomTypeId').value = selectedOption.dataset.roomTypeId;
            document.getElementById('walkInRoomPrice').value = selectedOption.dataset.roomPrice;
        }
    </script>
</body>

</html>