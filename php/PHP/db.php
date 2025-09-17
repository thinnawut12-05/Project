<?php
$servername = "localhost";      // ชื่อเซิร์ฟเวอร์ฐานข้อมูล
$username = "root";             // ชื่อผู้ใช้ฐานข้อมูล
$password = "";                 // รหัสผ่านฐานข้อมูล (ถ้าไม่มีให้เว้นว่าง)
$dbname = "hotel_db";      // ชื่อฐานข้อมูลที่ใช้

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $conn->connect_error);
}
?>