<?php
session_start();
include 'db.php';

$room_id = $_GET['room_id'] ?? '';
$checkin_date = $_GET['checkin_date'] ?? '';
$adults = $_GET['adults'] ?? 1;
$children = $_GET['children'] ?? 0;

echo "<h1>หน้าชำระเงิน</h1>";
echo "<p>ห้องที่เลือก: $room_id</p>";
echo "<p>วันที่เข้าพัก: $checkin_date</p>";
echo "<p>ผู้ใหญ่: $adults คน</p>";
echo "<p>เด็ก: $children คน</p>";
?>
