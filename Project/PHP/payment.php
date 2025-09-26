<?php 
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่าเส้นทางนี้ถูกต้อง

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['email'])) {
    header('Location: login.php'); // เปลี่ยนเส้นทางไปหน้าล็อกอินหากยังไม่ได้ล็อกอิน
    exit();
}

// รับ province_id จาก GET/POST
$province_id_from_param = $_GET['province_id'] ?? $_POST['province_id'] ?? null;

// ถ้ามี province_id ที่ส่งมา และยังไม่ได้เก็บใน session หรือมีการเปลี่ยน province_id
if ($province_id_from_param && (!isset($_SESSION['province_id']) || $_SESSION['province_id'] != $province_id_from_param)) {
    $sql = "SELECT Province_name FROM province WHERE Province_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $province_id_from_param); // สมมติว่า Province_Id ในตาราง 'province' เป็น INT
    $stmt->execute();
    $stmt->bind_result($province_name);
    if ($stmt->fetch()) {
        $_SESSION['province_name'] = $province_name;
        $_SESSION['province_id'] = $province_id_from_param; // เก็บ province_id ใน session
    }
    $stmt->close();
}

// ดึงข้อมูลการจองจาก GET และ SESSION
$First_name = $_SESSION['First_name'] ?? '';
$Last_name = $_SESSION['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);

$room_id = $_GET['room_id'] ?? ($_SESSION['room_id'] ?? null); // ใช้จาก GET ก่อน ถ้าไม่มีใช้จาก session
$price = floatval($_GET['price'] ?? ($_SESSION['price'] ?? 0)); // ใช้จาก GET ก่อน ถ้าไม่มีใช้จาก session
$num_rooms = intval($_GET['num_rooms'] ?? ($_SESSION['num_rooms'] ?? 1)); // ใช้จาก GET ก่อน ถ้าไม่มีใช้จาก session

$checkin_date_str = $_GET['checkin_date'] ?? ($_SESSION['checkin_date'] ?? date("Y-m-d"));
$checkout_date_str = $_GET['checkout_date'] ?? ($_SESSION['checkout_date'] ?? date("Y-m-d", strtotime($checkin_date_str . " +1 day")));

$total_adults = intval($_GET['total_adults'] ?? ($_SESSION['total_adults'] ?? 1));
$total_children = intval($_GET['total_children'] ?? ($_SESSION['total_children'] ?? 0));

$num_nights = 1;
try {
    if (!empty($checkin_date_str) && !empty($checkout_date_str)) {
        $checkin_date_obj = new DateTime($checkin_date_str);
        $checkout_date_obj = new DateTime($checkout_date_str);
        if ($checkout_date_obj <= $checkin_date_obj) {
            // หากวันเช็คเอาท์ไม่ถูกต้อง ให้เพิ่ม 1 วัน
            $checkout_date_obj = clone $checkin_date_obj;
            $checkout_date_obj->modify('+1 day');
            $checkout_date_str = $checkout_date_obj->format('Y-m-d');
        }
        $interval = $checkin_date_obj->diff($checkout_date_obj);
        $num_nights = max(1, (int)$interval->days);
    }
} catch (Exception $e) {
    $num_nights = 1;
}

$total_price = ($price * $num_rooms) * $num_nights;

$phone = "0967501732"; // หมายเลขโทรศัพท์สำหรับ PromptPay
$qr_url = "https://promptpay.io/$phone/$total_price.png";

// === การจัดการ expire_time เพื่อไม่ให้รีเซ็ตเมื่อรีเฟรชหน้าจอ ===
// ตั้งค่า expire_time ใน session หากยังไม่มี หรือหมดอายุไปแล้ว
if (!isset($_SESSION['expire_time']) || $_SESSION['expire_time'] < time()) {
    $_SESSION['expire_time'] = time() + (24 * 60 * 60); // หมดอายุ 24 ชั่วโมง
}
$expire_time = $_SESSION['expire_time']; // ใช้ค่าที่เก็บใน session

// เก็บข้อมูลการจองทั้งหมดลงใน session เพื่อใช้ในหน้า save_booking_later.php
$_SESSION['num_rooms'] = $num_rooms;
$_SESSION['total_adults'] = $total_adults;
$_SESSION['total_children'] = $total_children;
$_SESSION['checkin_date'] = $checkin_date_str;
$_SESSION['checkout_date'] = $checkout_date_str;
$_SESSION['room_id'] = $room_id;
$_SESSION['total_price'] = $total_price;
$_SESSION['num_nights'] = $num_nights;
// province_id ถูกเซ็ตไปแล้วด้านบน

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ชำระเงิน</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <style>
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
            align-items: center;
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
            justify-content: center; /* จัดให้อยู่ตรงกลาง */
            gap: 20px; /* เพิ่มระยะห่างระหว่างปุ่ม */
            margin-top: 20px;
        }
        .button-group button {
            margin-top: 0; /* ล้าง margin-top เดิม */
        }
        .button-group .btn-pay-later {
            background: #6c757d; /* สีเทาสำหรับปุ่มชำระเงินภายหลัง */
        }
        .button-group .btn-pay-later:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>หน้าชำระเงิน</h2>
        <p>สวัสดีคุณ <b><?= htmlspecialchars($full_name) ?></b></p>
        <p>คุณได้จองห้องพัก จำนวน <b><?= htmlspecialchars($num_rooms) ?></b> ห้อง</p>
        <p>สำหรับ <b><?= htmlspecialchars($num_nights) ?></b> คืน</p>
        <p class="price">ยอดที่ต้องชำระ: ฿ <?= number_format($total_price, 2) ?></p>
        <p>วันเช็คอิน: <b><?= htmlspecialchars($checkin_date_str) ?></b></p>
        <p>วันเช็คเอาท์: <b><?= htmlspecialchars($checkout_date_str) ?></b></p>
        <p>จำนวนผู้เข้าพักรวม: <b><?= htmlspecialchars($total_adults) ?></b> ผู้ใหญ่, <b><?= htmlspecialchars($total_children) ?></b> เด็ก</p>
        <p>สาขาที่เลือก: <b><?= htmlspecialchars($_SESSION['province_name'] ?? 'ไม่ได้ระบุ') ?></b></p>

        <div class="countdown">เวลาที่เหลือในการชำระ: <span id="timer"></span></div>
        <section class="room-gallery">
            <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code" width="200" height="200">
        </section>
        <form action="upload_slip.php" method="post" enctype="multipart/form-data" class="form-group">
            <label for="slip_upload">อัพโหลดสลิปการชำระเงิน:</label>
            <input type="file" name="slip" id="slip_upload" accept="image/*" required>
            <div class="button-group">
                <button type="submit">ยืนยันการชำระเงิน</button>
            </div>
        </form>
    </div>
    <script>
        var expireTime = <?= $expire_time ?> * 1000;
        var timerElement = document.getElementById('timer');
        function updateTimer() {
            var now = new Date().getTime();
            var distance = expireTime - now;
            
            // ตรวจสอบว่า distance เป็นค่าบวกหรือไม่ (เวลาหมดอายุหรือยัง)
            if (distance <= 0) {
                timerElement.innerHTML = "หมดเวลาชำระเงิน";
                clearInterval(countdownInterval);
                return;
            }

            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // เพิ่มเลขศูนย์นำหน้าถ้าค่าน้อยกว่า 10
            hours = String(hours).padStart(2, '0');
            minutes = String(minutes).padStart(2, '0');
            seconds = String(seconds).padStart(2, '0');

            timerElement.innerHTML = hours + " ชม. " + minutes + " นาที " + seconds + " วินาที ";
        }
        var countdownInterval = setInterval(updateTimer, 1000);
        updateTimer(); // เรียกใช้ครั้งแรกทันทีเมื่อโหลดหน้า

        function confirmPayLater() {
            if (confirm("คุณต้องการชำระเงินภายหลังหรือไม่? การจองจะถูกบันทึกและคุณสามารถชำระได้ในภายหลัง")) {
                window.location.href = 'save_booking_later.php';
            }
        }
    </script>
</body>
</html>