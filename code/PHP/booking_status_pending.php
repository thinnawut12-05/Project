<?php
session_start(); // *** เพิ่ม: เริ่ม session สำหรับเช็คสมาชิก ***
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

$First_name = $_SESSION['First_name'] ?? '';
$Last_name = $_SESSION['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);
$checkin_date = $_GET['checkin_date'] ?? '';
$checkout_date = $_GET['checkout_date'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <title>สถานะการจองของคุณ</title>
  <link rel="icon" type="image/png" href="../src/images/logo.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap">
  <!-- *** เพิ่ม: ลิงก์ไปยัง ino.css สำหรับสไตล์ของ Header *** -->
  <link rel="stylesheet" href="../CSS/css/ino.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOM7z4j8e+Q1z5l5x5l5x5l5x5l5x5l5x5l5x"
    crossorigin="anonymous" />
  <style>
    body {
      font-family: 'Kanit', sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f0f2f5; /* เพิ่มพื้นหลังสีอ่อน */
      display: flex; /* เพื่อให้ Header และ Container จัดเรียงกัน */
      flex-direction: column; /* จัดเรียงในแนวตั้ง */
      align-items: center; /* จัดกึ่งกลางแนวนอน */
      min-height: 100vh; /* ความสูงเต็มหน้าจอ */
    }
    /* *** เพิ่มสไตล์สำหรับ Header เพื่อให้ Navbar แสดงผลได้ดีขึ้น *** */
    header {
      width: 100%;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* เพิ่มเงาให้ Header */
      z-index: 100; /* ให้ Header อยู่ด้านบนสุด */
      position: sticky; /* ทำให้ Header ติดอยู่ด้านบนเมื่อเลื่อนหน้าจอ */
      top: 0;
    }

    .container {
      max-width: 1600px;
      margin: 40px auto; /* ปรับ margin-top เพื่อไม่ให้ชน Header */
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
      padding: 32px 20px;
      width: 95%; /* ใช้ความกว้าง 95% เพื่อให้ยืดหยุ่นมากขึ้น */
      box-sizing: border-box; /* ให้ padding นับรวมในความกว้าง */
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
    /* ปรับความกว้างของแต่ละคอลัมน์เพื่อไม่ให้ตารางดูแน่นเกินไป */
    th:nth-child(1), td:nth-child(1) { width: 100px; } /* รหัสการจอง */
    th:nth-child(2), td:nth-child(2) { width: 140px; } /* ชื่อผู้จอง */
    th:nth-child(3), td:nth-child(3) { width: 150px; } /* เวลาจอง */
    th:nth-child(4), td:nth-child(4) { width: 120px; } /* ชื่อสาขา */
    th:nth-child(5), td:nth-child(5) { width: 90px; }  /* จำนวนห้อง */
    th:nth-child(6), td:nth-child(6) { width: 80px; }  /* ผู้ใหญ่ */
    th:nth-child(7), td:nth-child(7) { width: 80px; }  /* เด็ก */
    th:nth-child(8), td:nth-child(8) { width: 120px; } /* วันเข้าพัก */
    th:nth-child(9), td:nth-child(9) { width: 120px; } /* วันเช็คเอาท์ */
    th:nth-child(10), td:nth-child(10) { width: 150px; } /* สถานะ */
    th:nth-child(11), td:nth-child(11) { width: 150px; } /* การดำเนินการ */


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
    .s6 { /* สถานะ: เช็คอินแล้ว */
        background: #e0ffe0;
        color: #1a8b4b;
        border: 1px solid #1abc9c;
    }
    .s7 { /* สถานะ: เช็คเอาท์แล้ว */
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
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
      margin-top: 20px;
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
    <!-- *** แทรก Header จาก index.php *** -->
    <header>
      <section class="logo">
        <a href="./home.php">
          <img src="../src/images/4.png" width="50" height="50" alt="Dom Inn Logo" />
          
        </a>
      </section>
      <nav>
        <a href="./index-type.php">ประเภทห้องพัก</a>
        <a href="./branchs.php">สาขาโรงแรมดอม อินน์</a>
        <a href="./detailsm.php">รายละเอียดต่างๆ</a>
        <a href="./booking_status_pending.php">การจองของฉัน</a>
        <a href="./score.php">คะแนน</a>
      </nav>
          <?php if ($full_name && $full_name !== ' '): ?>
      <div class="user-display">
        <a href="profile.php" class="profile-link"><?= htmlspecialchars($full_name) ?></a>
      </div>
    <?php endif; ?>
    </header>
    <!-- สำหรับโปรไฟล์เท่านั้น -->
  <style>
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
      color: #ffffff;
    }

    .profile-link:active {
      color: #ffffff;
    }
  </style>
  <!-- สำหรับโปรไฟล์เท่านั้น End-->
    <!-- *** สิ้นสุด Header ที่แทรกเข้ามา *** -->

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
                // *** ส่วนที่แก้ไข: เพิ่มสถานะ 6 (เช็คอินแล้ว) เข้าไปในเงื่อนไขการดูใบเสร็จ ***
                elseif (($b['Booking_status_Id'] == 3 || $b['Booking_status_Id'] == 6 || $b['Booking_status_Id'] == 7) && !empty($b['Receipt_Id'])): // สถานะ 3: ชำระเงินแล้ว, 6: เช็คอินแล้ว, หรือ 7: เช็คเอาท์แล้ว
                ?>
                  <a href="receipt_details.php?receipt_id=<?= htmlspecialchars($b['Receipt_Id']) ?>" target="_blank" class="action-button view-receipt">ดูใบเสร็จ</a>
                <?php
                elseif ($b['Booking_status_Id'] == 2): // สถานะ 2: ชำระเงินสำเร็จรอตรวจสอบ
                ?>
                  <span class="action-button disabled">รอการตรวจสอบ</span>
                <?php else: // สถานะอื่นๆ (เช่น ยกเลิก, ปฏิเสธ)
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