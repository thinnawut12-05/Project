<?php
session_start(); // *** เพิ่ม: เริ่ม session สำหรับเช็คสมาชิก (จำเป็นสำหรับ navbar ที่มีการเปลี่ยนสถานะ) ***
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <title>คะแนน</title>
    <!-- เดิม: <link rel="stylesheet" href="../CSS/css/style.css"> -->
    <!-- *** เพิ่ม: ลิงก์ไปยัง ino.css สำหรับสไตล์ของ Header *** -->
    <link rel="stylesheet" href="../CSS/css/ino.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
      integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOM7z4j8e+Q1z5l5x5l5x5l5x5l5x5l5x5l5x"
      crossorigin="anonymous" />
    <link rel="stylesheet" href="../CSS/css/summary.css"> <!-- เก็บ style.css เดิมไว้ หากมีสไตล์เฉพาะหน้าที่สำคัญ -->
    <style>
        /* เพิ่ม CSS สำหรับตารางและดาว หากยังไม่มีใน style.css */
        body {
            font-family: 'Kanit', sans-serif; /* ตัวอย่างการใช้ font Kanit */
            background-color: #f0f2f5;
            display: flex; /* เปลี่ยนเป็น flex เพื่อให้ header อยู่ด้านบน */
            flex-direction: column; /* จัดเรียงองค์ประกอบในแนวตั้ง */
            align-items: center; /* จัดให้อยู่กึ่งกลางแนวนอน */
            min-height: 100vh;
            margin: 0;
        }

        /* ปรับ margin-top ของ .container เพื่อไม่ให้ชนกับ header */
        .container {
            margin-top: 20px; /* หรือค่าที่เหมาะสม */
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            text-align: center;
        }

        h1 {
            color: #333;
            margin-bottom: 25px;
            font-size: 2.2em;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            text-align: left;
        }

        .summary-table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
        }

        .summary-table tr:nth-child(even) {
            background-color: #f8f8f8;
        }

        .summary-table tr:hover {
            background-color: #f1f1f1;
        }

        .star {
            color: #ffcc00; /* สีเหลืองทองสำหรับดาว */
            font-size: 1.2em;
        }

        .no-data {
            color: #777;
            font-size: 1.1em;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- *** แทรก Header จาก index.php *** -->
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
    <!-- *** สิ้นสุด Header ที่แทรกเข้ามา *** -->

    <div class="container">
        <h1>คะแนนโรงแรมของเรา</h1>

        <?php
        // ตั้งค่าการเชื่อมต่อฐานข้อมูล
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "hotel_db";

        // สร้างการเชื่อมต่อ
        $conn = new mysqli($servername, $username, $password, $dbname);

        // ตรวจสอบการเชื่อมต่อ
        if ($conn->connect_error) {
            die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
        }

        // กำหนด charset เป็น utf8 เพื่อรองรับภาษาไทย
        $conn->set_charset("utf8");

        // ดึงข้อมูล rating_timestamp, stars, comment
        // โดยใช้ rating_timestamp ใน SELECT และ ORDER BY
        $sql = "SELECT rating_timestamp, stars, comment
                FROM reservation
                WHERE rating_timestamp IS NOT NULL AND stars IS NOT NULL
                ORDER BY rating_timestamp DESC"; // เรียงตามวันที่คอมเมนต์ล่าสุด
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table class='summary-table'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>วันที่คอมเมนต์</th>";
            echo "<th>คะแนน (ดาว)</th>";
            echo "<th>คอมเมนต์</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            while($row = $result->fetch_assoc()) {
                // แปลง rating_timestamp เป็นรูปแบบไทย (วัน/เดือน/พ.ศ.)
                // ตรวจสอบให้แน่ใจว่า rating_timestamp ไม่เป็น NULL ก่อนแปลง
                $thaiDate = "-";
                if ($row["rating_timestamp"] !== NULL) {
                    $date = date_create($row["rating_timestamp"]);
                    $thaiDate = date_format($date, 'd/m/') . (date_format($date, 'Y') + 543);
                }


                echo "<tr>";
                echo "<td>" . htmlspecialchars($thaiDate) . "</td>";
                echo "<td>";
                if ($row["stars"] !== NULL) {
                    for ($i = 0; $i < $row["stars"]; $i++) {
                        echo "<span class='star'>&#9733;</span>";
                    }
                } else {
                    echo "-";
                }
                echo "</td>";
                echo "<td>" . htmlspecialchars($row["comment"] ?? "-") . "</td>";
                echo "</tr>";
            }

            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p class='no-data'>ไม่พบข้อมูลคะแนนหรือคอมเมนต์.</p>";
        }

        // ปิดการเชื่อมต่อ
        $conn->close();
        ?>
    </div>

</body>
</html>