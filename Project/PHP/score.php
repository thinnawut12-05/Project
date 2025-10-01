<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่าไฟล์ db.php เชื่อมต่อฐานข้อมูลได้ถูกต้อง

// ตรวจสอบว่ามี session email_member หรือไม่ หากไม่มี ให้ redirect ไปหน้า login
if (!isset($_SESSION['email'])) {
    header('Location: login.php'); // เปลี่ยนเป็นหน้า login ของคุณ
    exit();
}

$email_member = $_SESSION['email'];

// ดึงข้อมูลการจองทั้งหมดของลูกค้า พร้อมข้อมูลสถานะ, จังหวัด, ดาว และคอมเมนต์
$sql = "SELECT r.Reservation_Id, r.Guest_name, r.Number_of_rooms,
               r.Number_of_adults, r.Number_of_children,
               r.Booking_date, r.Check_out_date, r.Booking_status_Id,
               b.Booking_status_name,
               p.Province_name,
               r.stars,
               r.comment
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

$current_date = date('Y-m-d'); // วันที่ปัจจุบัน
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ให้คะแนนการจอง</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;700&display=swap">
  <style>
    body {
      font-family: 'Kanit', sans-serif;
      /* background: linear-gradient(120deg, #a8edea, #fed6e3); */
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .container {
      max-width: 1000px; /* เพิ่มความกว้างเพื่อให้มีพื้นที่มากขึ้น */
      margin: 40px auto;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
      padding: 32px 25px; /* ปรับ padding */
      width: 95%; /* เพิ่มความยืดหยุ่น */
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
      flex-wrap: wrap; /* ให้เนื้อหา card ขึ้นบรรทัดใหม่ได้ */
      gap: 15px; /* ระยะห่างระหว่างข้อมูล */
      align-items: flex-start; /* จัดให้อยู่ด้านบน */
      border: 1px solid #e0e0e0;
    }

    .booking-info {
      flex: 1; /* ให้ส่วนข้อมูลขยายได้ */
      min-width: 200px; /* อย่างน้อย 200px ก่อนจะหด */
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
      flex: 2; /* ให้ส่วน rating ขยายมากกว่า */
      min-width: 300px; /* อย่างน้อย 300px */
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
      user-select: none; /* ป้องกันการเลือกข้อความดาว */
    }
    .star-rating input[type="radio"] {
      display: none;
    }
    .star-rating label {
      font-size: 2.2em; /* ขนาดดาว */
      color: #bbb;
      cursor: pointer;
      padding: 0 3px;
      transition: color 0.2s ease-in-out;
    }
    .star-rating label:hover,
    .star-rating label:hover ~ label {
      color: #ffcc00; /* สีเมื่อ hover */
    }
    .star-rating input[type="radio"]:checked ~ label {
      color: #ffcc00; /* สีเมื่อเลือก */
    }

    .rating-comment textarea {
      width: calc(100% - 22px); /* ลบ padding */
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
        line-height: 1; /* จัดให้อยู่ในบรรทัดเดียว */
        margin-bottom: 5px;
    }
    .rated-comment {
        font-size: 0.95rem;
        color: #666;
        background: #f0f8ff; /* สีพื้นหลังอ่อนๆ สำหรับคอมเมนต์ */
        padding: 8px;
        border-radius: 5px;
        margin-top: 10px;
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
        background: #e8f5ff;
        color: #0984e3;
        border: 1px solid #74b9ff;
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
        .booking-info, .rating-section {
            min-width: unset;
            width: 100%;
        }
    }
  </style>
</head>
<body>
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
            <p><strong>ชื่อสาขา:</strong> <?= htmlspecialchars($b['Province_name'] ?? 'ไม่ระบุ') ?></p>
            <p><strong>จำนวนห้อง:</strong> <?= htmlspecialchars($b['Number_of_rooms']) ?></p>
            <p><strong>ผู้ใหญ่:</strong> <?= htmlspecialchars($b['Number_of_adults']) ?></p>
            <p><strong>เด็ก:</strong> <?= htmlspecialchars($b['Number_of_children']) ?></p>
            <p><strong>วันเข้าพัก:</strong> <?= htmlspecialchars($b['Booking_date']) ?></p>
            <p><strong>วันเช็คเอาท์:</strong> <?= htmlspecialchars($b['Check_out_date']) ?></p>
          </div>

          <div class="rating-section">
            <?php
            // ตรวจสอบว่าถึงกำหนดเช็คเอาท์แล้ว และยังไม่มีการให้คะแนน
            if ($b['Check_out_date'] < $current_date && $b['stars'] === NULL):
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
            // ตรวจสอบว่าถึงกำหนดเช็คเอาท์แล้ว และให้คะแนนแล้ว
            elseif ($b['Check_out_date'] < $current_date && $b['stars'] !== NULL):
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
              </div>
            <?php
            // การจองที่ยังไม่ถึงกำหนดเช็คเอาท์
            else:
            ?>
              <h4>สถานะปัจจุบัน</h4>
              <p class="status-badge future-booking-status">
                <?= htmlspecialchars($b['Booking_status_name'] ?? 'ไม่ทราบ') ?>
              </p>
              <p style="margin-top: 10px; font-size: 0.9em; color: #777;">
                สามารถให้คะแนนได้หลังวันเช็คเอาท์
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