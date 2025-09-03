<?php
session_start();
include 'db.php';

$First_name = $_SESSION['First_name'] ?? '';
$Last_name = $_SESSION['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);

$room_id = $_GET['room_id'] ?? null;
$price = $_GET['price'] ?? 0;
$num_rooms = $_GET['num_rooms'] ?? 1;

$total_price = $price * $num_rooms;

// กำหนดเวลาหมดอายุ 24 ชั่วโมง
$expire_time = time() + (24 * 60 * 60);
$_SESSION['expire_time'] = $expire_time;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ชำระเงิน</title>
    <link rel="stylesheet" href="in.css">
    <style>
        body {
            font-family: Tahoma, sans-serif;
            background: #f9f9f9;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .countdown {
            font-size: 18px;
            color: red;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .price {
            font-size: 22px;
            font-weight: bold;
            color: green;
        }

        input[type=file] {
            margin: 15px 0;
        }

        button {
            background: green;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }

        button:hover {
            background: darkgreen;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>หน้าชำระเงิน</h2>
        <p>สวัสดีคุณ <b><?= htmlspecialchars($full_name) ?></b></p>
        <p>คุณได้จองห้องพัก จำนวน <b><?= $num_rooms ?></b> ห้อง</p>
        <p class="price">ยอดที่ต้องชำระ: ฿ <?= number_format($total_price, 2) ?></p>

        <div class="countdown">
            เวลาที่เหลือในการชำระ: <span id="timer"></span>
        </div>
        <section class="room-gallery">
            <img src="./src/images/77.jpg" alt="Room 1" />
        </section>

        <form action="upload_slip.php" method="post" enctype="multipart/form-data">
            <label>อัพโหลดสลิปการชำระเงิน:</label><br>
            <input type="file" name="slip" accept="image/*" required><br>
            <input type="hidden" name="amount" value="<?= $total_price ?>">
            <input type="hidden" name="room_id" value="<?= $room_id ?>">
            <button type="submit">ยืนยันการชำระ</button>
        </form>
    </div>

    <script>
        // นับถอยหลัง 24 ชั่วโมง
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