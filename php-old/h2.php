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

$hotel_name = "Dom Inn"; // สามารถเปลี่ยนชื่อนี้ตามต้องการ หรือดึงจากฐานข้อมูล

// รับ province_id จาก GET
$province_id = isset($_GET['province_id']) ? intval($_GET['province_id']) : null;

// รับค่าผู้เข้าพักและวันที่ จาก GET (จาก home.php หรือการค้นหาตัวเอง)
// ใช้ _form เพื่อแยกจากชื่อคอลัมน์ใน DB (หากต้องการ)
$checkin_date = $_GET['checkin_date'] ?? '';
$checkout_date = $_GET['checkout_date'] ?? '';

$num_rooms = isset($_GET['num_rooms_form']) ? intval($_GET['num_rooms_form']) : 1;
$total_adults = isset($_GET['total_adults_form']) ? intval($_GET['total_adults_form']) : 1;
$total_children = isset($_GET['total_children_form']) ? intval($_GET['total_children_form']) : 0;



// *** เพิ่ม: ดึงจำนวนห้องว่างสูงสุดสำหรับสาขาที่เลือก ***
$total_available_rooms_for_current_province = 1; // Default min value
if ($province_id) {
    $sql_count_rooms = "SELECT COUNT(*) AS total_count FROM room WHERE Province_Id = ? AND Status = 'AVL'";
    $stmt_count = $conn->prepare($sql_count_rooms);
    if ($stmt_count) {
        $stmt_count->bind_param('i', $province_id);
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $row_count = $result_count->fetch_assoc();
        if ($row_count) {
            $total_available_rooms_for_current_province = max(1, $row_count['total_count']); // Ensure at least 1
        }
        $stmt_count->close();
    } else {
        error_log("Failed to prepare statement for room count: " . $conn->error);
    }
}
// ตรวจสอบให้แน่ใจว่า num_rooms ไม่เกินจำนวนห้องว่างจริง
$num_rooms = min($num_rooms, $total_available_rooms_for_current_province);

// กำหนดค่าเริ่มต้นสำหรับแต่ละห้องในฟอร์มของ hotel_rooms.php
$adults_per_room_initial = [];
$children_per_room_initial = [];

$remaining_adults = $total_adults;
$remaining_children = $total_children;

for ($i = 0; $i < $num_rooms; $i++) {
    // ผู้ใหญ่: อย่างน้อย 1 คนต่อห้อง, สูงสุด 2 คนต่อห้อง
    $adults_in_this_room = 1; // Default 1 adult
    if ($remaining_adults >= 2) {
        $adults_in_this_room = 2;
        $remaining_adults -= 2;
    } elseif ($remaining_adults == 1) {
        $adults_in_this_room = 1;
        $remaining_adults -= 1;
    }
    $adults_per_room_initial[] = $adults_in_this_room;

    // เด็ก: สูงสุด 1 คนต่อห้อง
    $children_in_this_room = 0;
    if ($remaining_children > 0) {
        $children_in_this_room = 1;
        $remaining_children -= 1;
    }
    $children_per_room_initial[] = $children_in_this_room;
}

// ถ้ายังมีผู้ใหญ่/เด็กเหลืออยู่ ให้กระจายไปยังห้องที่ยังไม่เต็ม
if ($remaining_adults > 0 || $remaining_children > 0) {
    for ($i = 0; $i < $num_rooms; $i++) {
        // เพิ่มผู้ใหญ่
        if ($adults_per_room_initial[$i] < 2 && $remaining_adults > 0) {
            $can_add_adults = 2 - $adults_per_room_initial[$i];
            $add_adults = min($can_add_adults, $remaining_adults);
            $adults_per_room_initial[$i] += $add_adults;
            $remaining_adults -= $add_adults;
        }
        // เพิ่มเด็ก
        if ($children_per_room_initial[$i] < 1 && $remaining_children > 0) {
            $can_add_children = 1 - $children_per_room_initial[$i];
            $add_children = min($can_add_children, $remaining_children);
            $children_per_room_initial[$i] += $add_children;
            $remaining_children -= $add_children;
        }
    }
}


// ดึงเฉพาะห้องที่ Status = 'AVL' เท่านั้น
$rooms = [];
if ($province_id) {
    // *** แก้ไข: ลบ rt.Room_type_description ออกจาก SELECT statement ***
    $sql_rooms = "SELECT Room_Id, Price, r.Room_type_Id, Number_of_people_staying, rt.Room_type_name
                  FROM room r
                  JOIN room_type rt ON r.Room_type_Id = rt.Room_type_Id
                  WHERE Province_Id = ?
                  AND Status = 'AVL'
                  ORDER BY r.Room_type_Id ASC";
    $stmt = $conn->prepare($sql_rooms);
    $stmt->bind_param('i', $province_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['name'] = $row['Room_type_name'];
        // *** แก้ไข: กลับไปใช้ hardcoded description ตาม Room_type_Id ***
        if ($row['Room_type_Id'] == 1) { // ห้องมาตรฐาน เตียงใหญ่
            $row['description'] = "ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน 
อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน(อายุต่ำกว่า 12 ปี)";
            $row['capacity'] = "3 คน"; // ผู้ใหญ่ 2, เด็ก 1
            $row['guests'] = "2 ผู้ใหญ่, 1 เด็ก";
            $row['bed_type'] = "1 เตียงใหญ่";
            $row['images'] = ["../src/images/1.jpg", "../src/images/6.avif", "../src/images/59.jpg"];
        } elseif ($row['Room_type_Id'] == 2) { // ห้องมาตรฐาน เตียงคู่
            $row['description'] = "ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน 
อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน(อายุต่ำกว่า 12 ปี)";
            $row['capacity'] = "3 คน"; // ผู้ใหญ่ 2, เด็ก 1
            $row['guests'] = "2 ผู้ใหญ่, 1 เด็ก";
            $row['bed_type'] = "2 เตียงเดี่ยว";
            $row['images'] = ["../src/images/2.jpg", "../src/images/6.avif", "../src/images/59.jpg"];
        } else { // ประเภทอื่น ๆ
            $row['description'] = "รายละเอียดห้องพัก"; // หรือดึงจากตาราง room_type หากมี column เพิ่มในอนาคต
            $row['capacity'] = $row['Number_of_people_staying'] . " คน";
            $row['guests'] = "ไม่ระบุ";
            $row['bed_type'] = "ไม่ระบุ";
            $row['images'] = ["../src/images/default.jpg"];
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
    /* CSS Styles (DO NOT MODIFY - unless specifically instructed for specific elements) */
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

    /* Styles for the calendar */
    .calendar-date.selected {
        background-color: #f05a28;
        color: white;
    }

    .calendar-date.past-date {
        color: #cccccc;
        cursor: not-allowed;
    }

    .calendar-date.blank {
        visibility: hidden;
    }
    .calendar-day-name { /* New CSS for day names */
        font-weight: bold;
        text-align: center;
        padding: 8px 0;
        color: #555;
    }

    /* --- New/Adjusted CSS for Room Input Layout --- */
    /* Container for "จำนวนห้อง" and individual room selectors */
    #rooms-container {
        display: flex;
        flex-wrap: wrap; /* Allows items to wrap to the next line */
        gap: 15px; /* Space between room cards */
        justify-content: flex-start;
        margin-top: 20px;
        padding: 15px; /* Add some padding to the container */
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        background-color: #fcfcfc;
    }

    /* Styling for the "จำนวนห้อง" input group */
    .room-input-group {
        flex: 0 0 100%; /* Takes full width */
        margin-bottom: 5px; /* Adjust spacing */
        display: flex;
        align-items: center;
        justify-content: flex-start;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .room-input-group label {
        margin-right: 15px;
        font-weight: bold;
        color: #333;
        font-size: 1.1em;
    }

    .room-input-group input[type="number"] {
        width: 80px; /* Wider width for the number input */
        padding: 8px 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1.1em;
        text-align: center;
        -moz-appearance: textfield; /* Hide spin buttons for Firefox */
    }
    .room-input-group input[type="number"]::-webkit-outer-spin-button,
    .room-input-group input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none; /* Hide spin buttons for Chrome/Safari */
        margin: 0;
    }


    /* Styling for individual room selection boxes */
    .room {
        flex: 0 0 calc(50% - 7.5px); /* Two columns, considering half of gap on each side (15px / 2 = 7.5px) */
        max-width: calc(50% - 7.5px);
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        background-color: #fff;
        box-sizing: border-box; /* Include padding/border in width */
        display: flex;
        flex-direction: column;
        align-items: flex-start; /* Align content to the left */
    }

    .room-header {
        width: 100%;
        border-bottom: 1px solid #eee;
        padding-bottom: 8px;
        margin-bottom: 15px;
    }
    .room-header h4 {
        margin-top: 0;
        margin-bottom: 0;
        color: #007bff;
        font-size: 1.2em;
        text-align: left;
    }

    .guest-group {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        font-size: 1em;
        width: 100%; /* Ensure guest group takes full width within room */
    }

    .guest-group span:first-child { /* Label like "ผู้ใหญ่", "เด็ก" */
        min-width: 70px; /* Align labels */
        text-align: left;
        margin-right: 10px;
        font-weight: normal;
        color: #555;
    }

    .guest-group button {
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px; /* Larger buttons */
        height: 30px;
        font-size: 1.2em; /* Larger button text */
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: background-color 0.2s;
        flex-shrink: 0; /* Prevent button from shrinking */
    }

    .guest-group button:hover {
        background-color: #0056b3;
    }

    .guest-group .adult-count,
    .guest-group .child-count {
        width: 40px; /* Wider count display */
        text-align: center;
        margin: 0 10px; /* More space around count */
        font-weight: bold;
        border: 1px solid #ddd;
        padding: 6px 0;
        border-radius: 4px;
        background-color: #fff;
        flex-shrink: 0; /* Prevent count from shrinking */
    }

    .child-age-container {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px dashed #eee;
        width: 100%; /* Take full width */
    }
    .child-age-container label {
        display: block;
        margin-bottom: 8px;
        font-size: 0.95em;
        font-weight: bold;
        color: #555;
    }
    .child-age-list select {
        width: calc(100% - 0px); /* Adjust width to fit */
        padding: 8px;
        margin-bottom: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1em;
        box-sizing: border-box; /* Include padding in width */
    }
    .guest-summary {
        flex: 0 0 100%; /* Summary takes full width */
        margin-top: 25px; /* More space above summary */
        padding: 18px;
        background-color: #e9ecef;
        border-radius: 8px;
        text-align: center;
        font-weight: bold;
        color: #333;
        box-sizing: border-box;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .guest-summary input {
         width: 100%;
         border: none;
         background: transparent;
         text-align: center;
         font-weight: bold;
         font-size: 1.2em; /* Larger font for summary */
         color: #333;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .room {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }
</style>

<head>
    <meta charset="UTF-8" />
    <title>เลือกห้องพัก</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../CSS/css/ino.css" />
    <link rel="stylesheet" href="../CSS/css/hotel_rooms.css" />
    <link rel="stylesheet" href="../CSS/css/modal_style.css" />
</head>
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
<body>
    <header>
        <section class="logo">
            <img src="../src/images/4.png" width="50" height="50" alt="Dom Inn Logo" />
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
            <input id="start-date" type="text" name="checkin_date" placeholder="วันที่เช็คอิน" readonly value="<?= htmlspecialchars($checkin_date) ?>" onclick="openCalendar()" />
            <input id="end-date" type="text" name="checkout_date" placeholder="วันที่เช็คเอ้าท์" readonly value="<?= htmlspecialchars($checkout_date) ?>" onclick="openCalendar()" />

            <div id="rooms-container">
                <div class="room-input-group">
                    <label for="num-rooms">จำนวนห้อง:</label>
                    <input type="number" id="num-rooms" value="<?= htmlspecialchars($num_rooms) ?>" min="1" max="<?= htmlspecialchars($total_available_rooms_for_current_province) ?>" onchange="updateRoomsFromInput()">
                </div>
                <?php
                // แสดงฟอร์มสำหรับแต่ละห้อง
                for ($r = 1; $r <= $num_rooms; $r++):
                    $current_adults = $adults_per_room_initial[$r - 1] ?? 1;
                    $current_children = $children_per_room_initial[$r - 1] ?? 0;
                ?>
                    <div class="room" data-room="<?= $r ?>">
                        <div class="room-header"><h4>ห้องที่ <?= $r ?></h4></div>
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
            <!-- Hidden inputs สำหรับส่งค่ารวมทั้งหมดไปยังตัวเอง (เพื่อใช้ในการค้นหา) -->
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
                        <form action="booking_confirmation.php" method="get" class="booking-form-item">
                            <input type="hidden" name="room_id" value="<?= $room['Room_Id'] ?>">
                            <input type="hidden" name="room_type_id_passed" value="<?= $room['Room_type_Id'] ?>">
                            <input type="hidden" name="price" value="<?= $room['Price'] ?>">
                            <input type="hidden" name="checkin_date" class="checkin_date_hidden" value="<?= htmlspecialchars($checkin_date) ?>">
                            <input type="hidden" name="checkout_date" class="checkout_date_hidden" value="<?= htmlspecialchars($checkout_date) ?>">
                            <input type="hidden" name="num_rooms" class="num_rooms_hidden" value="<?= htmlspecialchars($num_rooms) ?>">
                            <input type="hidden" name="total_adults" class="total_adults_hidden" value="<?= htmlspecialchars($total_adults) ?>">
                            <input type="hidden" name="total_children" class="total_children_hidden" value="<?= htmlspecialchars($total_children) ?>">
                            <input type="hidden" name="province_id" class="province_id_hidden_item" value="<?= htmlspecialchars($province_id) ?>">
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
            <div class="calendar-grid calendar-days" id="calendar-days"></div> <!-- Added class and ID -->
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
                            <form action="booking_confirmation.php" method="get" class="booking-form-item">
                                <input type="hidden" name="room_id" value="">
                                <input type="hidden" name="room_type_id_passed" value="">
                                <input type="hidden" name="price" value="">
                                <input type="hidden" name="checkin_date" class="checkin_date_hidden" value="<?= htmlspecialchars($checkin_date) ?>">
                                <input type="hidden" name="checkout_date" class="checkout_date_hidden" value="<?= htmlspecialchars($checkout_date) ?>">
                                <input type="hidden" name="num_rooms" class="num_rooms_hidden" value="<?= htmlspecialchars($num_rooms) ?>">
                                <input type="hidden" name="total_adults" class="total_adults_hidden" value="<?= htmlspecialchars($total_adults) ?>">
                                <input type="hidden" name="total_children" class="total_children_hidden" value="<?= htmlspecialchars($total_children) ?>">
                                <input type="hidden" name="province_id" class="province_id_hidden_item" value="<?= htmlspecialchars($province_id) ?>">
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
        // JavaScript (DO NOT MODIFY)
        document.addEventListener('DOMContentLoaded', function() {
            updateBranches();
            document.getElementById('region').addEventListener('change', function() {
                updateBranches();
            });
            document.getElementById('branch').addEventListener('change', function() {
                const selectedProvinceId = this.value;
                document.getElementById('province_id_submit').value = selectedProvinceId;
            });
            const urlParams = new URLSearchParams(window.location.search);
            const initialProvinceId = urlParams.get('province_id');
            if (initialProvinceId) {
                const branchSelect = document.getElementById('branch');
                branchSelect.value = initialProvinceId;
                updateBranches();
                document.getElementById('province_id_submit').value = initialProvinceId;

                const selectedBranchOption = branchSelect.querySelector(`option[value="${initialProvinceId}"]`);
                if (selectedBranchOption) {
                    const regionId = selectedBranchOption.getAttribute('data-region-id');
                    if (regionId) {
                        document.getElementById('region').value = regionId;
                    }
                }
            } else {
                document.getElementById('province_id_submit').value = '';
            }
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

        function updateGuestSummary() {
            let totalAdults = 0;
            let totalChildren = 0;
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

            const totalAdultsSubmitInput = document.getElementById('total_adults_submit');
            const totalChildrenSubmitInput = document.getElementById('total_children_submit');
            const numRoomsSubmitInput = document.getElementById('num_rooms_submit');

            if (totalAdultsSubmitInput) totalAdultsSubmitInput.value = totalAdults;
            if (totalChildrenSubmitInput) totalChildrenSubmitInput.value = totalChildren;
            if (numRoomsSubmitInput) numRoomsSubmitInput.value = currentNumRooms;

            const checkinDateVal = document.getElementById('start-date').value;
            const checkoutDateVal = document.getElementById('end-date').value;

            document.querySelectorAll('form.booking-form-item').forEach(form => {
                if (form.querySelector('input[name="num_rooms"]')) form.querySelector('input[name="num_rooms"]').value = currentNumRooms;
                if (form.querySelector('input[name="total_adults"]')) form.querySelector('input[name="total_adults"]').value = totalAdults;
                if (form.querySelector('input[name="total_children"]')) form.querySelector('input[name="total_children"]').value = totalChildren;

                if (form.querySelector('input[name="checkin_date"]')) form.querySelector('input[name="checkin_date"]').value = checkinDateVal;
                if (form.querySelector('input[name="checkout_date"]')) form.querySelector('input[name="checkout_date"]').value = checkoutDateVal;
            });
        }
    </script>
</body>

</html>