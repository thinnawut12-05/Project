<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <title>คะแนน</title>
    <link rel="stylesheet" href="../CSS/css/style.css">
</head>
<body>
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

        // ดึงข้อมูล booking_date, stars, comment
        $sql = "SELECT booking_date, stars, comment 
                FROM reservation 
                WHERE stars IS NOT NULL OR comment IS NOT NULL 
                ORDER BY booking_date DESC";
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
                // แปลงวันที่เป็นรูปแบบไทย (วัน/เดือน/พ.ศ.)
                $date = date_create($row["booking_date"]);
                $thaiDate = date_format($date, 'd/m/') . (date_format($date, 'Y') + 543);

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
