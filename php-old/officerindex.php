<?php
session_start();
// เชื่อมต่อฐานข้อมูล
include 'db.php'; // ตรวจสอบให้แน่ใจว่าไฟล์ db.php มีการเชื่อมต่อฐานข้อมูลที่ถูกต้อง

// เปิดการแสดงข้อผิดพลาดทั้งหมดสำหรับการดีบัก (สามารถปิดได้เมื่อใช้งานจริง)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php");
    exit();
}

$officer_email = $_SESSION['Email_Officer'];
$officer_fname = $_SESSION['First_name'];
$officer_lname = $_SESSION['Last_name'];
$current_province_id = $_SESSION['Province_id'];

$province_name = '';
$sql_province_name = "SELECT Province_name FROM province WHERE Province_ID = ?";
$stmt_province = $conn->prepare($sql_province_name);
if ($stmt_province) {
    $stmt_province->bind_param("i", $current_province_id);
    $stmt_province->execute();
    $result_province = $stmt_province->get_result();
    if ($result_province->num_rows > 0) {
        $province_data = $result_province->fetch_assoc();
        $province_name = $province_data['Province_name'];
    }
    $stmt_province->close();
} else {
    error_log("Failed to prepare province name statement: " . $conn->error);
}

// กำหนด ID ของสถานะต่างๆ (ต้องตรงกับในตาราง booking_status ของคุณ)
$status_id_pending_payment = 1;         // ยืนยันการจองและรอชำระเงิน
$status_id_payment_pending_review = 2;  // ชำระเงินสำเร็จรอการตรวจสอบ
$status_id_payment_confirmed = 3;       // ชำระเงินสำเร็จ
$status_id_cancelled_timeout = 4;       // ยกเลิกการจองเนื่องจากไม่ชำระเงินภายใน 24 ชม.
$status_id_cancelled_incomplete_payment = 5; // ยกเลิกการจองเนื่องจากชำระเงินไม่ครบภายใน 24 ชม.
$status_id_checked_in = 6;              // เช็คอินแล้ว
$status_id_completed = 7;               // เสร็จสมบูรณ์ (หรือ "เช็คเอาท์แล้ว")
$status_id_no_show_penalized = 8;       // ไม่มาเช็คอิน/ถูกปรับ (สถานะใหม่)


// ฟังก์ชันสำหรับแปลง Booking_status_Id เป็นชื่อสถานะที่อ่านง่าย
function getBookingStatusName($statusId) {
    switch ($statusId) {
        case 1: return "รอการชำระเงิน";
        case 2: return "ยืนยันแล้ว";
        case 3: return "ยกเลิกแล้ว";
        case 4: return "เช็คอินแล้ว";
        case 5: return "ยกเลิก (ไม่ครบ)"; // เพิ่มตามโครงสร้าง DB
        case 6: return "เช็คอินแล้ว"; // ใช้ 6 แทน 4 ถ้า 4 คือ "เช็คอินแล้ว"
        case 7: return "เสร็จสมบูรณ์";
        case 8: return "ไม่มาเช็คอิน/ถูกปรับ"; // สถานะใหม่
        default: return "ไม่ทราบสถานะ";
    }
}

// ดึงข้อมูลสำหรับ Pre-fill ในฟอร์มเช็คอิน (เมื่อคลิกปุ่ม 'เช็คอิน' จากการจองที่ยืนยันแล้ว)
$prefill_guest_name = $_GET['prefill_guest_name'] ?? '';
$prefill_email_member = $_GET['prefill_email_member'] ?? '';
$prefill_reservation_id = $_GET['prefill_reservation_id'] ?? ''; // ยังคงส่งค่านี้ไป process_checkin.php ได้ (hidden)

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <title>จัดการเคาน์เตอร์ - Dom Inn Hotel</title>
    <link rel="stylesheet" href="../CSS/css/officerindex.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* CSS สำหรับสถานะห้อง */
        .status-AVL { color: #27ae60; font-weight: bold; } /* สีเขียวสำหรับห้องว่าง */
        .status-OCC { color: #e74c3c; font-weight: bold; } /* สีแดงสำหรับห้องไม่ว่าง */
        .status-clean { color: #3498db; font-weight: bold; } /* สีน้ำเงินสำหรับห้องที่กำลังทำความสะอาด (ถ้ามี) */

        /* CSS สำหรับข้อความแจ้งเตือน */
        .message { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; text-align: center; }
        .error-message { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; text-align: center; }

        /* --- CSS ของปฏิทิน --- */
        #calendarOverlay { display: none; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000; }
        #calendarPopup { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); z-index: 1001; width: 90%; max-width: 400px; }
        .close-calendar { position: absolute; top: 10px; right: 15px; font-size: 28px; cursor: pointer; color: #aaa; }
        .close-calendar:hover { color: #333; }
        .calendar-container { text-align: center; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .calendar-month h2 { margin: 0; font-size: 1.5em; color: #333; }
        .nav-btn { background: none; border: none; font-size: 2em; cursor: pointer; color: #555; padding: 5px 10px; transition: color 0.2s ease; }
        .nav-btn:hover { color: #f05a28; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
        .calendar-day { font-weight: bold; color: #777; padding: 8px 0; }
        .calendar-date { padding: 10px 0; cursor: pointer; border-radius: 4px; transition: background-color 0.2s ease, color 0.2s ease; }
        .calendar-date:hover:not(.past-date):not(.selected) { background-color: #f0f0f0; }
        .calendar-date.selected { background-color: #28c1f0ff; color: white; }
        .calendar-date.selected-range { background-color: #aed6f1; color: #333; } /* สีอ่อนลงสำหรับช่วงวันที่ */
        .calendar-date.past-date { color: #cccccc; cursor: not-allowed; }
        .calendar-date.blank { visibility: hidden; }
        .calendar-container .btn { margin-top: 20px; background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; transition: background-color 0.3s ease; }
        .calendar-container .btn:hover { background-color: #0056b3; }

        /* General Table Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
        table thead { background-color: #007bff; color: white; }
        table th, table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        table tbody tr:nth-child(even) { background-color: #f2f2f2; }
        table tbody tr:hover { background-color: #e9e9e9; }
        
        /* Button styles within tables */
        table td button, table td a.btn-action {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            text-decoration: none; /* สำหรับปุ่มที่เป็น <a> */
            display: inline-block; /* ทำให้ <a> มี padding เหมือน button */
            text-align: center;
        }

        table td button:hover, table td a.btn-action:hover {
            background-color: #218838;
        }

        table td button.btn-cancel { background-color: #dc3545; }
        table td button.btn-cancel:hover { background-color: #c82333; }

        table td a.btn-checkin-prefill { background-color: #007bff; margin-right: 5px; }
        table td a.btn-checkin-prefill:hover { background-color: #0056b3; }

        .action-buttons-group { display: flex; gap: 5px; flex-wrap: wrap; } /* เพิ่ม flex-wrap */

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        .modal.show {
            display: flex;
            opacity: 1;
            pointer-events: auto;
        }
        .modal-content {
            background-color: #fefefe;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
            position: relative;
            transform: translateY(-20px);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }
        .modal-content .close-button {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .modal-content .close-button:hover,
        .modal-content .close-button:focus {
            color: #333;
        }
        .modal-content h3 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.8em;
            text-align: center;
        }
        .modal-content .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .modal-content .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
            font-size: 0.9em;
        }
        .modal-content .form-group input[type="text"],
        .modal-content .form-group input[type="number"],
        .modal-content .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 0.95em;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
        }
        .modal-actions button {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 0.95em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            border: none;
        }
        .modal-actions .btn-cancel-modal {
            background-color: #6c757d;
            color: white;
        }
        .modal-actions .btn-cancel-modal:hover {
            background-color: #5a6268;
        }
        .modal-actions .btn-confirm-penalty {
            background-color: #ffc107; /* Warning color */
            color: #333;
        }
        .modal-actions .btn-confirm-penalty:hover {
            background-color: #e0a800;
        }
        .modal-actions .btn-confirm-damage {
            background-color: #dc3545; /* Danger color */
            color: white;
        }
        .modal-actions .btn-confirm-damage:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <header>
        <h1>ระบบจัดการโรงแรม - เคาน์เตอร์</h1>
        <div class="user-info">
            สวัสดี, <?php echo htmlspecialchars($officer_fname . " " . $officer_lname); ?>
            (สาขา: <?php echo htmlspecialchars($province_name); ?>)
            <a href="logout.php">ออกจากระบบ</a>
            <a href="officer.php" class="btn-back">กลับเจ้าหน้าที่ดูแลระบบ</a>
        </div>
    </header>

    <nav>
        <button onclick="showSection('checkin')">เช็คอิน (ลูกค้า Walk-in)</button>
        <button onclick="showSection('checkout')">เช็คเอ้าท์</button>
        <button onclick="showSection('current_stays')">ดูสถานะห้องปัจจุบัน</button>
        <button onclick="showSection('pending_bookings')">จัดการการจองที่รอดำเนินการ</button>
        <button onclick="showSection('confirmed_bookings')">จัดการการจองที่ยืนยันแล้ว</button>
    </nav>

    <main>
        <?php
        if (isset($_SESSION['message'])) {
            echo "<div class='message'>" . htmlspecialchars($_SESSION['message']) . "</div>";
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo "<div class='error-message'>" . htmlspecialchars($_SESSION['error']) . "</div>";
            unset($_SESSION['error']);
        }
        ?>

        <!-- ส่วนเช็คอินลูกค้าใหม่ (Walk-in หรือจาก Reservation) -->
        <section id="checkin" class="active">
            <h2>เช็คอินลูกค้าใหม่ (Walk-in)</h2>
            <form action="process_checkin.php" method="POST">
                <label for="guest_name">ชื่อลูกค้า:</label>
                <input type="text" id="guest_name" name="guest_name" value="<?= htmlspecialchars($prefill_guest_name) ?>" required><br>

                <label for="email_member">อีเมลลูกค้า (ถ้ามี):</label>
                <input type="text" id="email_member" name="email_member" value="<?= htmlspecialchars($prefill_email_member) ?>" placeholder="เช่น customer@example.com หรือเว้นว่างหากไม่มี"><br>
                
                <!-- Hidden input เพื่อส่ง Reservation_Id ไป process_checkin.php หากมาจากปุ่มเช็คอินการจอง -->
                <?php if (!empty($prefill_reservation_id)): ?>
                    <input type="hidden" name="reservation_id_input" value="<?= htmlspecialchars($prefill_reservation_id) ?>">
                <?php endif; ?>

                <label for="num_people">จำนวนผู้เข้าพัก (ในห้องนี้):</label>
                <input type="number" id="num_people" name="num_people" min="1" value="1" required><br>

                <label for="room_id">เลือกห้องพัก (สาขาของคุณ):</label>
                <select id="room_id" name="room_id" required>
                    <option value="">-- กรุณาเลือกห้อง --</option>
                    <?php
                    // ดึงห้องพักที่ว่างสำหรับสาขาปัจจุบัน
                    $sql_available_rooms = "SELECT Room_ID, Room_number, Number_of_people_staying, Room_details
                                            FROM room
                                            WHERE Province_id = ? AND Status = 'AVL' ORDER BY Room_number ASC";
                    $stmt_rooms = $conn->prepare($sql_available_rooms);
                    if ($stmt_rooms) {
                        $stmt_rooms->bind_param("i", $current_province_id);
                        $stmt_rooms->execute();
                        $result_rooms = $stmt_rooms->get_result();

                        if ($result_rooms->num_rows > 0) {
                            while ($room = $result_rooms->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($room['Room_ID']) . "'>";
                                echo "ห้อง " . htmlspecialchars($room['Room_number']) .
                                     " (รองรับ " . htmlspecialchars($room['Number_of_people_staying']) . " คน) - " .
                                     htmlspecialchars($room['Room_details']);
                                echo "</option>";
                            }
                        } else {
                            echo "<option value=''>ไม่มีห้องว่างในขณะนี้</option>";
                        }
                        $stmt_rooms->close();
                    } else {
                        error_log("Failed to prepare available rooms statement: " . $conn->error);
                    }
                    ?>
                </select><br>

                <!-- Input สำหรับวันที่เช็คอิน/เช็คเอ้าท์ เพื่อใช้ปฏิทิน -->
                <label for="checkin_start_date_display">วันที่เช็คอิน:</label>
                <input id="checkin_start_date_display" type="text" placeholder="เลือกวันที่เช็คอิน" readonly value="<?php echo date('Y-m-d'); ?>" onclick="openCalendar(this)" /><br>
                <input type="hidden" name="check_in_date" id="checkin_date_for_submit" value="<?php echo date('Y-m-d'); ?>">

                <label for="checkin_end_date_display">วันที่เช็คเอ้าท์:</label>
                <input id="checkin_end_date_display" type="text" placeholder="เลือกวันที่เช็คเอ้าท์" readonly value="" onclick="openCalendar(this)" /><br>
                <input type="hidden" name="check_out_date" id="checkout_date_for_submit" value="">


                <label for="check_in_time">เวลาเช็คอิน:</label>
                <input type="time" id="check_in_time" name="check_in_time" value="<?php echo date('H:i'); ?>" required><br>

                <button type="submit">เช็คอิน</button>
            </form>
        </section>

        <!-- ส่วนเช็คเอ้าท์ลูกค้า -->
        <section id="checkout" style="display: none;">
            <h2>เช็คเอ้าท์ลูกค้า</h2>
            <table>
                <thead>
                    <tr>
                        <th>เลขที่การเข้าพัก</th>
                        <th>ห้องที่พัก</th>
                        <th>ชื่อลูกค้า</th>
                        <th>วันที่เช็คอิน</th>
                        <th>เวลาเช็คอิน</th>
                        <th>Receipt ID</th>
                        <th>Reservation ID</th>
                        <th>อีเมลลูกค้า</th>
                        <th>ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // ดึงข้อมูลลูกค้าที่กำลังเข้าพักในสาขาปัจจุบัน
                    $sql_current_stays = "SELECT s.Stay_id, r.Room_number, s.Guest_name, s.Check_in_date, s.Check_in_time, s.Room_id, s.Receipt_Id, s.Reservation_Id, s.Email_member
                                          FROM stay s
                                          JOIN room r ON s.Room_id = r.Room_ID
                                          WHERE r.Province_id = ? AND s.Check_out_date IS NULL ORDER BY s.Check_in_date DESC, s.Check_in_time DESC";
                    $stmt_stays = $conn->prepare($sql_current_stays);
                    if ($stmt_stays) {
                        $stmt_stays->bind_param("i", $current_province_id);
                        $stmt_stays->execute();
                        $result_stays = $stmt_stays->get_result();

                        if ($result_stays->num_rows > 0) {
                            while ($stay = $result_stays->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($stay['Stay_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($stay['Room_number']) . "</td>";
                                echo "<td>" . htmlspecialchars($stay['Guest_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($stay['Check_in_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($stay['Check_in_time']) . "</td>";
                                echo "<td>" . (isset($stay['Receipt_Id']) && $stay['Receipt_Id'] !== null && $stay['Receipt_Id'] !== '' ? htmlspecialchars($stay['Receipt_Id']) : '-') . "</td>";
                                echo "<td>" . (isset($stay['Reservation_Id']) && $stay['Reservation_Id'] !== null && $stay['Reservation_Id'] !== '' ? htmlspecialchars($stay['Reservation_Id']) : '-') . "</td>";
                                echo "<td>" . (isset($stay['Email_member']) && $stay['Email_member'] !== null && $stay['Email_member'] !== '' ? htmlspecialchars($stay['Email_member']) : '-') . "</td>";
                                echo "<td>";
                                echo "<div class='action-buttons-group'>"; // Group buttons
                                echo "<form action='process_checkout.php' method='POST' onsubmit='return confirm(\"ยืนยันการเช็คเอ้าท์ลูกค้า " . htmlspecialchars($stay['Guest_name']) . " จากห้อง " . htmlspecialchars($stay['Room_number']) . " หรือไม่?\");' style='display:inline;'>";
                                echo "<input type='hidden' name='stay_id' value='" . htmlspecialchars($stay['Stay_id']) . "'>";
                                echo "<input type='hidden' name='room_id' value='" . htmlspecialchars($stay['Room_id']) . "'>";
                                echo "<button type='submit' class='btn-action'>เช็คเอ้าท์</button>";
                                echo "</form>";
                                
                                // ปุ่มบันทึกความเสียหาย (จะเปิด Modal)
                                echo "<button type='button' class='btn-action btn-cancel' 
                                          data-stay-id='" . htmlspecialchars($stay['Stay_id']) . "' 
                                          data-room-id='" . htmlspecialchars($stay['Room_id']) . "'
                                          onclick='openDamageModal(this)'>
                                          <i class=\"fas fa-wrench\"></i> บันทึกเสียหาย
                                      </button>";
                                echo "</div>"; // .action-buttons-group
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9'>ไม่มีลูกค้ากำลังเข้าพักในขณะนี้</td></tr>";
                        }
                        $stmt_stays->close();
                    } else {
                        error_log("Failed to prepare current stays statement: " . $conn->error);
                    }
                    ?>
                </tbody>
            </table>
        </section>

        <!-- ส่วนดูสถานะห้องปัจจุบัน -->
        <section id="current_stays" style="display: none;">
            <h2>สถานะห้องปัจจุบัน</h2>
            <table>
                <thead>
                    <tr>
                        <th>เลขห้อง</th>
                        <th>รายละเอียด</th>
                        <th>สถานะ</th>
                        <th>ผู้เข้าพักปัจจุบัน</th>
                        <th>เช็คอินเมื่อ</th>
                        <th>อีเมลลูกค้า</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql_all_rooms_status = "SELECT r.Room_number, r.Room_details, r.Status, s.Guest_name, s.Check_in_date, s.Email_member
                                             FROM room r
                                             LEFT JOIN stay s ON r.Room_ID = s.Room_id AND s.Check_out_date IS NULL
                                             WHERE r.Province_id = ? ORDER BY r.Room_number ASC";
                    $stmt_all_rooms = $conn->prepare($sql_all_rooms_status);
                    if ($stmt_all_rooms) {
                        $stmt_all_rooms->bind_param("i", $current_province_id);
                        $stmt_all_rooms->execute();
                        $result_all_rooms = $stmt_all_rooms->get_result();

                        if ($result_all_rooms->num_rows > 0) {
                            while ($room_status = $result_all_rooms->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($room_status['Room_number']) . "</td>";
                                echo "<td>" . htmlspecialchars($room_status['Room_details']) . "</td>";
                                echo "<td class='status-" . htmlspecialchars($room_status['Status']) . "'>" . htmlspecialchars($room_status['Status']) . "</td>";
                                echo "<td>" . (isset($room_status['Guest_name']) ? htmlspecialchars($room_status['Guest_name']) : "-") . "</td>";
                                echo "<td>" . (isset($room_status['Check_in_date']) ? htmlspecialchars($room_status['Check_in_date']) : "-") . "</td>";
                                echo "<td>" . (isset($room_status['Email_member']) && $room_status['Email_member'] !== null && $room_status['Email_member'] !== '' ? htmlspecialchars($room_status['Email_member']) : '-') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>ไม่มีข้อมูลห้องพักในสาขานี้</td></tr>";
                        }
                        $stmt_all_rooms->close();
                    } else {
                        error_log("Failed to prepare all rooms status statement: " . $conn->error);
                    }
                    ?>
                </tbody>
            </table>
        </section>

        <!-- ส่วนจัดการการจองที่รอดำเนินการ (จากระบบจองออนไลน์) -->
        <section id="pending_bookings" style="display: none;">
            <h2>การจองที่รอดำเนินการ (รอชำระเงิน/รอตรวจสอบ)</h2>
            <table>
                <thead>
                    <tr>
                        <th>รหัสการจอง</th>
                        <th>ชื่อลูกค้า</th>
                        <th>เช็คอิน</th>
                        <th>เช็คเอาท์</th>
                        <th>ห้อง</th>
                        <th>ผู้ใหญ่</th>
                        <th>เด็ก</th>
                        <th>อีเมลลูกค้า</th>
                        <th>ราคารวม</th>
                        <th>สถานะ</th>
                        <th>ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql_pending_bookings = "SELECT r.Reservation_Id, r.Guest_name, r.Booking_date, r.Check_out_date,
                                             r.Number_of_rooms, r.Number_of_adults, r.Number_of_children,
                                             r.Email_member, r.Total_price, r.Booking_status_Id
                                             FROM reservation r
                                             WHERE r.Province_Id = ? AND r.Booking_status_Id = 1
                                             ORDER BY r.Booking_date DESC, r.Booking_time DESC";
                    $stmt_pending = $conn->prepare($sql_pending_bookings);
                    if ($stmt_pending) {
                        $stmt_pending->bind_param("i", $current_province_id);
                        $stmt_pending->execute();
                        $result_pending = $stmt_pending->get_result();
                        $today_date = date('Y-m-d');

                        if ($result_pending->num_rows > 0) {
                            while ($booking = $result_pending->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($booking['Reservation_Id']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Guest_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Booking_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Check_out_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Number_of_rooms']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Number_of_adults']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Number_of_children']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Email_member']) . "</td>";
                                echo "<td>" . number_format($booking['Total_price'], 2) . "</td>";
                                echo "<td>" . htmlspecialchars(getBookingStatusName($booking['Booking_status_Id'])) . "</td>";
                                echo "<td>";
                                echo "<div class='action-buttons-group'>";
                                echo "<form action='process_booking_status.php' method='POST' onsubmit='return confirm(\"ยืนยันการจอง # " . htmlspecialchars($booking['Reservation_Id']) . " หรือไม่?\");' style='display:inline;'>";
                                echo "<input type='hidden' name='reservation_id' value='" . htmlspecialchars($booking['Reservation_Id']) . "'>";
                                echo "<input type='hidden' name='action' value='confirm'>";
                                echo "<button type='submit' class='btn-action'>ยืนยันการจอง</button>";
                                echo "</form>";

                                // ปุ่มแจ้งปรับ (ไม่มาเช็คอิน)
                                $is_past_checkin_date = (new DateTime($booking['Booking_date']) < new DateTime($today_date));
                                $is_not_cancelled_or_checked_in = ($booking['Booking_status_Id'] != $status_id_cancelled_timeout &&
                                                                     $booking['Booking_status_Id'] != $status_id_cancelled_incomplete_payment &&
                                                                     $booking['Booking_status_Id'] != $status_id_checked_in &&
                                                                     $booking['Booking_status_Id'] != $status_id_no_show_penalized);
                                if ($is_past_checkin_date && $is_not_cancelled_or_checked_in) {
                                    echo "<button type='button' class='btn-action btn-confirm-penalty' 
                                              data-reservation-id='" . htmlspecialchars($booking['Reservation_Id']) . "' 
                                              data-guest-name='" . htmlspecialchars($booking['Guest_name']) . "'
                                              onclick='openPenaltyModal(this)'>
                                              <i class=\"fas fa-user-slash\"></i> แจ้งปรับ
                                          </button>";
                                }
                                echo "</div>"; // .action-buttons-group
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='11'>ไม่มีการจองที่รอดำเนินการในขณะนี้</td></tr>";
                        }
                        $stmt_pending->close();
                    } else {
                        error_log("Failed to prepare pending bookings statement: " . $conn->error);
                    }
                    ?>
                </tbody>
            </table>
        </section>

        <!-- ส่วนจัดการการจองที่ยืนยันแล้ว (จากระบบจองออนไลน์) -->
        <section id="confirmed_bookings" style="display: none;">
            <h2>การจองที่ยืนยันแล้ว</h2>
            <table>
                <thead>
                    <tr>
                        <th>รหัสการจอง</th>
                        <th>ชื่อลูกค้า</th>
                        <th>เช็คอิน</th>
                        <th>เช็คเอาท์</th>
                        <th>ห้อง</th>
                        <th>ผู้ใหญ่</th>
                        <th>เด็ก</th>
                        <th>อีเมลลูกค้า</th>
                        <th>ราคารวม</th>
                        <th>สถานะ</th>
                        <th>ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // ดึงข้อมูลการจองที่ยืนยันแล้ว หรือที่เช็คอินแล้ว (สถานะ 2 หรือ 4)
                    $sql_confirmed_bookings = "SELECT r.Reservation_Id, r.Guest_name, r.Booking_date, r.Check_out_date,
                                             r.Number_of_rooms, r.Number_of_adults, r.Number_of_children,
                                             r.Email_member, r.Total_price, r.Booking_status_Id
                                             FROM reservation r
                                             WHERE r.Province_Id = ? AND (r.Booking_status_Id = 2 OR r.Booking_status_Id = 3 OR r.Booking_status_Id = 6)
                                             ORDER BY r.Booking_date DESC, r.Booking_time DESC";
                    $stmt_confirmed = $conn->prepare($sql_confirmed_bookings);
                    if ($stmt_confirmed) {
                        $stmt_confirmed->bind_param("i", $current_province_id);
                        $stmt_confirmed->execute();
                        $result_confirmed = $stmt_confirmed->get_result();
                        $today_date = date('Y-m-d');

                        if ($result_confirmed->num_rows > 0) {
                            while ($booking = $result_confirmed->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($booking['Reservation_Id']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Guest_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Booking_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Check_out_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Number_of_rooms']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Number_of_adults']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Number_of_children']) . "</td>";
                                echo "<td>" . htmlspecialchars($booking['Email_member']) . "</td>";
                                echo "<td>" . number_format($booking['Total_price'], 2) . "</td>";
                                echo "<td>" . htmlspecialchars(getBookingStatusName($booking['Booking_status_Id'])) . "</td>";
                                echo "<td>";
                                echo "<div class='action-buttons-group'>";
                                // ปุ่มเช็คอินจะแสดงเฉพาะถ้าสถานะเป็น 'ยืนยันแล้ว' (Booking_status_Id = 2 หรือ 3)
                                if ($booking['Booking_status_Id'] == 2 || $booking['Booking_status_Id'] == 3) {
                                    $checkin_link = "officerindex.php?section=checkin" .
                                                    "&prefill_guest_name=" . urlencode($booking['Guest_name']) .
                                                    "&prefill_email_member=" . urlencode($booking['Email_member']) .
                                                    "&prefill_reservation_id=" . urlencode($booking['Reservation_Id']);
                                    echo "<a href='" . htmlspecialchars($checkin_link) . "' class='btn-action btn-checkin-prefill'>เช็คอิน</a>";
                                }
                                // ปุ่มยกเลิกแสดงเสมอสำหรับสถานะที่ยังไม่ได้ยกเลิก
                                if ($booking['Booking_status_Id'] != $status_id_cancelled_timeout &&
                                    $booking['Booking_status_Id'] != $status_id_cancelled_incomplete_payment &&
                                    $booking['Booking_status_Id'] != $status_id_no_show_penalized &&
                                    $booking['Booking_status_Id'] != $status_id_completed) {
                                    echo "<form action='process_booking_status.php' method='POST' onsubmit='return confirm(\"ยกเลิกการจอง # " . htmlspecialchars($booking['Reservation_Id']) . " หรือไม่?\");' style='display:inline;'>";
                                    echo "<input type='hidden' name='reservation_id' value='" . htmlspecialchars($booking['Reservation_Id']) . "'>";
                                    echo "<input type='hidden' name='action' value='cancel'>";
                                    echo "<button type='submit' class='btn-cancel'>ยกเลิก</button>";
                                    echo "</form>";
                                }

                                // ปุ่มแจ้งปรับ (ไม่มาเช็คอิน)
                                $is_past_checkin_date = (new DateTime($booking['Booking_date']) < new DateTime($today_date));
                                $is_not_cancelled_or_checked_in = ($booking['Booking_status_Id'] != $status_id_cancelled_timeout &&
                                                                     $booking['Booking_status_Id'] != $status_id_cancelled_incomplete_payment &&
                                                                     $booking['Booking_status_Id'] != $status_id_checked_in &&
                                                                     $booking['Booking_status_Id'] != $status_id_no_show_penalized);
                                if ($is_past_checkin_date && $is_not_cancelled_or_checked_in) {
                                    echo "<button type='button' class='btn-action btn-confirm-penalty' 
                                              data-reservation-id='" . htmlspecialchars($booking['Reservation_Id']) . "' 
                                              data-guest-name='" . htmlspecialchars($booking['Guest_name']) . "'
                                              onclick='openPenaltyModal(this)'>
                                              <i class=\"fas fa-user-slash\"></i> แจ้งปรับ
                                          </button>";
                                }
                                echo "</div>"; // .action-buttons-group
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='11'>ไม่มีการจองที่ยืนยันแล้วในขณะนี้</td></tr>";
                        }
                        $stmt_confirmed->close();
                    } else {
                        error_log("Failed to prepare confirmed bookings statement: " . $conn->error);
                    }
                    ?>
                </tbody>
            </table>
        </section>

    </main>

    <!-- Calendar Overlay and Popup HTML -->
    <div id="calendarOverlay" onclick="closeCalendar()"></div>
    <div id="calendarPopup">
        <span class="close-calendar" onclick="closeCalendar()">×</span>
        <div class="calendar-container">
            <div class="calendar-header">
                <span class="nav-btn" onclick="changeMonth(-1)">&#8249;</span>
                <div class="calendar-month">
                    <h2 id="month-label">Loading...</h2>
                </div>
                <span class="nav-btn" onclick="changeMonth(1)">&#8250;</span>
            </div>
            <div class="calendar-grid" id="calendar-days"></div>
            <div class="calendar-grid" id="calendar-dates"></div>
            <button class="btn" onclick="confirmDate()">ยืนยันวันเข้าพัก</button>
        </div>
    </div>

    <!-- Modal for Penalty -->
    <div id="penaltyModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('penaltyModal')">&times;</span>
            <h3>แจ้งปรับผู้เข้าพักไม่มาเช็คอิน</h3>
            <form action="process_adjustments.php" method="POST" id="penaltyForm">
                <input type="hidden" name="action" value="apply_penalty">
                <input type="hidden" name="reservation_id" id="penaltyReservationId">
                <input type="hidden" name="officer_email" value="<?= htmlspecialchars($officer_email) ?>">

                <p>การจอง: <strong id="penaltyModalReservationIdDisplay"></strong></p>
                <p>ลูกค้า: <span id="penaltyModalGuestNameDisplay"></span></p>

                <div class="form-group">
                    <label for="penaltyAmount">มูลค่าที่ปรับ (฿):</label>
                    <input type="number" id="penaltyAmount" name="penalty_amount" min="0" step="0.01" value="0.00" required>
                </div>
                <div class="form-group">
                    <label for="penaltyReason">เหตุผลในการปรับ:</label>
                    <textarea id="penaltyReason" name="penalty_reason" rows="3" placeholder="เช่น ไม่มาเช็คอินตามกำหนด" required></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="closeModal('penaltyModal')">ยกเลิก</button>
                    <button type="submit" class="btn-confirm-penalty"><i class="fas fa-gavel"></i> บันทึกค่าปรับ</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Damage Recording -->
    <div id="damageModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('damageModal')">&times;</span>
            <h3>บันทึกความเสียหายในห้องพัก</h3>
            <form action="process_adjustments.php" method="POST" id="damageForm">
                <input type="hidden" name="action" value="record_damage">
                <input type="hidden" name="stay_id" id="damageStayId">
                <input type="hidden" name="room_id" id="damageRoomId">
                <input type="hidden" name="officer_email" value="<?= htmlspecialchars($officer_email) ?>">

                <p>เลขที่การเข้าพัก: <strong id="damageModalStayIdDisplay"></strong></p>
                <p>ห้องพัก: <span id="damageModalRoomIdDisplay"></span></p>

                <div class="form-group">
                    <label for="damageItem">รายการของที่เสียหาย:</label>
                    <input type="text" id="damageItem" name="damage_item" required>
                </div>
                <div class="form-group">
                    <label for="damageDescription">รายละเอียดความเสียหาย:</label>
                    <textarea id="damageDescription" name="damage_description" rows="3" placeholder="เช่น กระจกแตกที่หน้าต่าง, รีโมทหาย" required></textarea>
                </div>
                <div class="form-group">
                    <label for="damageValue">มูลค่าความเสียหาย (฿):</label>
                    <input type="number" id="damageValue" name="damage_value" min="0" step="0.01" value="0.00" required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="closeModal('damageModal')">ยกเลิก</button>
                    <button type="submit" class="btn-confirm-damage"><i class="fas fa-exclamation-triangle"></i> บันทึกความเสียหาย</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        // Calendar target element
        let currentCalendarInput = null;

        // *** JavaScript สำหรับสลับ Section ***
        function showSection(sectionId) {
            document.querySelectorAll('section').forEach(section => {
                section.style.display = 'none';
                section.classList.remove('active'); // ลบ active class ออกจากทุก section
            });
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.style.display = 'block';
                targetSection.classList.add('active'); // เพิ่ม active class ให้ section ที่เลือก
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const section = urlParams.get('section');
            if (section) {
                showSection(section);
            } else {
                showSection('checkin'); // แสดง section 'checkin' เป็นค่าเริ่มต้น
            }

            // *** Initial setup for calendar dates on load ***
            // กำหนดวันที่เช็คอินเริ่มต้นสำหรับแสดงผลและ hidden input
            const today = new Date();
            const pad = n => n.toString().padStart(2, '0');
            const formattedToday = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`;

            const checkinDisplay = document.getElementById("checkin_start_date_display");
            const checkinHidden = document.getElementById("checkin_date_for_submit");
            if (checkinDisplay && checkinHidden && !checkinDisplay.value) { // กำหนดค่าเมื่อว่างเปล่าเท่านั้น
                checkinDisplay.value = formattedToday;
                checkinHidden.value = formattedToday;
            }

            // หากมีข้อมูล prefill ใน URL ให้แสดง section 'checkin'
            // (แม้ว่า Reservation ID จะถูกซ่อนในฟอร์ม แต่ process_checkin.php ยังสามารถรับค่าไปใช้งานได้)
            if (urlParams.get('prefill_guest_name') || urlParams.get('prefill_email_member')) {
                showSection('checkin');
            }
        });

        // --- JavaScript ของปฏิทิน ---
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();
        let isDragging = false; // ไม่ได้ใช้สำหรับการเลือกวันที่แบบคลิก
        let selectedDates = [];

        const today = new Date();
        today.setHours(0, 0, 0, 0); // ตั้งค่าเวลาเป็น 00:00:00 เพื่อเปรียบเทียบเฉพาะวันที่

        const calendarDaysEl = document.getElementById("calendar-days");
        const calendarDatesEl = document.getElementById("calendar-dates");
        const monthLabel = document.getElementById("month-label");
        const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
            "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];

        function openCalendar(inputElement) {
            currentCalendarInput = inputElement; // เก็บ reference ของ input ที่เรียกปฏิทิน
            document.getElementById("calendarOverlay").style.display = "block";
            document.getElementById("calendarPopup").style.display = "block";

            selectedDates = [];
            let initialDateValue = currentCalendarInput.value;
            if (initialDateValue) {
                const parts = initialDateValue.split('-');
                if (parts.length === 3) {
                    const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                    if (!isNaN(d)) selectedDates.push(d);
                    currentMonth = d.getMonth();
                    currentYear = d.getFullYear();
                }
            } else {
                // ถ้าไม่มีวันที่ถูกตั้งค่าไว้ ให้ใช้เดือน/ปีปัจจุบัน
                currentMonth = new Date().getMonth();
                currentYear = new Date().getFullYear();
            }
            
            // ถ้า input วันที่อื่นมีค่าอยู่ ให้เพิ่มเข้าไปใน selectedDates ด้วย (เพื่อไฮไลท์ช่วง)
            const otherDateInputId = (currentCalendarInput.id === 'checkin_start_date_display') ? 'checkin_end_date_display' : 'checkin_start_date_display';
            const otherDateInput = document.getElementById(otherDateInputId);
            if (otherDateInput) { // ตรวจสอบว่า input นี้มีอยู่จริง
                const otherDateValue = otherDateInput.value;
                if (otherDateValue) {
                    const parts = otherDateValue.split('-');
                    if (parts.length === 3) {
                        const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                        if (!isNaN(d)) selectedDates.push(d);
                    }
                }
            }

            selectedDates.sort((a, b) => a - b); // จัดเรียงวันที่ที่เลือกเสมอ
            renderDaysOfWeek();
            renderCalendar();
        }


        function closeCalendar() {
            document.getElementById("calendarOverlay").style.display = "none";
            document.getElementById("calendarPopup").style.display = "none";
            currentCalendarInput = null; // ล้าง reference
        }

        function toggleDate(el, dateObj) {
            if (!el.classList.contains("calendar-date") || el.classList.contains("past-date")) {
                return;
            }

            const index = selectedDates.findIndex(d =>
                d.getFullYear() === dateObj.getFullYear() &&
                d.getMonth() === dateObj.getMonth() &&
                d.getDate() === dateObj.getDate()
            );

            if (index > -1) {
                // ถ้าคลิกวันที่เดิมอีกครั้ง ให้ยกเลิกการเลือก
                el.classList.remove("selected");
                selectedDates.splice(index, 1);
            } else {
                // ถ้ามีการเลือกครบ 2 วันแล้ว และคลิกวันที่ 3 ให้ล้างการเลือกทั้งหมดแล้วเริ่มใหม่
                if (selectedDates.length >= 2) {
                    document.querySelectorAll('.calendar-date.selected, .calendar-date.selected-range').forEach(sEl => sEl.classList.remove('selected', 'selected-range'));
                    selectedDates = [];
                }
                
                el.classList.add("selected");
                selectedDates.push(dateObj);
                selectedDates.sort((a, b) => a - b); // จัดเรียงเพื่อให้วันที่เช็คอินมาก่อนเช็คเอ้าท์
            }
            
            renderCalendar(); // เรียก renderCalendar ใหม่เพื่ออัปเดตไฮไลท์
        }

        function highlightDateRange(startDate, endDate) {
            const allDateEls = calendarDatesEl.querySelectorAll('.calendar-date:not(.blank):not(.past-date)');
            allDateEls.forEach(el => {
                el.classList.remove('selected-range'); // ลบไฮไลท์ช่วงเดิมออก
                const day = parseInt(el.textContent);
                const currentCalDate = new Date(currentYear, currentMonth, day);
                currentCalDate.setHours(0,0,0,0);

                if (currentCalDate > startDate && currentCalDate < endDate) { // ไฮไลท์เฉพาะวันที่ที่อยู่ 'ระหว่าง' วันที่เลือก
                    el.classList.add('selected-range');
                }
            });
            // ตรวจสอบให้แน่ใจว่าวันเริ่มต้นและสิ้นสุดยังคงมี class 'selected'
            document.querySelectorAll('.calendar-date.selected').forEach(sEl => sEl.classList.add('selected'));
        }


        function renderDaysOfWeek() {
            calendarDaysEl.innerHTML = "";
            ["อา", "จ", "อ", "พ", "พฤ", "ศ", "ส"].forEach(d => {
                const el = document.createElement("div");
                el.className = "calendar-day";
                el.textContent = d;
                calendarDaysEl.appendChild(el);
            });
        }

        function renderCalendar() {
            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            monthLabel.textContent = `${monthNames[currentMonth]} ${currentYear}`;
            calendarDatesEl.innerHTML = "";

            // blank days
            for (let i = 0; i < firstDay; i++) {
                const blank = document.createElement("div");
                blank.className = "calendar-date blank";
                calendarDatesEl.appendChild(blank);
            }

            for (let i = 1; i <= daysInMonth; i++) {
                const dateEl = document.createElement("div");
                dateEl.className = "calendar-date";
                dateEl.textContent = i;

                const dateToCheck = new Date(currentYear, currentMonth, i);
                dateToCheck.setHours(0, 0, 0, 0);

                if (dateToCheck < today) dateEl.classList.add("past-date");

                // ตรวจสอบว่าวันที่อยู่ใน selectedDates หรือไม่
                const isSelected = selectedDates.some(d => dateToCheck.getTime() === d.getTime());
                if (isSelected) {
                    dateEl.classList.add("selected");
                }

                // ไฮไลท์ช่วงวันที่ หากมีการเลือก 2 วัน
                if (selectedDates.length === 2 && dateToCheck > selectedDates[0] && dateToCheck < selectedDates[1]) {
                     dateEl.classList.add("selected-range");
                }

                dateEl.addEventListener("click", () => {
                    toggleDate(dateEl, dateToCheck);
                });
                
                calendarDatesEl.appendChild(dateEl);
            }

             if (selectedDates.length === 2) {
                highlightDateRange(selectedDates[0], selectedDates[1]);
            }
        }


        function changeMonth(offset) {
            currentMonth += offset;
            if (currentMonth < 0) { currentMonth = 11; currentYear--; }
            else if (currentMonth > 11) { currentMonth = 0; currentYear++; }
            renderCalendar();
        }

        function confirmDate() {
            if (selectedDates.length === 0) {
                alert("กรุณาเลือกวันเช็คอินและเช็คเอาท์");
                return;
            }
            if (selectedDates.length === 1) {
                alert("กรุณาเลือกวันเช็คเอาท์ด้วย");
                return;
            }

            const sorted = selectedDates.sort((a, b) => a - b); // จัดเรียงอีกครั้งเพื่อความชัวร์

            const checkInDate = sorted[0];
            const checkOutDate = sorted[sorted.length - 1];

            const pad = n => n.toString().padStart(2, '0');

            // แสดงใน input ที่เห็น
            document.getElementById("checkin_start_date_display").value = `${checkInDate.getFullYear()}-${pad(checkInDate.getMonth() + 1)}-${pad(checkInDate.getDate())}`;
            document.getElementById("checkin_end_date_display").value = `${checkOutDate.getFullYear()}-${pad(checkOutDate.getMonth() + 1)}-${pad(checkOutDate.getDate())}`;

            // ส่งค่าไป hidden input สำหรับ submit
            document.getElementById("checkin_date_for_submit").value = `${checkInDate.getFullYear()}-${pad(checkInDate.getMonth() + 1)}-${pad(checkInDate.getDate())}`;
            document.getElementById("checkout_date_for_submit").value = `${checkOutDate.getFullYear()}-${pad(checkOutDate.getMonth() + 1)}-${pad(checkOutDate.getDate())}`;

            closeCalendar();
        }

        // Global functions for modal handling
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
            // Reset forms when closing
            if (modalId === 'penaltyModal') {
                document.getElementById('penaltyForm').reset();
            } else if (modalId === 'damageModal') {
                document.getElementById('damageForm').reset();
            }
        }

        // Functions for specific modals
        function openPenaltyModal(button) {
            const reservationId = button.dataset.reservationId;
            const guestName = button.dataset.guestName;
            document.getElementById('penaltyReservationId').value = reservationId;
            document.getElementById('penaltyModalReservationIdDisplay').textContent = reservationId;
            document.getElementById('penaltyModalGuestNameDisplay').textContent = guestName;
            openModal('penaltyModal');
        }

        function openDamageModal(button) {
            const stayId = button.dataset.stayId;
            const roomId = button.dataset.roomId;
            document.getElementById('damageStayId').value = stayId;
            document.getElementById('damageRoomId').value = roomId;
            document.getElementById('damageModalStayIdDisplay').textContent = stayId;
            document.getElementById('damageModalRoomIdDisplay').textContent = roomId;
            openModal('damageModal');
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            document.querySelectorAll('.modal.show').forEach(modal => {
                if (event.target == modal) {
                    closeModal(modal.id);
                }
            });
        });

    </script>
</body>
</html>
<?php
$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูล
?>