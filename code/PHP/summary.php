<?php
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <title>คะแนน</title>
    <!-- Corrected Font Awesome CDN Link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
          integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../CSS/css/ino.css" />
    <link rel="stylesheet" href="../CSS/css/summary.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding-bottom: 50px; /* เพิ่มพื้นที่ด้านล่างของ body */
        }

        .container {
            margin-top: 30px; /* เพิ่มระยะห่างด้านบน */
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 900px; /* เพิ่ม max-width เล็กน้อยเพื่อรองรับเนื้อหา */
            text-align: center;
            margin-bottom: 30px;
        }

        h1 {
            color: #333;
            margin-bottom: 30px; /* เพิ่มระยะห่างด้านล่าง */
            margin-top: 15px; /* เพิ่มระยะห่างด้านบน */
            font-size: 2.4em; /* เพิ่มขนาดฟอนต์ */
            border-bottom: 3px solid #007bff; /* เพิ่มเส้นใต้ */
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 45px; /* เพิ่มระยะห่างด้านบน */
            margin-bottom: 25px; /* เพิ่มระยะห่างด้านล่าง */
            font-size: 2em; /* เพิ่มขนาดฟอนต์ */
            text-align: left;
            border-bottom: 2px solid #007bff;
            padding-bottom: 8px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px; /* เพิ่มระยะห่างด้านบน */
            margin-bottom: 40px; /* เพิ่มระยะห่างด้านล่างระหว่างตาราง */
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #e0e0e0;
            padding: 14px 18px; /* เพิ่ม padding เพื่อให้มีพื้นที่มากขึ้น */
            text-align: left;
            font-size: 1.1em; /* เพิ่มขนาดฟอนต์ในเซลล์ */
        }

        .summary-table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1.2em; /* เพิ่มขนาดฟอนต์ในหัวตาราง */
        }

        .summary-table tr:nth-child(even) {
            background-color: #f8f8f8;
        }

        .summary-table tr:hover {
            background-color: #f1f1f1;
        }

        /* Star styling for summary.php */
        .star, .star-empty {
            font-size: 1.3em; /* เพิ่มขนาดดาว */
            margin: 0 2px; /* เพิ่มระยะห่างระหว่างดาว */
            display: inline-block;
        }
        .star i {
            color: #ffcc00;
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
            font-style: normal !important;
            vertical-align: middle;
        }
        .star-empty i {
            color: #bbb;
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 400 !important;
            font-style: normal !important;
            vertical-align: middle;
        }
        /* Specific styling for half-star icon */
        .star i.fa-star-half-alt {
            font-weight: 900 !important; /* Ensure solid style for half star */
        }


        .no-data {
            color: #777;
            font-size: 1.2em; /* เพิ่มขนาดฟอนต์ */
            margin-top: 25px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
        }
    </style>
</head>
<body>
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
        <a href="#">การจองของฉัน</a>
        <a href="./summary.php">คะแนน</a>
      </nav>
      <nav>
        <a href="./member.php">สมัครสมาชิก</a>
        <a href="./login.php">เข้าสู่ระบบ</a>
      </nav>
    </header>

    <div class="container">
        <h1>คะแนนเฉลี่ยโรงแรมของเราตามสาขา</h1>

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

        // ดึงข้อมูลคะแนนเฉลี่ยสำหรับแต่ละสาขา
        $sql_avg = "SELECT
                        p.Province_id,
                        p.Province_name,
                        AVG(r.stars) AS average_stars
                    FROM
                        reservation r
                    JOIN
                        province p ON r.Province_id = p.Province_id
                    WHERE
                        r.stars IS NOT NULL
                    GROUP BY
                        p.Province_id, p.Province_name
                    ORDER BY
                        p.Province_name ASC";
        $result_avg = $conn->query($sql_avg);

        $provinces_data = [];
        if ($result_avg->num_rows > 0) {
            echo "<table class='summary-table'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>สาขา</th>";
            echo "<th>คะแนนเฉลี่ย (ตัวเลข)</th>";
            echo "<th>คะแนนเฉลี่ย (ดาว)</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            while($row_avg = $result_avg->fetch_assoc()) {
                $provinces_data[] = $row_avg; // เก็บข้อมูลสาขาไว้ใช้ในส่วนถัดไป
                $provinceName = htmlspecialchars($row_avg["Province_name"]);
                $averageStars = (float)$row_avg["average_stars"];

                echo "<tr>";
                echo "<td>" . $provinceName . "</td>";
                echo "<td>" . number_format($averageStars, 2) . "</td>"; // แสดงทศนิยม 2 ตำแหน่ง
                echo "<td>";
                
                // Logic to display average stars including fractional part using Font Awesome icons
                $fullStars = floor($averageStars);
                $halfStarDisplayed = false;
                $remainder = $averageStars - $fullStars;

                // Determine if a half star should be displayed based on remainder
                if ($remainder >= 0.25 && $remainder < 0.75) {
                    $halfStarDisplayed = true;
                } elseif ($remainder >= 0.75) {
                    $fullStars++; // Round up to a full star
                }

                // Display full stars
                for ($i = 0; $i < $fullStars; $i++) {
                    echo "<span class='star'><i class='fas fa-star'></i></span>";
                }

                // Display half star if applicable
                if ($halfStarDisplayed) {
                    echo "<span class='star'><i class='fas fa-star-half-alt'></i></span>";
                }

                // Calculate and display empty stars
                $starsCurrentlyDisplayed = $fullStars + ($halfStarDisplayed ? 1 : 0);
                $emptyStars = 5 - $starsCurrentlyDisplayed;

                for ($i = 0; $i < $emptyStars; $i++) {
                    echo "<span class='star-empty'><i class='far fa-star'></i></span>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p class='no-data'>ไม่พบข้อมูลคะแนนรีวิวสำหรับสาขาใดๆ</p>";
        }

        // --- ส่วนที่ 2: แสดงรายละเอียดรีวิว 5 รายการล่าสุดแยกตามสาขา ---
        if (!empty($provinces_data)) {
            echo "<h1>รายละเอียดรีวิวล่าสุดแยกตามสาขา</h1>";
            foreach ($provinces_data as $province) {
                $province_id = $province['Province_id'];
                $province_name = htmlspecialchars($province['Province_name']);

                echo "<h2>สาขา: " . $province_name . "</h2>";

                // ดึงข้อมูล rating_timestamp, stars, comment 5 รายการล่าสุดสำหรับสาขาปัจจุบัน
                $sql_details = "SELECT rating_timestamp, stars, comment
                                FROM reservation
                                WHERE Province_id = $province_id
                                  AND rating_timestamp IS NOT NULL
                                  AND stars IS NOT NULL
                                ORDER BY rating_timestamp DESC
                                LIMIT 5";
                $result_details = $conn->query($sql_details);

                if ($result_details->num_rows > 0) {
                    echo "<table class='summary-table'>";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>วันที่คอมเมนต์</th>";
                    echo "<th>คะแนน (ดาว)</th>";
                    echo "<th>คอมเมนต์</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";

                    while($row_details = $result_details->fetch_assoc()) {
                        $thaiDate = "-";
                        if ($row_details["rating_timestamp"] !== NULL) {
                            $date = date_create($row_details["rating_timestamp"]);
                            $thaiDate = date_format($date, 'd/m/') . (date_format($date, 'Y') + 543);
                        }

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($thaiDate) . "</td>";
                        echo "<td>";
                        if ($row_details["stars"] !== NULL) {
                            $num_stars = (float)$row_details["stars"]; // แปลงเป็น float
                            // Individual review stars typically come as whole or half numbers.
                            // The original logic handles this sufficiently.
                            $temp_detail_stars = $num_stars; 
                            for ($i = 0; $i < 5; $i++) {
                                if ($temp_detail_stars >= 1) {
                                    echo "<span class='star'><i class='fas fa-star'></i></span>"; // Full star
                                    $temp_detail_stars -= 1;
                                } elseif ($temp_detail_stars >= 0.5) {
                                    echo "<span class='star'><i class='fas fa-star-half-alt'></i></span>"; // Half star
                                    $temp_detail_stars -= 0.5;
                                } else {
                                    echo "<span class='star-empty'><i class='far fa-star'></i></span>"; // Empty star
                                }
                            }
                        } else {
                            echo "-";
                        }
                        echo "</td>";
                        echo "<td>" . htmlspecialchars($row_details["comment"] ?? "-") . "</td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    echo "</table>";
                } else {
                    echo "<p class='no-data'>ไม่พบข้อมูลรีวิวสำหรับสาขา " . $province_name . ".</p>";
                }
            }
        }

        // ปิดการเชื่อมต่อ
        $conn->close();
        ?>
    </div>

</body>
</html>