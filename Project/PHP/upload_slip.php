<?php
session_start();
include 'db.php'; // ตรวจสอบว่าไฟล์ db.php เชื่อมต่อฐานข้อมูลด้วย $conn ได้ถูกต้อง

// เปิดการแสดงข้อผิดพลาดทั้งหมดสำหรับการดีบัก
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 1. ตั้งค่า Timezone ที่ส่วนหัวของสคริปต์ PHP ---
// สำคัญ: คุณควรตั้งค่า Timezone ให้ถูกต้องตามภูมิภาคของคุณ
// สำหรับประเทศไทยคือ 'Asia/Bangkok'
date_default_timezone_set('Asia/Bangkok');
error_log("DEBUG: upload_slip.php - PHP Default Timezone: " . date_default_timezone_get());

// ตรวจสอบว่า $conn ถูกสร้างขึ้นและเป็น object ของ mysqli
if (!isset($conn) || $conn->connect_error) {
  error_log("Database connection failed in upload_slip.php: " . ($conn->connect_error ?? 'Connection object not initialized.'));
  die("<p class='error'>❌ ข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . ($conn->connect_error ?? 'ไม่สามารถเชื่อมต่อได้') . "</p>");
}
$conn->set_charset("utf8");

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ (ผ่านอีเมลใน session)
if (!isset($_SESSION['email'])) {
  error_log("ERROR: upload_slip.php - User not logged in, redirecting to login.php");
  header('Location: login.php');
  exit();
}
$email_member = $_SESSION['email']; // อีเมลของผู้ใช้งานปัจจุบัน

// *** สำคัญ: ดึง Reservation_Id จาก POST ที่ส่งมาจาก payment.php ***
$reservation_id = $_POST['reservation_id'] ?? ($_SESSION['current_reservation_id'] ?? null);

if (!$reservation_id) {
  error_log("ERROR: upload_slip.php - reservation_id is missing in POST data for user " . $email_member);
  $_SESSION['error'] = "ไม่พบรหัสการจอง กรุณาลองใหม่อีกครั้ง";
  header("Location: payment.php?error=no_reservation_id_for_slip");
  exit();
}

// --- 2. ดึงข้อมูลการจองและข้อมูลสมาชิก (เบอร์โทร) เพื่อนำไปใช้บันทึกในตาราง receipt ---
$guest_name = "";
$phone_number = "";
$num_rooms = 0;
$adults = 0;
$children = 0;
$checkin_date = "";
$checkout_date = "";
$total_price = 0;
$province_id_from_db = null;
$province_name_to_display = "ไม่ระบุ";

// เพิ่มการ JOIN ตาราง member เพื่อดึงเบอร์โทรศัพท์
$sql_fetch_details = "SELECT r.Guest_name, r.Number_of_rooms, r.Number_of_adults, r.Number_of_children,
                      r.Booking_date, r.Check_out_date, r.Total_price, r.Province_Id,
                      m.Phone_number
                      FROM reservation r
                      JOIN member m ON r.Email_member = m.Email_member 
                      WHERE r.Reservation_Id = ?";
$stmt_details = $conn->prepare($sql_fetch_details);

if ($stmt_details === false) {
  error_log("Failed to prepare statement for fetching guest details in upload_slip.php: " . $conn->error);
  die("<p class='error'>❌ เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับดึงข้อมูลการจอง: " . $conn->error . "</p>");
}

// ใช้ 's' สำหรับ Reservation_Id เพราะเราสร้างมันเป็น string (หรือ BIGINT ใน DB)
$stmt_details->bind_param("s", $reservation_id);
$stmt_details->execute();
$result_details = $stmt_details->get_result();

if ($row_details = $result_details->fetch_assoc()) {
  $guest_name = $row_details['Guest_name'];
  $num_rooms = $row_details['Number_of_rooms'];
  $adults = $row_details['Number_of_adults'];
  $children = $row_details['Number_of_children'];
  $checkin_date = $row_details['Booking_date'];
  $checkout_date = $row_details['Check_out_date'];
  $total_price = $row_details['Total_price'] ?? 0;
  $province_id_from_db = $row_details['Province_Id'];
  $phone_number = $row_details['Phone_number']; // ดึงเบอร์โทรศัพท์

  // ดึงชื่อจังหวัดจาก DB
  $sql_province_name = "SELECT Province_name FROM province WHERE Province_Id = ?";
  $stmt_province_name = $conn->prepare($sql_province_name);
  if ($stmt_province_name) {
    $stmt_province_name->bind_param("i", $province_id_from_db);
    $stmt_province_name->execute();
    $stmt_province_name->bind_result($province_name_db);
    $stmt_province_name->fetch();
    $province_name_to_display = $province_name_db;
    $stmt_province_name->close();
  }
} else {
  error_log("No booking or member details found for reservation_id: " . $reservation_id . " for user " . $email_member);
  die("<p class='error'>❌ ไม่พบข้อมูลการจองสำหรับรหัส: " . htmlspecialchars($reservation_id) . "</p>");
}
$stmt_details->close();


// --- 3. จัดการการอัปโหลดไฟล์สลิป ---
$receipt_image_filename = NULL;
$targetDir = "uploads/receipts/"; // โฟลเดอร์สำหรับเก็บสลิป (อยู่ในโฟลเดอร์ PHP/)

// ตรวจสอบและสร้างโฟลเดอร์ถ้ายังไม่มี
if (!is_dir($targetDir)) {
  if (!mkdir($targetDir, 0777, true)) { // 0777 เพื่อให้มีสิทธิ์เขียน (ควรปรับให้ปลอดภัยขึ้นใน production)
    error_log("ERROR: upload_slip.php - Failed to create directory: " . $targetDir);
    die("<p class='error'>❌ ไม่สามารถสร้างโฟลเดอร์สำหรับอัปโหลดสลิปได้</p>");
  } else {
    error_log("DEBUG: upload_slip.php - Directory created successfully: " . $targetDir);
  }
}

if (isset($_FILES['slip']) && $_FILES['slip']['error'] == 0) {
  $fileExtension = pathinfo($_FILES["slip"]["name"], PATHINFO_EXTENSION);
  // สร้างชื่อไฟล์ที่ไม่ซ้ำกัน
  $fileName = uniqid('receipt_', true) . '.' . $fileExtension;
  $targetFilePath = $targetDir . $fileName;

  if (move_uploaded_file($_FILES["slip"]["tmp_name"], $targetFilePath)) {
    $receipt_image_filename = $fileName;
    error_log("DEBUG: upload_slip.php - File uploaded successfully: " . $targetFilePath);
  } else {
    error_log("ERROR: upload_slip.php - Failed to upload file. Error code: " . $_FILES["slip"]["error"] . " Target: " . $targetFilePath);
    die("<p class='error'>❌ อัปโหลดไฟล์สลิปไม่สำเร็จ กรุณาลองใหม่</p>");
  }
} else {
  error_log("ERROR: upload_slip.php - No slip file uploaded or upload error: " . ($_FILES['slip']['error'] ?? 'No file selected.'));
  die("<p class='error'>❌ กรุณาเลือกไฟล์สลิปเพื่อยืนยันการชำระเงิน</p>");
}

// --- 4. บันทึกข้อมูลลงในตาราง `receipt` ---

// *** ฟังก์ชันสำหรับสร้าง ID ที่ไม่ซ้ำกันและอยู่ในช่วง INT ***
function generateUniqueIntId($conn, $table, $idColumn)
{
  $isUnique = false;
  $newId = 0;
  $maxAttempts = 100; // จำกัดจำนวนครั้งที่ลองเพื่อป้องกันลูปไม่รู้จบ

  for ($i = 0; $i < $maxAttempts && !$isUnique; $i++) {
    // สร้างตัวเลขสุ่มที่อยู่ในช่วงของ signed INT (max 2147483647)
    // เริ่มจาก 100,000,000 (9 หลัก) เพื่อให้มีค่าที่แตกต่างกันพอสมควร
    $newId = mt_rand(100000000, 2147483647);
    error_log("DEBUG: generateUniqueIntId: Attempt $i - Generated ID: $newId"); // Debug log

    $check_sql = "SELECT 1 FROM $table WHERE $idColumn = ?";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) {
      $check_stmt->bind_param("i", $newId); // 'i' เพราะ Receipt_Id เป็น INT
      $check_stmt->execute();
      $check_stmt->store_result();
      if ($check_stmt->num_rows == 0) {
        $isUnique = true; // พบ ID ที่ไม่ซ้ำกัน
        error_log("DEBUG: generateUniqueIntId: Found unique ID: $newId"); // Debug log
      } else {
        error_log("DEBUG: generateUniqueIntId: ID $newId is NOT unique, trying again."); // Debug log
      }
      $check_stmt->close();
    } else {
      // จัดการข้อผิดพลาดหาก prepare statement ล้มเหลว
      error_log("ERROR: generateUniqueIntId - Failed to prepare unique ID check statement for $table.$idColumn: " . $conn->error);
      die("Error checking for unique ID.");
    }
  }

  if (!$isUnique) {
    // หากไม่สามารถสร้าง ID ที่ไม่ซ้ำกันได้หลังจากพยายามหลายครั้ง
    error_log("CRITICAL ERROR: generateUniqueIntId - Failed to generate a unique ID for $table.$idColumn after $maxAttempts attempts. Last ID tried: $newId");
    die("Error: Could not generate a unique receipt ID.");
  }

  return $newId;
}

// *** ใช้ฟังก์ชันเพื่อสร้าง Receipt_Id ***
$receipt_id = generateUniqueIntId($conn, 'receipt', 'Receipt_Id');

// เพิ่ม debug log เพื่อยืนยันค่า Receipt_Id ก่อน INSERT
error_log("DEBUG: upload_slip.php - About to insert into receipt: Receipt_Id = " . $receipt_id . " (Type: " . gettype($receipt_id) . ")");


$receipt_date = date('Y-m-d'); // วันที่ปัจจุบัน (จะใช้ Timezone ที่ตั้งไว้ด้านบน)
$receipt_time = date('H:i:s'); // เวลาปัจจุบัน (จะใช้ Timezone ที่ตั้งไว้ด้านบน)

// เพิ่ม debug log สำหรับเวลาที่กำลังจะถูกบันทึก
error_log("DEBUG: upload_slip.php - Date to be saved: " . $receipt_date);
error_log("DEBUG: upload_slip.php - Time to be saved: " . $receipt_time);


$email_admin = NULL; // ค่าเริ่มต้นเป็น NULL เพราะยังไม่มีแอดมินอนุมัติ
$status_receipt = 'No'; // ค่าเริ่มต้นเป็น 'No' ตามที่กำหนด

// *** แก้ไข: ใช้ Payment_image_file ใน INSERT statement (กลับมาใช้ตามที่คุณแจ้ง) ***
$sql_insert_receipt = "INSERT INTO receipt (Receipt_Id, Guest_name, Receipt_date, Receipt_time,
                                            Phone_number, Payment_image_file, Email_Admin, Status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_insert_receipt = $conn->prepare($sql_insert_receipt);

if ($stmt_insert_receipt === false) {
  error_log("ERROR: upload_slip.php - Failed to prepare receipt insert statement: " . $conn->error);
  // ตรวจสอบว่า $targetFilePath ถูกกำหนดค่าแล้วก่อน unlink
  if (isset($targetFilePath) && file_exists($targetFilePath)) {
    unlink($targetFilePath); // ลบไฟล์ที่อัปโหลดไปแล้วออกหากเกิดข้อผิดพลาดตรงนี้
    error_log("DEBUG: upload_slip.php - Deleted uploaded file due to SQL prepare error: " . $targetFilePath);
  }
  die("<p class='error'>❌ เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับบันทึกสลิป: " . $conn->error . "</p>");
}

// ผูกพารามิเตอร์สำหรับตาราง receipt
// i (สำหรับ Receipt_Id ที่เป็น INT), แล้วตามด้วย sssssss
$stmt_insert_receipt->bind_param(
  "isssssss",
  $receipt_id,    // ต้องเป็น 'i'
  $guest_name,
  $receipt_date,
  $receipt_time,
  $phone_number,
  $receipt_image_filename, // ตัวแปรยังคงชื่อเดิมคือ $receipt_image_filename
  $email_admin, // ส่ง NULL เป็นสตริง
  $status_receipt
);

if (!$stmt_insert_receipt->execute()) {
  error_log("ERROR: upload_slip.php - Error inserting into receipt table: " . $stmt_insert_receipt->error);
  // ตรวจสอบว่า $targetFilePath ถูกกำหนดค่าแล้วก่อน unlink
  if (isset($targetFilePath) && file_exists($targetFilePath)) {
    unlink($targetFilePath); // ลบไฟล์ที่อัปโหลดไปแล้วออกหากบันทึกข้อมูลใน DB ไม่สำเร็จ
    error_log("DEBUG: upload_slip.php - Deleted uploaded file due to SQL execution error: " . $targetFilePath);
  }
  die("<p class='error'>❌ เกิดข้อผิดพลาดในการบันทึกข้อมูลสลิป: " . $stmt_insert_receipt->error . "</p>");
}
$stmt_insert_receipt->close();


// --- 5. อัปเดตตาราง `reservation` เพื่อเปลี่ยนสถานะ และบันทึก Receipt_Id ---
$booking_status_id_paid_pending = 2; // สถานะ "ชำระเงินสำเร็จรอตรวจสอบ"

$sql_update_reservation = "UPDATE reservation
                           SET Booking_status_Id = ?, Receipt_Id = ? 
                           WHERE Reservation_Id = ?";
$stmt_update_reservation = $conn->prepare($sql_update_reservation);

if ($stmt_update_reservation === false) {
  error_log("ERROR: upload_slip.php - Failed to prepare reservation update statement: " . $conn->error);
  die("<p class='error'>❌ เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับอัปเดตสถานะการจอง: " . $conn->error . "</p>");
}

// ผูกพารามิเตอร์: 'i' สำหรับ Booking_status_Id (int), 'i' สำหรับ Receipt_Id (int), 's' สำหรับ Reservation_Id (string)
$stmt_update_reservation->bind_param(
  "iis", // 'i' สำหรับ Booking_status_Id, 'i' สำหรับ Receipt_Id (เป็น int), 's' สำหรับ Reservation_Id
  $booking_status_id_paid_pending,
  $receipt_id,       // ส่งค่า Receipt_Id (ที่เป็น int) ไปบันทึกในตาราง reservation
  $reservation_id
);

if (!$stmt_update_reservation->execute()) {
  error_log("ERROR: upload_slip.php - Error updating reservation status: " . $stmt_update_reservation->error);
  die("<p class='error'>❌ เกิดข้อผิดพลาดในการอัปเดตสถานะการจอง: " . $stmt_update_reservation->error . "</p>");
}
$stmt_update_reservation->close();

// --- 6. ล้าง session ที่เกี่ยวข้องกับการจองทั้งหมดหลังจากที่ทำรายการสำเร็จแล้ว ---
unset($_SESSION['current_reservation_id']);
unset($_SESSION['total_price']);
unset($_SESSION['expire_time']);
unset($_SESSION['province_id']);
unset($_SESSION['province_name']);
error_log("DEBUG: upload_slip.php - All relevant session variables unset.");

$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูล

// --- 7. แสดงผลลัพธ์หรือเปลี่ยนเส้นทางไปยังหน้าแสดงผลสำเร็จ ---
// *** สร้าง URL สำหรับรูปภาพโดยตรง *** (จะใช้ใน receipt_details.php)
// สมมติว่าไฟล์ upload_slip.php อยู่ที่ C:\xampp\htdocs\dom-inn\Project\PHP\
// และโฟลเดอร์ uploads/receipts/ อยู่ที่ C:\xampp\htdocs\dom-inn\Project\PHP\uploads\receipts\
// Base URL สำหรับ Project (ตัวอย่าง: http://localhost/dom-inn/Project/)
$base_project_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$base_project_url .= "/dom-inn/Project/"; // ปรับตาม path จริงของ Project ใน htdocs

// URL สำหรับโฟลเดอร์รูปภาพ (สัมพัทธ์กับ root ของ Project)
$image_folder_web_path = "PHP/" . $targetDir; // targetDir = "uploads/receipts/"

?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <title>บันทึกการชำระเงินสำเร็จ</title>
  <link rel="icon" type="image/png" href="../src/images/logo.png" />
  <style>
    /* Styles as provided previously */
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
    <h2>✅ บันทึกการชำระเงินสำเร็จ</h2>
    <p>รหัสการจอง: <span class='highlight'>#<?= htmlspecialchars($reservation_id) ?></span></p>
    <!-- <p>รหัสใบเสร็จ: <span class='highlight'>#<?= htmlspecialchars($receipt_id) ?></span></p> -->
    <p>คุณ <span class='highlight'><?= htmlspecialchars($guest_name) ?></span> ได้จองห้องจำนวน <span class='highlight'><?= htmlspecialchars($num_rooms) ?></span> ห้อง</p>
    <p>ยอดเงินที่ต้องชำระ: <span class='highlight'>฿ <?= number_format($total_price, 2) ?></span></p>
    <p>วันเข้าพัก: <span class='highlight'><?= htmlspecialchars($checkin_date) ?></span> ถึง <span class='highlight'><?= htmlspecialchars($checkout_date) ?></span></p>
    <p>จำนวนผู้เข้าพัก: <span class='highlight'><?= htmlspecialchars($adults) ?></span> ผู้ใหญ่, <span class='highlight'><?= htmlspecialchars($children) ?></span> เด็ก</p>
    <p>สาขาที่จอง: <span class='highlight'><?= htmlspecialchars($province_name_to_display) ?></span></p>
    <p>สถานะการจอง: <span class='highlight'>ชำระเงินสำเร็จรอตรวจสอบ</span></p>

    <a href='home.php'>กลับไปหน้าหลัก</a>
    <!-- <a href='receipt_details.php?receipt_id=<?= htmlspecialchars($receipt_id) ?>' target="_blank" class='btn-green'>ดูรายละเอียดสลิป</a> -->
  </div>
</body>

</html>