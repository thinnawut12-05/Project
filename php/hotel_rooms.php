<?php
include 'db.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$First_name = $_SESSION['First_name'] ?? '';
$Last_name = $_SESSION['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);

if (isset($conn)) {
    $conn->set_charset("utf8");
}

// ดึงข้อมูลภูมิภาค
$regions = [];
$sql_regions = "SELECT Region_Id, Region_name FROM region ORDER BY Region_Id ASC";
if ($result_regions = $conn->query($sql_regions)) {
    while ($row = $result_regions->fetch_assoc()) {
        $regions[] = $row;
    }
    $result_regions->free();
}

// ดึงข้อมูลจังหวัด/สาขา
$provinces = [];
$sql_provinces = "SELECT Province_Id, Province_name, Region_Id FROM province ORDER BY Province_name ASC";
if ($result_provinces = $conn->query($sql_provinces)) {
    while ($row = $result_provinces->fetch_assoc()) {
        $provinces[] = $row;
    }
    $result_provinces->free();
}

$hotel_name = "HOP INN AYUTTHAYA";

// รับ province_id จาก GET
$province_id = isset($_GET['province_id']) ? intval($_GET['province_id']) : null;

// รับค่าผู้เข้าพักและวันที่ จาก GET
$checkin_date = $_GET['checkin_date'] ?? '';
$adults = $_GET['adults'] ?? 1;
$children = $_GET['children'] ?? 0;

$rooms = [];
if ($province_id) {
    // ดึงเฉพาะ 2 room_type: 1 (เตียงใหญ่) และ 2 (เตียงคู่) ของสาขาที่เลือก
    $sql_rooms = "SELECT * FROM room WHERE Province_Id = ? AND (Room_type_Id = 1 OR Room_type_Id = 2) ORDER BY Room_type_Id ASC";
    $stmt = $conn->prepare($sql_rooms);
    $stmt->bind_param('i', $province_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row['Room_type_Id'] == 1) {
            $row['name'] = "ห้องมาตรฐาน เตียงใหญ่";
            $row['description'] = "ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน 
อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน(อายุต่ำกว่า 12 ปี)";
            $row['capacity'] = $row['Number_of_people_staying'] . " คน";
            $row['guests'] = "2 ผู้ใหญ่, 1 เด็ก";
            $row['bed_type'] = "1 เตียงใหญ่";
            $row['images'] = ["./src/images/1.jpg", "./src/images/6.avif"];
        } elseif ($row['Room_type_Id'] == 2) {
            $row['name'] = "ห้องมาตรฐาน เตียงคู่";
            $row['description'] = "ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน 
อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน(อายุต่ำกว่า 12 ปี)";
            $row['capacity'] = $row['Number_of_people_staying'] . " คน";
            $row['guests'] = "2 ผู้ใหญ่, 1 เด็ก";
            $row['bed_type'] = "2 เตียงเดี่ยว";
            $row['images'] = ["./src/images/2.jpg", "./src/images/6.avif"];
        }
        $row['price'] = number_format($row['Price'], 2);
        $rooms[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <title>เลือกห้องพัก - <?= htmlspecialchars($hotel_name) ?></title>
    <link rel="icon" type="image/png" href="./src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="./in.css" />
    <link rel="stylesheet" href="hotel_rooms.css" />
    <link rel="stylesheet" href="modal_style.css" />
    <style>
        .profile-link, .profile-link:visited {
            text-decoration: none;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .profile-link:hover { background-color: rgba(255,255,255,0.2); color: #fff; }
        .profile-link:active { color: #fff; }
    </style>
</head>

<body>
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

    <section class="booking-form">
        <form action="" method="get">
            <select id="region" onchange="updateBranches()">
                <option disabled selected value>เลือกภูมิภาค</option>
                <?php foreach ($regions as $region): ?>
                <option value="<?= htmlspecialchars($region['Region_Id']) ?>">
                    <?= htmlspecialchars($region['Region_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select id="branch" name="province_id">
                <option disabled selected value>เลือกสาขา</option>
                <?php foreach ($provinces as $province): ?>
                <option value="<?= htmlspecialchars($province['Province_Id']) ?>"
                    data-region-id="<?= htmlspecialchars($province['Region_Id']) ?>"
                    style="display:none;" <?= ($province_id == $province['Province_Id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($province['Province_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- ส่วนของวันที่และผู้เข้าพัก -->
            <input id="date-range" name="checkin_date" type="text" placeholder="วันที่เช็คอิน - วันที่เช็คเอ้าท์" readonly value="<?= htmlspecialchars($checkin_date) ?>" onclick="openCalendar()" />

            <div id="rooms-container">
                <div class="room" data-room="1">
                    <h4>ห้องที่ 1</h4>
                    <div class="guest-group">
                        <span>ผู้ใหญ่</span>
                        <button type="button" onclick="changeGuest(this, 'adult', -1)">–</button>
                        <span class="adult-count"><?= htmlspecialchars($adults) ?></span>
                        <button type="button" onclick="changeGuest(this, 'adult', 1)">+</button>
                    </div>
                    <div class="guest-group">
                        <span>เด็ก</span>
                        <button type="button" onclick="changeGuest(this, 'child', -1)">–</button>
                        <span class="child-count"><?= htmlspecialchars($children) ?></span>
                        <button type="button" onclick="changeGuest(this, 'child', 1)">+</button>
                    </div>
                    <div class="child-age-container" style="display:none; margin-top:8px;">
                        <label>อายุของเด็กแต่ละคน (ปี):</label>
                        <div class="child-age-list"></div>
                    </div>
                </div>
                <div class="room-input-group">
                    <label for="num-rooms">จำนวนห้อง:</label>
                    <input type="number" id="num-rooms" value="1" min="1" max="5" onchange="updateRoomsFromInput()">
                </div>
                <div class="guest-summary">
                    <input id="guest-summary-input" type="text" readonly value="ผู้ใหญ่ <?= htmlspecialchars($adults) ?>, เด็ก <?= htmlspecialchars($children) ?> คน" />
                </div>
            </div>
            <input type="hidden" name="adults" id="adults" value="<?= htmlspecialchars($adults) ?>">
            <input type="hidden" name="children" id="children" value="<?= htmlspecialchars($children) ?>">
            <button type="submit" class="btn">ค้นหาห้องพัก</button>
        </form>
    </section>

    <main class="room-selection-container">
        <?php if (!$province_id): ?>
            <div style="color:#888;text-align:center;padding:2rem;font-size:1.2rem;">"กรุณาเลือกสาขาที่ท่านจะเข้าพักก่อน!!"</div>
        <?php elseif (count($rooms) === 0): ?>
            <div style="color:#888;text-align:center;padding:2rem;font-size:1.2rem;">ไม่พบข้อมูลห้องพักสำหรับสาขานี้</div>
        <?php else: ?>
            <?php foreach ($rooms as $room): ?>
            <div class="room-card">
                <div class="card-content">
                    <div class="room-image"><img src="<?= htmlspecialchars($room['images'][0]) ?>" alt="<?= htmlspecialchars($room['name']) ?>"></div>
                    <div class="room-info">
                        <div class="info-header">
                            <h2><?= htmlspecialchars($room['name']) ?></h2>
                            <div class="room-price">฿ <?= htmlspecialchars($room['price']) ?>*</div>
                        </div>
                        <div class="room-icons">
                            <div class="icon-group"><i class="fas fa-users"></i> <?= htmlspecialchars($room['capacity']) ?></div>
                            <div class="icon-group"><i class="fas fa-user-friends"></i> <?= htmlspecialchars($room['guests']) ?></div>
                            <div class="icon-group"><i class="fas fa-bed"></i> <?= htmlspecialchars($room['bed_type']) ?></div>
                        </div>
                        <p class="room-description"><?= nl2br(htmlspecialchars($room['description'])) ?></p>
                        <button class="details-btn" onclick='openRoomDetailsModal(<?= json_encode($room) ?>)'>รายละเอียดห้อง</button>
                    </div>
                </div>
                <div class="booking-action"><button class="btn-book">จอง</button></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- ปฏิทินและ Modal (เหมือนเดิม) -->
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
                        <button class="booking-button">จอง</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="./test.js"></script>
    <script src="modal_script.js"></script>
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