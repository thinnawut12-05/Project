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

// รับค่าผู้เข้าพักและวันที่ จาก GET (จาก home.php)
$checkin_date = $_GET['checkin_date'] ?? '';
$checkout_date = $_GET['checkout_date'] ?? '';

// รับค่าจำนวนห้อง ผู้ใหญ่ และเด็ก (รวมทั้งหมด) จาก home.php
$num_rooms = isset($_GET['num_rooms']) ? intval($_GET['num_rooms']) : 1;
$total_adults = isset($_GET['total_adults']) ? intval($_GET['total_adults']) : 1;
$total_children = isset($_GET['total_children']) ? intval($_GET['total_children']) : 0;

// กำหนดค่าเริ่มต้นสำหรับแต่ละห้องในฟอร์มของ hotel_rooms.php
// สมมติว่าห้องแรกมีผู้ใหญ่ = total_adults และเด็ก = total_children
// หรือคุณอาจปรับ logic ตรงนี้ได้หากต้องการกระจายผู้เข้าพักไปแต่ละห้อง
$adults_per_room_initial = [$total_adults];
$children_per_room_initial = [$total_children];
// หาก num_rooms มากกว่า 1 และคุณต้องการให้ห้องที่ 2 เป็นต้นไปมีผู้เข้าพักเริ่มต้นที่ 1 ผู้ใหญ่ 0 เด็ก
for ($i = 1; $i < $num_rooms; $i++) {
    $adults_per_room_initial[] = 1;
    $children_per_room_initial[] = 0;
}


$rooms = [];
if ($province_id) {
    $sql_rooms = "SELECT Room_Id, Price, Room_number, Room_type_Id, Number_of_people_staying
                  FROM room
                  WHERE Province_Id = ?
                  AND (Room_type_Id = 1 OR Room_type_Id = 2)
                  ORDER BY Room_type_Id ASC";
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
            $row['images'] = ["../src/images/1.jpg", "../src/images/6.avif"];
        } elseif ($row['Room_type_Id'] == 2) {
            $row['name'] = "ห้องมาตรฐาน เตียงคู่";
            $row['description'] = "ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน 
อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน(อายุต่ำกว่า 12 ปี)";
            $row['capacity'] = $row['Number_of_people_staying'] . " คน";
            $row['guests'] = "2 ผู้ใหญ่, 1 เด็ก";
            $row['bed_type'] = "2 เตียงเดี่ยว";
            $row['images'] = ["../src/images/2.jpg", "../src/images/6.avif"];
        }
        $row['price'] = number_format($row['Price'], 2);
        $rooms[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="th">
<style>
    .btn-book {
        display: inline-block;
        padding: 8px 16px;
        background-color: #f05a28;
        color: #fff;
        border-radius: 5px;
        text-decoration: none;
        text-align: center;
        transition: background-color 0.3s;
        border: none;
        cursor: pointer;
    }

    .btn-book:hover {
        background-color: #d94a1f;
    }

    /* เพิ่มสไตล์สำหรับวันที่ที่ถูกเลือกในปฏิทิน */
    .calendar-date.selected {
        background-color: #f05a28;
        /* สีส้ม */
        color: white;
    }

    .calendar-date.past-date {
        color: #cccccc;
        /* สีเทาอ่อนสำหรับวันที่ในอดีต */
        cursor: not-allowed;
    }

    .calendar-date.blank {
        visibility: hidden;
        /* ซ่อนวันที่ว่าง */
    }
</style>

<head>
    <meta charset="UTF-8" />
    <title>เลือกห้องพัก - <?= htmlspecialchars($hotel_name) ?></title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../CSS/css/ino.css" />
    <link rel="stylesheet" href="../CSS/css/hotel_rooms.css" />
    <link rel="stylesheet" href="../CSS/css/modal_style.css" />
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
            color: #fff;
        }

        .profile-link:active {
            color: #fff;
        }
    </style>
</head>

<body>
    <header>
        <section class="logo">
            <img src="../src/images/4.png" width="50" height="50" />
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

    <section class="booking-form">
        <form action="" method="get">
            <select id="region" onchange="updateBranches()">
                <option disabled selected value>เลือกภูมิภาค</option>
                <?php
                $selected_region_id = '';
                if ($province_id) {
                    $selected_province_obj = array_filter($provinces, fn($p) => $p['Province_Id'] == $province_id);
                    if (!empty($selected_province_obj)) {
                        $selected_region_id = array_values($selected_province_obj)[0]['Region_Id'];
                    }
                }
                foreach ($regions as $region): ?>
                    <option value="<?= htmlspecialchars($region['Region_Id']) ?>"
                        <?= ($selected_region_id == $region['Region_Id']) ? 'selected' : '' ?>>
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

            <!-- Input text field สำหรับแสดงวันที่ -->
            <input id="start-date" type="text" placeholder="วันที่เช็คอิน" readonly value="<?= htmlspecialchars($checkin_date) ?>" onclick="openCalendar()" />
            <input id="end-date" type="text" placeholder="วันที่เช็คเอ้าท์" readonly value="<?= htmlspecialchars($checkout_date) ?>" onclick="openCalendar()" />

            <div id="rooms-container">
                <div class="room-input-group">
                    <label for="num-rooms">จำนวนห้อง:</label>
                    <input type="number" id="num-rooms" value="<?= htmlspecialchars($num_rooms) ?>" min="1" max="5" onchange="updateRoomsFromInput()">
                </div>
                <?php
                // Loop เพื่อสร้าง div ของแต่ละห้องตามจำนวน num_rooms
                for ($r = 1; $r <= $num_rooms; $r++):
                    $current_adults = $adults_per_room_initial[$r - 1] ?? 1;
                    $current_children = $children_per_room_initial[$r - 1] ?? 0;
                ?>
                    <div class="room" data-room="<?= $r ?>">
                        <h4>ห้องที่ <?= $r ?></h4>
                        <div class="guest-group">
                            <span>ผู้ใหญ่</span>
                            <button type="button" onclick="changeGuest(this, 'adult', -1)">–</button>
                            <span class="adult-count"><?= htmlspecialchars($current_adults) ?></span>
                            <button type="button" onclick="changeGuest(this, 'adult', 1)">+</button>
                        </div>
                        <div class="guest-group">
                            <span>เด็ก</span>
                            <button type="button" onclick="changeGuest(this, 'child', -1)">–</button>
                            <span class="child-count"><?= htmlspecialchars($current_children) ?></span>
                            <button type="button" onclick="changeGuest(this, 'child', 1)">+</button>
                        </div>
                        <div class="child-age-container" style="display:none; margin-top:8px;">
                            <label>อายุของเด็กแต่ละคน (ปี):</label>
                            <div class="child-age-list"></div>
                        </div>
                    </div>
                <?php endfor; ?>

                <div class="guest-summary">
                    <input id="guest-summary-input" type="text" readonly value="ผู้ใหญ่ <?= htmlspecialchars($total_adults) ?>, เด็ก <?= htmlspecialchars($total_children) ?> คน" />
                </div>
            </div>
            <!-- Hidden inputs สำหรับส่งค่าไปยังตัวเอง (เพื่อใช้ในการค้นหา) -->
            <input type="hidden" name="checkin_date" id="checkin_date_submit" value="<?= htmlspecialchars($checkin_date) ?>">
            <input type="hidden" name="checkout_date" name="checkout_date_submit" id="checkout_date_submit" value="<?= htmlspecialchars($checkout_date) ?>">
            <input type="hidden" name="total_adults" id="total_adults_submit" value="<?= htmlspecialchars($total_adults) ?>">
            <input type="hidden" name="total_children" id="total_children_submit" value="<?= htmlspecialchars($total_children) ?>">
            <input type="hidden" name="num_rooms" id="num_rooms_submit" value="<?= htmlspecialchars($num_rooms) ?>">

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
                    <div class="booking-action">
                        <form action="payment.php" method="get" class="booking-form-item">
                            <input type="hidden" name="room_id" value="<?= $room['Room_Id'] ?>">
                            <input type="hidden" name="price" value="<?= $room['Price'] ?>">
                            <input type="hidden" name="checkin_date" value="<?= htmlspecialchars($checkin_date) ?>">
                            <input type="hidden" name="checkout_date" value="<?= htmlspecialchars($checkout_date) ?>">
                            <input type="hidden" name="num_rooms" value="<?= htmlspecialchars($num_rooms) ?>">
                            <input type="hidden" name="total_adults" value="<?= htmlspecialchars($total_adults) ?>">
                            <input type="hidden" name="total_children" value="<?= htmlspecialchars($total_children) ?>">
                            <input type="hidden" name="province_id" value="<?= htmlspecialchars($province_id) ?>">
                            <button type="submit" class="btn-book">จอง</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

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

    <!-- Modal สำหรับรายละเอียดห้องพัก -->
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
                        <div class="booking-action">
                            <form action="payment.php" method="get" class="booking-form-item">
                                <input type="hidden" name="room_id" value="<?= $room['Room_Id'] ?>">
                                <input type="hidden" name="price" value="<?= $room['Price'] ?>">
                                <input type="hidden" name="checkin_date" value="<?= htmlspecialchars($checkin_date) ?>">
                                <input type="hidden" name="checkout_date" value="<?= htmlspecialchars($checkout_date) ?>">
                                <input type="hidden" name="num_rooms" value="<?= htmlspecialchars($num_rooms) ?>">
                                <input type="hidden" name="total_adults" value="<?= htmlspecialchars($total_adults) ?>">
                                <input type="hidden" name="total_children" value="<?= htmlspecialchars($total_children) ?>">
                                <input type="hidden" name="province_id" value="<?= htmlspecialchars($province_id) ?>">
                                <button type="submit" class="btn-book">จอง</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../JS/js/test.js"></script>
    <script src="../JS/js/modal_script.js"></script>
    <script src="../JS/js/calendar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // เรียก updateBranches() ครั้งแรกเมื่อ DOM โหลดเสร็จ เพื่อตั้งค่าเริ่มต้น
            // หากต้องการให้ dropdown สาขาถูกกรองตามภูมิภาคที่ถูกเลือกไว้ก่อนหน้า (ถ้ามี)
            updateBranches();

            // เมื่อเลือกภูมิภาค
            document.getElementById('region').addEventListener('change', function() {
                updateBranches();
            });

            // เมื่อเลือกสาขา
            document.getElementById('branch').addEventListener('change', function() {
                const selectedProvinceId = this.value;
                document.getElementById('province_id_submit').value = selectedProvinceId; // อัปเดต hidden input สำหรับ form ค้นหา
            });

            // ตรวจสอบว่ามี province_id ใน URL หรือไม่ เพื่อตั้งค่าเริ่มต้น
            const urlParams = new URLSearchParams(window.location.search);
            const initialProvinceId = urlParams.get('province_id');
            if (initialProvinceId) {
                const branchSelect = document.getElementById('branch');
                branchSelect.value = initialProvinceId;
                updateBranches(); // เรียกอีกครั้งเพื่อแสดงสาขาที่เลือก
                document.getElementById('province_id_submit').value = initialProvinceId; // อัปเดต hidden input

                const selectedBranchOption = branchSelect.querySelector(`option[value="${initialProvinceId}"]`);
                if (selectedBranchOption) {
                    const regionId = selectedBranchOption.getAttribute('data-region-id');
                    if (regionId) {
                        document.getElementById('region').value = regionId;
                    }
                }
            } else {
                // ถ้าไม่มี province_id ใน URL ให้ตั้งค่า province_id_submit เป็นค่าว่างเริ่มต้น
                document.getElementById('province_id_submit').value = '';
            }

            // เรียก updateGuestSummary() อีกครั้งเพื่อให้แน่ใจว่า hidden inputs ได้รับค่าที่ถูกต้องเมื่อ DOM โหลด
            updateGuestSummary();
        });

        function updateBranches() {
            const regionSelect = document.getElementById('region');
            const branchSelect = document.getElementById('branch');
            const selectedRegionId = regionSelect.value;

            const branchOptions = branchSelect.getElementsByTagName('option');

            for (let i = 0; i < branchOptions.length; i++) {
                const option = branchOptions[i];
                const regionIdOfBranch = option.getAttribute('data-region-id');

                if (option.value === "" || regionIdOfBranch === selectedRegionId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }

            const currentSelectedBranch = branchSelect.value;
            const currentSelectedBranchOption = branchSelect.querySelector(`option[value="${currentSelectedBranch}"][data-region-id="${selectedRegionId}"]`);
            if (!currentSelectedBranchOption && currentSelectedBranch !== "") {
                branchSelect.value = "";
                document.getElementById('province_id_submit').value = '';
            } else if (currentSelectedBranch === "") {
                document.getElementById('province_id_submit').value = '';
            } else {
                document.getElementById('province_id_submit').value = currentSelectedBranch;
            }
        }
        //อัพเทดจำนวนคนเข้าพัก
        //ฟังก์ชันนี้จะอัปเดต hidden input ในฟอร์มจองทุกอันตามค่าที่เลือก
        function updateGuestSummary() {
            let totalAdults = 0;
            let totalChildren = 0;
            // ดึงจำนวนห้องปัจจุบันจาก input field ที่ผู้ใช้เลือก
            const currentNumRooms = parseInt(document.getElementById('num-rooms').value);

            document.querySelectorAll('.room').forEach(room => {
                totalAdults += parseInt(room.querySelector('.adult-count').textContent);
                totalChildren += parseInt(room.querySelector('.child-count').textContent);
            });

            const summaryText = `ผู้ใหญ่ ${totalAdults}, เด็ก ${totalChildren} คน`;
            const summaryInput = document.getElementById('guest-summary-input');
            if (summaryInput) {
                summaryInput.value = summaryText;
            }

            // Update hidden inputs สำหรับฟอร์มค้นหาด้านบน (ที่จะรีโหลดหน้า hotel_rooms.php)
            const totalAdultsSubmitInput = document.getElementById('total_adults_submit');
            const totalChildrenSubmitInput = document.getElementById('total_children_submit');
            const numRoomsSubmitInput = document.getElementById('num_rooms_submit');

            if (totalAdultsSubmitInput) totalAdultsSubmitInput.value = totalAdults;
            if (totalChildrenSubmitInput) totalChildrenSubmitInput.value = totalChildren;
            if (numRoomsSubmitInput) numRoomsSubmitInput.value = currentNumRooms;

            // ดึงค่าวันที่เช็คอิน/เช็คเอาท์ปัจจุบันจาก input fields
            const checkinDateVal = document.getElementById('start-date').value;
            const checkoutDateVal = document.getElementById('end-date').value;

            // อัปเดต hidden inputs สำหรับฟอร์ม "จอง" ของแต่ละห้อง (ทั้งใน room-card และใน modal)
            document.querySelectorAll('form.booking-form-item').forEach(form => {
                // อัปเดตจำนวนห้อง, ผู้ใหญ่, เด็ก
                if (form.querySelector('input[name="num_rooms"]')) form.querySelector('input[name="num_rooms"]').value = currentNumRooms;
                if (form.querySelector('input[name="total_adults"]')) form.querySelector('input[name="total_adults"]').value = totalAdults;
                if (form.querySelector('input[name="total_children"]')) form.querySelector('input[name="total_children"]').value = totalChildren;

                // อัปเดตวันที่เช็คอิน/เช็คเอาท์
                if (form.querySelector('input[name="checkin_date"]')) form.querySelector('input[name="checkin_date"]').value = checkinDateVal;
                if (form.querySelector('input[name="checkout_date"]')) form.querySelector('input[name="checkout_date"]').value = checkoutDateVal;
            });
        }
    </script>
</body>

</html>