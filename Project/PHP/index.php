<?php 
session_start(); // เริ่ม session สำหรับเช็คสมาชิก
include 'db.php'; // เชื่อมต่อฐานข้อมูล

// --- Fetch Regions ---
$sql_regions = "SELECT Region_Id, Region_name FROM region ORDER BY Region_Id ASC";
$result_regions = $conn->query($sql_regions);
$regions = [];
if ($result_regions->num_rows > 0) {
  while ($row = $result_regions->fetch_assoc()) {
    $regions[] = $row;
  }
}

// --- Fetch Provinces ---
$sql_provinces = "SELECT Province_Id, Province_name, Region_Id FROM province ORDER BY Province_name ASC";
$result_provinces = $conn->query($sql_provinces);
$provinces = [];
if ($result_provinces->num_rows > 0) {
  while ($row = $result_provinces->fetch_assoc()) {
    $provinces[] = $row;
  }
}

// ดึงวันที่จาก GET/POST
$checkin_date = $_GET['checkin_date'] ?? '';
$checkout_date = $_GET['checkout_date'] ?? '';

// ปิด connection
$conn->close();
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
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <header>
    <section class="logo">
      <a href="./nextpage.php">
        <img src="../src/images/4.png" width="50" height="50" alt="Dom Inn Logo" />
      </a>
    </section>
    <nav>
      <a href="./type.php">ประเภทห้องพัก</a>
      <a href="./branch.php">สาขาโรงแรมดอม อินน์</a>
      <a href="./details.php">รายละเอียดต่างๆ</a>
      <a href="#">การจองของฉัน</a>
      <a href="./summary.php">คะแนน</a>
    </nav>
    <nav>
      <a href="./member.php">สมัครสมาชิก</a>
      <a href="./login.php">เข้าสู่ระบบ</a>
    </nav>
  </header>

  <section class="booking-form">
    <select id="region" onchange="updateBranches()">
      <option disabled selected value>เลือกภูมิภาค</option>
      <?php foreach ($regions as $region): ?>
        <option value="<?= htmlspecialchars($region['Region_Id']) ?>">
          <?= htmlspecialchars($region['Region_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select id="branch">
      <option disabled selected value>เลือกสาขา</option>
      <?php foreach ($provinces as $province): ?>
        <option value="<?= htmlspecialchars($province['Province_Id']) ?>"
          data-region-id="<?= htmlspecialchars($province['Region_Id']) ?>"
          style="display:none;">
          <?= htmlspecialchars($province['Province_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <input id="start-date" name="checkin_date" type="text" placeholder="วันที่เช็คอิน" readonly value="<?= htmlspecialchars($checkin_date) ?>" onclick="openCalendar()" />
    <input id="end-date" name="checkout_date" type="text" placeholder="วันที่เช็คเอ้าท์" readonly value="<?= htmlspecialchars($checkout_date) ?>" onclick="openCalendar()" />

    <div id="rooms-container">
      <div class="room-input-group">
        <label for="num-rooms">จำนวนห้อง:</label>
        <input type="number" id="num-rooms" value="1" min="1" max="5" onchange="updateRoomsFromInput()">
      </div>
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
      <div class="guest-summary">
        <input id="guest-summary-input" type="text" readonly value="ผู้ใหญ่ 1, เด็ก 0 คน" />
      </div>
    </div>

    <button class="btn">จองเลย</button>
  </section>

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

  <script src="../JS/js/calendar.js"></script>

  <script>
    // Dynamic branch dropdown
    function updateBranches() {
      const regionSelect = document.getElementById('region');
      const branchSelect = document.getElementById('branch');
      const selectedRegionId = regionSelect.value;

      branchSelect.selectedIndex = 0;
      const branchOptions = branchSelect.getElementsByTagName('option');

      for (let i = 0; i < branchOptions.length; i++) {
        const option = branchOptions[i];
        const regionIdOfBranch = option.getAttribute('data-region-id');
        option.style.display = (regionIdOfBranch === selectedRegionId) ? '' : 'none';
      }

      if (branchOptions.length > 0) branchOptions[0].style.display = '';
    }

    // --- SweetAlert2: Booking alert ---
    const isMember = <?php echo isset($_SESSION['member_id']) ? 'true' : 'false'; ?>;
    const bookingButton = document.querySelector('.btn');

    bookingButton.addEventListener('click', function(e) {
      e.preventDefault();

      if (!isMember) {
        Swal.fire({
          icon: 'warning',
          title: 'ท่านยังไม่เป็นสมาชิก',
          text: 'โปรดสมัครสมาชิกของโรงแรม Dom-Inn ก่อนทำการจอง',
          showCancelButton: true,
          confirmButtonText: 'สมัครสมาชิก',
          cancelButtonText: 'ยกเลิก',
          allowOutsideClick: false
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = './member.php';
          }
        });
      } else {
        Swal.fire({
          icon: 'success',
          title: 'จองสำเร็จ',
          text: 'คุณสามารถทำการชำระเงินต่อได้',
          timer: 2000,
          showConfirmButton: false
        });
      }
    });
  </script>
</body>
</html>
