<?php
session_start();
include 'db.php';

$First_name = $_SESSION['First_name'] ?? '';
$Last_name = $_SESSION['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);

$room_id = $_GET['room_id'] ?? null;
$price = $_GET['price'] ?? 0;
$num_rooms = $_GET['num_rooms'] ?? 1;
$checkin_date_str = $_GET['checkin_date'] ?? date("Y-m-d");
$checkout_date_str = $_GET['checkout_date'] ?? date("Y-m-d");

// รับค่าผู้ใหญ่และเด็กจาก URL
$adults_str = $_GET['adults'] ?? '1';
$children_str = $_GET['children'] ?? '0';

// แปลงสตริงที่คั่นด้วยคอมมาให้เป็นอาเรย์ของตัวเลข
$adults_arr = array_map('intval', explode(',', $adults_str));
$children_arr = array_map('intval', explode(',', $children_str));

// คำนวณจำนวนผู้ใหญ่และเด็กทั้งหมด
$total_adults = array_sum($adults_arr);
$total_children = array_sum($children_arr);
$total_guests = $total_adults + $total_children;

// คำนวณจำนวนคืนที่เข้าพัก
$num_nights = 0;
try {
    if (!empty($checkin_date_str) && !empty($checkout_date_str)) {
        $checkin_date_obj = new DateTime($checkin_date_str);
        $checkout_date_obj = new DateTime($checkout_date_str);
        $interval = $checkin_date_obj->diff($checkout_date_obj);
        $num_nights = $interval->days;
    }
} catch (Exception $e) {
    $num_nights = 1; // ตั้งค่าเริ่มต้นหากวันที่ผิดพลาด
}

if ($num_nights == 0) {
    $num_nights = 1; // อย่างน้อยต้องจอง 1 คืน
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

$expire_time = time() + (24 * 60 * 60);
$_SESSION['expire_time'] = $expire_time;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ชำระเงิน</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #f9f9f9; }
        .container { max-width: 600px; margin: 50px auto; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .price { font-size: 22px; font-weight: bold; color: green; }
        .countdown { font-size: 18px; color: red; font-weight: bold; }
        button { background: green; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
        button:hover { background: darkgreen; }
    </style>
</head>
<body>
    <div class="container">
        <h2>หน้าชำระเงิน</h2>
        <p>สวัสดีคุณ <b><?= htmlspecialchars($full_name) ?></b></p>
        <p>คุณได้จองห้องพัก จำนวน <b><?= htmlspecialchars($num_rooms) ?></b> ห้อง</p>
        <p class="price">ยอดที่ต้องชำระ: ฿ <?= number_format($total_price, 2) ?></p>
        <p>วันเข้าพัก: <b><?= htmlspecialchars($checkin_date_str) ?></b></p>
        <p>วันเช็คเอาท์: <b><?= htmlspecialchars($checkout_date_str) ?></b></p>
        <p>จำนวนผู้เข้าพักรวม: <b><?= htmlspecialchars($total_adults) ?></b> ผู้ใหญ่, <b><?= htmlspecialchars($total_children) ?></b> เด็ก</p>

        <div class="countdown">เวลาที่เหลือในการชำระ: <span id="timer"></span></div>

        <section class="room-gallery">
            <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code" width="200" height="200">
        </section>

        <form action="upload_slip.php" method="post" enctype="multipart/form-data">
            <label>อัพโหลดสลิปการชำระเงิน:</label><br>
            <input type="file" name="slip" accept="image/*" required><br><br>
            <button type="submit">ชำระเงิน</button>
        </form>
    </div>

    <script>
        var expireTime = <?= $expire_time ?> * 1000;
        var timerElement = document.getElementById('timer');

        function updateTimer() {
            var now = new Date().getTime();
            var distance = expireTime - now;
            if (distance <= 0) {
                timerElement.innerHTML = "หมดเวลาชำระเงิน";
                return;
            }
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            timerElement.innerHTML = hours + " ชม. " + minutes + " นาที " + seconds + " วินาที ";
        }

        setInterval(updateTimer, 1000);
        updateTimer();
    </script>
</body>
</html>