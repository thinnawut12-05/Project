<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่า db.php อยู่ในตำแหน่งที่ถูกต้อง
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$First_name = $_SESSION['First_name'] ?? '';
$Last_name = $_SESSION['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);
$email_member = $_SESSION['email'] ?? ''; // ดึงอีเมลผู้ใช้จาก session

if (isset($conn)) {
    $conn->set_charset("utf8");
}

// 1. ดึงค่าพารามิเตอร์ GET จาก hotel_rooms.php (หรือจาก session หากมีการย้อนกลับมา)
// ใช้ GET เป็นหลัก หากมีการส่งมาจากหน้าก่อน
$room_id = $_GET['room_id'] ?? ($_SESSION['temp_booking_data']['room_id'] ?? null);
$room_type_id = intval($_GET['room_type_id'] ?? ($_SESSION['temp_booking_data']['room_type_id'] ?? 0));
$price_per_room_from_get = floatval($_GET['price'] ?? ($_SESSION['temp_booking_data']['price'] ?? 0));
$checkin_date_str = $_GET['checkin_date'] ?? ($_SESSION['temp_booking_data']['checkin_date'] ?? date("Y-m-d"));
$checkout_date_str = $_GET['checkout_date'] ?? ($_SESSION['temp_booking_data']['checkout_date'] ?? date("Y-m-d", strtotime($checkin_date_str . " +1 day")));
$num_rooms = intval($_GET['num_rooms'] ?? ($_SESSION['temp_booking_data']['num_rooms'] ?? 1));
$total_adults = intval($_GET['total_adults'] ?? ($_SESSION['temp_booking_data']['total_adults'] ?? 1));
$total_children = intval($_GET['total_children'] ?? ($_SESSION['temp_booking_data']['total_children'] ?? 0));
$province_id = isset($_GET['province_id']) ? intval($_GET['province_id']) : ($_SESSION['temp_booking_data']['province_id'] ?? null);

// ตรวจสอบพารามิเตอร์ที่จำเป็น และผู้ใช้ต้องล็อกอิน
if (!$room_id || !$province_id || empty($email_member)) {
    header("Location: hotel_rooms.php"); // ถ้าข้อมูลไม่ครบ หรือไม่ได้ล็อกอิน Redirect กลับไป
    exit();
}

// 2. ดึงรายละเอียดห้องพักจากฐานข้อมูล
$room_info = [];
$price_per_room = $price_per_room_from_get; 

if ($room_id && $conn) {
    $sql_room = "SELECT Room_Id, Price, Room_number, Room_type_Id, Number_of_people_staying
                  FROM room
                  WHERE Room_Id = ?";
    $stmt_room = $conn->prepare($sql_room);
    if ($stmt_room) {
        $stmt_room->bind_param('i', $room_id);
        $stmt_room->execute();
        $result_room = $stmt_room->get_result();
        if ($row = $result_room->fetch_assoc()) {
            $room_info = $row;
            // กำหนดข้อมูลเพิ่มเติมสำหรับแสดงผลตาม Room_type_Id ที่ได้จาก DB
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
            $price_per_room = floatval($room_info['Price']);
            $room_type_id = $room_info['Room_type_Id'];
        }
        $stmt_room->close();
    } else {
        error_log("Failed to prepare room select statement: " . $conn->error);
    }
}


// 3. ดึงชื่อจังหวัด/สาขาจากฐานข้อมูล
$province_name = '';
if ($province_id && $conn) {
    $sql_province = "SELECT Province_name FROM province WHERE Province_Id = ?";
    $stmt_province = $conn->prepare($sql_province);
    if ($stmt_province) {
        $stmt_province->bind_param('i', $province_id);
        $stmt_province->execute();
        $stmt_province->bind_result($province_name_db);
        $stmt_province->fetch();
        $province_name = $province_name_db;
        $stmt_province->close();
    } else {
        error_log("Failed to prepare province select statement: " . $conn->error);
    }
}

// คำนวณจำนวนคืนและราคารวม
$num_nights = 1;
try {
    if (!empty($checkin_date_str) && !empty($checkout_date_str)) {
        $checkin_date_obj = new DateTime($checkin_date_str);
        $checkout_date_obj = new DateTime($checkout_date_str);
        if ($checkout_date_obj <= $checkin_date_obj) {
            $checkout_date_obj = clone $checkin_date_obj;
            $checkout_date_obj->modify('+1 day');
            $checkin_date_str = $checkin_date_obj->format('Y-m-d');
            $checkout_date_str = $checkout_date_obj->format('Y-m-d');
        }
        $interval = $checkin_date_obj->diff($checkout_date_obj);
        $num_nights = max(1, (int)$interval->days);
    }
} catch (Exception $e) {
    $num_nights = 1;
}
$total_price = ($price_per_room * $num_rooms) * $num_nights;


// *** สำคัญ: เก็บข้อมูลการจองทั้งหมดลงใน Session เพื่อให้ success_booking.php เรียกใช้ ***
// ล้างข้อมูลการจองชั่วคราวเก่าก่อน หากมี
unset($_SESSION['temp_booking_data']);

$_SESSION['temp_booking_data'] = [
    'First_name' => $First_name,
    'Last_name' => $Last_name,
    'full_name' => $full_name,
    'email_member' => $email_member,
    'room_id' => $room_id,
    'room_type_id' => $room_type_id,
    'price' => $price_per_room, // ราคาต่อห้อง
    'checkin_date' => $checkin_date_str,
    'checkout_date' => $checkout_date_str,
    'num_rooms' => $num_rooms,
    'total_adults' => $total_adults,
    'total_children' => $total_children,
    'province_id' => $province_id,
    'province_name' => $province_name,
    'num_nights' => $num_nights,
    'total_price' => $total_price, // ราคารวม
];

// ในทางกลับกัน ถ้ามี $_SESSION['current_reservation_id'] จากการจองค้างเก่าให้ลบทิ้งไป
unset($_SESSION['current_reservation_id']);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>ยืนยันการจอง - HOP INN</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../CSS/css/ino.css" />
    <link rel="stylesheet" href="../CSS/css/booking_confirmation.css" />
    <style>
        /* (Styles as provided previously in success_booking.php) */
        .profile-link, .profile-link:visited {
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
        .confirmation-container { /* Changed from .success-container */
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .confirmation-container h1 {
            color: #007bff;
            margin-bottom: 20px;
            font-size: 2.5em;
        }
        .confirmation-container p {
            font-size: 1.1em;
            color: #555;
            margin-bottom: 10px;
        }
        .confirmation-container p b {
            color: #007bff;
        }
        .room-image {
            max-width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
        .action-buttons .btn-confirm { /* Changed class for the confirm button */
            background: #28a745;
            color: #fff;
        }
        .action-buttons .btn-confirm:hover {
            background: #218838;
        }
        .action-buttons .btn-back {
            background: #6c757d;
            color: #fff;
        }
        .action-buttons .btn-back:hover {
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

    <main class="confirmation-container">
        <h1>ยืนยันการจองของคุณ: <?= htmlspecialchars($full_name) ?></h1>
        <p>โปรดตรวจสอบรายละเอียดการจองด้านล่างก่อนดำเนินการต่อ</p>

 
        <div class="room-details-section" style="margin-top: 20px;">
            <h3>สรุปการจอง</h3>
            <p><b>วันที่เช็คอิน:</b> <?= htmlspecialchars($checkin_date_str) ?></p>
            <p><b>วันที่เช็คเอาท์:</b> <?= htmlspecialchars($checkout_date_str) ?></p>
            <p><b>จำนวนคืน:</b> <?= htmlspecialchars($num_nights) ?> คืน</p>
            <p><b>จำนวนห้อง:</b> <?= htmlspecialchars($num_rooms) ?> ห้อง</p>
            <p><b>จำนวนผู้ใหญ่:</b> <?= htmlspecialchars($total_adults) ?> ท่าน</p>
            <p><b>จำนวนเด็ก:</b> <?= htmlspecialchars($total_children) ?> ท่าน</p>
            <p style="font-size: 1.3em; color: #dc3545; font-weight: bold; margin-top: 15px;"><b>ยอดรวมที่ต้องชำระ:</b> ฿ <?= number_format($total_price, 2) ?></p>
        </div>

        <div class="action-buttons">
            <button onclick="history.back()" class="btn btn-back">แก้ไขการจอง</button>
            <!-- Changed to a form that submits to the actual success_booking.php -->
            <form action="success_booking.php" method="post" style="display:inline;">
                <button type="submit" class="btn btn-confirm">ยืนยันการจอง</button>
            </form>
        </div>
    </main>
</body>
</html>