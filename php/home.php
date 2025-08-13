<?php
include 'db.php'; // เชื่อมต่อฐานข้อมูล
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();
$First_name = $_SESSION['First_name'] ?? '';
$Last_name = $_SESSION['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);

// include 'db.php'; // เปิดใช้งานเมื่อมีไฟล์เชื่อมต่อ DB

// ดึงข้อมูลภูมิภาคจากฐานข้อมูล (ตัวอย่างจำลอง)
// ตัวอย่างข้อมูลจำลอง หากเชื่อมต่อจริงให้ใช้ mysqli หรือ PDO
// $regions = [
//     ['id' => 'north', 'name' => 'ดอมอินน์ ภูมิภาคเหนือ'],
//     ['id' => 'central', 'name' => 'ดอมอินน์ ภูมิภาคกลาง'],
//     ['id' => 'northeast', 'name' => 'ดอมอินน์ ภูมิภาคตะวันออกเฉียงเหนือ'],
//     ['id' => 'west', 'name' => 'ดอมอินน์ ภูมิภาคตะวันตก'],
//     ['id' => 'south', 'name' => 'ดอมอินน์ ภูมิภาคใต้'],
// ];
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <title>Dom Inn Hotel</title>
  <link rel="icon" type="image/png" href="./src/images/logo.png" />
  <link rel="stylesheet" href="./in.css" />
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOM7z4j8e+Q1z5l5x5l5x5l5x5l5x5l5x"
    crossorigin="anonymous" />
</head>

<body>
  <header>
    <section class="logo">
      <img src="./src/images/4.png" width="50" height="50" />
    </section>
    <nav>
      <a href="./type.php">ประเภทห้องพัก</a>
      <a href="#">สาขาโรงแรมดอม อินน์</a>
      <a href="#">รายละเอียดต่างๆ</a>
      <a href="#">การจองของฉัน</a>
      <a href="./score.php">คะแนน</a>

    </nav>
    <?php if ($full_name): ?>
      <div class="user-display">
        <?= htmlspecialchars($full_name) ?>
      </div>
    <?php endif; ?>

  </header>

  <section class="booking-form">
    <select id="region" onchange="updateBranches()">
      <option disabled selected value>เลือกภูมิภาค</option>
      <?php foreach ($regions as $region): ?>
        <option value="<?= $region['id'] ?>"><?= $region['name'] ?></option>
      <?php endforeach; ?>
    </select>

    <select id="branch">
      <option disabled selected value>เลือกสาขา</option>
    </select>

    <input id="date-range" type="text" placeholder="วันที่เช็คอิน - วันที่เช็คเอ้าท์" readonly onclick="openCalendar()" />

    <div class="guest-selector">
      <label>จำนวนผู้เข้าพัก</label>

      <div class="guest-group">
        <span>ผู้ใหญ่</span>
        <button type="button" onclick="changeGuest('adult', -1)">–</button>
        <span id="adult-count">0</span>
        <button type="button" onclick="changeGuest('adult', 1)">+</button>
      </div>

      <div class="guest-group">
        <span>เด็ก</span>
        <button type="button" onclick="changeGuest('child', -1)">–</button>
        <span id="child-count">0</span>
        <button type="button" onclick="changeGuest('child', 1)">+</button>
      </div>

      <div id="child-age-container" style="display:none; margin-top:8px;">
        <label>อายุของเด็กแต่ละคน (ปี):</label>
        <div id="child-age-list"></div>
      </div>

      <div class="guest-summary">
        <input id="guest-summary-input" type="text" readonly value="ผู้ใหญ่ 1, เด็ก 0 คน" />
      </div>
    </div>

    <button class="btn">จองเลย</button>
  </section>

  <section class="room-gallery">
    <img src="./src/images/1.jpg" alt="Room 1" />
    <img src="./src/images/2.jpg" alt="Room 2" />
  </section>

  <section class="hotel-description">
    <h2>ยินดีต้อนรับ สู่ โรงแรมของเรา</h2>
    <p>เรามุ่งมั่นที่จะสร้างเครือข่ายโรงแรมที่มอบความสะดวกสบายด้วยราคาที่เป็นมิตร
      ความแน่วแน่ เพื่อคุณภาพที่สม่ำเสมอในทุกสาขาทั่วภูมิภาคเอเชียแปซิฟิก
      คือหัวใจของทุกสิ่งที่เราทำ
      เราต้องการให้ลูกค้าทุกคนสัมผัสความสุขและความพึงพอใจ
      เพราะสามารถไว้วางใจเราได้ทุกครั้ง
      ความสุขของคุณคือคำมั่นสัญญาของเรา
      เพราะความสม่ำเสมอของเราคือความสะดวกสบายของคุณ </p>
    <p>"ความสม่ำเสมอเป็นของคุณ"</p>
  </section>

  <!-- POPUP CALENDAR -->
  <div id="calendarOverlay" onclick="closeCalendar()"></div>
  <div id="calendarPopup">
    <span class="close-calendar" onclick="closeCalendar()">×</span>
    <div class="calendar-container">
      <div class="calendar-header">
        <span class="nav-btn" onclick="changeMonth(-1)">&#8249;</span>
        <div class="calendar-month">
          <h2 id="month-label">Loading...</h2>
        </div>
        <span class="nav-btn" onclick="changeMonth(1)">&#8250;</span>
      </div>
      <div class="calendar-grid" id="calendar-days"></div>
      <div class="calendar-grid" id="calendar-dates"></div>
      <button class="btn" onclick="confirmDate()">ยืนยันวันเข้าพัก</button>
    </div>
  </div>

  <script src="sc.js"></script>
</body>

</html>