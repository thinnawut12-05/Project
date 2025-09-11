<?php
session_start();
include 'db.php';

// ดึงข้อมูลจาก session
$First_name   = $_SESSION['First_name'] ?? '';
$Last_name    = $_SESSION['Last_name'] ?? '';
$full_name    = trim($First_name . ' ' . $Last_name);

$num_rooms    = $_SESSION['num_rooms'] ?? 1;
$adults       = $_SESSION['adults'] ?? 1;
$children     = $_SESSION['children'] ?? 0;
$checkin_date = $_SESSION['checkin_date'] ?? date("Y-m-d");
$checkout_date= $_SESSION['checkout_date'] ?? date("Y-m-d");
$total_price  = $_SESSION['total_price'] ?? 0;
$room_id      = $_SESSION['room_id'] ?? null;

// จำลอง email สมาชิก
$email_member = $_SESSION['email'] ?? 'guest@example.com';

// สถานะการจอง (1 = รอตรวจสอบ, 2 = ชำระแล้ว, 3 = ยกเลิก)
$status_id = 1;

// อัพโหลดไฟล์สลิป
if (isset($_FILES['slip']) && $_FILES['slip']['error'] == 0) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES["slip"]["name"]);
    $targetFilePath = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["slip"]["tmp_name"], $targetFilePath)) {

        // ✅ สร้าง Reservation_Id เอง (ใช้ timestamp + random)
        $reservation_id = time() . rand(100, 999);

        // --- Insert ข้อมูลการจองลงตาราง reservation ---
        $sql = "INSERT INTO reservation 
                (Reservation_Id, Guest_name, Number_of_rooms, Booking_time, 
                 Number_of_adults, Number_of_children, Booking_date, 
                 Check_out_date, Email_member, Receipt_Id, Booking_status_Id) 
                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        // Receipt_Id ตอนนี้ใส่ NULL ไปก่อน (ยังไม่มีใบเสร็จ)
        $receipt_id = null;

        $stmt->bind_param("isiissssii", 
            $reservation_id,   // Reservation_Id
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
            echo "<p>รหัสการจอง: $reservation_id</p>";
            echo "<p>คุณ $full_name ได้จองห้องจำนวน $num_rooms ห้อง</p>";
            echo "<p>ยอดเงินที่ต้องชำระ: ฿ " . number_format($total_price, 2) . "</p>";
            echo "<p>วันเข้าพัก: $checkin_date ถึง $checkout_date</p>";
            echo "<p>จำนวนผู้เข้าพัก: $adults ผู้ใหญ่, $children เด็ก</p>";
            echo "<p>สถานะการจอง: รอตรวจสอบการชำระเงิน</p>";
            echo "<p><a href='index.php'>กลับไปหน้าหลัก</a></p>";
        } else {
            echo "❌ เกิดข้อผิดพลาด: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "❌ อัพโหลดไฟล์ไม่สำเร็จ";
    }
} else {
    echo "❌ กรุณาเลือกไฟล์สลิป";
}
?>
