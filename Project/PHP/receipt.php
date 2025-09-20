<?php 
// receipt.php
session_start();

// ดึงข้อมูลจาก SESSION ที่ถูกเก็บจากหน้า upload_slip.php
$booking_id     = $_SESSION['reservation_id'] ?? "DOM123456789";
$hotel_name     = "Dom Inn Hotel - สาขากรุงเทพฯ";
$guest_name     = trim(($_SESSION['First_name'] ?? '') . ' ' . ($_SESSION['Last_name'] ?? ''));
$room_type      = $_SESSION['room_type'] ?? "Deluxe Room";
$num_rooms      = $_SESSION['num_rooms'] ?? 1;
$checkin_date   = $_SESSION['checkin_date'] ?? date("Y-m-d");
$checkout_date  = $_SESSION['checkout_date'] ?? date("Y-m-d");
$total_price    = $_SESSION['total_price'] ?? 0;

// คำนวณจำนวนคืน
$checkin_ts     = strtotime($checkin_date);
$checkout_ts    = strtotime($checkout_date);
$total_nights   = ceil(($checkout_ts - $checkin_ts) / 86400); // 1 วัน = 86400 วินาที

// คำนวณราคาต่อคืน
$price_per_night = ($total_nights > 0 && $num_rooms > 0) ? $total_price / ($num_rooms * $total_nights) : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ใบเสร็จการจองห้องพัก</title>
  <style>
    body {
      font-family: Tahoma, sans-serif;
      background: #f5f5f5;
      margin: 0;
      padding: 30px;
    }
    .receipt {
      background: #fff;
      padding: 30px;
      max-width: 800px;
      margin: auto;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0,0,0,0.15);
    }
    .header {
      text-align: center;
      border-bottom: 2px solid #007bff;
      padding-bottom: 20px;
      margin-bottom: 20px;
    }
    .header img {
      width: 120px;
    }
    .header h2 {
      margin: 10px 0 0 0;
      color: #007bff;
    }
    .section {
      margin-bottom: 20px;
    }
    .section h3 {
      margin-bottom: 10px;
      border-bottom: 1px solid #ccc;
      padding-bottom: 5px;
      color: #333;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    table, th, td {
      border: 1px solid #ddd;
    }
    th, td {
      padding: 10px;
      text-align: left;
    }
    th {
      background: #f0f0f0;
    }
    .total {
      text-align: right;
      font-size: 18px;
      font-weight: bold;
      color: #d9534f;
    }
    .footer {
      text-align: center;
      font-size: 14px;
      color: #666;
      margin-top: 30px;
    }
  </style>
</head>
<body>
  <div class="receipt">
    <div class="header">
      <img src="../src/images/4.png" alt="Dom Inn Logo">
      <h2>Dom Inn Hotel</h2>
      <p>ใบเสร็จการจองห้องพัก</p>
      <p>หมายเลขการจอง: <strong><?= htmlspecialchars($booking_id) ?></strong></p>
    </div>

    <div class="section">
      <h3>รายละเอียดผู้เข้าพัก</h3>
      <p><strong>ชื่อผู้จอง:</strong> <?= htmlspecialchars($guest_name) ?></p>
      <p><strong>โรงแรม:</strong> <?= htmlspecialchars($hotel_name) ?></p>
    </div>

    <div class="section">
      <h3>รายละเอียดการเข้าพัก</h3>
      <table>
        <tr>
          <th>ประเภทห้อง</th>
          <th>จำนวนห้อง</th>
          <th>เช็คอิน</th>
          <th>เช็คเอาท์</th>
          <th>จำนวนคืน</th>
          <th>ราคาต่อคืน</th>
          <th>ราคารวม</th>
        </tr>
        <tr>
          <td><?= htmlspecialchars($room_type) ?></td>
          <td><?= $num_rooms ?></td>
          <td><?= $checkin_date ?></td>
          <td><?= $checkout_date ?></td>
          <td><?= $total_nights ?></td>
          <td><?= number_format($price_per_night) ?> บาท</td>
          <td><?= number_format($total_price) ?> บาท</td>
        </tr>
      </table>
    </div>

    <div class="section total">
      รวมทั้งหมด: <?= number_format($total_price) ?> บาท
    </div>

    <div class="footer">
      <p>ขอบคุณที่เลือกใช้บริการ Dom Inn Hotel</p>
      <p>อีเมลยืนยันการจองและใบเสร็จถูกส่งไปยังอีเมลของคุณแล้ว</p>
    </div>
  </div>
</body>
</html>
