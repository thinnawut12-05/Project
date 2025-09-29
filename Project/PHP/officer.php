<?php
session_start();
// ตัวอย่างเช็ค session เจ้าหน้าที่
$officer_name = $_SESSION['First_name'] ?? "เจ้าหน้าที่";
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
            /* เพิ่มเพื่อจัด footer ไม่ให้ทับเนื้อหา */
            flex-direction: column;
            /* เพิ่มเพื่อจัด footer ไม่ให้ทับเนื้อหา */
            min-height: 100vh;
            /* เพิ่มเพื่อจัด footer ไม่ให้ทับเนื้อหา */
        }

        header {
            background-color: #2a5d9f;
            color: white;
            text-align: center;
            padding: 20px 0;
            font-size: 24px;
            position: relative;
            /* สำหรับจัดตำแหน่งปุ่มออกจากระบบ */
        }

        header .logout-button {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #f44336;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.2s;
        }

        header .logout-button:hover {
            background-color: #d32f2f;
        }

        .container {
            flex: 1;
            /* ทำให้ container ขยายเพื่อดัน footer ลงไปด้านล่าง */
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            /* จัดให้อยู่กึ่งกลางในแนวตั้ง */
            margin: 30px;
            gap: 20px;
        }

        .card {
            background-color: white;
            width: 250px;
            height: 150px;
            display: flex;
            flex-direction: column;
            /* เปลี่ยนเป็น column เพื่อจัดไอคอนและข้อความในแนวตั้ง */
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
            /* ขนาดไอคอน */
            margin-bottom: 10px;
            /* ระยะห่างระหว่างไอคอนกับข้อความ */
        }

        .card span {
            font-weight: bold;
        }

        footer {
            text-align: center;
            padding: 15px;
            background-color: #eee;
            /* position: fixed; */
            /* ลบ fixed ออกเพราะ body มี flex แล้ว */
            width: 100%;
            /* bottom: 0; */
            margin-top: auto;
            /* ดัน footer ไปด้านล่างสุด */
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.05);
            /* เพิ่มเงาเล็กน้อย */
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                gap: 15px;
            }

            .card {
                width: 90%;
                /* ทำให้การ์ดกว้างขึ้นบนมือถือ */
                max-width: 300px;
                /* จำกัดความกว้างสูงสุด */
                height: 120px;
                /* ปรับความสูง */
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
        }

        @media (max-width: 480px) {
            header {
                font-size: 20px;
                padding: 15px 0;
            }

            header .logout-button {
                font-size: 12px;
                padding: 5px 10px;
                right: 10px;
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
        สวัสดี, <?php echo htmlspecialchars($officer_name); ?>! | หน้าหลักเจ้าหน้าที่
        <a href="index.php" class="logout-button">ออกจากระบบ</a>
    </header>

    <div class="container">
        <a href="officer_dashboard.php" class="card">
            <div class="icon">⚠️</div> <!-- ไอคอนแจ้งเตือน -->
            <span>แจ้งห้องไม่พร้อมใช้งาน</span>
        </a>
        <a href="adjustment.php" class="card">
            <div class="icon">💸</div> <!-- ไอคอนเงิน/ปรับ -->
            <span>แจ้งปรับ</span>
        </a>
        <a href="receive_customer.php" class="card">
            <div class="icon">🏨</div> <!-- ไอคอนโรงแรม/รับลูกค้า -->
            <span>รับลูกค้า</span>
        </a>
    </div>

    <!-- เพิ่มส่วน footer เพื่อให้ครบถ้วน -->
    <footer>
    </footer>

</body>

</html>