<?php 
session_start();
include 'db.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ยืนยันการจอง</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #74b9ff, #a29bfe);
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 700px;
      margin: 50px auto;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      padding: 30px;
      text-align: center;
      animation: fadeIn 0.7s ease-in-out;
    }

    h2 {
      color: #2d3436;
      font-size: 26px;
      margin-bottom: 15px;
    }

    p {
      font-size: 16px;
      color: #555;
      margin: 8px 0;
    }

    .highlight {
      color: #0984e3;
      font-weight: bold;
    }

    a {
      display: inline-block;
      margin-top: 20px;
      text-decoration: none;
      background: #0984e3;
      color: #fff;
      padding: 10px 20px;
      border-radius: 8px;
      transition: background 0.3s;
    }

    a:hover {
      background: #0652dd;
    }

    .btn-green {
      background: #27ae60;
    }

    .btn-green:hover {
      background: #1e8449;
    }

    .error {
      color: #d63031;
      font-weight: bold;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="container">
    <?php
    $First_name   = $_SESSION['First_name'] ?? '';
    $Last_name    = $_SESSION['Last_name'] ?? '';
    $full_name    = trim($First_name . ' ' . $Last_name);

    $num_rooms    = $_SESSION['num_rooms'] ?? 1;
    $adults       = $_SESSION['total_adults'] ?? 1;
    $children     = $_SESSION['total_children'] ?? 0;
    $checkin_date = $_SESSION['checkin_date'] ?? date("Y-m-d");
    $checkout_date= $_SESSION['checkout_date'] ?? date("Y-m-d");
    $total_price  = $_SESSION['total_price'] ?? 0;
    $room_id      = $_SESSION['room_id'] ?? null;
    $email_member = $_SESSION['email'] ?? 'guest@example.com';
    $status_id = 1;

    if (isset($_FILES['slip']) && $_FILES['slip']['error'] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES["slip"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["slip"]["tmp_name"], $targetFilePath)) {
            // ✅ สร้าง Reservation ID
            $reservation_id = time() . rand(100, 999);
            $_SESSION['reservation_id'] = $reservation_id; // เก็บใน session

            $sql = "INSERT INTO reservation 
                    (Reservation_Id, Guest_name, Number_of_rooms, Booking_time, 
                     Number_of_adults, Number_of_children, Booking_date, 
                     Check_out_date, Email_member, Receipt_Id, Booking_status_Id) 
                    VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $receipt_id = null;

            $stmt->bind_param("isiissssii", 
                $reservation_id,   
                $full_name, 
                $num_rooms, 
                $adults, 
                $children, 
                $checkin_date, 
                $checkout_date, 
                $email_member, 
                $receipt_id, 
                $status_id
            );

            if ($stmt->execute()) {
                echo "<h2>✅ บันทึกการจองสำเร็จ</h2>";
                echo "<p>รหัสการจอง: <span class='highlight'>$reservation_id</span></p>";
                echo "<p>คุณ <span class='highlight'>$full_name</span> ได้จองห้องจำนวน <span class='highlight'>$num_rooms</span> ห้อง</p>";
                echo "<p>ยอดเงินที่ต้องชำระ: <span class='highlight'>฿ " . number_format($total_price, 2) . "</span></p>";
                echo "<p>วันเข้าพัก: <span class='highlight'>$checkin_date</span> ถึง <span class='highlight'>$checkout_date</span></p>";
                echo "<p>จำนวนผู้เข้าพัก: <span class='highlight'>$adults</span> ผู้ใหญ่, <span class='highlight'>$children</span> เด็ก</p>";
                echo "<p>สถานะการจอง: <span class='highlight'>รอตรวจสอบการชำระเงิน</span></p>";

                // ✅ ปุ่มกลับหน้าหลัก
                echo "<a href='index.php'>กลับไปหน้าหลัก</a>";

                // ✅ ปุ่มดูใบเสร็จ
                echo "<a href='receipt.php?booking_id=$reservation_id' class='btn-green'>ดูใบเสร็จ</a>";
            } else {
                echo "<p class='error'>❌ เกิดข้อผิดพลาด: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            echo "<p class='error'>❌ อัพโหลดไฟล์ไม่สำเร็จ</p>";
        }
    } else {
        echo "<p class='error'>❌ กรุณาเลือกไฟล์สลิป</p>";
    }
    ?>
  </div>
</body>
</html>
