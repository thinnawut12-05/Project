<?php 
session_start();
// ตรวจสอบ session เจ้าหน้าที่
// หากไม่มี session Email_Officer แสดงว่ายังไม่ได้ล็อกอิน ให้ redirect ไปหน้า login
if (!isset($_SESSION['Email_Officer']) || !isset($_SESSION['Province_id'])) {
    header("Location: officer_login.php"); // ควร redirect ไป officer_login.php
    exit();
}

// เชื่อมต่อฐานข้อมูลเพื่อดึงชื่อสาขา
// หากคุณมีไฟล์ db.php ที่เชื่อมต่อฐานข้อมูลอยู่แล้ว สามารถใช้ include ได้
// แต่ในตัวอย่างนี้ ผมจะเขียนโค้ดเชื่อมต่อฐานข้อมูลใหม่ในไฟล์นี้
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8"); // ตั้งค่า charset เพื่อรองรับภาษาไทย

$officer_name = $_SESSION['First_name'] ?? "เจ้าหน้าที่"; // ดึงชื่อเจ้าหน้าที่จาก session (ถ้ามี)
$officerProvinceId = $_SESSION['Province_id'];
$officerProvinceName = 'ไม่ระบุสาขา'; // กำหนดค่าเริ่มต้น

// ดึงชื่อจังหวัดจาก Province_id
if ($officerProvinceId !== null) {
    $stmt_province = $conn->prepare("SELECT Province_name FROM province WHERE Province_Id = ?");
    if ($stmt_province) {
        $stmt_province->bind_param("i", $officerProvinceId);
        $stmt_province->execute();
        $stmt_province->bind_result($provinceNameFromDB);
        if ($stmt_province->fetch()) {
            $officerProvinceName = $provinceNameFromDB;
        }
        $stmt_province->close();
    } else {
        error_log("Error preparing province name statement: " . $conn->error);
    }
}

$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูลเมื่อเสร็จสิ้น
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <title>หน้าหลักเจ้าหน้าที่</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            background-color: #2a5d9f;
            color: white;
            text-align: center;
            padding: 20px 0;
            font-size: 24px;
            position: relative;
            display: flex; /* ใช้ flexbox เพื่อจัดองค์ประกอบ */
            justify-content: space-between; /* จัดให้ข้อความอยู่ซ้าย/ขวา */
            align-items: center; /* จัดให้อยู่กึ่งกลางแนวตั้ง */
            padding: 20px; /* เพิ่ม padding ด้านข้าง */
        }
        header .title-text {
            flex-grow: 1; /* ทำให้ส่วนข้อความเติบโตเพื่อกินพื้นที่ตรงกลาง */
            text-align: center; /* จัดข้อความให้อยู่กึ่งกลาง */
        }


        header .logout-button {
            /* ตำแหน่งถูกปรับให้ยืดหยุ่นด้วย flexbox */
            background-color: #f44336;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.2s;
            white-space: nowrap; /* ป้องกันปุ่มขึ้นบรรทัดใหม่ */
        }

        header .logout-button:hover {
            background-color: #d32f2f;
        }

        .container {
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            margin: 30px;
            gap: 20px;
        }

        .card {
            background-color: white;
            width: 250px;
            height: 150px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            font-size: 18px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }

        .card .icon {
            font-size: 3em;
            margin-bottom: 10px;
        }

        .card span {
            font-weight: bold;
        }

        footer {
            text-align: center;
            padding: 15px;
            background-color: #eee;
            width: 100%;
            margin-top: auto;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.05);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                gap: 15px;
            }

            .card {
                width: 90%;
                max-width: 300px;
                height: 120px;
                font-size: 16px;
            }

            .card .icon {
                font-size: 2.5em;
                margin-bottom: 8px;
            }

            header .logout-button {
                font-size: 14px;
                padding: 6px 12px;
            }
            header {
                flex-direction: column; /* ให้รายการเรียงกันในแนวตั้ง */
                gap: 10px;
                font-size: 20px;
            }
        }

        @media (max-width: 480px) {
            header {
                font-size: 18px; /* ปรับขนาดฟอนต์ของ header เล็กลง */
                padding: 10px;
            }
            header .title-text {
                font-size: 1.1em; /* ปรับขนาดข้อความชื่อสาขา */
            }

            header .logout-button {
                font-size: 12px;
                padding: 5px 10px;
            }

            .container {
                margin: 15px;
            }

            .card {
                height: 100px;
                font-size: 14px;
            }

            .card .icon {
                font-size: 2em;
                margin-bottom: 5px;
            }
        }
    </style>
</head>

<body>

    <header>
        <!-- *** ส่วนที่แก้ไข: แสดงชื่อสาขา *** -->
        <span class="title-text">สวัสดี, <?php echo htmlspecialchars($officer_name); ?>! | หน้าหลักเจ้าหน้าที่ (สาขา: <?php echo htmlspecialchars($officerProvinceName); ?>)</span>
        <a href="index.php" class="logout-button">ออกจากระบบ</a> <!-- เปลี่ยนไป officer_logout.php -->
    </header>

    <div class="container">
        <a href="officer_dashboard.php" class="card">
            <div class="icon">⚠️</div> <!-- ไอคอนแจ้งเตือน -->
            <span>แจ้งห้องไม่พร้อมใช้งาน</span>
        </a>
        <a href="counter_operations.php" class="card">
            <div class="icon">🏨</div> <!-- ไอคอนเงิน/ปรับ -->
            <span>รับลูกค้า-walkin</span>
            
        </a>
            <a href="customer_reception.php" class="card">
            <div class="icon">🛎️</div> <!-- ไอคอนโรงแรม/รับลูกค้า -->
            <span>เข้าพักออนไลน์</span>
        </a>

        <a href="occupancy_stats.php" class="card">
            <div class="icon">📈</div> <!-- ไอคอนโรงแรม/รับลูกค้า -->
            <span>สรุปรายงานแต่ละเดือน</span>
        </a>

          <a href="officer_add_room.php" class="card">
            <div class="icon">🛌</div> <!-- ไอคอนโรงแรม/รับลูกค้า -->
            <span>เพิ่มห้องพักใหม่</span>
        </a>
    </div>

    <footer>
    </footer>

</body>

</html>