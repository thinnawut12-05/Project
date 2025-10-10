<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่าเส้นทางนี้ถูกต้อง

// เปิดการแสดงข้อผิดพลาดทั้งหมดสำหรับการดีบัก
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['email'])) {
    header('Location: login.php'); // เปลี่ยนเส้นทางไปหน้าล็อกอินหากยังไม่ได้ล็อกอิน
    exit();
}

// *** ดึง Reservation_Id จาก GET ก่อน (จาก success_booking.php) หรือจาก session เป็นตัวสำรอง
$reservation_id = $_GET['reservation_id'] ?? ($_SESSION['current_reservation_id'] ?? null);

if (!$reservation_id) {
    error_log("Error: reservation_id is missing in payment.php for user " . ($_SESSION['email'] ?? 'unknown'));
    header('Location: home.php?error=no_reservation_id_found'); // หรือหน้าแจ้งข้อผิดพลาดอื่นๆ
    exit();
}

// เก็บ reservation_id ใน session เพื่อให้ upload_slip.php ใช้
$_SESSION['current_reservation_id'] = $reservation_id;

// --- ดึงข้อมูลการจองที่เหลือจาก DB ด้วย reservation_id ---
$full_name = "";
$num_rooms = 0;
$total_adults = 0;
$total_children = 0;
$checkin_date_str = "";
$checkout_date_str = "";
$province_id = null;
$province_name = 'ไม่ได้ระบุ';
$total_price = 0;
$num_nights = 0;
$booking_status_id_current = 0; // เพิ่มเพื่อเก็บสถานะปัจจุบัน

$sql_fetch_booking = "SELECT Guest_name, Number_of_rooms, Number_of_adults, Number_of_children,
                      Booking_date, Check_out_date, Province_Id, Total_price, Booking_status_Id
                      FROM reservation WHERE Reservation_Id = ?";
$stmt_fetch = $conn->prepare($sql_fetch_booking);
if ($stmt_fetch) {
    $stmt_fetch->bind_param("s", $reservation_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    if ($row_fetch = $result_fetch->fetch_assoc()) {
        $full_name = $row_fetch['Guest_name'];
        $num_rooms = $row_fetch['Number_of_rooms'];
        $total_adults = $row_fetch['Number_of_adults'];
        $total_children = $row_fetch['Number_of_children'];
        $checkin_date_str = $row_fetch['Booking_date'];
        $checkout_date_str = $row_fetch['Check_out_date'];
        $province_id = $row_fetch['Province_Id'];
        $total_price = $row_fetch['Total_price'];
        $booking_status_id_current = $row_fetch['Booking_status_Id']; // เก็บสถานะปัจจุบัน

        // คำนวณจำนวนคืนจากวันที่ที่ดึงจาก DB
        try {
            $checkin_date_obj = new DateTime($checkin_date_str);
            $checkout_date_obj = new DateTime($checkout_date_str);
            $interval = $checkin_date_obj->diff($checkout_date_obj);
            $num_nights = max(1, (int)$interval->days);
        } catch (Exception $e) {
            $num_nights = 1;
        }

        // ดึงชื่อจังหวัด
        $sql_province = "SELECT Province_name FROM province WHERE Province_Id = ?";
        $stmt_province = $conn->prepare($sql_province);
        if ($stmt_province) {
            $stmt_province->bind_param('i', $province_id);
            $stmt_province->execute();
            $stmt_province->bind_result($province_name_db);
            $stmt_province->fetch();
            $province_name = $province_name_db;
            $stmt_province->close();
        }
    } else {
        // ไม่พบข้อมูลการจองใน DB
        error_log("No booking found for reservation_id: " . $reservation_id);
        $_SESSION['error'] = 'ไม่พบข้อมูลการจองที่ต้องการชำระเงิน.';
        header('Location: home.php');
        exit();
    }
    $stmt_fetch->close();
} else {
    error_log("Failed to prepare statement for fetching booking: " . $conn->error);
    $_SESSION['error'] = 'เกิดข้อผิดพลาดในการดึงข้อมูลการจองเพื่อชำระเงิน.';
    header('Location: home.php');
    exit();
}

$phone = "0967501732"; // หมายเลขโทรศัพท์สำหรับ PromptPay
$qr_url = "https://promptpay.io/$phone/$total_price.png";

// === การจัดการ expire_time เพื่อไม่ให้รีเซ็ตเมื่อรีเฟรชหน้าจอ ===
// ตั้งค่า expire_time ใน session หากยังไม่มี หรือหมดอายุไปแล้ว
// ในโค้ดเดิมของคุณตั้งไว้ 30 วินาทีเพื่อทดสอบ
// ควรตั้งเป็น (24 * 60 * 60) สำหรับ 24 ชั่วโมงใน Production
if (!isset($_SESSION['expire_time'])) {
    $_SESSION['expire_time'] = time() + 40; //(24 * 60 * 60); // 24 ชั่วโมง
}
$expire_time = $_SESSION['expire_time'];

// ตรวจสอบสถานะการจอง หากไม่ใช่ 'รอการชำระเงิน' (สถานะ 1) หรือหมดเวลาแล้ว
// ให้ redirect ออกไป
if ($booking_status_id_current != 1) { // ตรวจสอบว่าสถานะยังเป็น 'รอการชำระเงิน'
    // หากสถานะไม่ใช่ 1 แล้ว เช่น ถูกยกเลิกไปแล้ว, ชำระแล้ว
    $_SESSION['error'] = 'การจองนี้ไม่สามารถชำระเงินได้แล้ว (สถานะการจองไม่อนุญาต).';
    header('Location: booking_status_pending.php'); // Redirect ไปหน้าสถานะการจอง
    exit();
}

// ตรวจสอบเวลาหมดอายุ ณ จุดที่โหลดหน้า
if (time() > $expire_time) {
    // ถ้าเวลาหมดอายุแล้ว
    // อัปเดตสถานะการจองเป็น 4 (ยกเลิกการจองเนื่องจากไม่ชำระเงินภายใน 24 ชม.)
    $status_id_cancelled_timeout = 4; // กำหนดสถานะ 4

    $sql_update_status = "UPDATE reservation SET Booking_status_Id = ? WHERE Reservation_Id = ?";
    $stmt_update_status = $conn->prepare($sql_update_status);
    if ($stmt_update_status) {
        $stmt_update_status->bind_param("is", $status_id_cancelled_timeout, $reservation_id);
        $stmt_update_status->execute();
        $stmt_update_status->close();
    } else {
        error_log("Failed to prepare update status after timeout: " . $conn->error);
    }
    $conn->close(); // ปิด connection ก่อน redirect

    $_SESSION['error'] = 'การชำระเงินหมดเวลาแล้ว! การจองของคุณถูกยกเลิก.';
    header('Location: booking_status_pending.php'); // Redirect ไปหน้าสถานะการจอง
    exit();
}


// ให้แน่ใจว่า Province_Id และ Province_name ถูกเก็บใน Session สำหรับหน้าอื่นๆ ถ้าจำเป็น
$_SESSION['province_id'] = $province_id;
$_SESSION['province_name'] = $province_name;

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ชำระเงิน</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../CSS/css/ino.css" />
    <link rel="stylesheet" href="../CSS/css/hotel_rooms.css" />
    <link rel="stylesheet" href="../CSS/css/modal_style.css" />
    <style>
        /* Styles as provided previously */
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

        body {
            font-family: 'Tahoma', sans-serif;
            background: #f9f9f9;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            color: #0056b3;
            margin-bottom: 20px;
        }

        p {
            margin-bottom: 10px;
            font-size: 16px;
        }

        p b {
            color: #007bff;
        }

        .price {
            font-size: 26px;
            font-weight: bold;
            color: #28a745;
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #28a745;
            border-radius: 8px;
            display: inline-block;
        }

        .countdown {
            font-size: 20px;
            color: #dc3545;
            font-weight: bold;
            margin: 20px 0;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 8px;
        }

        button {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s ease;
            margin-top: 15px;
        }

        button:hover {
            background: #0056b3;
        }

        .form-group {
            margin-top: 25px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #555;
        }

        .form-group input[type="file"] {
            display: block;
            margin: 0 auto 15px auto;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .room-gallery {
            display: flex;
            justify-content: center;
            /* จัดให้อยู่ตรงกลาง */
            gap: 20px;
            /* เพิ่มระยะห่างระหว่างปุ่ม */
            margin: 20px 0;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 10px;
            background-color: #fdfdfd;
        }

        .room-gallery img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .button-group {
            display: flex;
            justify-content: center;
            /* จัดให้อยู่ตรงกลาง */
            gap: 20px;
            /* เพิ่มระยะห่างระหว่างปุ่ม */
            margin-top: 20px;
        }

        .button-group button {
            margin-top: 0;
            /* ล้าง margin-top เดิม */
        }

        .button-group .btn-pay-later {
            background: #6c757d;
            /* สีเทาสำหรับปุ่มชำระเงินภายหลัง */
        }

        .button-group .btn-pay-later:hover {
            background: #5a6268;
        }
        


    </style>
</head>

<body>
    <header>
        <section class="logo">
            <img src="../src/images/4.png" width="50" height="50" />
        </section>
        <nav>
            <a href="./type.php">ประเภทห้องพัก</a>
            <a href="./branch.php">สาขาโรงแรมดอม อินน์</a>
            <a href="./details.php">รายละเอียดต่างๆ</a>
            <a href="./booking_status_pending.php">การจองของฉัน</a>
            <a href="./rate_booking.php">คะแนน</a>
        </nav>
        <?php if ($full_name && $full_name !== ' '): ?>
            <div class="user-display">
                <a href="profile.php" class="profile-link"><?= htmlspecialchars($full_name) ?></a>
            </div>
        <?php endif; ?>
    </header>
    <div class="container">
        <h2>หน้าชำระเงิน</h2>
        <p>สวัสดีคุณ <b><?= htmlspecialchars($full_name) ?></b></p>
        <p>รหัสการจองของคุณคือ: <b><?= htmlspecialchars($reservation_id) ?></b></p>
        <p>คุณได้จองห้องพัก จำนวน <b><?= htmlspecialchars($num_rooms) ?></b> ห้อง</p>
        <p>สำหรับ <b><?= htmlspecialchars($num_nights) ?></b> คืน</p>
        <p class="price">ยอดที่ต้องชำระ: ฿ <?= number_format($total_price, 2) ?></p>
        <p>วันเช็คอิน: <b><?= htmlspecialchars($checkin_date_str) ?></b></p>
        <p>วันเช็คเอาท์: <b><?= htmlspecialchars($checkout_date_str) ?></b></p>
        <p>จำนวนผู้เข้าพักรวม: <b><?= htmlspecialchars($total_adults) ?></b> ผู้ใหญ่, <b><?= htmlspecialchars($total_children) ?></b> เด็ก</p>
        <p>สาขาที่เลือก: <b><?= htmlspecialchars($province_name) ?></b></p>

        <div class="countdown">เวลาที่เหลือในการชำระ: <span id="timer"></span></div>
        <section class="room-gallery">
            <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code" width="200" height="200">
        </section>
        <form action="upload_slip.php" method="post" enctype="multipart/form-data" class="form-group">
            <label for="slip_upload">อัพโหลดสลิปการชำระเงิน:</label>
            <input type="file" name="slip" id="slip_upload" accept="image/*" required>
            <!-- *** สำคัญ: ส่ง reservation_id ไปยัง upload_slip.php *** -->
            <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($reservation_id) ?>">
            <div class="button-group">
                <button type="submit">ยืนยันการชำระเงิน</button>
            </div>
            <div style="text-align:center;">
                <a href="home.php" class="back-btn">กลับหน้าหลัก</a>
            </div>
        </form>
    </div>
    <script>
        var expireTime = <?= $expire_time ?> * 1000;
        var timerElement = document.getElementById('timer');
        var submitButton = document.querySelector('.button-group button[type="submit"]');

        function updateTimer() {
            var now = new Date().getTime();
            var distance = expireTime - now;

            if (distance <= 0) {
                timerElement.innerHTML = "หมดเวลาชำระเงิน";
                clearInterval(countdownInterval);
                submitButton.disabled = true; // ปิดการใช้งานปุ่มยืนยัน
                submitButton.textContent = 'หมดเวลา (ไม่สามารถชำระได้)';
                submitButton.style.backgroundColor = '#cccccc';
                submitButton.style.cursor = 'not-allowed';

                // อัปเดตสถานะการจองผ่าน AJAX หรือ redirect เพื่อให้ PHP ทำการอัปเดตสถานะ
                // วิธีง่ายสุดคือ redirect ให้ PHP ทำงาน
                window.location.href = 'payment.php?reservation_id=<?= htmlspecialchars($reservation_id) ?>&timeout=true';
                return;
            }

            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            hours = String(hours).padStart(2, '0');
            minutes = String(minutes).padStart(2, '0');
            seconds = String(seconds).padStart(2, '0');

            timerElement.innerHTML = hours + " ชม. " + minutes + " นาที " + seconds + " วินาที ";
        }
        var countdownInterval = setInterval(updateTimer, 1000);
        updateTimer();
    </script>
</body>

</html>