<?php
// เริ่มต้น session (ถ้าต้องการใช้ในระบบ login)
session_start();

// ถ้าต้องการเชื่อมฐานข้อมูล ก็ include ได้
// include 'db.php';

// ตัวอย่างการดึงข้อมูลห้องพักจากฐานข้อมูล (ถ้ามี)
// $sql = "SELECT * FROM rooms";
// $result = $conn->query($sql);
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
      // ถ้าใช้ฐานข้อมูล
      /*
      while($row = $result->fetch_assoc()) {
          echo '<div class="room-card">';
          echo '<img src="' . $row['image'] . '" alt="' . $row['name'] . '">';
          echo '<div class="info">';
          echo '<h3>' . $row['name'] . '</h3>';
          echo '<p>' . $row['location'] . '</p>';
          echo '<p class="price">฿ ' . number_format($row['price']) . ' / คืน</p>';
          echo '</div>';
          echo '<a href="booking.php?id=' . $row['id'] . '" class="btn-book">เลือกวันที่ต้องการ</a>';
          echo '</div>';
      }
      */

      // ตัวอย่างแบบข้อมูลคงที่
      $rooms = [
        [
          'image' => './src/images/1.jpg',
          'name' => 'โรงแรมดอมอินน์ ตัวอย่าง ห้องพัก1',
          'location' => 'จังหวัดกระบี่ ประเทศไทย',
          'price' => 1500,
          'link' => 'room1.php'
        ],
        [
          'image' => './src/images/2.jpg',
          'name' => 'โรงแรมดอมอินน์ ตัวอย่าง ห้องพัก2',
          'location' => 'จังหวัดเชียงราย ประเทศไทย',
          'price' => 1700,
          'link' => '#'
        ]
      ];

      foreach ($rooms as $room) {
          echo '<div class="room-card">';
          echo '<img src="' . $room['image'] . '" alt="' . $room['name'] . '">';
          echo '<div class="info">';
          echo '<h3>' . $room['name'] . '</h3>';
          echo '<p>' . $room['location'] . '</p>';
          echo '<p class="price">฿ ' . number_format($room['price']) . ' / คืน</p>';
          echo '</div>';
          echo '<a href="' . $room['link'] . '" class="btn-book">ดูรายละเอียด</a>';
          echo '</div>';
      }
      ?>

    </div>
  </section>

  
</body>
</html>
