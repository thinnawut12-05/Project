<?php
// --- ส่วนที่แก้ไข: เริ่มการเชื่อมต่อและดึงข้อมูล ---
include 'db.php'; // ไฟล์สำหรับเชื่อมต่อฐานข้อมูลของคุณ
$conn->set_charset("utf8"); // ตั้งค่าให้รองรับภาษาไทย

// เตรียมคำสั่ง SQL เพื่อดึงข้อมูลห้องพัก
$sql = "SELECT room_type_id, Room_type_name FROM room_type ORDER BY room_type_id ASC";
$result = $conn->query($sql);
// --- จบส่วนที่แก้ไข ---

?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <title>ประเภทห้องพัก | Dom Inn Hotel</title>
  <link rel="icon" type="image/png" href="./src/images/logo.png" />
  <link rel="stylesheet" href="./type.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

  <header>
    <section class="logo">
      <img src="./src/images/4.png" width="50" height="50" />
    </section>
   
  </header>

  <section class="room-listing">
    <h2>เลือกห้องที่คุณต้องการ</h2>
    <div class="room-grid">

      <?php
      // --- ส่วนที่แก้ไข: วนลูปแสดงผลจากฐานข้อมูล ---
      if ($result && $result->num_rows > 0) {
          // วนลูปแสดงข้อมูลทีละแถว
          while($row = $result->fetch_assoc()) {
              // --- ข้อมูลตัวอย่างที่เราจะกำหนดเอง เนื่องจากไม่มีในตาราง ---
              $location = 'โรงแรมในเครือดอมอินน์'; // ข้อความตัวอย่าง
              $price = 930; // ราคาตัวอย่าง
              $image_path = './src/images/' . $row['room_type_id'] . '.jpg'; // สร้างชื่อไฟล์รูปจาก id เช่น 1.jpg, 2.jpg
              $link_url = 'hotel_rooms.php?id=' . $row['room_type_id']; // สร้างลิงก์ไปยังหน้ารายละเอียด
              
              echo '<div class="room-card">';
              echo '<img src="' . htmlspecialchars($image_path) . '" alt="' . htmlspecialchars($row['Room_type_name']) . '">';
              echo '<div class="info">';
              echo '<h3>' . htmlspecialchars($row['Room_type_name']) . '</h3>'; // ดึงชื่อจากฐานข้อมูล
              echo '<p>' . htmlspecialchars($location) . '</p>';
              echo '<p class="price">฿ ' . number_format($price) . ' / คืน</p>';
              echo '</div>';
              echo '<a href="' . htmlspecialchars($link_url) . '" class="btn-book">จองเลย</a>';
              echo '</div>';
          }
      } else {
          echo "<p>ไม่พบข้อมูลประเภทห้องพัก</p>";
      }
      
      // ปิดการเชื่อมต่อฐานข้อมูล
      $conn->close();
      // --- จบส่วนที่แก้ไข ---
      ?>

    </div>
  </section>

</body>
</html>