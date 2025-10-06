<?php 
session_start();
// ตรวจสอบ session เจ้าหน้าที่
// หากไม่มี session Email_Officer แสดงว่ายังไม่ได้ล็อกอิน ให้ redirect ไปหน้า login
if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php");
    exit();
}

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
        <a href="counter_operations.php" class="card">
            <div class="icon">🏨</div> <!-- ไอคอนเงิน/ปรับ -->
            <span>รับลูกค้า</span>
        </a>
        <a href="occupancy_stats.php" class="card">
            <div class="icon">📈</div> <!-- ไอคอนโรงแรม/รับลูกค้า -->
            <span>สรปุรายงานแต่ละเดือน</span>
        </a>

        <a href="customer_reception.php" class="card">
            <div class="icon">🛎️</div> <!-- ไอคอนโรงแรม/รับลูกค้า -->
            <span>เข้าพักออนไลน์</span>
        </a>
    </div>

    <footer>
    </footer>

</body>

</html>