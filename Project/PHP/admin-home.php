<?php
session_start(); // ต้องเรียกใช้ session_start() ที่ด้านบนสุดของทุกหน้าที่จะใช้ session

// ตรวจสอบว่ามีค่า First_name และ Last_name ใน Session หรือไม่
$admin_first_name = $_SESSION['First_name'] ?? 'ผู้ดูแลระบบ'; // กำหนดค่าเริ่มต้นหากไม่มีใน Session
$admin_last_name = $_SESSION['Last_name'] ?? '';

// รวมชื่อ-สกุล
$admin_name = "คุณ" . $admin_first_name . " " . $admin_last_name;

// หากต้องการให้แสดงแค่ชื่อต้น (เช่น "คุณสมชาย") สามารถใช้โค้ดนี้แทน:
// $admin_name = "คุณ" . $admin_first_name;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <title>หน้าแอดมิน</title>
    <link rel="stylesheet" href="../CSS/css/admin-home.css">
    <style>
        /* CSS สำหรับ Header */
        .header {
            background-color: #34495e;
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative; /* สำคัญ: เพื่อให้ปุ่มออกจากระบบสามารถจัดตำแหน่งแบบ absolute ได้ */
            display: flex; /* ใช้ flexbox เพื่อจัดวางเนื้อหาใน header */
            align-items: center; /* จัดกึ่งกลางแนวตั้ง */
            justify-content: center; /* จัดกึ่งกลางแนวนอนสำหรับ h1, p */
            min-height: 80px; /* กำหนดความสูงขั้นต่ำของ header */
        }

        .header h1, .header .welcome-message {
            margin: 0;
            padding: 0 10px; /* เพิ่ม padding เพื่อไม่ให้ข้อความชิดขอบเกินไป */
            line-height: 1.2;
        }

        /* CSS สำหรับปุ่ม "ออกจากระบบ" */
        .logout-link {
            text-decoration: none;
            color: #e74c3c; /* สีแดงสำหรับข้อความ */
            font-weight: bold;
            padding: 10px 15px; /* เพิ่ม padding เพื่อให้ปุ่มมีขนาดใหญ่ขึ้น */
            border-radius: 8px; /* ปรับลดความโค้งมนเล็กน้อย */
            background-color: #fff; /* พื้นหลังสีขาว */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            
            /* จัดตำแหน่งแบบ Absolute ให้อยู่มุมขวาบน */
            position: absolute;
            top: 50%; /* จัดกึ่งกลางแนวตั้งของ header */
            right: 20px; /* ระยะห่างจากขอบขวา */
            transform: translateY(-50%); /* ปรับตำแหน่งให้กึ่งกลางสมบูรณ์แบบแนวตั้ง */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* เพิ่มเงาเล็กน้อยเพื่อให้มีมิติ */
            white-space: nowrap; /* ป้องกันข้อความแตกบรรทัด */
        }

        .logout-link:hover {
            background-color: #fcebeb; /* เปลี่ยนสีพื้นหลังเมื่อวางเมาส์ */
            transform: translateY(-50%) scale(1.03); /* ขยายเล็กน้อยเมื่อวางเมาส์ */
            box-shadow: 0 4px 10px rgba(0,0,0,0.15); /* เพิ่มเงาเมื่อวางเมาส์ */
        }

        /* เพิ่ม media query สำหรับหน้าจอขนาดเล็กถ้าจำเป็น */
        @media (max-width: 768px) {
            .header {
                flex-direction: column; /* วางเรียงจากบนลงล่าง */
                padding: 15px;
                min-height: unset;
            }
            .logout-link {
                position: static; /* ให้ปุ่มกลับมาอยู่ใน flow ปกติ */
                margin-top: 15px; /* เพิ่มระยะห่างด้านบน */
                transform: none; /* ยกเลิก transform */
                width: fit-content; /* ปรับความกว้างให้พอดีเนื้อหา */
                align-self: center; /* จัดกึ่งกลางเมื่อเป็น flex item */
            }
        }

        /* CSS อื่นๆ (admin-home.css ที่มีอยู่เดิม) */
        .container {
            max-width: 960px;
            margin: 30px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .admin-menu {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap; /* ให้เมนูขึ้นบรรทัดใหม่เมื่อจอเล็ก */
        }

        .menu-item {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px 30px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 180px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .menu-item:hover {
            background-color: #e9f5ff;
            border-color: #a0d4ff;
            color: #007bff;
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .menu-item .icon {
            font-size: 3em;
            margin-bottom: 15px;
            line-height: 1;
        }

        .menu-item span {
            font-size: 1.1em;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            color: #777;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <div> <!-- ใช้ div นี้สำหรับจัดกลุ่ม h1 และ p เพื่อให้เนื้อหาหลักอยู่ตรงกลาง -->
            <h1>หน้าผู้ดูแลระบบ</h1>
            <p class="welcome-message">ยินดีต้อนรับ, <?php echo htmlspecialchars($admin_name); ?>!</p>
        </div>
        <a href="index.php" class="logout-link">ออกจากระบบ</a>
    </div>

    <div class="container">
        <div class="admin-menu">
            <a href="admin.php" class="menu-item">
                <div class="icon">💰</div>
                <span>ตรวจสอบการชำระเงิน</span>
            </a>
            <a href="add_officer.php" class="menu-item">
                <div class="icon">➕</div>
                <span>เพิ่มเจ้าหน้าที่</span>
            </a>
             <a href="manage_officers.php" class="menu-item">
                <div class="icon">➖</div>
                <span>ลบเจ้าหน้าที่</span>
            </a>
        </div>


        <div class="footer">
            <!-- อาจมีเนื้อหา footer เพิ่มเติม -->
        </div>
    </div>
</body>

</html>