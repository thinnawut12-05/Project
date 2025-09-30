<?php
session_start();
include 'db.php';

// รับ email จาก session
$email_member = $_SESSION['email'] ?? '';

// ดึงข้อมูลการจองทั้งหมดของลูกค้า + ชื่อสถานะ + ชื่อจังหวัด + จำนวนผู้ใหญ่และเด็ก
$sql = "SELECT r.Reservation_Id, r.Guest_name, r.Number_of_rooms, 
               r.Number_of_adults, r.Number_of_children,
               r.Booking_date, r.Check_out_date, r.Booking_status_Id,
               b.Booking_status_name,
               p.Province_name
        FROM reservation r
        LEFT JOIN booking_status b ON r.Booking_status_Id = b.Booking_status_Id
        LEFT JOIN province p ON r.Province_Id = p.Province_Id
        WHERE r.Email_member = ?
        ORDER BY r.Booking_date DESC";
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
  <title>สถานะการจอง</title>
   <link rel="icon" type="image/png" href="../src/images/logo.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap">
  <style>
    body {
      font-family: 'Kanit', sans-serif;
      /* background: linear-gradient(120deg, #a8edea, #fed6e3); */
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 1500px;
      margin: 40px auto;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
      padding: 32px 20px;
    }

    h2 {
      text-align: center;
      color: #3b3b3b;
      margin-bottom: 30px;
      font-size: 2rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
      background: #fafafa;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
    }

    th,
    td {
      padding: 10px 10px;
      text-align: center;
      font-size: 1rem;
      white-space: nowrap; /* Added this line to force all text in cells to a single line */
    }

    th {
      background: #74b9ff;
      color: #fff;
    }

    tr:nth-child(even) {
      background: #f1f6fb;
    }

    .status {
      padding: 6px 14px;
      border-radius: 20px;
      font-weight: 700;
      display: inline-block;
      font-size: 0.9rem;
      /* white-space: nowrap; - This was already here, now also applied to all td */
    }

    .s1 {
      background: #fff6e0;
      color: #f39c12;
      border: 1px solid #f7b731;
    }

    .s2 {
      background: #e8f5ff;
      color: #0984e3;
      border: 1px solid #74b9ff;
    }

    .s3 {
      background: #eaffea;
      color: #27ae60;
      border: 1px solid #2ecc71;
    }

    .s4,
    .s5 {
      background: #ffeaea;
      color: #e74c3c;
      border: 1px solid #ff7675;
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
      transition: 0.3s;
    }

    .back-btn:hover {
      background: #0652dd;
    }
  </style>
</head>

<body>
  <div class="container">
    <h2>สถานะการจองของคุณ</h2>
    <?php if (count($bookings) === 0): ?>
      <div class="no-booking">ไม่มีรายการจอง</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>รหัสการจอง</th>
            <th>ชื่อผู้จอง</th>
            <th>ชื่อสาขา</th>
            <th>จำนวนห้อง</th>
            <th>ผู้ใหญ่</th>
            <th>เด็ก</th>
            <th>วันเข้าพัก</th>
            <th>วันเช็คเอาท์</th>
            <th>สถานะ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['Reservation_Id']) ?></td>
              <td><?= htmlspecialchars($b['Guest_name']) ?></td>
              <td><?= htmlspecialchars($b['Province_name'] ?? 'ไม่ระบุ') ?></td>
              <td><?= htmlspecialchars($b['Number_of_rooms']) ?></td>
              <td><?= htmlspecialchars($b['Number_of_adults']) ?></td>
              <td><?= htmlspecialchars($b['Number_of_children']) ?></td>
              <td><?= htmlspecialchars($b['Booking_date']) ?></td>
              <td><?= htmlspecialchars($b['Check_out_date']) ?></td>
              <td>
                <span class="status s<?= $b['Booking_status_Id'] ?>">
                  <?= htmlspecialchars($b['Booking_status_name'] ?? 'ไม่ทราบ') ?>
                </span>
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