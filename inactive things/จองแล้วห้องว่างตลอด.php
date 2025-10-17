<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่า db.php อยู่ในตำแหน่งที่ถูกต้อง

// เปิดการแสดง error เพื่อช่วยในการ Debug (ควรปิดเมื่อใช้งานจริงบน Production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// ดึงรายการสถานะการจองทั้งหมดสำหรับ dropdown filter และ update
$booking_statuses = [];
$stmt_statuses = $conn->prepare("SELECT Booking_status_Id, Booking_status_name FROM booking_status ORDER BY Booking_status_Id ASC");
if ($stmt_statuses === false) {
    error_log("ERROR: Failed to prepare booking_status select statement: " . $conn->error);
} else {
    $stmt_statuses->execute();
    $result_statuses = $stmt_statuses->get_result();
    while ($row = $result_statuses->fetch_assoc()) {
        $booking_statuses[] = $row;
    }
    $stmt_statuses->close();
}


// กำหนด ID ของสถานะต่างๆ
$status_id_pending_payment = 1; // ยืนยันการจองและรอชำระเงิน
$status_id_payment_pending_review = 2; // ชำระเงินสำเร็จรอการตรวจสอบ
$status_id_payment_confirmed = 3; // ชำระเงินสำเร็จ
$status_id_cancelled_timeout = 4; // ยกเลิกการจองเนื่องจากไม่ชำระเงินภายใน 24 ชม.
$status_id_cancelled_incomplete_payment = 5; // ยกเลิกการจองเนื่องจากชำระเงินไม่ครบภายใน 24 ชม.
$status_id_checked_in = 6; // เช็คอินแล้ว
$status_id_completed = 7; // เสร็จสมบูรณ์ (หรือ "เช็คเอาท์แล้ว" ตามที่คุณเปลี่ยนชื่อ)


// --- ฟังก์ชันสำหรับสร้าง ID ที่ไม่ซ้ำกันและเป็นสตริงตัวเลข/ตัวอักษร (สำหรับ Stay_id) ---
function generateUniqueStayId($conn, $table = 'stay', $idColumn = 'Stay_id')
{
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
            if ($check_stmt->num_rows == 0) {
                $isUnique = true;
            }
            $check_stmt->close();
        } else {
            error_log("ERROR: generateUniqueStayId - Failed to prepare unique ID check statement for $table.$idColumn: " . $conn->error);
            throw new Exception("Error checking for unique ID for Stay_id.");
        }
    }
    if (!$isUnique) {
        error_log("CRITICAL ERROR: generateUniqueStayId - Failed to generate a unique ID for $table.$idColumn after $maxAttempts attempts.");
        throw new Exception("Error: Could not generate a unique Stay ID.");
    }
    return $newId;
}


// --- ส่วนจัดการ POST Request สำหรับการดำเนินการ (Update Status, Check-in, Check-out, Cancel) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $reservation_id = $_POST['reservation_id'] ?? null;

    if ($reservation_id) {
        try {
            switch ($action) {
                case 'update_status':
                    $new_status_id = $_POST['new_status_id'] ?? null;
                    if ($new_status_id) {
                        $stmt = $conn->prepare("UPDATE reservation SET Booking_status_Id = ? WHERE Reservation_Id = ? AND Province_Id = ?");
                        if ($stmt === false) {
                            throw new Exception("Failed to prepare update_status statement: " . $conn->error);
                        }
                        $stmt->bind_param("isi", $new_status_id, $reservation_id, $current_province_id);
                        if ($stmt->execute()) {
                            $_SESSION['message'] = "อัปเดตสถานะการจอง #" . htmlspecialchars($reservation_id) . " สำเร็จแล้ว.";
                        } else {
                            throw new Exception("เกิดข้อผิดพลาดในการอัปเดตสถานะ: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                    break;

                case 'check_in_booking':
                    $checkin_date = $_POST['checkin_date'] ?? date('Y-m-d');
                    $checkin_time = $_POST['checkin_time'] ?? date('H:i:s');
                    $num_rooms_booked = $_POST['num_rooms_booked'] ?? 1;
                    $selected_room_ids = isset($_POST['selected_room_ids']) && is_array($_POST['selected_room_ids']) ? $_POST['selected_room_ids'] : [];

                    if (count($selected_room_ids) != $num_rooms_booked) {
                        $_SESSION['error'] = "กรุณาเลือกห้องพักให้ครบตามจำนวนที่จอง (" . $num_rooms_booked . " ห้อง).";
                        break;
                    }

                    $conn->begin_transaction();
                    try {
                        $stmt_get_reservation_details = $conn->prepare("SELECT Email_member, Guest_name, Receipt_id FROM reservation WHERE Reservation_Id = ?");
                        if ($stmt_get_reservation_details === false) {
                            throw new Exception("Failed to prepare reservation details fetch statement: " . $conn->error);
                        }
                        $stmt_get_reservation_details->bind_param("s", $reservation_id);
                        $stmt_get_reservation_details->execute();
                        $result_reservation_details = $stmt_get_reservation_details->get_result();
                        $member_email = null;
                        $guest_name_from_reservation = null;
                        $receipt_id_from_reservation = null;
                        if ($row_reservation_details = $result_reservation_details->fetch_assoc()) {
                            $member_email = $row_reservation_details['Email_member'];
                            $guest_name_from_reservation = $row_reservation_details['Guest_name'];
                            $receipt_id_from_reservation = $row_reservation_details['Receipt_id'];
                        } else {
                            throw new Exception("ไม่พบข้อมูลการจองสำหรับ Reservation ID: " . htmlspecialchars($reservation_id));
                        }
                        $stmt_get_reservation_details->close();

                        $receipt_id_for_stay = (!empty($receipt_id_from_reservation) && is_numeric($receipt_id_from_reservation)) ? (int)$receipt_id_from_reservation : NULL;

                        $valid_pre_checkin_status_ids = [$status_id_pending_payment, $status_id_payment_pending_review, $status_id_payment_confirmed];
                        $valid_pre_checkin_placeholders = implode(',', array_fill(0, count($valid_pre_checkin_status_ids), '?'));
                        $valid_pre_checkin_types = str_repeat('i', count($valid_pre_checkin_status_ids));

                        $stmt_update_booking = $conn->prepare(
                            "UPDATE reservation SET Booking_status_Id = ? 
                            WHERE Reservation_Id = ? AND Province_Id = ? AND Booking_status_Id IN ($valid_pre_checkin_placeholders)"
                        );
                        if ($stmt_update_booking === false) {
                            throw new Exception("Failed to prepare booking status update statement: " . $conn->error);
                        }

                        $bind_params_booking_update = array_merge(
                            [$status_id_checked_in, $reservation_id, $current_province_id],
                            $valid_pre_checkin_status_ids
                        );
                        $bind_types_booking_update = 'isi' . $valid_pre_checkin_types;
                        call_user_func_array([$stmt_update_booking, 'bind_param'], array_merge([$bind_types_booking_update], $bind_params_booking_update));
                        $stmt_update_booking->execute();
                        if ($stmt_update_booking->affected_rows === 0) {
                            throw new Exception("ไม่พบการจอง หรือสถานะไม่ถูกต้องสำหรับเช็คอิน (ต้องเป็นสถานะ 'ยืนยันการจองและรอชำระเงิน', 'ชำระเงินสำเร็จรอการตรวจสอบ' หรือ 'ชำระเงินสำเร็จ').");
                        }
                        $stmt_update_booking->close();

                        foreach ($selected_room_ids as $room_id) {
                            $stmt_check_room = $conn->prepare("SELECT Status FROM room WHERE Room_ID = ? AND Province_id = ?");
                            if ($stmt_check_room === false) {
                                throw new Exception("Failed to prepare room status check statement: " . $conn->error);
                            }
                            $stmt_check_room->bind_param("si", $room_id, $current_province_id);
                            $stmt_check_room->execute();
                            $result_check_room = $stmt_check_room->get_result();
                            $room_status_data = $result_check_room->fetch_assoc();
                            $stmt_check_room->close();

                            if (!$room_status_data || $room_status_data['Status'] !== 'AVL') {
                                throw new Exception("ห้องพัก " . htmlspecialchars($room_id) . " ไม่ว่างหรือไม่ถูกต้อง (หรือไม่อยู่ในสาขา).");
                            }

                            $new_stay_id = generateUniqueStayId($conn);

                            $stmt_insert_stay = $conn->prepare("INSERT INTO stay (Stay_id, Room_id, Guest_name, Check_in_date, Check_in_time, Check_out_date, Check_out_time, Reservation_Id, Email_member, Receipt_Id) VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?)");
                            if ($stmt_insert_stay === false) {
                                error_log("Failed to prepare stay insert statement: " . $conn->error);
                                throw new Exception("Failed to prepare stay insert statement: " . $conn->error);
                            }
                            $stmt_insert_stay->bind_param("sssssssi", $new_stay_id, $room_id, $guest_name_from_reservation, $checkin_date, $checkin_time, $reservation_id, $member_email, $receipt_id_for_stay);
                            $stmt_insert_stay->execute();
                            if ($stmt_insert_stay->affected_rows === 0) {
                                throw new Exception("ไม่สามารถสร้างรายการเข้าพักสำหรับห้อง " . htmlspecialchars($room_id) . " ได้.");
                            }
                            $stmt_insert_stay->close();
                        }

                        $conn->commit();
                        $_SESSION['message'] = "เช็คอินการจอง #" . htmlspecialchars($reservation_id) . " และห้องพักสำเร็จแล้ว.";
                    } catch (Exception $e) {
                        $conn->rollback();
                        throw $e;
                    }
                    break;

                case 'check_out_booking':
                    // ตั้งค่า default timezone สำหรับ PHP เพื่อให้แน่ใจว่าเวลาถูกต้อง
                    date_default_timezone_set('Asia/Bangkok'); // หรือ timezone ที่เหมาะสมกับเซิร์ฟเวอร์ของคุณ

                    $checkout_date = date('Y-m-d');
                    $checkout_time = date('H:i:s'); // ค่าเวลาปัจจุบัน

                    // --- DEBUG LOGGING: เพิ่มการบันทึกค่าที่สำคัญก่อนดำเนินการ ---
                    error_log("DEBUG Check-out: Reservation_Id=" . $reservation_id);
                    error_log("  PHP Timezone: " . date_default_timezone_get());
                    error_log("  PHP generated checkout_date: " . $checkout_date);
                    error_log("  PHP generated checkout_time: " . $checkout_time); // ค่านี้คือที่ PHP สร้างขึ้นและจะถูกส่ง
                    // --- END DEBUG LOGGING ---

                    // อัปเดตสถานะการจองในตาราง 'reservation' เป็น 'เสร็จสมบูรณ์' (Booking_status_Id = 7)
                    $stmt_update_reservation_status = $conn->prepare(
                        "UPDATE reservation SET Booking_status_Id = ? 
                        WHERE Reservation_Id = ? AND Province_Id = ? AND Booking_status_Id = ?"
                    );
                    if ($stmt_update_reservation_status === false) {
                        throw new Exception("Failed to prepare booking status update (Completed) statement: " . $conn->error);
                    }
                    $stmt_update_reservation_status->bind_param("isii", $status_id_completed, $reservation_id, $current_province_id, $status_id_checked_in);
                    $stmt_update_reservation_status->execute();
                    if ($stmt_update_reservation_status->affected_rows === 0) {
                        throw new Exception("ไม่พบการจอง หรือสถานะไม่ถูกต้องสำหรับเช็คเอาท์ (ต้องเป็นสถานะ 'เช็คอินแล้ว').");
                    }
                    $stmt_update_reservation_status->close();

                    $conn->begin_transaction();
                    try {
                        // อัปเดต Check_out_date และ Check_out_time ในตาราง 'stay'
                        $stmt_update_stay = $conn->prepare(
                            "UPDATE stay 
                            SET Check_out_date = ?, Check_out_time = ? 
                            WHERE Reservation_Id = ? AND Check_out_date IS NULL AND Check_in_date IS NOT NULL"
                        );
                        if ($stmt_update_stay === false) {
                            throw new Exception("Failed to prepare stay update statement: " . $conn->error);
                        }

                        // --- DEBUG LOGGING: ตรวจสอบค่าที่จะ bind_param สำหรับ UPDATE stay ---
                        $params_to_bind_stay = [$checkout_date, $checkout_time, $reservation_id];
                        error_log("  Attempting to bind parameters for UPDATE stay: " . json_encode($params_to_bind_stay));
                        error_log("  Types for stay update: sss");
                        // --- END DEBUG LOGGING ---

                        // ผูกพารามิเตอร์: s (checkout_date), s (checkout_time), s (reservation_id)
                        $stmt_update_stay->bind_param("sss", $checkout_date, $checkout_time, $reservation_id);
                        $stmt_update_stay->execute();

                        if ($stmt_update_stay->affected_rows === 0) {
                            error_log("WARNING: No active stay records updated for Reservation_Id #" . htmlspecialchars($reservation_id) . ". This might be expected if all rooms were already checked out manually or if the conditions were not met.");
                        } else {
                            error_log("INFO: Successfully updated " . $stmt_update_stay->affected_rows . " stay records for Reservation_Id #" . htmlspecialchars($reservation_id) . ". Check_out_time should now be: " . $checkout_time);
                        }
                        $stmt_update_stay->close();

                        $conn->commit();
                        $_SESSION['message'] = "เช็คเอ้าท์การจอง #" . htmlspecialchars($reservation_id) . " และห้องพักสำเร็จแล้ว.";
                    } catch (Exception $e) {
                        $conn->rollback();
                        throw $e; // ส่ง Exception ต่อไปให้ catch หลัก
                    }
                    break;

                case 'cancel_booking':
                    $valid_pre_cancel_status_ids = [
                        $status_id_pending_payment,
                        $status_id_payment_pending_review,
                        $status_id_payment_confirmed,
                        $status_id_checked_in
                    ];
                    $valid_pre_cancel_placeholders = implode(',', array_fill(0, count($valid_pre_cancel_status_ids), '?'));
                    $valid_pre_cancel_types = str_repeat('i', count($valid_pre_cancel_status_ids));

                    $cancel_target_status_id = $status_id_cancelled_timeout;

                    $stmt = $conn->prepare(
                        "UPDATE reservation SET Booking_status_Id = ? 
                        WHERE Reservation_Id = ? AND Province_Id = ? AND Booking_status_Id IN ($valid_pre_cancel_placeholders)"
                    );
                    if ($stmt === false) {
                        throw new Exception("Failed to prepare booking status update (Cancelled) statement: " . $conn->error);
                    }

                    $bind_params_cancel_update = array_merge(
                        [$cancel_target_status_id, $reservation_id, $current_province_id],
                        $valid_pre_cancel_status_ids
                    );
                    $bind_types_cancel_update = 'isi' . $valid_pre_cancel_types;

                    call_user_func_array([$stmt, 'bind_param'], array_merge([$bind_types_cancel_update], $bind_params_cancel_update));

                    if ($stmt->execute()) {
                        if ($stmt->affected_rows === 0) {
                            throw new Exception("ไม่พบการจอง หรือสถานะไม่ถูกต้องสำหรับการยกเลิก.");
                        }
                        $_SESSION['message'] = "ยกเลิกการจอง #" . htmlspecialchars($reservation_id) . " สำเร็จแล้ว.";
                    } else {
                        throw new Exception("เกิดข้อผิดพลาดในการยกเลิกการจอง: " . $stmt->error);
                    }
                    $stmt->close();
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
            error_log("ERROR: customer_reception.php - " . $e->getMessage()); // บันทึก error ลง log file
        }
    }
    header("Location: customer_reception.php");
    exit();
}


// --- ส่วนดึงข้อมูลการจองเพื่อแสดงผล (พร้อม Filter) ---
$filter_status_id = $_GET['filter_status_id'] ?? null;
$search_query = $_GET['search_query'] ?? '';
$search_type = $_GET['search_type'] ?? 'guest_name';

$sql_bookings = "SELECT r.Reservation_Id, r.Guest_name, r.Number_of_rooms, r.Number_of_adults, r.Number_of_children,
                        r.Booking_date AS Check_in_date_Reserved, r.Check_out_date, r.Total_price, 
                        bs.Booking_status_name, r.Booking_status_Id, r.Booking_time, " . // เพิ่ม r.Booking_status_Id
    "r.Email_member, r.Receipt_id, " .
    "NULL AS Room_types_booked, " .
    "(SELECT COUNT(s.Stay_id) FROM stay s WHERE s.Reservation_Id = r.Reservation_Id AND s.Check_out_date IS NULL) AS current_stays_count
                 FROM reservation r
                 JOIN booking_status bs ON r.Booking_status_Id = bs.Booking_status_Id
                 WHERE r.Province_Id = ? ";

$params = [$current_province_id];
$param_types = 'i';

if ($filter_status_id && $filter_status_id != 'all') {
    $sql_bookings .= " AND r.Booking_status_Id = ?";
    $params[] = $filter_status_id;
    $param_types .= 'i';
}

if (!empty($search_query)) {
    $search_query_like = '%' . $search_query . '%';
    if ($search_type === 'booking_id') {
        $sql_bookings .= " AND r.Reservation_Id LIKE ?";
        $params[] = $search_query_like;
        $param_types .= 's';
    } elseif ($search_type === 'guest_name') {
        $sql_bookings .= " AND r.Guest_name LIKE ?";
        $params[] = $search_query_like;
        $param_types .= 's';
    }
}

$sql_bookings .= " GROUP BY r.Reservation_Id ORDER BY r.Booking_date DESC, r.Booking_time DESC";

$stmt_bookings = $conn->prepare($sql_bookings);
if ($stmt_bookings === false) {
    error_log("CRITICAL ERROR: Failed to prepare booking select statement: " . $conn->error);
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลการจอง: " . $conn->error . " (กรุณาตรวจสอบชื่อคอลัมน์ Reservation_Id, Booking_status_name).";
} else {
    if (!empty($params)) {
        $bind_args = [];
        $bind_args[] = $param_types;
        foreach ($params as $key => $value) {
            $bind_args[] = &$params[$key]; // ส่งเป็น reference
        }
        call_user_func_array([$stmt_bookings, 'bind_param'], $bind_args);
    }
    $stmt_bookings->execute();
    $result_bookings = $stmt_bookings->get_result();
    $bookings = $result_bookings->fetch_all(MYSQLI_ASSOC);
    $stmt_bookings->close();
}


// ดึงห้องว่างสำหรับ Check-in Modal
$available_rooms = [];
$sql_available_rooms = "SELECT Room_ID, Room_number, r.Room_type_Id, rt.Room_type_name
                        FROM room r
                        JOIN room_type rt ON r.Room_type_Id = rt.Room_type_Id
                        WHERE r.Province_id = ? AND r.Status = 'AVL'
                        ORDER BY r.Room_number ASC";
$stmt_avail_rooms = $conn->prepare($sql_available_rooms);
if ($stmt_avail_rooms === false) {
    error_log("ERROR: Failed to prepare available rooms select statement: " . $conn->error);
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลห้องว่าง (กรุณาตรวจสอบชื่อคอลัมน์ Room_type_name).";
} else {
    $stmt_avail_rooms->bind_param("i", $current_province_id);
    $stmt_avail_rooms->execute();
    $result_avail_rooms = $stmt_avail_rooms->get_result();
    while ($row = $result_avail_rooms->fetch_assoc()) {
        $available_rooms[] = $row;
    }
    $stmt_avail_rooms->close();
}


// ปิดการเชื่อมต่อฐานข้อมูล
if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <title>จัดการการจองลูกค้า - Dom Inn Hotel (สาขา: <?= htmlspecialchars($province_name) ?>)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* Basic Reset & Body */
        body {
            font-family: 'Sarabun', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f6;
            color: #333;
            line-height: 1.6;
        }

        /* Header */
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

        /* Main Content */
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

        /* Alert Messages */
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
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Filter Form */
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }

        .filter-form label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            font-size: 0.9em;
        }

        .filter-form select,
        .filter-form input[type="text"],
        .filter-form button {
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 0.95em;
            box-sizing: border-box;
        }

        .filter-form button {
            background-color: #28a745;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
            min-width: 100px;
        }

        .filter-form button:hover {
            background-color: #218838;
        }

        /* Table Styles */
        .booking-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .booking-table th,
        .booking-table td {
            border: 1px solid #dee2e6;
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
            font-size: 0.95em;
        }

        .booking-table th {
            background-color: #e9ecef;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
        }

        .booking-table tbody tr:nth-child(odd) {
            background-color: #fcfdfe;
        }

        .booking-table tbody tr:hover {
            background-color: #e2f3ff;
        }

        /* Status Badges - ปรับให้ตรงกับชื่อสถานะใน DB */
        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.85em;
            white-space: nowrap;
        }

        /* จากภาพ phpMyAdmin */
        .status-ยืนยันการจองและรอชำระเงิน {
            background-color: #ffc107;
            color: #333;
        }

        /* Warning yellow */
        .status-ชำระเงินสำเร็จรอการตรวจสอบ {
            background-color: #ffc107;
            color: #333;
        }

        /* Warning yellow */
        .status-ชำระเงินสำเร็จ {
            background-color: #28a745;
            color: white;
        }

        /* Success green */
        .status-ยกเลิกการจองเนื่องจากไม่ชำระเงินภายใน-24-ชม{
            background-color: #dc3545;
            color: white;
        }

        /* Danger red */
        .status-ยกเลิกการจองเนื่องจากชำระเงินไม่ครบภายใน-24-ชม{
            background-color: #dc3545;
            color: white;
        }

        /* Danger red */

        /* สถานะที่เพิ่มใหม่ */
        .status-เช็คอินแล้ว {
            background-color: #007bff;
            /* สีน้ำเงิน */
            color: white;
        }

        /* Primary blue */
        .status-เสร็จสมบูรณ์,
        /* ถ้าใช้ชื่อนี้ใน DB (ตามรูป phpMyAdmin ล่าสุด) */
        .status-เช็คเอาท์แล้ว {
            /* ถ้าใช้ชื่อนี้ใน DB (ตามที่คุณอาจจะตั้งไว้) */
            background-color: #6c757d;
            /* สีเทาเข้ม */
            color: white;
        }

        /* Secondary gray */


        /* Action Buttons in Table */
        .action-buttons-cell button,
        .action-buttons-cell a {
            padding: 7px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            margin-right: 5px;
            transition: background-color 0.2s ease, transform 0.2s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-view-details {
            background-color: #17a2b8;
            color: white;
        }

        .btn-view-details:hover {
            background-color: #138496;
            transform: translateY(-1px);
        }

        .btn-check-in {
            background-color: #28a745;
            color: white;
        }

        .btn-check-in:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        .btn-check-out {
            background-color: #dc3545;
            color: white;
        }

        .btn-check-out:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }

        .btn-cancel {
            background-color: #ffc107;
            color: #333;
        }

        .btn-cancel:hover {
            background-color: #e0a800;
            transform: translateY(-1px);
        }

        .btn-update-status {
            background-color: #007bff;
            color: white;
        }

        .btn-update-status:hover {
            background-color: #0069d9;
            transform: translateY(-1px);
        }


        /* --- Modal Styles --- */
        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 1000;
            /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            background-color: rgba(0, 0, 0, 0.6);
            /* Black w/ opacity */
            display: flex;
            /* Use flexbox for centering */
            justify-content: center;
            /* Center horizontally */
            align-items: center;
            /* Center vertically */
            opacity: 0;
            /* Start with opacity 0 for fade-in */
            transition: opacity 0.3s ease;
            pointer-events: none;
            /* Allows clicks outside until 'show' */
        }

        .modal.show {
            opacity: 1;
            pointer-events: auto;
            /* Re-enable clicks when shown */
        }

        .modal-content {
            background-color: #fefefe;
            padding: 30px;
            border: 1px solid #ddd;
            width: 90%;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            /* Stronger shadow */
            position: relative;
            transform: translateY(-50px);
            /* Start slightly above center */
            transition: transform 0.3s ease, opacity 0.3s ease;
            opacity: 0;
        }

        .modal.show .modal-content {
            transform: translateY(0);
            /* Move to center */
            opacity: 1;
        }

        .modal-content h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 1.8em;
            margin-bottom: 20px;
            text-align: center;
        }

        .modal-content p {
            font-size: 1em;
            color: #555;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .modal-content b {
            color: #333;
        }

        .modal-content .close-button {
            color: #aaa;
            position: absolute;
            top: 15px;
            right: 25px;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-content .close-button:hover,
        .modal-content .close-button:focus {
            color: #333;
            text-decoration: none;
        }

        .modal-actions {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .modal-actions button,
        .modal-actions a {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            border: none;
            text-decoration: none;
            font-weight: 500;
        }

        .modal-actions .btn-cancel-modal {
            background-color: #6c757d;
            color: white;
        }

        .modal-actions .btn-cancel-modal:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .modal-actions .btn-confirm-action {
            background-color: #28a745;
            color: white;
        }

        .modal-actions .btn-confirm-action:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .modal-actions .btn-danger-action {
            background-color: #dc3545;
            color: white;
        }

        .modal-actions .btn-danger-action:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        .modal-actions .btn-warning-action {
            background-color: #ffc107;
            color: #333;
        }

        .modal-actions .btn-warning-action:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
        }


        /* Specific Check-in Modal Styles */
        #checkInModal .modal-content,
        #checkOutModal .modal-content,
        #cancelBookingModal .modal-content {
            max-width: 450px;
        }

        .form-group-modal {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group-modal label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group-modal input[type="text"],
        .form-group-modal input[type="date"],
        .form-group-modal input[type="time"],
        .form-group-modal select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 0.95em;
        }

        .form-group-modal .room-selection-container {
            border: 1px solid #ced4da;
            max-height: 150px;
            overflow-y: auto;
            padding: 10px;
            border-radius: 5px;
            background-color: #fff;
        }

        .form-group-modal .room-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            padding: 5px;
            border-bottom: 1px dashed #eee;
        }

        .form-group-modal .room-item:last-child {
            border-bottom: none;
        }

        .form-group-modal .room-item input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .form-group-modal .room-item label {
            margin: 0;
            font-weight: normal;
            flex-grow: 1;
        }

        .form-group-modal p {
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>

<body>
    <header>
        <h1>ระบบจัดการโรงแรม - การจองลูกค้า</h1>
        <div class="user-info">
            สวัสดี, <?php echo htmlspecialchars($officer_fname . " " . $officer_lname); ?>
            (สาขา: <?php echo htmlspecialchars($province_name); ?>)
            <a href="index.php">ออกจากระบบ</a>
            <a href="officer.php" class="btn-back">กลับเจ้าหน้าที่ดูแลระบบ</a>
        </div>
    </header>

    <main class="container">
        <h2>จัดการการจองของลูกค้า</h2>

        <?= $message ?>

        <!-- Filter and Search Form -->
        <form action="" method="get" class="filter-form">
            <div class="filter-group">
                <label for="filter_status_id">สถานะ:</label>
                <select id="filter_status_id" name="filter_status_id">
                    <option value="all" <?= (!isset($_GET['filter_status_id']) || $_GET['filter_status_id'] == 'all') ? 'selected' : '' ?>>-- ทั้งหมด --</option>
                    <?php foreach ($booking_statuses as $status): ?>
                        <option value="<?= htmlspecialchars($status['Booking_status_Id']) ?>"
                            <?= (isset($_GET['filter_status_id']) && $_GET['filter_status_id'] == $status['Booking_status_Id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($status['Booking_status_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="search_type">ค้นหาด้วย:</label>
                <select id="search_type" name="search_type">
                    <option value="booking_id" <?= (isset($_GET['search_type']) && $_GET['search_type'] == 'booking_id') ? 'selected' : '' ?>>รหัสการจอง</option>
                    <option value="guest_name" <?= (isset($_GET['search_type']) && $_GET['search_type'] == 'guest_name') ? 'selected' : '' ?>>ชื่อลูกค้า</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="search_query">คำค้นหา:</label>
                <input type="text" id="search_query" name="search_query" value="<?= htmlspecialchars($search_query) ?>" placeholder="ป้อนคำค้นหา...">
            </div>
            <div class="filter-group">
                <button type="submit"><i class="fas fa-search"></i> ค้นหา</button>
            </div>
        </form>

        <!-- Booking List Table -->
        <?php if (empty($bookings)): ?>
            <p style="text-align: center; padding: 20px; font-size: 1.1em; color: #666;">ไม่พบข้อมูลการจองตามเงื่อนไขที่เลือก.</p>
        <?php else: ?>
            <table class="booking-table">
                <thead>
                    <tr>
                        <th>รหัสการจอง</th>
                        <th>ชื่อลูกค้า</th>
                        <th>จำนวนห้อง</th>
                        <th>ผู้ใหญ่/เด็ก</th>
                        <th>เช็คอิน</th>
                        <th>เช็คเอาท์</th>
                        <th>ราคา</th>
                        <th>สถานะ</th>
                        <th>ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking):
                        $current_status_name = htmlspecialchars($booking['Booking_status_name']);
                        $current_status_id = htmlspecialchars($booking['Booking_status_Id']); // ใช้ ID ที่ดึงมาโดยตรง
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['Reservation_Id']) ?></td>
                            <td><?= htmlspecialchars($booking['Guest_name']) ?></td>
                            <td><?= htmlspecialchars($booking['Number_of_rooms']) ?></td>
                            <td><?= htmlspecialchars($booking['Number_of_adults']) ?> / <?= htmlspecialchars($booking['Number_of_children']) ?></td>
                            <td><?= htmlspecialchars($booking['Check_in_date_Reserved']) ?></td>
                            <td><?= htmlspecialchars($booking['Check_out_date']) ?></td>
                            <td>฿<?= number_format($booking['Total_price'], 2) ?></td>
                            <td><span class="status-badge status-<?= str_replace(' ', '-', $current_status_name) ?>"><?= $current_status_name ?></span></td>
                            <td class="action-buttons-cell">
                                <button class="btn-view-details"
                                    data-booking='<?= json_encode($booking, JSON_UNESCAPED_UNICODE) ?>'
                                    onclick="openBookingDetailsModal(this)">
                                    <i class="fas fa-info-circle"></i> ดูรายละเอียด
                                </button>
                                <?php
                                // เงื่อนไขสำหรับการแสดงปุ่ม "เช็คอิน"
                                // อนุญาตให้เช็คอินได้ถ้าสถานะเป็น ยืนยันการจองและรอชำระเงิน (1), ชำระเงินสำเร็จรอการตรวจสอบ (2), หรือ ชำระเงินสำเร็จ (3)
                                $can_check_in = ($current_status_id == $status_id_pending_payment ||
                                    $current_status_id == $status_id_payment_pending_review ||
                                    $current_status_id == $status_id_payment_confirmed);
                                ?>
                                <?php if ($can_check_in): ?>
                                    <button class="btn-check-in"
                                        data-booking-id="<?= htmlspecialchars($booking['Reservation_Id']) ?>"
                                        data-guest-name="<?= htmlspecialchars($booking['Guest_name']) ?>"
                                        data-checkin-date="<?= htmlspecialchars($booking['Check_in_date_Reserved']) ?>"
                                        data-num-rooms="<?= htmlspecialchars($booking['Number_of_rooms']) ?>"
                                        onclick="openCheckInModal(this)">
                                        <i class="fas fa-sign-in-alt"></i> เช็คอิน
                                    </button>
                                <?php endif; ?>

                                <?php
                                // เงื่อนไขสำหรับการแสดงปุ่ม "เช็คเอาท์"
                                // อนุญาตให้เช็คเอาท์ได้ถ้าสถานะเป็น 'เช็คอินแล้ว' (6)
                                $can_check_out = ($current_status_id == $status_id_checked_in);
                                ?>
                                <?php if ($can_check_out): ?>
                                    <button class="btn-check-out"
                                        data-booking-id="<?= htmlspecialchars($booking['Reservation_Id']) ?>"
                                        data-guest-name="<?= htmlspecialchars($booking['Guest_name']) ?>"
                                        onclick="openCheckOutModal(this)">
                                        <i class="fas fa-sign-out-alt"></i> เช็คเอาท์
                                    </button>
                                <?php endif; ?>

                                <?php
                                // เงื่อนไขสำหรับการแสดงปุ่ม "ยกเลิก"
                                // อนุญาตให้ยกเลิกได้หากสถานะยังไม่ เสร็จสมบูรณ์ (7) หรือถูกยกเลิกไปแล้ว (4, 5)
                                $can_cancel = !($current_status_id == $status_id_completed ||
                                    $current_status_id == $status_id_cancelled_timeout ||
                                    $current_status_id == $status_id_cancelled_incomplete_payment);
                                ?>
                                <?php if ($can_cancel): ?>
                                    <button class="btn-cancel"
                                        data-booking-id="<?= htmlspecialchars($booking['Reservation_Id']) ?>"
                                        data-guest-name="<?= htmlspecialchars($booking['Guest_name']) ?>"
                                        onclick="openCancelBookingModal(this)">
                                        <i class="fas fa-times-circle"></i> ยกเลิก
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <!-- Modal for Booking Details -->
    <div id="bookingDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('bookingDetailsModal')">&times;</span>
            <h3>รายละเอียดการจอง <span id="modalBookingId"></span></h3>
            <p><b>ชื่อลูกค้า:</b> <span id="modalGuestName"></span></p>
            <p><b>จำนวนห้องที่จอง:</b> <span id="modalNumRooms"></span> ห้อง</p>
            <p><b>จำนวนผู้ใหญ่:</b> <span id="modalAdults"></span> ท่าน</p>
            <p><b>จำนวนเด็ก:</b> <span id="modalChildren"></span> ท่าน</p>
            <p><b>วันเช็คอิน:</b> <span id="modalCheckInDate"></span></p>
            <p><b>วันเช็คเอาท์:</b> <span id="modalCheckOutDate"></span></p>
            <p><b>ราคารวม:</b> ฿<span id="modalTotalPrice"></span></p>
            <p><b>สถานะ:</b> <span id="modalStatus" class="status-badge"></span></p>
            <p><b>เวลาที่จอง:</b> <span id="modalBookingTime"></span></p>
            <p><b>อีเมลสมาชิก:</b> <span id="modalEmailMember"></span></p>
            <p><b>รหัสใบเสร็จ:</b> <span id="modalReceiptId"></span></p> <!-- เพิ่มช่องแสดง Receipt_id -->

            <div class="modal-actions">
                <button type="button" class="btn-cancel-modal" onclick="closeModal('bookingDetailsModal')">ปิด</button>
            </div>
        </div>
    </div>

    <!-- Modal for Check-in Confirmation -->
    <div id="checkInModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('checkInModal')">&times;</span>
            <h3>ยืนยันการเช็คอิน</h3>
            <p>กำลังเช็คอินการจอง <strong id="checkInModalBookingId"></strong> ของลูกค้า <strong><span id="checkInModalGuestName"></span></strong></p>
            <form action="customer_reception.php" method="POST" id="checkInForm">
                <input type="hidden" name="action" value="check_in_booking">
                <input type="hidden" name="reservation_id" id="checkInBookingIdHidden">
                <input type="hidden" name="guest_name" id="checkInGuestNameHidden">
                <input type="hidden" name="num_rooms_booked" id="checkInNumRoomsBooked">

                <div class="form-group-modal">
                    <label for="checkInDate">วันที่เช็คอิน:</label>
                    <input type="date" id="checkInDate" name="checkin_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group-modal">
                    <label for="checkInTime">เวลาเช็คอิน:</label>
                    <input type="time" id="checkInTime" name="checkin_time" value="<?= date('H:i') ?>" required>
                </div>
                <div class="form-group-modal">
                    <label>เลือกห้องพักว่างที่ต้องการ (จำนวน <span id="checkInRequiredRooms"></span> ห้อง):</label>
                    <div class="room-selection-container" id="availableRoomsContainer">
                        <?php if (empty($available_rooms)): ?>
                            <p>ไม่มีห้องว่างสำหรับสาขาของคุณในขณะนี้.</p>
                        <?php else: ?>
                            <?php foreach ($available_rooms as $room): ?>
                                <div class="room-item">
                                    <input type="checkbox" name="selected_room_ids[]" value="<?= htmlspecialchars($room['Room_ID']) ?>" id="room_<?= htmlspecialchars($room['Room_ID']) ?>">
                                    <label for="room_<?= htmlspecialchars($room['Room_ID']) ?>">ห้อง <?= htmlspecialchars($room['Room_number']) ?> (<?= htmlspecialchars($room['Room_type_name']) ?>)</label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <p style="color:red; font-size:0.9em; margin-top:5px;" id="roomSelectionError"></p>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="closeModal('checkInModal')">ยกเลิก</button>
                    <button type="submit" class="btn-confirm-action">ยืนยันเช็คอิน</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Check-out Confirmation -->
    <div id="checkOutModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('checkOutModal')">&times;</span>
            <h3>ยืนยันการเช็คเอาท์</h3>
            <p>คุณแน่ใจหรือไม่ที่จะเช็คเอาท์การจอง <strong id="checkOutModalBookingId"></strong> ของลูกค้า <strong><span id="checkOutModalGuestName"></span></strong>?</p>
            <form action="customer_reception.php" method="POST">
                <input type="hidden" name="action" value="check_out_booking">
                <input type="hidden" name="reservation_id" id="checkOutBookingIdHidden">
                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="closeModal('checkOutModal')">ยกเลิก</button>
                    <button type="submit" class="btn-danger-action">ยืนยันเช็คเอาท์</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Cancel Booking Confirmation -->
    <div id="cancelBookingModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('cancelBookingModal')">&times;</span>
            <h3>ยืนยันการยกเลิกการจอง</h3>
            <p>คุณแน่ใจหรือไม่ที่จะยกเลิกการจอง <strong id="cancelBookingModalBookingId"></strong> ของลูกค้า <strong><span id="cancelBookingModalGuestName"></span></strong>?</p>
            <p style="color: #dc3545; font-weight: bold;">การดำเนินการนี้ไม่สามารถย้อนกลับได้!</p>
            <form action="customer_reception.php" method="POST">
                <input type="hidden" name="action" value="cancel_booking">
                <input type="hidden" name="reservation_id" id="cancelBookingIdHidden">
                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="closeModal('cancelBookingModal')">ไม่, เก็บไว้ก่อน</button>
                    <button type="submit" class="btn-warning-action">ใช่, ยกเลิกการจอง</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Function to open any modal
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        // Function to close any modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        // --- Booking Details Modal ---
        function openBookingDetailsModal(button) {
            const booking = JSON.parse(button.dataset.booking);
            document.getElementById('modalBookingId').textContent = booking.Reservation_Id;
            document.getElementById('modalGuestName').textContent = booking.Guest_name;
            document.getElementById('modalNumRooms').textContent = booking.Number_of_rooms;
            document.getElementById('modalAdults').textContent = booking.Number_of_adults;
            document.getElementById('modalChildren').textContent = booking.Number_of_children;
            document.getElementById('modalCheckInDate').textContent = booking.Check_in_date_Reserved;
            document.getElementById('modalCheckOutDate').textContent = booking.Check_out_date;
            document.getElementById('modalTotalPrice').textContent = parseFloat(booking.Total_price).toLocaleString('th-TH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const statusElement = document.getElementById('modalStatus');
            statusElement.textContent = booking.Booking_status_name;
            // แก้ไข: ใช้ regex เพื่อแทนที่ช่องว่างทั้งหมดในชื่อสถานะด้วย '-' สำหรับ CSS class
            statusElement.className = `status-badge status-${booking.Booking_status_name.replace(/ /g, '-')}`;

            document.getElementById('modalBookingTime').textContent = booking.Booking_time ? booking.Booking_time : 'ไม่ระบุ';
            document.getElementById('modalEmailMember').textContent = booking.Email_member ? booking.Email_member : 'ไม่มี';
            document.getElementById('modalReceiptId').textContent = booking.Receipt_id ? booking.Receipt_id : 'ไม่มี'; // แสดง Receipt_id

            openModal('bookingDetailsModal');
        }

        // --- Check-in Modal ---
        function openCheckInModal(button) {
            const bookingId = button.dataset.bookingId;
            const guestName = button.dataset.guestName; // ดึง Guest_name จาก data-guest-name
            const checkinDate = button.dataset.checkinDate;
            const numRooms = parseInt(button.dataset.numRooms);

            document.getElementById('checkInModalBookingId').textContent = bookingId;
            document.getElementById('checkInModalGuestName').textContent = guestName; // แสดง Guest_name ที่ดึงมา
            document.getElementById('checkInBookingIdHidden').value = bookingId;
            document.getElementById('checkInGuestNameHidden').value = guestName; // ตั้งค่า Guest_name ที่ซ่อนไว้
            document.getElementById('checkInNumRoomsBooked').value = numRooms;
            document.getElementById('checkInRequiredRooms').textContent = numRooms; // แสดงจำนวนห้องที่ต้องเลือก

            // ตั้งค่าวันที่และเวลาให้เป็นปัจจุบันเมื่อเปิด Modal
            const now = new Date();
            const year = now.getFullYear();
            const month = (now.getMonth() + 1).toString().padStart(2, '0');
            const day = now.getDate().toString().padStart(2, '0');
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');

            document.getElementById('checkInDate').value = `${year}-${month}-${day}`;
            document.getElementById('checkInTime').value = `${hours}:${minutes}`;

            document.querySelectorAll('#availableRoomsContainer input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('roomSelectionError').textContent = '';

            openModal('checkInModal');
        }

        // Validate Check-in form before submission
        document.getElementById('checkInForm').addEventListener('submit', function(event) {
            const numRoomsBooked = parseInt(document.getElementById('checkInNumRoomsBooked').value);
            const selectedRooms = document.querySelectorAll('#availableRoomsContainer input[name="selected_room_ids[]"]:checked');
            const roomSelectionError = document.getElementById('roomSelectionError');

            if (selectedRooms.length !== numRoomsBooked) {
                roomSelectionError.textContent = `กรุณาเลือกห้องพักให้ครบตามจำนวนที่จอง (${numRoomsBooked} ห้อง).`;
                event.preventDefault(); // Stop form submission
            } else {
                roomSelectionError.textContent = ''; // Clear error
            }
        });

        // --- Check-out Modal ---
        function openCheckOutModal(button) {
            const bookingId = button.dataset.bookingId;
            const guestName = button.dataset.guestName;
            document.getElementById('checkOutModalBookingId').textContent = bookingId;
            document.getElementById('checkOutModalGuestName').textContent = guestName;
            document.getElementById('checkOutBookingIdHidden').value = bookingId;
            openModal('checkOutModal');
        }

        // --- Cancel Booking Modal ---
        function openCancelBookingModal(button) {
            const bookingId = button.dataset.bookingId;
            const guestName = button.dataset.guestName;
            document.getElementById('cancelBookingModalBookingId').textContent = bookingId;
            document.getElementById('cancelBookingModalGuestName').textContent = guestName;
            document.getElementById('cancelBookingIdHidden').value = bookingId;
            openModal('cancelBookingModal');
        }

        // Close modal when clicking outside (on the overlay)
        window.addEventListener('click', function(event) {
            document.querySelectorAll('.modal.show').forEach(modal => {
                if (event.target == modal) {
                    modal.classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>