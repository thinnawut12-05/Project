<?php
// เชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// กด submit แล้ว insert ลง stay
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $check_in_date = $_POST['check_in_date'];
    $check_in_time = $_POST['check_in_time'];
    $check_out_date = $_POST['check_out_date'];
    $check_out_time = $_POST['check_out_time'];
    $guest_name = $_POST['guest_name'];
    $room_id = $_POST['room_id'];
    $receipt_id = $_POST['receipt_id'];
    $reservation_id = $_POST['reservation_id'];
    $email_member = $_POST['email_member'];

    $stmt = $conn->prepare("INSERT INTO stay (Check_in_date, Check_in_time, Check_out_date, Check_out_time, Guest_name, Room_id, Receipt_id, Reservation_id, Email_member) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $check_in_date, $check_in_time, $check_out_date, $check_out_time, $guest_name, $room_id, $receipt_id, $reservation_id, $email_member);

    if ($stmt->execute()) {
        $msg = "บันทึกข้อมูลผู้เข้าพักสำเร็จ!";
    } else {
        $msg = "เกิดข้อผิดพลาด: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกการเข้าพัก (หน้าเคาน์เตอร์)</title>
    <style>
        body {
            background: #f7f7fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            background: #fff;
            max-width: 550px;
            margin: 40px auto;
            padding: 32px 36px 24px 36px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        h2 {
            color: #1e3662;
            text-align: center;
            margin-bottom: 28px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #24375d;
            font-weight: 500;
        }
        input, select {
            width: 100%;
            padding: 9px 10px;
            margin-bottom: 17px;
            border: 1px solid #bfc9da;
            border-radius: 5px;
            background: #f9fafb;
            font-size: 1rem;
        }
        button {
            background: #3366cc;
            color: #fff;
            padding: 11px 0;
            width: 100%;
            border: none;
            border-radius: 5px;
            font-size: 1.07rem;
            font-weight: bold;
            transition: background 0.3s;
            cursor: pointer;
        }
        button:hover {
            background: #254796;
        }
        .msg {
            text-align: center;
            margin-bottom: 15px;
            color: green;
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>บันทึกผู้เข้าพัก (หน้าเคาน์เตอร์)</h2>
    <?php if (!empty($msg)): ?>
        <div class="msg"><?php echo $msg; ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>วันที่เช็คอิน</label>
        <input type="date" name="check_in_date" required>

        <label>เวลาเช็คอิน</label>
        <input type="time" name="check_in_time" required>

        <label>วันที่เช็คเอาท์</label>
        <input type="date" name="check_out_date" required>

        <label>เวลาเช็คเอาท์</label>
        <input type="time" name="check_out_time" required>

        <label>ชื่อผู้เข้าพัก</label>
        <input type="text" name="guest_name" required>

        <label>หมายเลขห้อง</label>
        <input type="text" name="room_id" required>

        <label>หมายเลขใบเสร็จ</label>
        <input type="text" name="receipt_id">

        <label>หมายเลขจอง</label>
        <input type="text" name="reservation_id">

        <label>Email สมาชิก (ถ้ามี)</label>
        <input type="email" name="email_member">

        <button type="submit">บันทึกการเข้าพัก</button>
    </form>
</div>
</body>
</html>