<?php
session_start();
include 'db.php';

$full_name      = trim(($_SESSION['First_name'] ?? '') . ' ' . ($_SESSION['Last_name'] ?? ''));
$num_rooms      = $_SESSION['num_rooms'] ?? 1;
$total_adults   = $_SESSION['total_adults'] ?? 1;
$total_children = $_SESSION['total_children'] ?? 0;
$checkin_date   = $_SESSION['checkin_date'] ?? date("Y-m-d");
$checkout_date  = $_SESSION['checkout_date'] ?? date("Y-m-d");
$total_price    = $_SESSION['total_price'] ?? 0;
$province_name  = $_SESSION['province_name'] ?? 'ไม่ได้ระบุ';
$reservation_id = $_GET['booking_id'] ?? 'ไม่ระบุ';

$status_text = 'ชำระเงินเรียบร้อย'; // แก้เป็นสถานะเรียบร้อย
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ใบการจอง - Agoda Style</title>
      <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f2f3f7;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 700px;
            margin: 50px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background-color: #007aff;
            color: #fff;
            padding: 20px 30px;
            font-size: 22px;
            font-weight: 700;
            text-align: center;
        }

        .content {
            padding: 30px;
        }

        .content h3 {
            color: #333;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .row label {
            color: #555;
            font-weight: 500;
        }

        .row span {
            font-weight: 600;
            color: #111;
        }

        .section {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .status {
            font-weight: 700;
            color: #28a745;
            /* สีเขียวแสดงชำระแล้ว */
        }

        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background-color: #007aff;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .button:hover {
            background-color: #0051c7;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">ใบการจองของคุณ</div>
        <div class="content">
            <h3>สวัสดีคุณ <?= htmlspecialchars($full_name) ?></h3>

            <div class="row">
                <label>รหัสการจอง:</label>
                <span><?= htmlspecialchars($reservation_id) ?></span>
            </div>
            <div class="row">
                <label>สาขา:</label>
                <span><?= htmlspecialchars($province_name) ?></span>
            </div>
            <div class="row">
                <label>จำนวนห้อง:</label>
                <span><?= htmlspecialchars($num_rooms) ?></span>
            </div>
            <div class="row">
                <label>จำนวนผู้เข้าพัก:</label>
                <span><?= htmlspecialchars($total_adults) ?> ผู้ใหญ่, <?= htmlspecialchars($total_children) ?> เด็ก</span>
            </div>
            <div class="row">
                <label>วันเช็คอิน:</label>
                <span><?= htmlspecialchars($checkin_date) ?></span>
            </div>
            <div class="row">
                <label>วันเช็คเอาท์:</label>
                <span><?= htmlspecialchars($checkout_date) ?></span>
            </div>
            <div class="row">
                <label>ยอดชำระ:</label>
                <span>฿ <?= number_format($total_price, 2) ?></span>
            </div>
            <div class="row section">
                <label>สถานะการจอง:</label>
                <span class="status"><?= htmlspecialchars($status_text) ?></span>
            </div>

            <div style="text-align:center;">
                <a href="home.php" class="button">กลับหน้าหลัก</a>
            </div>
        </div>
    </div>

</body>

</html>