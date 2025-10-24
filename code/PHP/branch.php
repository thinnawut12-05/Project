<?php
session_start(); // *** เพิ่ม: เริ่ม session สำหรับเช็คสมาชิก (จำเป็นสำหรับ navbar ที่มีการเปลี่ยนสถานะ) ***

// เชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// กำหนด charset เป็น utf8 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8");

// ดึงข้อมูลจังหวัด + ที่อยู่ + เบอร์โทร
$sql = "SELECT Province_name, Address, Phone FROM province";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>สาขาโรงแรม Dom inn</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="../CSS/css/branch.css">
    <!-- *** เพิ่ม: ลิงก์ไปยัง ino.css สำหรับสไตล์ของ Header *** -->
    <link rel="stylesheet" href="../CSS/css/ino.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
      integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOM7z4j8e+Q1z5l5x5l5x5l5x5l5x5l5x5l5x"
      crossorigin="anonymous" />
</head>

<body>
    <!-- *** แทนที่ Header เดิมด้วย Header จาก index.php *** -->
    <header>
      <section class="logo">
        <a href="./index.php">
          <img src="../src/images/4.png" width="50" height="50" alt="Dom Inn Logo" />
        </a>
      </section>
      <nav>
        <a href="./index-type.php">ประเภทห้องพัก</a>
        <a href="./branch.php">สาขาโรงแรมดอม อินน์</a>
        <a href="./details.php">รายละเอียดต่างๆ</a>
        <a href="#">การจองของฉัน</a> <!-- หากมีหน้านี้ ให้เปลี่ยน # เป็น path ที่ถูกต้อง -->
        <a href="./summary.php">คะแนน</a>
      </nav>
      <nav>
        <a href="./member.php">สมัครสมาชิก</a>
        <a href="./login.php">เข้าสู่ระบบ</a>
      </nav>
    </header>
    <!-- *** สิ้นสุด Header ที่เพิ่มเข้ามา *** -->

    <div class="header2">
        <h1>สาขาโรงแรม Dom inn</h1>
    </div>

    <div class="container">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $province = $row["Province_name"];
                $address  = $row["Address"];
                $phone    = $row["Phone"];

                echo "
                <div class='branch-card'>
                    <h2>Dom inn สาขา {$province}</h2>
                    <p>ที่อยู่: {$address}</p>
                    <p>โทร: {$phone}</p>
                </div>
                ";
            }
        } else {
            echo "<p style='text-align:center;'>ไม่พบข้อมูล</p>";
        }
        $conn->close();
        ?>
    </div>

</body>

</html>