<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่าไฟล์นี้มีอยู่และเชื่อมต่อ DB ได้ถูกต้อง

$First_name = $_SESSION['First_name'] ?? '';
$Last_name = $_SESSION['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);

$room_id = $_GET['room_id'] ?? null;
$price = floatval($_GET['price'] ?? 0);
$num_rooms = intval($_GET['num_rooms'] ?? 1);

// *** ปรับปรุงการกำหนดค่าเริ่มต้นของ checkout_date_str ให้ดีขึ้นอีกนิด ***
// กำหนดค่าเริ่มต้นเป็น 'Y-m-d' เพื่อให้ DateTime สามารถ Parse ได้อย่างถูกต้องและมีปีเสมอ
$checkin_date_str = $_GET['checkin_date'] ?? date("Y-m-d");
// ค่าเริ่มต้นของ checkout_date ควรเป็นวันถัดไปจาก checkin_date เสมอ เพื่อให้มีอย่างน้อย 1 คืน
// ให้แน่ใจว่า checkin_date_str มีค่าอยู่ก่อน
$checkout_date_str = $_GET['checkout_date'] ?? date("Y-m-d", strtotime($checkin_date_str . " +1 day"));


// รับค่าผู้ใหญ่และเด็กจาก URL
$adults_str = $_GET['adults'] ?? '1';
$children_str = $_GET['children'] ?? '0';

// แปลงสตริงที่คั่นด้วยคอมมาให้เป็นอาเรย์ของตัวเลข
// ปรับปรุงการจัดการค่าว่างเพื่อป้องกัน error/warning
$adults_arr = !empty($adults_str) ? array_map('intval', explode(',', $adults_str)) : [0];
$children_arr = !empty($children_str) ? array_map('intval', explode(',', $children_str)) : [0];

// คำนวณจำนวนผู้ใหญ่และเด็กทั้งหมด
$total_adults = array_sum($adults_arr);
$total_children = array_sum($children_arr);
$total_guests = $total_adults + $total_children;

// คำนวณจำนวนคืนที่เข้าพัก
$num_nights = 0;
try {
    if (!empty($checkin_date_str) && !empty($checkout_date_str)) {
        // สร้าง DateTime object
        $checkin_date_obj = new DateTime($checkin_date_str);
        $checkout_date_obj = new DateTime($checkout_date_str);

        // *** การตรวจสอบที่สำคัญที่สุด: ตรวจสอบและปรับปรุงวันที่เช็คเอาท์ ***
        // ถ้า checkout_date น้อยกว่าหรือเท่ากับ checkin_date (ซึ่งไม่ควรเกิดขึ้นจริงในการจอง)
        // ให้ปรับ checkout_date เป็นวันถัดไปของ checkin_date
        if ($checkout_date_obj <= $checkin_date_obj) {
            $checkout_date_obj = clone $checkin_date_obj; // ทำสำเนาเพื่อไม่ให้เปลี่ยน checkin_date
            $checkout_date_obj->modify('+1 day'); // เพิ่ม 1 วัน
            // อัพเดท string เพื่อให้แสดงผลถูกต้องในหน้าเว็บ
            $checkout_date_str = $checkout_date_obj->format('Y-m-d');
        }

        $interval = $checkin_date_obj->diff($checkout_date_obj);
        $num_nights = (int)$interval->days;

        // *** Debugging: คุณสามารถเปิดคอมเมนต์บรรทัดเหล่านี้เพื่อดูค่าวันที่และจำนวนคืนที่คำนวณได้ ***
        // echo "<pre>";
        // echo "DEBUG: checkin_date_str: " . $checkin_date_str . "\n";
        // echo "DEBUG: checkout_date_str: " . $checkout_date_str . "\n";
        // echo "DEBUG: checkin_date_obj: " . $checkin_date_obj->format('Y-m-d') . "\n";
        // echo "DEBUG: checkout_date_obj: " . $checkout_date_obj->format('Y-m-d') . "\n";
        // echo "DEBUG: Raw Interval days: " . $interval->days . "\n";
        // echo "DEBUG: num_nights (after cast): " . $num_nights . "\n";
        // echo "</pre>";

    }
} catch (Exception $e) {
    // บันทึกข้อผิดพลาดสำหรับการดีบั๊ก
    error_log("Date parsing error: " . $e->getMessage());
    $num_nights = 1; // ตั้งค่าเริ่มต้นหากวันที่ผิดพลาด
}

// ตรวจสอบขั้นสุดท้ายเพื่อให้แน่ใจว่ามีอย่างน้อย 1 คืน
if ($num_nights <= 0) {
    $num_nights = 1;
}

// คำนวณราคารวม: (ราคาต่อคืน * จำนวนห้อง) * จำนวนคืน
$total_price = ($price * $num_rooms) * $num_nights;

// ข้อมูล PromptPay
$phone = "0967501732";
$qr_url = "https://promptpay.io/$phone/$total_price.png";

// เก็บ session
$_SESSION['num_rooms'] = $num_rooms;
$_SESSION['total_adults'] = $total_adults;
$_SESSION['total_children'] = $total_children;
$_SESSION['checkin_date'] = $checkin_date_str;
$_SESSION['checkout_date'] = $checkout_date_str;
$_SESSION['room_id'] = $room_id;
$_SESSION['total_price'] = $total_price;
$_SESSION['num_nights'] = $num_nights;

$expire_time = time() + (24 * 60 * 60); // กำหนดให้หมดอายุ 24 ชั่วโมง
$_SESSION['expire_time'] = $expire_time;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ชำระเงิน</title>
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

        /* จัดกลาง QR */
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

        <div class="countdown">เวลาที่เหลือในการชำระ: <span id="timer"></span></div>

        <section class="room-gallery">
            <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code" width="200" height="200">
        </section>

        <form action="upload_slip.php" method="post" enctype="multipart/form-data" class="form-group">
            <label for="slip_upload">อัพโหลดสลิปการชำระเงิน:</label>
            <input type="file" name="slip" id="slip_upload" accept="image/*" required>
            <button type="submit">ยืนยันการชำระเงิน</button>
        </form>
    </div>

    <script>
        // เปลี่ยน expireTime ให้เป็น milliseconds ทันที
        var expireTime = <?= $expire_time ?> * 1000;
        var timerElement = document.getElementById('timer');

        function updateTimer() {
            var now = new Date().getTime();
            var distance = expireTime - now;

            if (distance <= 0) {
                timerElement.innerHTML = "หมดเวลาชำระเงิน";
                clearInterval(countdownInterval); // หยุดนับถอยหลังเมื่อหมดเวลา
                return;
            }

            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            timerElement.innerHTML = hours + " ชม. " + minutes + " นาที " + seconds + " วินาที ";
        }

        var countdownInterval = setInterval(updateTimer, 1000);
        updateTimer();
    </script>
</body>

</html>