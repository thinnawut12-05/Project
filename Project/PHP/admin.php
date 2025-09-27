<?php
session_start();
include 'db.php'; // เชื่อมต่อฐานข้อมูล

$error = "";

// --- ตรรกะการล็อกอิน ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password_input = $_POST['password'] ?? ''; // ใช้ชื่อตัวแปรใหม่สำหรับรหัสผ่านที่ผู้ใช้ป้อน

    // 1. ดึงข้อมูลผู้ดูแลระบบจากฐานข้อมูลโดยใช้อีเมล
    // *** สำคัญ: ไม่ดึง Password มาเปรียบเทียบตรงๆ ใน SQL แล้ว ***
    // เราจะดึงค่าแฮชจากฐานข้อมูลมาเปรียบเทียบด้วย password_verify() ใน PHP
    $sql = "SELECT Email_Admin, Password, First_name, Last_name FROM admin WHERE Email_Admin=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email); // 's' สำหรับ string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $hashed_password_from_db = $row['Password']; // ดึงรหัสผ่านที่ถูกแฮชจากฐานข้อมูล

        // 2. ใช้ password_verify เพื่อตรวจสอบรหัสผ่านที่ผู้ใช้ป้อน
        if (password_verify($password_input, $hashed_password_from_db)) {
            // รหัสผ่านถูกต้อง
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $row['Email_Admin'];
            $_SESSION['admin_name'] = $row['First_name'] . " " . $row['Last_name'];
            // สามารถเพิ่มการ redirect ไปหน้า dashboard ได้ที่นี่หากต้องการ
            // header("Location: admin_dashboard.php"); // เช่น ถ้ามีหน้า dashboard แยก
            // exit();
        } else {
            // รหัสผ่านไม่ถูกต้อง
            $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง!";
        }
    } else {
        // ไม่พบอีเมลในระบบ (หรือไม่มีผู้ใช้ที่มีอีเมลนี้)
        // เพื่อความปลอดภัย ไม่ควรบอกว่าอีเมลถูกต้องแต่รหัสผ่านผิด หรืออีเมลผิด
        // ควรให้ข้อความแจ้งเตือนรวมๆ กัน
        $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง!";
    }
    $stmt->close();
}

// --- ถ้ายังไม่ได้ล็อกอิน ให้แสดงฟอร์มเข้าสู่ระบบ ---
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']):
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบแอดมิน - Dom Inn Hotel</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="../CSS/css/admin.css">
    <style>
        /* สไตล์ CSS สำหรับหน้า Login */
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #34495e; /* สีพื้นหลังเหมือน navbar */
        }
        .login-box {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            text-align: center;
            width: 350px;
            max-width: 90%;
        }
        .login-box h2 {
            color: #34495e;
            margin-bottom: 30px;
        }
        .login-box input[type="email"],
        .login-box input[type="password"] {
            width: calc(100% - 20px);
            padding: 12px 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .login-box button {
            background-color: #3498db;
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s ease;
            width: 100%;
        }
        .login-box button:hover {
            background-color: #2980b9;
        }
        .login-box .error {
            color: #e74c3c;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .login-box a {
            display: block;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9em;
        }
        .login-box a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>เข้าสู่ระบบแอดมิน</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="อีเมล" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <button type="submit" name="login">เข้าสู่ระบบ</button>
        </form>
        <a href="index.php">⬅ กลับหน้าหลัก</a>
    </div>
</body>
</html>
<?php
    exit(); // หยุดการทำงานของสคริปต์หลังจากแสดงหน้าล็อกอิน
endif;

// --- โค้ดส่วนนี้จะทำงานเมื่อผู้ดูแลระบบล็อกอินสำเร็จแล้วเท่านั้น ---

// --- ตรรกะสำหรับการอัปเดตสถานะการจอง ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['status'])) {
    $reservation_id = $_POST['reservation_id'];
    $status = $_POST['status'];
    $admin_email = $_SESSION['admin_email'] ?? NULL;

    if ($status == 3) { // อนุมัติ (สถานะ 3)
        $sql_update = "UPDATE reservation SET Booking_status_Id = ?, Email_Admin = ? WHERE Reservation_Id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iss", $status, $admin_email, $reservation_id); // 'i' สำหรับ integer, 's' สำหรับ string
    } else { // ปฏิเสธ (สถานะ 5) หรือสถานะอื่นๆ
        $sql_update = "UPDATE reservation SET Booking_status_Id = ? WHERE Reservation_Id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("is", $status, $reservation_id); // 'i' สำหรับ integer, 's' สำหรับ string
    }

    $stmt_update->execute();
    $stmt_update->close();
    // ไม่มีการ Redirect หลังจากอัปเดตสถานะ อาจจะทำให้ผู้ใช้สับสน
    // โดยปกติควรจะ redirect กลับไปหน้าเดิมเพื่อป้องกันการส่งข้อมูลซ้ำ
    // หรือมีการ refresh หน้าเพื่อแสดงข้อมูลที่อัปเดต
    header("Location: " . $_SERVER['PHP_SELF']); // Redirect เพื่อ refresh หน้า
    exit();
}

// ดึงข้อมูล reservation ทั้งหมดเพื่อแสดงในตาราง พร้อม JOIN ตาราง province
$sql_select = "SELECT r.*, b.Booking_status_name, p.Province_name
               FROM reservation r
               LEFT JOIN booking_status b ON r.Booking_status_Id = b.Booking_status_Id
               LEFT JOIN province p ON r.Province_Id = p.Province_Id -- เพิ่ม LEFT JOIN กับตาราง province
               ORDER BY r.Booking_time DESC";
$result = $conn->query($sql_select);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แผงควบคุมผู้ดูแลระบบ - Dom Inn Hotel</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <style>
        /* CSS สำหรับหน้า Admin Dashboard (ส่วนใหญ่เหมือนเดิม) */
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .admin-navbar {
            background-color: #34495e;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .admin-navbar ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .admin-navbar ul li {
            margin-right: 20px;
        }
        .admin-navbar ul li a {
            color: #ecf0f1;
            text-decoration: none;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .admin-navbar ul li a:hover,
        .admin-navbar ul li a.active {
            background-color: #1abc9c;
        }

        .welcome-text {
            color: #ecf0f1;
            font-weight: bold;
            margin-right: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: #3498db;
            color: #fff;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f8f8f8;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            font-size: 0.9em;
            transition: background 0.3s ease;
            margin: 2px;
            display: inline-block;
            text-decoration: none;
        }

        .btn-approve {
            background: #2ecc71;
        }
        .btn-approve:hover {
            background: #27ae60;
        }

        .btn-reject {
            background: #e74c3c;
        }
        .btn-reject:hover {
            background: #c0392b;
        }

        .receipt-thumbnail {
            width: 70px;
            height: auto;
            border-radius: 5px;
            border: 1px solid #ddd;
            vertical-align: middle;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .receipt-thumbnail:hover {
            transform: scale(1.05);
        }

        .status-text {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            color: #333;
            display: inline-block;
        }
        /* ชื่อคลาสสำหรับสถานะต้องตรงกับค่า Booking_status_name ที่ถูกแปลงแล้ว */
        .status-ชำระเงินสำเร็จแล้ว { background-color: #2ecc71; color: #fff; } /* เพิ่มเติม: หากสถานะจริงคือ "ชำระเงินสำเร็จแล้ว" */
        .status-ชำระเงินสำเร็จสำหรับการตรวจสอบ { background-color: #f39c12; color: #333; }
        .status-ปฏิเสธ { background-color: #e74c3c; color: #fff; } /* เพิ่มเติม: หากสถานะจริงคือ "ปฏิเสธ" */
        /* ถ้ามีสถานะอื่นๆ ต้องเพิ่ม class ที่นี่ */


        .no-file-text {
            color: #333;
            font-weight: bold;
            font-size: 0.9em;
        }

        .logout-link {
            text-decoration: none;
            color: #e74c3c;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 5px;
            background-color: #fff;
            transition: background-color 0.3s ease;
            float: right;
            margin-top: 5px;
        }
        .logout-link:hover {
            background-color: #fdd;
        }
    </style>
</head>
<body>
    <div class="admin-navbar">
        <div class="welcome-text">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></div>
        <ul>
            <li><a href="#"">แจ้งเจ้าหน้าที่</a></li>
            <li><a href="#">จัดการเจ้าหน้าที่</a></li>
        </ul>
        <a href="logout.php" class="logout-link">ออกจากระบบ</a> <!-- ควรมีไฟล์ logout.php แยกต่างหาก -->
    </div>

    <h2>ตรวจสอบหลักฐานการโอนเงิน</h2>
    <table>
        <thead>
            <tr>
                <th>รหัสจอง</th>
                <th>ชื่อผู้เข้าพัก</th>
                <th>จำนวนห้อง</th>
                <th>เช็คอิน</th>
                <th>เช็คเอาท์</th>
                <th>ผู้ใหญ่</th>
                <th>เด็ก</th>
                <th>อีเมลลูกค้า</th>
                <th>สาขา</th> <!-- เพิ่มคอลัมน์สาขา -->
                <th>หลักฐานการโอน</th>
                <th>สถานะ</th>
                <th>การกระทำ</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Reservation_Id']) ?></td>
                        <td><?= htmlspecialchars($row['Guest_name']) ?></td>
                        <td><?= htmlspecialchars($row['Number_of_rooms']) ?></td>
                        <td><?= htmlspecialchars($row['Booking_date']) ?></td>
                        <td><?= htmlspecialchars($row['Check_out_date']) ?></td>
                        <td><?= htmlspecialchars($row['Number_of_adults']) ?></td>
                        <td><?= htmlspecialchars($row['Number_of_children']) ?></td>
                        <td><?= htmlspecialchars($row['Email_member']) ?></td>
                        <td><?= htmlspecialchars($row['Province_name'] ?? 'ไม่ระบุ') ?></td> <!-- แสดงชื่อสาขา -->
                        <td>
                            <?php if (!empty($row['receipt_image'])): ?>
                                <a href="uploads/receipts/<?= htmlspecialchars($row['receipt_image']) ?>" target="_blank">
                                    <img src="uploads/receipts/<?= htmlspecialchars($row['receipt_image']) ?>" alt="สลิปโอนเงิน" class="receipt-thumbnail">
                                </a>
                            <?php else: ?>
                                <span class="no-file-text">ไม่มีไฟล์</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                // แปลงชื่อสถานะให้เป็นรูปแบบที่ใช้ใน CSS class
                                // ตัวอย่าง: "ชำระเงินสำเร็จสำหรับการตรวจสอบ" -> "ชำระเงินสำเร็จสำหรับการตรวจสอบ" (ใน CSS ก็ต้องตรงกัน)
                                // หรือถ้า Booking_status_name มีเว้นวรรค อาจจะต้องแทนที่ด้วย -
                                $status_class = str_replace(' ', '-', strtolower($row['Booking_status_name'] ?? 'ไม่ทราบ'));
                            ?>
                            <span class="status-text status-<?= $status_class ?>">
                                <?= htmlspecialchars($row['Booking_status_name'] ?? 'ไม่ทราบ') ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($row['Reservation_Id']) ?>">
                                <input type="hidden" name="status" value="3"> <!-- สถานะ 3 สำหรับอนุมัติ -->
                                <button type="submit" class="btn btn-approve">อนุมัติ</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($row['Reservation_Id']) ?>">
                                <input type="hidden" name="status" value="5"> <!-- สถานะ 5 สำหรับปฏิเสธ -->
                                <button type="submit" class="btn btn-reject">ปฏิเสธ</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="12">ไม่พบข้อมูลการจองที่รอการตรวจสอบ</td> <!-- ปรับ colspan เป็น 12 -->
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

<?php
$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูลเมื่อเสร็จสิ้น
?>