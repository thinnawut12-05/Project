<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่า db.php อยู่ในตำแหน่งที่ถูกต้อง

// เปิดการแสดงข้อผิดพลาดทั้งหมดสำหรับการดีบัก
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 1. ตรวจสอบการเชื่อมต่อฐานข้อมูลอย่างแข็งขัน ---
if (!isset($conn) || $conn->connect_error) {
    error_log("ERROR: success_booking.php - Database connection failed: " . ($conn->connect_error ?? "Connection object not initialized."));
    header("Location: error_page.php?msg=db_connection_failed"); // เปลี่ยนเส้นทางไปหน้าข้อผิดพลาด
    exit();
}
$conn->set_charset("utf8"); // ตรวจสอบให้แน่ใจว่าใช้ชุดอักขระ UTF-8

// --- ฟังก์ชันสำหรับสร้าง ID ที่ไม่ซ้ำกันและเป็นสตริงตัวเลข 10 หลัก (สำหรับ Reservation_Id) ---
function generateUniqueVarcharId($conn, $table, $idColumn) {
    $isUnique = false;
    $newId = '';
    $maxAttempts = 100; // จำกัดจำนวนครั้งที่ลองเพื่อป้องกันลูปไม่รู้จบ

    for ($i = 0; $i < $maxAttempts && !$isUnique; $i++) {
        // สร้างตัวเลขสุ่มที่มี 10 หลัก และเป็นสตริง
        // ตัวเลขเริ่มต้นที่ 1,000,000,000 และไม่เกิน 9,999,999,999 เพื่อให้ได้ 10 หลักเสมอ
        $newId = (string)mt_rand(1000000000, 9999999999); 
        error_log("DEBUG: generateUniqueVarcharId - Attempt $i - Generated ID: " . $newId);

        $check_sql = "SELECT 1 FROM $table WHERE $idColumn = ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt) {
            $check_stmt->bind_param("s", $newId); // 's' เพราะ Reservation_Id เป็น VARCHAR
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows == 0) {
                $isUnique = true; // พบ ID ที่ไม่ซ้ำกัน
                error_log("DEBUG: generateUniqueVarcharId - Found unique ID: " . $newId);
            } else {
                error_log("DEBUG: generateUniqueVarcharId - ID " . $newId . " is NOT unique, trying again.");
            }
            $check_stmt->close();
        } else {
            error_log("ERROR: generateUniqueVarcharId - Failed to prepare unique ID check statement for $table.$idColumn: " . $conn->error);
            die("Error checking for unique ID.");
        }
    }

    if (!$isUnique) {
        error_log("CRITICAL ERROR: generateUniqueVarcharId - Failed to generate a unique ID for $table.$idColumn after $maxAttempts attempts. Last ID tried: " . $newId);
        die("Error: Could not generate a unique Reservation ID.");
    }
    return $newId;
}


// --- 2. ตรวจสอบข้อมูลการจองชั่วคราวใน session ---
if (!isset($_SESSION['temp_booking_data']) || empty($_SESSION['temp_booking_data'])) {
    error_log("ERROR: success_booking.php - Missing temporary booking data in session. Redirecting to hotel_rooms.php.");
    header("Location: hotel_rooms.php?error=no_temp_booking_data");
    exit();
}

$booking_data = $_SESSION['temp_booking_data'];

$First_name = $booking_data['First_name'] ?? '';
$Last_name = $booking_data['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);
$email_member = $booking_data['email_member'] ?? '';

// --- 3. ตรวจสอบข้อมูลสำคัญอีกครั้งก่อนดำเนินการ ---
if (empty($booking_data['room_id']) || empty($booking_data['province_id']) || empty($email_member) || !isset($booking_data['total_price'])) {
    error_log("ERROR: success_booking.php - Missing critical booking data. Session data: " . json_encode($booking_data));
    unset($_SESSION['temp_booking_data']);
    header("Location: hotel_rooms.php?error=missing_critical_data");
    exit();
}

// === เริ่มต้นการบันทึกข้อมูลการจองลงฐานข้อมูล ===

// *** จุดสำคัญ: สร้าง Reservation_Id ที่ไม่ซ้ำกันและเป็น VARCHAR(10) ***
$reservation_id = generateUniqueVarcharId($conn, 'reservation', 'Reservation_Id');
error_log("DEBUG: success_booking.php - Final Reservation_Id to INSERT: " . $reservation_id . " (Type: " . gettype($reservation_id) . ")");


// กำหนดสถานะการจองเริ่มต้นเป็น 'รอการชำระเงิน' (สมมติว่า Booking_status_Id = 1)
$booking_status_id = 1;

// คำสั่ง INSERT เพื่อบันทึกข้อมูลการจองใหม่
$sql_insert = "INSERT INTO reservation (Reservation_Id, Guest_name, Number_of_rooms, Number_of_adults,
                                        Number_of_children, Booking_date, Check_out_date,
                                        Email_member, Province_Id, Booking_status_Id, Booking_time, Total_price)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

$stmt_insert = $conn->prepare($sql_insert);

// --- การจัดการข้อผิดพลาดสำหรับ prepare statement ---
if ($stmt_insert === false) {
    error_log("ERROR: success_booking.php - Failed to prepare reservation insert statement: " . $conn->error);
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
}

// กำหนดประเภทพารามิเตอร์สำหรับ bind_param
// 's' (สำหรับ Reservation_Id ที่เป็น VARCHAR), s,i,i,i,s,s,s,i,i,d
$bind_types = 'ssiiisssiid'; // 's' สำหรับ Reservation_Id, 's' สำหรับ Booking_time (NOW()), 'd' สำหรับ Total_price
$bind_values = [
    $reservation_id, // ใส่ Reservation_Id ที่สร้างเป็น VARCHAR
    $full_name,
    $booking_data['num_rooms'],
    $booking_data['total_adults'],
    $booking_data['total_children'],
    $booking_data['checkin_date'],
    $booking_data['checkout_date'],
    $email_member,
    $booking_data['province_id'],
    $booking_status_id,
    $booking_data['total_price'] // Total_price
];

$bind_param_args = [$bind_types]; 
foreach ($bind_values as &$val) { 
    $bind_param_args[] = &$val; 
}
call_user_func_array([$stmt_insert, 'bind_param'], $bind_param_args);

// --- ดำเนินการคำสั่งและจัดการข้อผิดพลาด ---
if ($stmt_insert->execute()) {
    error_log("DEBUG: success_booking.php - Reservation INSERT successful. Reservation_Id used: " . $reservation_id);
    // ไม่ต้องมี SELECT เพื่อยืนยันอีกแล้ว เพราะ generateUniqueVarcharId ได้ยืนยันความไม่ซ้ำและประเภทข้อมูลแล้ว

    $_SESSION['current_reservation_id'] = $reservation_id;
    $_SESSION['total_price'] = $booking_data['total_price'];

    error_log("DEBUG: success_booking.php - Session current_reservation_id set to: " . $_SESSION['current_reservation_id']);
    error_log("DEBUG: success_booking.php - Session total_price set to: " . $_SESSION['total_price']);

    unset($_SESSION['expire_time']);
    unset($_SESSION['booking_params_hash']);
    unset($_SESSION['temp_booking_data']);
} else {
    error_log("ERROR: success_booking.php - Error inserting reservation: " . $stmt_insert->error);
    die("เกิดข้อผิดพลาดในการบันทึกการจอง: " . $stmt_insert->error);
}
$stmt_insert->close();
// === สิ้นสุดการบันทึกข้อมูลการจองลงฐานข้อมูล ===


// สร้าง Query String สำหรับส่งไปยัง payment.php
$payment_link_query = http_build_query([
    'reservation_id' => $reservation_id, // ใช้ ID ที่เป็น VARCHAR(10)
    'total_price' => $booking_data['total_price']
]);

error_log("DEBUG: success_booking.php - Constructed payment_link_query: " . $payment_link_query);
error_log("DEBUG: success_booking.php - Full URL for payment button: payment.php?" . $payment_link_query);


// ดึง room_info อีกครั้งเพื่อแสดงผล (จากฐานข้อมูล)
$room_info = [];
if (isset($booking_data['room_id']) && $booking_data['room_id'] && $conn) {
    $sql_room = "SELECT Room_Id, Price, Room_number, Room_type_Id, Number_of_people_staying
                  FROM room
                  WHERE Room_Id = ?";
    $stmt_room_display = $conn->prepare($sql_room);
    if ($stmt_room_display) {
        $stmt_room_display->bind_param('i', $booking_data['room_id']);
        $stmt_room_display->execute();
        $result_room_display = $stmt_room_display->get_result();
        if ($row_display = $result_room_display->fetch_assoc()) {
            $room_info = $row_display;
            if ($room_info['Room_type_Id'] == 1) {
                $room_info['name'] = "ห้องมาตรฐาน เตียงใหญ่";
                $room_info['description'] = "ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน(อายุต่ำกว่า 12 ปี)";
                $room_info['capacity_display'] = $room_info['Number_of_people_staying'] . " คน";
                $room_info['guests_display'] = "2 ผู้ใหญ่, 1 เด็ก";
                $room_info['bed_type'] = "1 เตียงใหญ่";
                $room_info['images'] = ["../src/images/1.jpg", "../src/images/6.avif"];
            } elseif ($room_info['Room_type_Id'] == 2) {
                $room_info['name'] = "ห้องมาตรฐาน เตียงคู่";
                $room_info['description'] = "ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน(อายุต่ำกว่า 12 ปี)";
                $room_info['capacity_display'] = $room_info['Number_of_people_staying'] . " คน";
                $room_info['guests_display'] = "2 ผู้ใหญ่, 1 เด็ก";
                $room_info['bed_type'] = "2 เตียงเดี่ยว";
                $room_info['images'] = ["../src/images/2.jpg", "../src/images/6.avif"];
            }
        }
        $stmt_room_display->close();
    } else {
        error_log("ERROR: success_booking.php - Failed to prepare room select statement for display: " . $conn->error);
    }
}
$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูล
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <title>การจองสำเร็จ - HOP INN</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../CSS/css/ino.css" />
    <link rel="stylesheet" href="../CSS/css/booking_confirmation.css" />
    <style>
        /* (Styles as provided previously in success_booking.php) */
        .profile-link,
        .profile-link:visited {
            text-decoration: none;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .profile-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .profile-link:active {
            color: #fff;
        }

        .success-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-container h1 {
            color: #28a745;
            margin-bottom: 20px;
            font-size: 2.5em;
        }

        .success-container p {
            font-size: 1.1em;
            color: #555;
            margin-bottom: 10px;
        }

        .success-container p b {
            color: #007bff;
        }

        .room-image {
            max-width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .action-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .action-buttons .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            font-size: 1.05em;
            border: none;
            cursor: pointer;
        }

        .action-buttons .btn-pay {
            background: #007bff;
            color: #fff;
        }

        .action-buttons .btn-pay:hover {
            background: #0056b3;
        }

        .action-buttons .btn-status {
            background: #ffc107;
            color: #333;
        }

        .action-buttons .btn-status:hover {
            background: #e0a800;
        }

        .action-buttons .btn-home {
            background: #6c757d;
            color: #fff;
        }

        .action-buttons .btn-home:hover {
            background: #5a6268;
        }

        .room-details-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
            text-align: left;
            border: 1px solid #e9ecef;
        }

        .room-details-section h3 {
            color: #007bff;
            margin-bottom: 15px;
            text-align: center;
        }

        .room-details-section p {
            margin-bottom: 8px;
        }

        .reservation-id-display {
            font-size: 1.2em;
            color: #007bff;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <header>
        <section class="logo">
            <img src="../src/images/4.png" width="50" height="50" alt="Logo" />
        </section>
        <nav>
            <a href="./type.php">ประเภทห้องพัก</a>
            <a href="./branch.php">สาขาโรงแรมดอม อินน์</a>
            <a href="./details.php">รายละเอียดต่างๆ</a>
            <a href="./booking_status_pending.php">การจองของฉัน</a>
            <a href="./score.php">คะแนน</a>
        </nav>
        <?php if ($full_name && $full_name !== ' '): ?>
            <div class="user-display">
                <a href="profile.php" class="profile-link"><?= htmlspecialchars($full_name) ?></a>
            </div>
        <?php endif; ?>
    </header>

    <main class="success-container">
        <h1><i class="fas fa-check-circle"></i> การจองของคุณสำเร็จแล้ว!</h1>
        <p>คุณ <b><?= htmlspecialchars($full_name) ?></b> ขอบคุณที่เลือกใช้บริการของเรา</p>
        <?php if ($reservation_id): ?>
            <p class="reservation-id-display">รหัสการจองของคุณคือ: <b>#<?= htmlspecialchars($reservation_id) ?></b></p>
        <?php else: ?>
            <p style='color:red;'>ไม่สามารถสร้างรหัสการจองได้ในขณะนี้ กรุณาติดต่อผู้ดูแลระบบ</p>
        <?php endif; ?>
        <p>รายละเอียดการจองของคุณมีดังนี้:</p>

        <div class="room-details-section">
            <h3>ข้อมูลห้องพักและสาขา</h3>
            <p><b>สาขา:</b> <?= htmlspecialchars($booking_data['province_name'] ?? 'ไม่ระบุ') ?></p>
        </div>

        <div class="room-details-section" style="margin-top: 20px;">
            <h3>สรุปการจอง</h3>
            <p><b>วันที่เช็คอิน:</b> <?= htmlspecialchars($booking_data['checkin_date'] ?? 'ไม่ระบุ') ?></p>
            <p><b>วันที่เช็คเอาท์:</b> <?= htmlspecialchars($booking_data['checkout_date'] ?? 'ไม่ระบุ') ?></p>
            <p><b>จำนวนคืน:</b> <?= htmlspecialchars($booking_data['num_nights'] ?? 'ไม่ระบุ') ?> คืน</p>
            <p><b>จำนวนห้อง:</b> <?= htmlspecialchars($booking_data['num_rooms'] ?? 'ไม่ระบุ') ?> ห้อง</p>
            <p><b>จำนวนผู้ใหญ่:</b> <?= htmlspecialchars($booking_data['total_adults'] ?? 'ไม่ระบุ') ?> ท่าน</p>
            <p><b>จำนวนเด็ก:</b> <?= htmlspecialchars($booking_data['total_children'] ?? 'ไม่ระบุ') ?> ท่าน</p>
            <p style="font-size: 1.3em; color: #dc3545; font-weight: bold; margin-top: 15px;"><b>ยอดรวมที่ต้องชำระ:</b> ฿ <?= number_format($booking_data['total_price'] ?? 0, 2) ?></p>
        </div>

        <div class="action-buttons">
            <?php if ($reservation_id): // แสดงปุ่มเหล่านี้เฉพาะเมื่อการจองสำเร็จและมี reservation_id 
            ?>
                <a href="payment.php?<?= htmlspecialchars($payment_link_query) ?>" class="btn btn-pay">ดำเนินการชำระเงิน</a>
                <a href="booking_status_pending.php" class="btn btn-status">ดูสถานะการจองของฉัน</a>
            <?php endif; ?>
            <a href="home.php" class="btn btn-home">กลับหน้าหลัก</a>
        </div>
    </main>
</body>

</html>