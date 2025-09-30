<?php
include 'db.php'; // ตรวจสอบให้แน่ใจว่า db.php อยู่ในตำแหน่งที่ถูกต้อง
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$First_name = $_SESSION['First_name'] ?? '';
$Last_name = $_SESSION['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);

if (isset($conn)) {
    $conn->set_charset("utf8");
}

// 1. ดึงค่าพารามิเตอร์ GET จาก hotel_rooms.php
$room_id = $_GET['room_id'] ?? null;
// $room_type_id_passed_from_hotel_rooms = intval($_GET['room_type_id_passed'] ?? 0); // ถูกคอมเมนต์ออก
$price_per_room_from_get = floatval($_GET['price'] ?? 0); // ราคาจาก GET (จะถูกแทนที่ด้วยราคาจาก DB หากพบข้อมูล)
$checkin_date_str = $_GET['checkin_date'] ?? date("Y-m-d");
$checkout_date_str = $_GET['checkout_date'] ?? date("Y-m-d", strtotime($checkin_date_str . " +1 day"));
$num_rooms = intval($_GET['num_rooms'] ?? 1);
$total_adults = intval($_GET['total_adults'] ?? 1);
$total_children = intval($_GET['total_children'] ?? 0);
$province_id = isset($_GET['province_id']) ? intval($_GET['province_id']) : null;

// ตรวจสอบพารามิเตอร์ที่จำเป็น
if (!$room_id || !$province_id) {
    header("Location: hotel_rooms.php"); // ถ้าข้อมูลไม่ครบ Redirect กลับไปเลือกห้อง
    exit();
}

// 2. ดึงรายละเอียดห้องพักจากฐานข้อมูล
$room_info = [];
// กำหนดค่าเริ่มต้นสำหรับราคาต่อห้อง เพื่อใช้ในกรณีที่ดึงข้อมูลห้องจาก DB ไม่สำเร็จ
$price_per_room = $price_per_room_from_get; 

if ($room_id && $conn) {
    $sql_room = "SELECT Room_Id, Price, Room_number, Room_type_Id, Number_of_people_staying
                  FROM room
                  WHERE Room_Id = ?";
    $stmt_room = $conn->prepare($sql_room);
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
        // ใช้ราคาที่ดึงจากฐานข้อมูลเป็นหลัก
        $price_per_room = floatval($room_info['Price']);
    }
    $stmt_room->close();
}


// 3. ดึงชื่อจังหวัด/สาขาจากฐานข้อมูล
$province_name = '';
if ($province_id && $conn) {
    $sql_province = "SELECT Province_name FROM province WHERE Province_Id = ?";
    $stmt_province = $conn->prepare($sql_province);
    $stmt_province->bind_param('i', $province_id);
    $stmt_province->execute();
    $stmt_province->bind_result($province_name_db);
    $stmt_province->fetch();
    $province_name = $province_name_db;
    $stmt_province->close();
}

// Calculate num_nights and total_price
$num_nights = 1; // Initial value
try {
    if (!empty($checkin_date_str) && !empty($checkout_date_str)) {
        $checkin_date_obj = new DateTime($checkin_date_str);
        $checkout_date_obj = new DateTime($checkout_date_str);
        if ($checkout_date_obj <= $checkin_date_obj) {
            // Ensure checkout is at least 1 day after checkin if dates are invalid
            $checkout_date_obj = clone $checkin_date_obj;
            $checkout_date_obj->modify('+1 day');
            // Update the string representations as well
            $checkin_date_str = $checkin_date_obj->format('Y-m-d');
            $checkout_date_str = $checkout_date_obj->format('Y-m-d');
        }
        $interval = $checkin_date_obj->diff($checkout_date_obj);
        $num_nights = max(1, (int)$interval->days);
    }
} catch (Exception $e) {
    // Fallback if date parsing fails
    $num_nights = 1;
}
$total_price = ($price_per_room * $num_rooms) * $num_nights;

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <title>ยืนยันการจอง - HOP INN</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../CSS/css/ino.css" />
    <link rel="stylesheet" href="../CSS/css/hotel_rooms.css" />
    <link rel="stylesheet" href="../CSS/css/modal_style.css" />
    <link rel="stylesheet" href="../CSS/css/booking_confirmation.css" />
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
        <style>
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
    </style>
    </header>

    <main class="confirmation-container">
        <h1>ยืนยันการจองของคุณ:<?= htmlspecialchars($full_name) ?></h1>

        <div class="booking-summary" style="display: flex; justify-content: center;">
            <!-- เดิมทีเป็น div class="room-details-card" ถูกลบออกไปแล้ว -->

            <div class="booking-overview" style="width: 100%; max-width: 500px;"> <!-- ปรับ width เพื่อให้เนื้อหาอยู่ตรงกลาง -->
                <h3>ข้อมูลการจองของคุณ</h3>
                <p>สาขา: <b><?= htmlspecialchars($province_name) ?></b></p>

                <!-- Guest selection controls, similar to hotel_rooms.php -->
                <div id="rooms-container" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                    <div class="room-input-group">
                        <label for="num-rooms">จำนวนห้อง:</label>
                        <input type="number" id="num-rooms" value="<?= htmlspecialchars($num_rooms) ?>" min="1" max="5" onchange="updateRoomsFromInput()">
                    </div>
                    <?php
                    for ($r = 1; $r <= $num_rooms; $r++):
                        $current_adults = $adults_per_room_initial[$r - 1] ?? 1;
                        $current_children = $children_per_room_initial[$r - 1] ?? 0;
                    ?>
                        <div class="room" data-room="<?= $r ?>" style="margin-bottom: 10px; padding: 10px; border: 1px solid #f0f0f0; border-radius: 5px; background-color: #fff;">
                            <h4>ห้องที่ <?= $r ?></h4>
                            <div class="guest-group">
                                <span>ผู้ใหญ่</span>
                                <button type="button" onclick="changeGuest(this, 'adult', -1)">–</button>
                                <span class="adult-count"><?= htmlspecialchars($current_adults) ?></span>
                                <button type="button" onclick="changeGuest(this, 'adult', 1)">+</button>
                            </div>
                            <div class="guest-group">
                                <span>เด็ก</span>
                                <button type="button" onclick="changeGuest(this, 'child', -1)">–</button>
                                <span class="child-count"><?= htmlspecialchars($current_children) ?></span>
                                <button type="button" onclick="changeGuest(this, 'child', 1)">+</button>
                            </div>
                            <div class="child-age-container" style="display:none; margin-top:8px;">
                                <label>อายุของเด็กแต่ละคน (ปี):</label>
                                <div class="child-age-list"></div>
                            </div>
                        </div>
                    <?php endfor; ?>

                    <div class="guest-summary" style="margin-top: 15px;">
                        <input id="guest-summary-input" type="text" readonly value="ผู้ใหญ่ <?= htmlspecialchars($total_adults) ?>, เด็ก <?= htmlspecialchars($total_children) ?> คน" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #f8f8f8;" />
                    </div>
                </div>
                <!-- End Guest selection controls -->

                <div class="date-selection-area">
                    <p>วันที่เช็คอิน: <span id="display-checkin-date"><b><?= htmlspecialchars($checkin_date_str) ?></b></span></p>
                    <p>วันที่เช็คเอาท์: <span id="display-checkout-date"><b><?= htmlspecialchars($checkout_date_str) ?></b></span></p>
                </div>

                <p>จำนวนคืน: <span id="display-num-nights"><b><?= htmlspecialchars($num_nights) ?></b></span></p>
                <p class="final-price">รวมยอดชำระ: ฿ <span id="display-total-price"><b><?= number_format($total_price, 2) ?></b></span></p>
            </div>
        </div>

        <div class="action-buttons">
            <button onclick="history.back()" class="btn-back">กลับไปเลือกห้อง</button>
            <form id="confirmBookingForm" action="payment.php" method="get">
                <!-- Hidden inputs สำหรับส่งข้อมูลทั้งหมดไปยังหน้า payment.php -->
                <input type="hidden" name="room_id" id="form-room-id" value="<?= htmlspecialchars($room_id) ?>">
                <input type="hidden" name="room_type_id" id="form-room-type-id" value="<?= htmlspecialchars($room_info['Room_type_Id'] ?? '') ?>">
                <input type="hidden" name="price" id="form-price-per-room" value="<?= htmlspecialchars($price_per_room) ?>">
                <input type="hidden" name="checkin_date" id="form-checkin-date" value="<?= htmlspecialchars($checkin_date_str) ?>">
                <input type="hidden" name="checkout_date" id="form-checkout-date" value="<?= htmlspecialchars($checkout_date_str) ?>">
                <input type="hidden" name="num_rooms" id="form-num-rooms" value="<?= htmlspecialchars($num_rooms) ?>">
                <input type="hidden" name="total_adults" id="form-total-adults" value="<?= htmlspecialchars($total_adults) ?>">
                <input type="hidden" name="total_children" id="form-total-children" value="<?= htmlspecialchars($total_children) ?>">
                <input type="hidden" name="province_id" id="form-province-id" value="<?= htmlspecialchars($province_id) ?>">
                <button type="submit" class="btn-confirm">ยืนยันการจองและชำระเงิน</button>
            </form>
        </div>
    </main>

    <!-- Modal สำหรับปฏิทิน (DO NOT MODIFY) -->

    <script src="../JS/js/test.js"></script>
    <script src="../JS/js/booking_confirmation.js"></script>
</body>

</html>