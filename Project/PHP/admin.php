<?php
session_start();
include 'db.php'; // ตรวจสอบว่า db.php เชื่อมต่อฐานข้อมูลเรียบร้อย

// --- ตรวจสอบการเชื่อมต่อฐานข้อมูล ---
if (!$conn) {
    die("❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . mysqli_connect_error());
}

// --- ตรรกะการล็อกอิน ---
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password_input = $_POST['password'] ?? '';

    $sql = "SELECT Email_Admin, Password, First_name, Last_name FROM admin WHERE Email_Admin=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $hashed_password_from_db = $row['Password'];

        if (password_verify($password_input, $hashed_password_from_db)) {
            // เข้าสู่ระบบสำเร็จ
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $row['Email_Admin'];
            $_SESSION['admin_name'] = $row['First_name'] . " " . $row['Last_name'];

            header("Location: " . $_SERVER['PHP_SELF']); // รีเฟรชหน้า
            exit();
        } else {
            $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง!";
        }
    } else {
        $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง!";
    }
    $stmt->close();
}

// --- แสดงฟอร์มล็อกอินถ้ายังไม่ได้เข้าสู่ระบบ ---
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']):
?>
<?php
    exit();
endif;

// --- ตรรกะอัปเดตสถานะการจอง ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['status'])) {
    $reservation_id = $_POST['reservation_id'];
    $status = $_POST['status'];
    $admin_email = $_SESSION['admin_email'] ?? NULL;

    if ($status == 3) { // อนุมัติ
        $sql_update = "UPDATE reservation SET Booking_status_Id = ?, Email_Admin = ? WHERE Reservation_Id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iss", $status, $admin_email, $reservation_id);
    } else { // ปฏิเสธ
        $sql_update = "UPDATE reservation SET Booking_status_Id = ? WHERE Reservation_Id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("is", $status, $reservation_id);
    }
    $stmt_update->execute();
    $stmt_update->close();
    header("Location: " . $_SERVER['PHP_SELF']); // รีเฟรชหน้า
    exit();
}

// --- ดึงข้อมูล reservation ---
$sql_select = "SELECT r.*, b.Booking_status_name, p.Province_name
               FROM reservation r
               LEFT JOIN booking_status b ON r.Booking_status_Id = b.Booking_status_Id
               LEFT JOIN province p ON r.Province_Id = p.Province_Id
               ORDER BY r.Booking_time DESC";
$result = $conn->query($sql_select);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Dom Inn Hotel</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <style>
        /* CSS เบื้องต้น */
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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

        .status-ชำระเงินสำเร็จแล้ว {
            background-color: #2ecc71;
            color: #fff;
        }

        .status-ชำระเงินสำเร็จสำหรับการตรวจสอบ {
            background-color: #f39c12;
            color: #333;
        }

        .status-ปฏิเสธ {
            background-color: #e74c3c;
            color: #fff;
        }

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
           .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
           .btn-back:hover {
            background-color: #5a6268;
        }
    </style>
</head>

<body>
    <div class="admin-navbar">
        <div class="welcome-text">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></div>
         <a href="index.php" class="logout-link">ออกจากระบบ</a>
        <a href="admin-home.php" class="btn-back">กลับหน้าผู้ดูแลระบบ</a>
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
                <th>สาขา</th>
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
                        <td><?= htmlspecialchars($row['Province_name'] ?? 'ไม่ระบุ') ?></td>
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
                            <?php $status_class = str_replace(' ', '-', strtolower($row['Booking_status_name'] ?? 'ไม่ทราบ')); ?>
                            <span class="status-text status-<?= $status_class ?>">
                                <?= htmlspecialchars($row['Booking_status_name'] ?? 'ไม่ทราบ') ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($row['Reservation_Id']) ?>">
                                <input type="hidden" name="status" value="3">
                                <button type="submit" class="btn btn-approve">อนุมัติ</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($row['Reservation_Id']) ?>">
                                <input type="hidden" name="status" value="5">
                                <button type="submit" class="btn btn-reject">ปฏิเสธ</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="12">ไม่พบข้อมูลการจองที่รอการตรวจสอบ</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

<?php
$conn->close();
?>