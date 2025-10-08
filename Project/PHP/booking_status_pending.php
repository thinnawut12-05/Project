<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่า db.php อยู่ในตำแหน่งที่ถูกต้อง

// เปิดการแสดงข้อผิดพลาดทั้งหมดสำหรับการดีบัก
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 1. ตรวจสอบการเชื่อมต่อฐานข้อมูลอย่างแข็งขัน ---
if (!isset($conn) || $conn->connect_error) {
  // หากการเชื่อมต่อมีปัญหา ให้บันทึกข้อผิดพลาดและหยุดการทำงาน
  error_log("Database connection failed: " . ($conn->connect_error ?? "Connection object not initialized."));
  die("❌ ข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . ($conn->connect_error ?? 'ไม่สามารถเชื่อมต่อได้') . "</p>");
}
$conn->set_charset("utf8"); // ตรวจสอบให้แน่ใจว่าใช้ชุดอักขระ UTF-8

// --- 2. รับ email จาก session และตรวจสอบการล็อกอิน ---
$email_member = $_SESSION['email'] ?? '';

// หากไม่มีอีเมลใน session ให้เปลี่ยนเส้นทางไปหน้าล็อกอิน
if (empty($email_member)) {
    header('Location: login.php'); // หรือหน้าแจ้งเตือนว่าต้องล็อกอิน
    exit();
}

// --- 3. ดึงข้อมูลการจองทั้งหมดของลูกค้าจากฐานข้อมูล ---
// *** แก้ไข: เพิ่ม r.Booking_time, r.Total_price และ r.Receipt_Id เข้ามาใน SELECT statement ***
$sql = "SELECT r.Reservation_Id, r.Guest_name, r.Booking_time, r.Number_of_rooms, 
               r.Number_of_adults, r.Number_of_children,
               r.Booking_date, r.Check_out_date, r.Booking_status_Id, r.Total_price, r.Receipt_Id,
               b.Booking_status_name,
               p.Province_name
        FROM reservation r
        LEFT JOIN booking_status b ON r.Booking_status_Id = b.Booking_status_Id
        LEFT JOIN province p ON r.Province_Id = p.Province_Id
        WHERE r.Email_member = ?
        ORDER BY r.Booking_date DESC"; // เรียงลำดับตามวันที่จองล่าสุด

$stmt = $conn->prepare($sql);

// --- 4. จัดการข้อผิดพลาดหาก prepare statement ล้มเหลว ---
if ($stmt === false) {
    error_log("Failed to prepare statement for fetching bookings: " . $conn->error);
    die("❌ เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
}

// ผูกพารามิเตอร์และดำเนินการ query
$stmt->bind_param('s', $email_member);
$stmt->execute();
$result = $stmt->get_result();

$bookings = []; // อาร์เรย์สำหรับเก็บข้อมูลการจอง
while ($row = $result->fetch_assoc()) {
  $bookings[] = $row;
}
$stmt->close(); // ปิด statement
$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูลเมื่อเสร็จสิ้น
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <title>สถานะการจอง - HOP INN</title>
  <link rel="icon" type="image/png" href="../src/images/logo.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap">
  <style>
    body {
      font-family: 'Kanit', sans-serif;
      /* background: linear-gradient(120deg, #a8edea, #fed6e3); */
      margin: 0;
      padding: 0;
      background-color: #f0f2f5; /* เพิ่มพื้นหลังสีอ่อน */
    }

    .container {
      max-width: 1600px;
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
      white-space: nowrap; /* Force all text in cells to a single line */
      vertical-align: middle; /* จัดให้เนื้อหาอยู่กึ่งกลางแนวตั้ง */
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
    }

    .s1 { /* สถานะ: ยืนยันการจองและรอการชำระเงิน */
      background: #fff6e0;
      color: #f39c12;
      border: 1px solid #f7b731;
    }

    .s2 { /* สถานะ: ชำระเงินสำเร็จรอตรวจสอบ */
      background: #e8f5ff;
      color: #0984e3;
      border: 1px solid #74b9ff;
    }

    .s3 { /* สถานะ: ยืนยันการจองและชำระเงินแล้ว */
      background: #eaffea;
      color: #27ae60;
      border: 1px solid #2ecc71;
    }

    .s4,
    .s5 { /* สถานะ: ยกเลิก/ไม่สำเร็จ */
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

    /* Style for the action button */
    .action-button {
        display: inline-block;
        padding: 6px 12px;
        background-color: #007bff; /* Blue */
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        font-size: 0.9rem;
        transition: background-color 0.2s;
        margin: 2px 0; /* เพิ่ม margin แนวตั้งเล็กน้อย */
    }
    .action-button:hover {
        background-color: #0056b3;
    }
    .action-button.disabled {
        background-color: #cccccc; /* Grey for disabled */
        cursor: not-allowed;
        opacity: 0.7;
    }
    .action-button.view-receipt { /* สีสำหรับปุ่มดูใบเสร็จ */
        background-color: #28a745; /* Green */
    }
    .action-button.view-receipt:hover {
        background-color: #218838;
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
            <th>เวลาจอง</th>
            <th>ชื่อสาขา</th>
            <th>จำนวนห้อง</th>
            <th>ผู้ใหญ่</th>
            <th>เด็ก</th>
            <th>วันเข้าพัก</th>
            <th>วันเช็คเอาท์</th>
            <th>สถานะ</th>
            <th>การดำเนินการ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['Reservation_Id']) ?></td>
              <td><?= htmlspecialchars($b['Guest_name']) ?></td>
              <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($b['Booking_time']))) ?></td>
              <td><?= htmlspecialchars($b['Province_name'] ?? 'ไม่ระบุ') ?></td>
              <td><?= htmlspecialchars($b['Number_of_rooms']) ?></td>
              <td><?= htmlspecialchars($b['Number_of_adults']) ?></td>
              <td><?= htmlspecialchars($b['Number_of_children']) ?></td>
              <td><?= htmlspecialchars($b['Booking_date']) ?></td>
              <td><?= htmlspecialchars($b['Check_out_date']) ?></td>
              <td>
                <span class="status s<?= htmlspecialchars($b['Booking_status_Id']) ?>">
                  <?= htmlspecialchars($b['Booking_status_name'] ?? 'ไม่ทราบ') ?>
                </span>
              </td>
              <td> <!-- คอลัมน์สำหรับ Action -->
                <?php
                if ($b['Booking_status_Id'] == 1): // สถานะ 1: ยืนยันการจองและรอการชำระเงิน
                ?>
                  <a href="payment.php?reservation_id=<?= htmlspecialchars($b['Reservation_Id']) ?>" class="action-button">ชำระเงิน</a>
                <?php 
                elseif ($b['Booking_status_Id'] == 3 && !empty($b['Receipt_Id'])): // สถานะ 3: ยืนยันการจองและชำระเงินแล้ว (และมี Receipt_Id)
                ?>
                  <a href="receipt_details.php?receipt_id=<?= htmlspecialchars($b['Receipt_Id']) ?>" target="_blank" class="action-button view-receipt">ดูใบเสร็จ</a>
                <?php
                elseif ($b['Booking_status_Id'] == 2): // สถานะ 2: ชำระเงินสำเร็จรอตรวจสอบ
                ?>
                  <span class="action-button disabled">รอการตรวจสอบ</span>
                <?php else: // สถานะอื่นๆ (เช่น ยกเลิก, ปฏิเสธ) หรือสถานะ 3 แต่ไม่มี Receipt_Id
                ?>
                  <span class="action-button disabled">ไม่มี</span>
                <?php endif; ?>
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