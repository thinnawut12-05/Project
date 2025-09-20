<?php
// --- START: PHP code for database connection and fetching data ---

// Database connection details
$servername = "localhost"; // หรือ IP ของเซิร์ฟเวอร์ฐานข้อมูล
$username = "root";       // ชื่อผู้ใช้ฐานข้อมูล (ค่าเริ่มต้นคือ root)
$password = "";           // รหัสผ่าน (ค่าเริ่มต้นมักจะว่าง)
$dbname = "hotel_db";     // ชื่อฐานข้อมูล

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Set character set to utf8 to support Thai language
$conn->set_charset("utf8");

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// --- Fetch Regions ---
$sql_regions = "SELECT Region_Id, Region_name FROM region ORDER BY Region_Id ASC";
$result_regions = $conn->query($sql_regions);
$regions = [];
if ($result_regions->num_rows > 0) {
  while ($row = $result_regions->fetch_assoc()) {
    $regions[] = $row;
  }
}

// --- Fetch Provinces (Branches) ---
$sql_provinces = "SELECT Province_Id, Province_name, Region_Id FROM province ORDER BY Province_name ASC";
$result_provinces = $conn->query($sql_provinces);
$provinces = [];
if ($result_provinces->num_rows > 0) {
  while ($row = $result_provinces->fetch_assoc()) {
    $provinces[] = $row;
  }
}

// Close the database connection
$conn->close();

// --- END: PHP code ---
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <title>Dom Inn Hotel</title>
  <link rel="icon" type="image/png" href="../src/images/logo.png" />
  <link rel="stylesheet" href="../CSS/css/ino.css" />
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOM7z4j8e+Q1z5l5x5l5x5l5x5l5x5l5x5l5x"
    crossorigin="anonymous" />
</head>

<body>
  <header>
    <section class="logo">
       <a href="./nextpage.php"> <!-- เปลี่ยน index.php เป็นหน้าที่คุณต้องการ -->
        <img src="../src/images/4.png" width="50" height="50" alt="Dom Inn Logo" />
      </a>
    </section>
    <nav>
      <a href="./type.php">ประเภทห้องพัก</a>
      <a href="./branch.php">สาขาโรงแรมดอม อินน์</a>
      <a href="./details.php">รายละเอียดต่างๆ</a>
      <a href="#">การจองของฉัน</a>
      <a href="./score.php">คะแนน</a>
    </nav>
    <nav>
      <a href="./member.php">สมัครสมาชิก</a>
      <a href="./login.php">เข้าสู่ระบบ</a>
    </nav>
  </header>

  <section class="booking-form">
    <!-- Dropdown ภูมิภาค -->
    <select id="region" onchange="updateBranches()">
      <option disabled selected value>เลือกภูมิภาค</option>
      <?php
      foreach ($regions as $region):
      ?>
        <option value="<?= htmlspecialchars($region['Region_Id']) ?>">
          <?= htmlspecialchars($region['Region_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Dropdown สาขา (จังหวัด) -->
    <select id="branch">
      <option disabled selected value>เลือกสาขา</option>
      <?php
      foreach ($provinces as $province):
      ?>
        <!-- เพิ่ม data-region-id เพื่อให้ Javascript รู้ว่าสาขานี้อยู่ภูมิภาคไหน -->
        <option value="<?= htmlspecialchars($province['Province_Id']) ?>"
          data-region-id="<?= htmlspecialchars($province['Region_Id']) ?>"
          style="display:none;"> <!-- ซ่อนไว้ก่อนเป็นค่าเริ่มต้น -->
          <?= htmlspecialchars($province['Province_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <input id="date-range" type="text" placeholder="วันที่เช็คอิน - วันที่เช็คเอ้าท์" readonly onclick="openCalendar()" />

    <div id="rooms-container">
      <!-- ช่องกรอกจำนวนห้องพัก -->
      <div class="room-input-group">
        <label for="num-rooms">จำนวนห้อง:</label>
        <input type="number" id="num-rooms" value="1" min="1" max="5" onchange="updateRoomsFromInput()">
      </div>

      <!-- ห้องแรก (จะถูกสร้างหรืออัปเดตด้วย JavaScript) -->
      <div class="room" data-room="1">
        <h4>ห้องที่ 1</h4>

        <div class="guest-group">
          <span>ผู้ใหญ่</span>
          <button type="button" onclick="changeGuest(this, 'adult', -1)">–</button>
          <span class="adult-count">1</span>
          <button type="button" onclick="changeGuest(this, 'adult', 1)">+</button>
        </div>

        <div class="guest-group">
          <span>เด็ก</span>
          <button type="button" onclick="changeGuest(this, 'child', -1)">–</button>
          <span class="child-count">0</span>
          <button type="button" onclick="changeGuest(this, 'child', 1)">+</button>
        </div>


        <div class="child-age-container" style="display:none; margin-top:8px;">
          <label>อายุของเด็กแต่ละคน (ปี):</label>
          <div class="child-age-list"></div>
        </div>
      </div>
      <!-- ปุ่มเพิ่มห้องจะถูกลบออกไป และควบคุมด้วยช่องกรอกตัวเลขแทน -->
      <div class="guest-summary">
        <input id="guest-summary-input" type="text" readonly value="ผู้ใหญ่ 1, เด็ก 0 คน" />
      </div>
    </div>

    <button class="btn">จองเลย</button>
  </section>

  <!-- ... ส่วนที่เหลือของ HTML ... -->

  <section class="room-gallery">
    <img src="../src/images/1.jpg" alt="Room 1" />
    <img src="../src/images/2.jpg" alt="Room 2" />
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

  <script src="../JS/js/test.js"></script>

  <!-- START: JAVASCRIPT for dynamic dropdown -->
  <script>
    function updateBranches() {
      const regionSelect = document.getElementById('region');
      const branchSelect = document.getElementById('branch');
      const selectedRegionId = regionSelect.value;

      // รีเซ็ต dropdown สาขาให้กลับไปที่ค่าเริ่มต้น "เลือกสาขา"
      branchSelect.selectedIndex = 0;

      // ดึง option ทั้งหมดใน dropdown สาขา
      const branchOptions = branchSelect.getElementsByTagName('option');

      // วนลูปเพื่อตรวจสอบทีละ option
      for (let i = 0; i < branchOptions.length; i++) {
        const option = branchOptions[i];
        const regionIdOfBranch = option.getAttribute('data-region-id');

        // ถ้า option มี data-region-id ตรงกับภูมิภาคที่เลือก ให้แสดง option นั้น
        if (regionIdOfBranch === selectedRegionId) {
          option.style.display = '';
        } else {
          // ถ้าไม่ตรง ให้ซ่อนไว้
          option.style.display = 'none';
        }
      }

      // ทำให้ option แรก "เลือกสาขา" แสดงผลเสมอ
      if (branchOptions.length > 0) {
        branchOptions[0].style.display = '';
      }
    }
  </script>
  <!-- END: JAVASCRIPT for dynamic dropdown -->

</body>

</html>