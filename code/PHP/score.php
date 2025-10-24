<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่าไฟล์ db.php เชื่อมต่อฐานข้อมูลได้ถูกต้อง

// ตั้งค่า default timezone สำหรับ PHP เพื่อให้แน่ใจว่าเวลาถูกต้อง
date_default_timezone_set('Asia/Bangkok'); // กำหนด timezone เป็น Asia/Bangkok (สำคัญ!)

// ตรวจสอบว่ามี session email_member หรือไม่ หากไม่มี ให้ redirect ไปหน้า login
if (!isset($_SESSION['email'])) {
  header('Location: login.php'); // เปลี่ยนเป็นหน้า login ของคุณ
  exit();
}

$email_member = $_SESSION['email'];

// ดึงข้อมูลการจองทั้งหมดของลูกค้า พร้อมข้อมูลสถานะ, จังหวัด, ดาว และคอมเมนต์
// สำคัญ: ต้องดึง Booking_status_Id มาด้วย
$sql = "SELECT r.Reservation_Id, r.Guest_name, r.Booking_time, r.Number_of_rooms,
               r.Number_of_adults, r.Number_of_children,
               r.Booking_date, r.Check_out_date, r.Booking_status_Id,
               b.Booking_status_name,
               p.Province_name,
               r.stars,
               r.comment,
               r.rating_timestamp    /* *** เพิ่ม: ดึงคอลัมน์ rating_timestamp *** */
        FROM reservation r
        LEFT JOIN booking_status b ON r.Booking_status_Id = b.Booking_status_Id
        LEFT JOIN province p ON r.Province_Id = p.Province_Id
        WHERE r.Email_member = ?
        ORDER BY r.Check_out_date DESC, r.Booking_date DESC"; // เรียงตามวันเช็คเอาท์ล่าสุด
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email_member);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
  $bookings[] = $row;
}
$stmt->close();

// กำหนด ID ของสถานะ "เช็คเอาท์แล้ว" (เสร็จสมบูรณ์) และ "เช็คอินแล้ว"
$status_id_completed = 7; // ตรวจสอบว่า ID 7 ใน booking_status table คือ 'เช็คเอาท์แล้ว'
$status_id_checked_in = 6; // ตรวจสอบว่า ID 6 ใน booking_status table คือ 'เช็คอินแล้ว'

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
  <title>ให้คะแนนการจอง</title>
  <link rel="icon" type="image/png" href="../src/images/logo.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;700&display=swap">
  <!-- *** เพิ่ม: ลิงก์ไปยัง ino.css สำหรับสไตล์ของ Header *** -->
  <link rel="stylesheet" href="../CSS/css/ino.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOM7z4j8e+Q1z5l5x5l5x5l5x5l5x5l5x5l5x"
    crossorigin="anonymous" />
  <style>
    body {
      font-family: 'Kanit', sans-serif;
      background: #f8f9fa;
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column; /* จัดเรียงองค์ประกอบในแนวตั้ง */
      align-items: center; /* จัดกึ่งกลางแนวนอน */
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
      max-width: 1000px;
      margin: 40px auto; /* ปรับ margin-top เพื่อไม่ให้ชน Header */
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
      padding: 32px 25px;
      width: 95%;
      box-sizing: border-box;
    }

    h2 {
      text-align: center;
      color: #3b3b3b;
      margin-bottom: 30px;
      font-size: 2rem;
      font-weight: 700;
    }

    .booking-card {
      background: #fafafa;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
      margin-bottom: 20px;
      padding: 20px;
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      align-items: flex-start;
      border: 1px solid #e0e0e0;
    }

    .booking-info {
      flex: 1;
      min-width: 200px;
      font-size: 0.95rem;
      color: #555;
    }

    .booking-info p {
      margin: 5px 0;
    }

    .booking-info strong {
      color: #333;
    }

    .rating-section {
      flex: 2;
      min-width: 300px;
      background: #fdfdfd;
      padding: 15px;
      border-radius: 8px;
      border: 1px solid #eee;
    }

    .rating-section h4 {
      margin-top: 0;
      color: #0984e3;
      font-weight: 500;
      font-size: 1.1rem;
      margin-bottom: 10px;
    }

    /* Star Rating CSS */
    .star-rating {
      display: inline-flex;
      flex-direction: row-reverse;
      justify-content: center;
      margin-bottom: 15px;
      user-select: none;
    }

    .star-rating input[type="radio"] {
      display: none;
    }

    .star-rating label {
      font-size: 2.2em;
      color: #bbb;
      cursor: pointer;
      padding: 0 3px;
      transition: color 0.2s ease-in-out;
    }

    .star-rating label:hover,
    .star-rating label:hover~label {
      color: #ffcc00;
    }

    .star-rating input[type="radio"]:checked~label {
      color: #ffcc00;
    }

    .rating-comment textarea {
      width: calc(100% - 22px);
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      min-height: 80px;
      font-family: 'Kanit', sans-serif;
      font-size: 0.95rem;
      resize: vertical;
      margin-bottom: 15px;
    }

    .submit-btn {
      display: block;
      width: 100%;
      padding: 10px 15px;
      background: #28a745;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 500;
      cursor: pointer;
      font-size: 1rem;
      transition: background 0.3s ease;
    }

    .submit-btn:hover {
      background: #218838;
    }

    .rated-display {
      text-align: center;
      padding: 10px 0;
      color: #27ae60;
      font-weight: 500;
    }

    .rated-stars {
      color: #ffcc00;
      font-size: 1.8em;
      line-height: 1;
      margin-bottom: 5px;
    }

    .rated-comment {
      font-size: 0.95rem;
      color: #666;
      background: #f0f8ff;
      padding: 8px;
      border-radius: 5px;
      margin-top: 10px;
    }

    /* เพิ่มสไตล์สำหรับวันที่ให้คะแนน */
    .rated-date {
        font-size: 0.85rem;
        color: #888;
        margin-top: 5px;
    }

    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-weight: 600;
      display: inline-block;
      font-size: 0.85rem;
      margin-top: 5px;
    }

    .future-booking-status {
      /* สำหรับสถานะที่ยังไม่เสร็จสมบูรณ์/ยังไม่เช็คอิน */
      background: #e8f5ff;
      color: #0984e3;
      border: 1px solid #74b9ff;
    }

    .checked-in-status {
      /* สำหรับสถานะเช็คอินแล้ว */
      background: #d1ecf1;
      color: #0c5460;
      border: 1px solid #bee5eb;
    }

    .completed-status {
      /* สำหรับสถานะเช็คเอาท์แล้ว */
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

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .booking-card {
        flex-direction: column;
        align-items: stretch;
      }

      .booking-info,
      .rating-section {
        min-width: unset;
        width: 100%;
      }
    }
  </style>
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
        <a href="./type.php">ประเภทห้องพัก</a>
        <a href="./branch.php">สาขาโรงแรมดอม อินน์</a>
        <a href="./details.php">รายละเอียดต่างๆ</a>
        <a href="./booking_status_pending.php">การจองของฉัน</a>
        <a href="./score.php">คะแนน</a>
      </nav>
          <?php if ($full_name && $full_name !== ' '): ?>
      <div class="user-display">
        <a href="profile.php" class="profile-link"><?= htmlspecialchars($full_name) ?></a>
      </div>
    <?php endif; ?>
    </header>
    <!-- *** สิ้นสุด Header ที่แทรกเข้ามา *** -->

  <div class="container">
    <h2>ให้คะแนนการจองของคุณ</h2>

    <?php if (isset($_SESSION['message'])): ?>
      <div style="background-color: <?= $_SESSION['message_type'] == 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $_SESSION['message_type'] == 'success' ? '#155724' : '#721c24' ?>; border: 1px solid <?= $_SESSION['message_type'] == 'success' ? '#c3e6cb' : '#f5c6cb' ?>; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
        <?= $_SESSION['message'] ?>
      </div>
      <?php
      unset($_SESSION['message']);
      unset($_SESSION['message_type']);
      ?>
    <?php endif; ?>

    <?php if (count($bookings) === 0): ?>
      <div class="no-booking">ไม่มีรายการจองในขณะนี้</div>
    <?php else: ?>
      <?php foreach ($bookings as $b): ?>
        <div class="booking-card">
          <div class="booking-info">
            <p><strong>รหัสการจอง:</strong> <?= htmlspecialchars($b['Reservation_Id']) ?></p>
            <p><strong>ชื่อผู้จอง:</strong> <?= htmlspecialchars($b['Guest_name']) ?></p>
            <p><strong>วันที่/เวลาจอง:</strong> <?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($b['Booking_time']))) ?></p>
            <p><strong>ชื่อสาขา:</strong> <?= htmlspecialchars($b['Province_name'] ?? 'ไม่ระบุ') ?></p>
            <p><strong>จำนวนห้อง:</strong> <?= htmlspecialchars($b['Number_of_rooms']) ?></p>
            <p><strong>ผู้ใหญ่:</strong> <?= htmlspecialchars($b['Number_of_adults']) ?></p>
            <p><strong>เด็ก:</strong> <?= htmlspecialchars($b['Number_of_children']) ?></p>
            <p><strong>วันเข้าพัก:</strong> <?= htmlspecialchars($b['Booking_date']) ?></p>
            <p><strong>วันเช็คเอาท์:</strong> <?= htmlspecialchars($b['Check_out_date']) ?></p>
          </div>

          <div class="rating-section">
            <?php
            // --- เงื่อนไขการแสดงฟอร์มให้คะแนน: ตรวจสอบสถานะการจองเป็น 'เช็คเอาท์แล้ว' และยังไม่มีการให้คะแนน ---
            if ($b['Booking_status_Id'] == $status_id_completed && $b['stars'] === NULL):
            ?>
              <h4>ให้คะแนนประสบการณ์ของคุณ</h4>
              <form action="process_rating.php" method="POST">
                <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($b['Reservation_Id']) ?>">
                <div class="star-rating">
                  <input type="radio" id="star5-<?= $b['Reservation_Id'] ?>" name="stars" value="5" required /><label for="star5-<?= $b['Reservation_Id'] ?>">★</label>
                  <input type="radio" id="star4-<?= $b['Reservation_Id'] ?>" name="stars" value="4" /><label for="star4-<?= $b['Reservation_Id'] ?>">★</label>
                  <input type="radio" id="star3-<?= $b['Reservation_Id'] ?>" name="stars" value="3" /><label for="star3-<?= $b['Reservation_Id'] ?>">★</label>
                  <input type="radio" id="star2-<?= $b['Reservation_Id'] ?>" name="stars" value="2" /><label for="star2-<?= $b['Reservation_Id'] ?>">★</label>
                  <input type="radio" id="star1-<?= $b['Reservation_Id'] ?>" name="stars" value="1" /><label for="star1-<?= $b['Reservation_Id'] ?>">★</label>
                </div>
                <div class="rating-comment">
                  <textarea name="comment" placeholder="เขียนคอมเมนต์ของคุณ (ไม่จำเป็น)" rows="3"></textarea>
                </div>
                <button type="submit" class="submit-btn">ส่งคะแนน</button>
              </form>
            <?php
            // --- เงื่อนไขสำหรับจองที่เช็คเอาท์แล้ว และมีการให้คะแนนแล้ว ---
            elseif ($b['Booking_status_Id'] == $status_id_completed && $b['stars'] !== NULL):
            ?>
              <h4>คุณให้คะแนนแล้ว</h4>
              <div class="rated-display">
                <div class="rated-stars">
                  <?php for ($i = 0; $i < $b['stars']; $i++) echo '★'; ?>
                  <?php for ($i = 0; $i < (5 - $b['stars']); $i++) echo '☆'; ?>
                </div>
                <?php if ($b['comment']): ?>
                  <p class="rated-comment"><?= htmlspecialchars($b['comment']) ?></p>
                <?php else: ?>
                  <p class="rated-comment">ไม่มีคอมเมนต์</p>
                <?php endif; ?>
                <?php /* *** เพิ่ม: แสดงวันที่ให้คะแนนล่าสุดหากมีข้อมูล *** */ ?>
                <?php if ($b['rating_timestamp']): ?>
                  <p class="rated-date">ให้คะแนนล่าสุด: <?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($b['rating_timestamp']))) ?></p>
                <?php endif; ?>
              </div>
            <?php
            // --- เงื่อนไขสำหรับจองที่ยังไม่เช็คเอาท์ (สถานะอื่นๆ) ---
            else:
              $status_class = '';
              if ($b['Booking_status_Id'] == $status_id_checked_in) { // เช็คอินแล้ว
                $status_class = 'checked-in-status';
              } elseif ($b['Booking_status_Id'] < $status_id_checked_in) { // สถานะก่อนเช็คอิน เช่น ยืนยันการจอง, ชำระเงินสำเร็จ
                $status_class = 'future-booking-status';
              } else { // สถานะอื่นๆ เช่น ยกเลิก
                $status_class = 'future-booking-status';
              }
            ?>
              <h4>สถานะปัจจุบัน</h4>
              <p class="status-badge <?= $status_class ?>">
                <?= htmlspecialchars($b['Booking_status_name'] ?? 'ไม่ทราบ') ?>
              </p>
              <p style="margin-top: 10px; font-size: 0.9em; color: #777;">
                สามารถให้คะแนนได้เมื่อสถานะเป็น "เช็คเอาท์แล้ว"
              </p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <div style="text-align:center;">
      <a href="home.php" class="back-btn">กลับหน้าหลัก</a>
    </div>
  </div>
</body>

</html>