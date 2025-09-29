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
        $servername = "localhost"; // หรือ IP ของ MySQL Server ของคุณ
        $username = "root"; // ชื่อผู้ใช้ MySQL ของคุณ
        $password = ""; // รหัสผ่าน MySQL ของคุณ (อาจจะว่างเปล่าสำหรับ localhost)
        $dbname = "hotel_db"; // ชื่อฐานข้อมูลของคุณ

        // สร้างการเชื่อมต่อ
        $conn = new mysqli($servername, $username, $password, $dbname);

        // ตรวจสอบการเชื่อมต่อ
        if ($conn->connect_error) {
            die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
        }

        // กำหนด charset เป็น utf8 เพื่อรองรับภาษาไทย
        $conn->set_charset("utf8");

        // สร้าง Query สำหรับดึงข้อมูลจากตาราง reservation
        // ดึงข้อมูล guest_name, stars, และ comment
        $sql = "SELECT guest_name, stars, comment FROM reservation WHERE stars IS NOT NULL OR comment IS NOT NULL ORDER BY booking_date DESC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<table class='summary-table'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>ชื่อผู้เข้าพัก</th>";
            echo "<th>คะแนน (ดาว)</th>";
            echo "<th>คอมเมนต์</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            // วนลูปแสดงผลข้อมูลแต่ละแถว
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["guest_name"]) . "</td>";
                echo "<td>";
                if ($row["stars"] !== NULL) {
                    for ($i = 0; $i < $row["stars"]; $i++) {
                        echo "<span class='star'>&#9733;</span>"; // รหัส HTML ของดาว
                    }
                } else {
                    echo "-";
                }
                echo "</td>";
                echo "<td>" . htmlspecialchars($row["comment"] ?? "-") . "</td>"; // ใช้ ?? เพื่อจัดการค่า NULL
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