<?php
session_start();
include 'db.php'; // ตรวจสอบว่าไฟล์ db.php เชื่อมต่อฐานข้อมูลด้วย $conn ได้ถูกต้อง

// ตรวจสอบว่า $conn ถูกสร้างขึ้นและเป็น object ของ mysqli
if (!isset($conn) || $conn->connect_error) {
  echo "<p class='error'>❌ ข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . ($conn->connect_error ?? 'ไม่สามารถเชื่อมต่อได้') . "</p>";
  exit();
}

// ดึงข้อมูลจาก Session
$First_name   = $_SESSION['First_name'] ?? '';
$Last_name    = $_SESSION['Last_name'] ?? '';
$full_name    = trim($First_name . ' ' . $Last_name);

$num_rooms    = $_SESSION['num_rooms'] ?? 1;
$adults       = $_SESSION['total_adults'] ?? 1;
$children     = $_SESSION['total_children'] ?? 0;
$checkin_date = $_SESSION['checkin_date'] ?? date("Y-m-d");
$checkout_date = $_SESSION['checkout_date'] ?? date("Y-m-d");
$total_price  = $_SESSION['total_price'] ?? 0;
$room_id      = $_SESSION['room_id'] ?? null;
$email_member = $_SESSION['email'] ?? 'guest@example.com';

// ใช้ Booking_status_Id = 2 (ชำระเงินสำเร็จรอตรวจสอบ)
$status_id = 2;
$receipt_image_filename = NULL;

// ตรวจสอบการอัปโหลดไฟล์สลิป
if (isset($_FILES['slip']) && $_FILES['slip']['error'] == 0) {
  $targetDir = "uploads/receipts/";
  if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
  }

  $fileExtension = pathinfo($_FILES["slip"]["name"], PATHINFO_EXTENSION);
  $fileName = uniqid('receipt_', true) . '.' . $fileExtension;
  $targetFilePath = $targetDir . $fileName;

  if (move_uploaded_file($_FILES["slip"]["tmp_name"], $targetFilePath)) {
    $receipt_image_filename = $fileName;
  } else {
    echo "<p class='error'>❌ อัพโหลดไฟล์ไม่สำเร็จ</p>";
    exit();
  }
} else {
  echo "<p class='error'>❌ กรุณาเลือกไฟล์สลิป</p>";
  exit();
}

// สร้าง Reservation ID
$reservation_id = time() . rand(100, 999);
$_SESSION['reservation_id'] = $reservation_id;

// *** เพิ่มโค้ดเพื่อดึง Province_Id จากตาราง room โดยใช้ room_id ***
$province_id_to_save = null;
$province_name_to_display = "ไม่ระบุ"; // กำหนดค่าเริ่มต้น
if ($room_id !== null) {
  // ใช้ prepared statement เพื่อป้องกัน SQL Injection
  $sql_get_province_id = "SELECT Province_Id FROM room WHERE Room_Id = ?";
  $stmt_get_province_id = $conn->prepare($sql_get_province_id);

  if ($stmt_get_province_id === false) {
    echo "<p class='error'>❌ ข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับดึง Province_Id: " . $conn->error . "</p>";
    exit();
  }

  $stmt_get_province_id->bind_param("i", $room_id); // 'i' คือ integer สำหรับ Room_Id
  $stmt_get_province_id->execute();
  $stmt_get_province_id->bind_result($province_id_to_save); // ผูกผลลัพธ์เข้ากับตัวแปร
  $stmt_get_province_id->fetch(); // ดึงข้อมูล
  $stmt_get_province_id->close();

  if ($province_id_to_save !== null) {
    // *** เพิ่มโค้ดเพื่อดึง Province_Name จากตาราง province โดยใช้ Province_Id ***
    $sql_get_province_name = "SELECT Province_Name FROM province WHERE Province_Id = ?";
    $stmt_get_province_name = $conn->prepare($sql_get_province_name);

    if ($stmt_get_province_name === false) {
      echo "<p class='error'>❌ ข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับดึง Province_Name: " . $conn->error . "</p>";
      exit();
    }

    $stmt_get_province_name->bind_param("i", $province_id_to_save);
    $stmt_get_province_name->execute();
    $stmt_get_province_name->bind_result($province_name_to_display);
    $stmt_get_province_name->fetch();
    $stmt_get_province_name->close();
    // *** สิ้นสุดการเพิ่มโค้ดดึง Province_Name ***
  } else {
    echo "<p class='error'>❌ ไม่พบข้อมูล Province_Id สำหรับ Room_Id: $room_id</p>";
    exit();
  }
} else {
  echo "<p class='error'>❌ ไม่พบ Room_Id ใน Session ไม่สามารถระบุ Province ได้</p>";
  exit();
}
// *** สิ้นสุดการเพิ่มโค้ดดึง Province_Id ***

// บันทึกลงฐานข้อมูล (เพิ่ม Province_Id)
$sql = "INSERT INTO reservation
        (Reservation_Id, Guest_name, Number_of_rooms, Booking_time,
         Number_of_adults, Number_of_children, Booking_date,
         Check_out_date, Email_member, receipt_image, Booking_status_Id, Province_Id)
        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)"; // เพิ่ม ?, สำหรับ Province_Id

$stmt = $conn->prepare($sql);

if ($stmt === false) {
  echo "<p class='error'>❌ ข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับการจอง: " . $conn->error . "</p>";
  exit();
}

// 'ssiiissssi' สำหรับ 10 ตัวแปรเดิม, เพิ่ม 'i' สำหรับ Province_Id (รวมเป็น 11 ตัวแปร)
$stmt->bind_param(
  "ssiiissssii", // อัปเดต type string
  $reservation_id,          // s
  $full_name,               // s
  $num_rooms,               // i
  $adults,                  // i
  $children,                // i
  $checkin_date,            // s
  $checkout_date,           // s
  $email_member,            // s
  $receipt_image_filename,  // s
  $status_id,               // i
  $province_id_to_save      // i (เพิ่มเข้ามาสำหรับ Province_Id)
);
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <title>ยืนยันการจอง</title>
  <link rel="icon" type="image/png" href="../src/images/logo.png" />
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #74b9ff, #a29bfe);
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 700px;
      margin: 50px auto;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      padding: 30px;
      text-align: center;
      animation: fadeIn 0.7s ease-in-out;
    }

    h2 {
      color: #2d3436;
      font-size: 26px;
      margin-bottom: 15px;
    }

    p {
      font-size: 16px;
      color: #555;
      margin: 8px 0;
    }

    .highlight {
      color: #0984e3;
      font-weight: bold;
    }

    a {
      display: inline-block;
      margin-top: 20px;
      text-decoration: none;
      background: #0984e3;
      color: #fff;
      padding: 10px 20px;
      border-radius: 8px;
      transition: background 0.3s;
    }

    a:hover {
      background: #0652dd;
    }

    .btn-green {
      background: #27ae60;
    }

    .btn-green:hover {
      background: #1e8449;
    }

    .error {
      color: #d63031;
      font-weight: bold;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <?php
    if ($stmt->execute()) {
      echo "<h2>✅ บันทึกการจองสำเร็จ</h2>";
      echo "<p>รหัสการจอง: <span class='highlight'>$reservation_id</span></p>";
      echo "<p>คุณ <span class='highlight'>$full_name</span> ได้จองห้องจำนวน <span class='highlight'>$num_rooms</span> ห้อง</p>";
      echo "<p>ยอดเงินที่ต้องชำระ: <span class='highlight'>฿ " . number_format($total_price, 2) . "</span></p>";
      echo "<p>วันเข้าพัก: <span class='highlight'>$checkin_date</span> ถึง <span class='highlight'>$checkout_date</span></p>";
      echo "<p>จำนวนผู้เข้าพัก: <span class='highlight'>$adults</span> ผู้ใหญ่, <span class='highlight'>$children</span> เด็ก</p>";
      echo "<p>สาขาที่จอง: <span class='highlight'>$province_name_to_display</span></p>"; // เปลี่ยนเป็นแสดง Province_Name
      echo "<p>สถานะการจอง: <span class='highlight'>ชำระเงินสำเร็จรอตรวจสอบ</span></p>";

      echo "<a href='home.php'>กลับไปหน้าหลัก</a>";
      echo "<a href='receipt.php?booking_id=$reservation_id' class='btn-green'>ดูใบเสร็จ</a>";
    } else {
      echo "<p class='error'>❌ เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
    ?>
  </div>
</body>

</html>