<?php
// --- START: ส่วนเชื่อมต่อฐานข้อมูลและดึงข้อมูลจากโค้ดแรก ---
include 'db.php'; // สมมติว่าไฟล์นี้ใช้เชื่อมต่อฐานข้อมูล
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$First_name = $_SESSION['First_name'] ?? '';
$Last_name = $_SESSION['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);

// ตั้งค่าการเชื่อมต่อให้รองรับภาษาไทย
if (isset($conn)) {
    $conn->set_charset("utf8");
}

// 1. ดึงข้อมูลภูมิภาค (Region)
$regions = [];
$sql_regions = "SELECT Region_Id, Region_name FROM region ORDER BY Region_Id ASC";
if ($result_regions = $conn->query($sql_regions)) {
    while ($row = $result_regions->fetch_assoc()) {
        $regions[] = $row;
    }
    $result_regions->free();
}

// 2. ดึงข้อมูลสาขา/จังหวัด (Province)
$provinces = [];
$sql_provinces = "SELECT Province_Id, Province_name, Region_Id FROM province ORDER BY Province_name ASC";
if ($result_provinces = $conn->query($sql_provinces)) {
    while ($row = $result_provinces->fetch_assoc()) {
        $provinces[] = $row;
    }
    $result_provinces->free();
}
// --- END: ส่วนเชื่อมต่อฐานข้อมูลและดึงข้อมูลจากโค้ดแรก ---


// --- START: ข้อมูลห้องพักจากโค้ดที่สอง ---
$hotel_name = "HOP INN AYUTTHAYA";

// --- ข้อมูลสำหรับห้องที่ 1 ---
$room1_data = [
    "name" => "ห้องมาตรฐาน เตียงใหญ่",
    "description" => "ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน 
    อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน(อายุต่ำกว่า 12 ปี)",
    "capacity" => "3 คน",
    "guests" => "2 ผู้ใหญ่, 1 เด็ก",
    "bed_type" => "1 เตียงใหญ่",
    "price" => "930.00",
    "images" => ["./src/images/1.jpg", "./src/images/6.avif"]
];

// --- ข้อมูลสำหรับห้องที่ 2 ---
$room2_data = [
    "name" => "ห้องมาตรฐาน เตียงคู่",
    "description" => "ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน 
    อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน(อายุต่ำกว่า 12 ปี)",
    "capacity" => "3 คน",
    "guests" => "2 ผู้ใหญ่, 1 เด็ก",
    "bed_type" => "2 เตียงเดี่ยว",
    "price" => "930.00",
    "images" => ["./src/images/2.jpg", "./src/images/6.avif"]
];
// --- END: ข้อมูลห้องพักจากโค้ดที่สอง ---
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>เลือกห้องพัก - <?= htmlspecialchars($hotel_name) ?></title>
    <link rel="icon" type="image/png" href="./src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <!-- CSS จากทั้งสองไฟล์ -->
    <link rel="stylesheet" href="./in.css" /> <!-- CSS สำหรับฟอร์มค้นหา -->
    <link rel="stylesheet" href="hotel_rooms.css" />
    <link rel="stylesheet" href="modal_style.css" />

    <!-- Style สำหรับโปรไฟล์ (จากโค้ดแรก) -->
    <style>
        .profile-link, .profile-link:visited {
            text-decoration: none; color: #ffffff; padding: 8px 12px;
            border-radius: 5px; transition: background-color 0.3s ease;
        }
        .profile-link:hover { background-color: rgba(255, 255, 255, 0.2); color: #ffffff; }
        .profile-link:active { color: #ffffff; }
    </style>
</head>
<body>

    <!-- Header จากโค้ดแรก -->
    <header>
        <section class="logo">
            <img src="./src/images/4.png" width="50" height="50" />
        </section>
        <nav>
            <a href="./type.php">ประเภทห้องพัก</a>
            <a href="#">สาขาโรงแรมดอม อินน์</a>
            <a href="./details.php">รายละเอียดต่างๆ</a>
            <a href="#">การจองของฉัน</a>
            <a href="./score.php">คะแนน</a>
        </nav>
        <?php if ($full_name && $full_name !== ' '): ?>
            <div class="user-display">
                <a href="profile.php" class="profile-link"><?= htmlspecialchars($full_name) ?></a>
            </div>
        <?php endif; ?>
    </header>

    <!-- ฟอร์มเลือกวันและจำนวนผู้เข้าพัก จากโค้ดแรก -->
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

        <input id="date-range" type="text" placeholder="วันที่เช็คอิน - วันที่เช็คเอ้าท์" readonly onclick="openCalendar()" />

        <div id="rooms-container">
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
            <button type="button" id="add-room-btn" onclick="addRoom()">+ เพิ่มห้อง</button>
            <div class="guest-summary">
                <input id="guest-summary-input" type="text" readonly value="ผู้ใหญ่ 1, เด็ก 0 คน" />
            </div>
        </div>

        <form action="./hotel_rooms.php" method="get">
            <input type="hidden" name="hotel_name" value="<?= htmlspecialchars($hotel_name) ?>">
            <input type="hidden" name="checkin_date" id="checkin_date" value="">
            <input type="hidden" name="adults" id="adults" value="1">
            <input type="hidden" name="children" id="children" value="0">
            <!-- <button type="submit" class="btn">ค้นหา</button> เปลี่ยนข้อความปุ่ม -->
        </form>
    </section>

    <!-- ส่วนแสดงรายการห้องพัก จากโค้ดที่สอง -->
    <main class="room-selection-container">
        <!-- Room Card 1 -->
        <div class="room-card">
            <div class="card-content">
                <div class="room-image"><img src="<?= htmlspecialchars($room1_data['images'][0]) ?>" alt="<?= htmlspecialchars($room1_data['name']) ?>"></div>
                <div class="room-info">
                    <div class="info-header">
                        <h2><?= htmlspecialchars($room1_data['name']) ?></h2>
                        <div class="room-price">฿ <?= htmlspecialchars($room1_data['price']) ?>*</div>
                    </div>
                    <div class="room-icons">
                        <div class="icon-group"><i class="fas fa-users"></i> <?= htmlspecialchars($room1_data['capacity']) ?></div>
                        <div class="icon-group"><i class="fas fa-user-friends"></i> <?= htmlspecialchars($room1_data['guests']) ?></div>
                        <div class="icon-group"><i class="fas fa-bed"></i> <?= htmlspecialchars($room1_data['bed_type']) ?></div>
                    </div>
                    <p class="room-description"><?= nl2br(htmlspecialchars($room1_data['description'])) ?></p>
                    <button class="details-btn" onclick='openRoomDetailsModal(<?= json_encode($room1_data) ?>)'>รายละเอียดห้อง</button>
                </div>
            </div>
            <div class="booking-action"><button class="btn-book">เข้าสู่ระบบและจอง</button></div>
        </div>

        <!-- Room Card 2 -->
        <div class="room-card">
            <div class="card-content">
                <div class="room-image"><img src="<?= htmlspecialchars($room2_data['images'][0]) ?>" alt="<?= htmlspecialchars($room2_data['name']) ?>"></div>
                <div class="room-info">
                    <div class="info-header">
                        <h2><?= htmlspecialchars($room2_data['name']) ?></h2>
                        <div class="room-price">฿ <?= htmlspecialchars($room2_data['price']) ?>*</div>
                    </div>
                    <div class="room-icons">
                        <div class="icon-group"><i class="fas fa-users"></i> <?= htmlspecialchars($room2_data['capacity']) ?></div>
                        <div class="icon-group"><i class="fas fa-user-friends"></i> <?= htmlspecialchars($room2_data['guests']) ?></div>
                        <div class="icon-group"><i class="fas fa-bed"></i> <?= htmlspecialchars($room2_data['bed_type']) ?></div>
                    </div>
                    <p class="room-description"><?= nl2br(htmlspecialchars($room2_data['description'])) ?></p>
                    <button class="details-btn" onclick='openRoomDetailsModal(<?= json_encode($room2_data) ?>)'>รายละเอียดห้อง</button>
                </div>
            </div>
            <div class="booking-action"><button class="btn-book">เข้าสู่ระบบและจอง</button></div>
        </div>
    </main>

    <!-- POPUP CALENDAR จากโค้ดแรก -->
    <div id="calendarOverlay" onclick="closeCalendar()"></div>
    <div id="calendarPopup">
        <span class="close-calendar" onclick="closeCalendar()">×</span>
        <div class="calendar-container">
            <div class="calendar-header">
                <span class="nav-btn" onclick="changeMonth(-1)">&#8249;</span>
                <div class="calendar-month"><h2 id="month-label">Loading...</h2></div>
                <span class="nav-btn" onclick="changeMonth(1)">&#8250;</span>
            </div>
            <div class="calendar-grid" id="calendar-days"></div>
            <div class="calendar-grid" id="calendar-dates"></div>
            <button class="btn" onclick="confirmDate()">ยืนยันวันเข้าพัก</button>
        </div>
    </div>

    <!-- MODAL จากโค้ดที่สอง -->
    <div class="modal-overlay" id="roomModal" style="display: none;">
        <div class="modal-container">
            <span class="modal-close-btn" onclick="closeModal()">&times;</span>
            <div class="modal-content">
                <div class="modal-left">
                    <h1 class="room-title" id="modal-title"></h1>
                    <p class="room-description" id="modal-description"></p>
                    <div class="room-features" id="modal-features"></div>
                    <div class="gallery-container" id="modal-gallery"></div>
                    <div class="gallery-dots" id="modal-dots"></div>
                    <p><a href="#" class="amenities-link">สิ่งอำนวยความสะดวกในห้องพัก</a></p>
                </div>
                <div class="modal-right">
                    <div class="booking-box">
                        <h3>ราคาที่คุณเลือก</h3>
                        <p class="rate-info">Best Flexible Rate <a href="#">รายละเอียด</a></p>
                        <p class="rate-price" id="modal-price"></p>
                        <hr>
                        <div class="booking-total" id="modal-total"></div>
                        <button class="booking-button">เข้าสู่ระบบและจอง</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT จากทั้งสองไฟล์ -->
    <script src="./test.js"></script> <!-- Script สำหรับฟอร์มค้นหาและปฏิทิน -->
    <script src="modal_script.js"></script>

    <!-- Inline SCRIPT สำหรับ Dropdown (จากโค้ดแรก) -->
    <script>
        function updateBranches() {
            const regionSelect = document.getElementById('region');
            const branchSelect = document.getElementById('branch');
            const selectedRegionId = regionSelect.value;
            branchSelect.selectedIndex = 0;
            const branchOptions = branchSelect.getElementsByTagName('option');
            for (let i = 0; i < branchOptions.length; i++) {
                const option = branchOptions[i];
                const regionIdOfBranch = option.getAttribute('data-region-id');
                if (regionIdOfBranch === selectedRegionId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
            if (branchOptions.length > 0) {
                branchOptions[0].style.display = '';
            }
        }
    </script>
</body>
</html>