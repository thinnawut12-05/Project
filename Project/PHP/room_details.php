<?php
// --- ข้อมูลสมมติสำหรับห้องพัก ---
// ในการใช้งานจริง คุณควรดึงข้อมูลเหล่านี้มาจากฐานข้อมูล
$room_name = "ห้องมาตรฐาน เตียงใหญ่";
$room_description = "ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, โต๊ะทำงาน, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)";
$room_capacity = "3 คน";
$room_guests = "2 ผู้ใหญ่, 1 เด็ก";
$room_bed_type = "1 เตียงใหญ่";
$room_price = "930.00";

// Array ของรูปภาพสำหรับแกลเลอรี
$room_images = [
    "https://via.placeholder.com/800x500/cccccc/ffffff?text=Room+Image+1",
    "https://via.placeholder.com/800x500/bbbbbb/ffffff?text=Room+Image+2",
    "https://via.placeholder.com/800x500/aaaaaa/ffffff?text=Room+Image+3",
    "https://via.placeholder.com/800x500/999999/ffffff?text=Room+Image+4",
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดห้องพัก</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Link to external CSS file -->
    <link rel="stylesheet" href="../CSS/css/modal_style.css">
</head>
<body>

<div class="modal-overlay" id="roomModal">
    <div class="modal-container">
        <span class="modal-close-btn" onclick="closeModal()">&times;</span>
        
        <div class="modal-content">
            <!-- Left Column: Room Details -->
            <div class="modal-left">
                <h1 class="room-title"><?= htmlspecialchars($room_name) ?></h1>
                <p class="room-description"><?= htmlspecialchars($room_description) ?></p>

                <div class="room-features">
                    <div class="feature-item"><i class="fas fa-users"></i> <?= htmlspecialchars($room_capacity) ?></div>
                    <div class="feature-item"><i class="fas fa-user-friends"></i> <?= htmlspecialchars($room_guests) ?></div>
                    <div class="feature-item"><i class="fas fa-bed"></i> <?= htmlspecialchars($room_bed_type) ?></div>
                </div>

                <div class="gallery-container">
                    <?php foreach ($room_images as $index => $image_url): ?>
                        <div class="gallery-slide <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= htmlspecialchars($image_url) ?>" alt="Room Image <?= $index + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="gallery-nav">
                        <button class="gallery-arrow" onclick="changeSlide(-1)">&#10094;</button>
                        <button class="gallery-arrow" onclick="changeSlide(1)">&#10095;</button>
                    </div>
                </div>
                <div class="gallery-dots" id="gallery-dots">
                    <?php foreach ($room_images as $index => $image_url): ?>
                        <span class="dot <?= $index === 0 ? 'active' : '' ?>" onclick="currentSlide(<?= $index ?>)"></span>
                    <?php endforeach; ?>
                </div>

                <p><a href="#" class="amenities-link">สิ่งอำนวยความสะดวกในห้องพัก</a></p>
            </div>

            <!-- Right Column: Booking Info -->
            <div class="modal-right">
                <div class="booking-box">
                    <div class="booking-details">
                        <h3>ราคาที่คุณเลือก</h3>
                        <p class="rate-info">Member: Best Flexible Rate <a href="#">รายละเอียด</a></p>
                        <p class="rate-price"><i class="fas fa-tag"></i> ฿<?= htmlspecialchars($room_price) ?></p>
                        <hr>
                        <div class="booking-total">
                            <span>ยอดรวม</span>
                            <span>฿<?= htmlspecialchars($room_price) ?></span>
                        </div>
                    </div>
                    <button class="booking-button">เข้าสู่ระบบและจอง</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Link to external JavaScript file -->
<script src="../JS/js/modal_script.js"></script>

</body>
</html>