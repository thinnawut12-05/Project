<?php
session_start();
include 'db.php';

// รับ email จาก session
$email_member = $_SESSION['email'] ?? '';

// ดึงข้อมูลการจองที่รอการตรวจสอบ (Booking_status_Id = 2)
$sql = "SELECT Reservation_Id, Guest_name, Number_of_rooms, Booking_date, Check_out_date, Booking_status_Id 
        FROM reservation 
        WHERE Email_member = ? AND Booking_status_Id = 2
        ORDER BY Booking_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email_member);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>สถานะการจอง - รอการตรวจสอบ</title>
     <link rel="icon" type="image/png" href="../src/images/logo.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap">
  <style>
    body {
      font-family: 'Kanit', sans-serif;
      background: linear-gradient(120deg,#a8edea,#fed6e3);
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 900px;
      margin: 40px auto;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      padding: 32px 20px;
    }
    h2 {
      text-align: center;
      color: #3b3b3b;
      margin-bottom: 30px;
      font-size: 2rem;
      letter-spacing: 1px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
      background: #fafafa;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    }
    th, td {
      padding: 16px 10px;
      text-align: center;
      font-size: 1rem;
    }
    th {
      background: #74b9ff;
      color: #fff;
      font-weight: 700;
      letter-spacing: 0.5px;
      border-bottom: 2px solid #dfe6e9;
    }
    tr:nth-child(even) { background: #f1f6fb; }
    .status-pending {
      color: #f39c12;
      font-weight: 700;
      background: #fff6e0;
      padding: 6px 16px;
      border-radius: 20px;
      display: inline-block;
      font-size: 0.95rem;
      border: 1px solid #f7b731;
    }
    .no-booking {
      text-align: center;
      padding: 32px 0;
      color: #888;
      font-size: 1.15rem;
    }
    .back-btn {
      display: inline-block;
      padding: 10px 24px;
      background: #0984e3;
      color: #fff;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      margin-top: 15px;
      transition: background 0.3s;
    }
    .back-btn:hover { background: #0652dd; }
  </style>
</head>
<body>
  <div class="container">
    <h2>รายการจองที่รอการตรวจสอบ</h2>
    <?php if (count($bookings) === 0): ?>
      <div class="no-booking">ไม่มีรายการจองที่รอการตรวจสอบในขณะนี้</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>รหัสการจอง</th>
            <th>ชื่อผู้จอง</th>
            <th>จำนวนห้อง</th>
            <th>วันเข้าพัก</th>
            <th>วันเช็คเอาท์</th>
            <th>สถานะ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($bookings as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['Reservation_Id']) ?></td>
            <td><?= !empty($b['Guest_name']) ? htmlspecialchars($b['Guest_name']) : '<span style="color:#d63031;">ไม่พบชื่อ</span>' ?></td>
            <td><?= htmlspecialchars($b['Number_of_rooms']) ?></td>
            <td><?= htmlspecialchars($b['Booking_date']) ?></td>
            <td><?= htmlspecialchars($b['Check_out_date']) ?></td>
            <td>
              <span class="status-pending">รอการตรวจสอบ</span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <div style="text-align:center;">
      <a href="home.php" class="back-btn">กลับหน้าหลัก</a>
    </div>
  </div>
</body>
</html>