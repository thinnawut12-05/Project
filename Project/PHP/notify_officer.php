<?php
session_start();
include 'db.php';

// ตรวจสอบว่าเข้าสู่ระบบแล้ว
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// ✅ ถ้ามีการกดปุ่มแจ้ง
if (isset($_GET['notify_id'])) {
    $reservation_id = intval($_GET['notify_id']);
    $sql_update = "UPDATE reservation SET Notified = 1 WHERE Reservation_Id = $reservation_id";
    $conn->query($sql_update);

    // ส่ง flag success กลับมา
    header("Location: notify_officer.php?success=1");
    exit();
}

// ดึงข้อมูลการจอง + เจ้าหน้าที่ + สาขา
$sql = "SELECT r.Reservation_Id, r.Guest_name, r.Number_of_rooms, r.Booking_date, r.Check_out_date, 
               r.Number_of_adults, r.Number_of_children, r.Notified,
               p.Province_name, 
               o.First_name AS Officer_First, o.Last_name AS Officer_Last, o.Phone_number AS Officer_Phone
        FROM reservation r
        LEFT JOIN province p ON r.Province_Id = p.Province_Id
        LEFT JOIN officer o ON r.Province_Id = o.Province_Id
        ORDER BY r.Booking_date ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แจ้งเจ้าหน้าที่แต่ละสาขา</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <style>
        body {
            font-family: Tahoma, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 95%;
            margin: 30px auto;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }

        table th,
        table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        table th {
            background: #3498db;
            color: #fff;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: #fff;
            text-decoration: none;
        }

        .btn-notify {
            background: #27ae60;
        }

        .btn-notify:hover {
            background: #219150;
        }

        .btn-disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }

        .btn-logout {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            text-decoration: none;
            background: #e74c3c;
            color: white;
            border-radius: 5px;
            text-align: center;
            width: 150px;
        }

        .btn-logout:hover {
            background: #c0392b;
        }

        /* ================= Modal Style ================= */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: #fff;
            margin: 15% auto;
            padding: 20px;
            width: 350px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.4s;
        }

        .modal-content h3 {
            margin-bottom: 15px;
            color: #27ae60;
        }

        .modal-content button {
            padding: 10px 20px;
            border: none;
            background: #27ae60;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-content button:hover {
            background: #219150;
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

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>📢 การแจ้งเจ้าหน้าที่แต่ละสาขา</h2>
        <table>
            <tr>
                <th>รหัสการจอง</th>
                <th>ชื่อผู้จอง</th>
                <th>จำนวนห้อง</th>
                <th>ผู้ใหญ่</th>
                <th>เด็ก</th>
                <th>วันที่เข้าพัก</th>
                <th>วันที่ออก</th>
                <th>สาขา</th>
                <th>เจ้าหน้าที่</th>
                <th>เบอร์โทรเจ้าหน้าที่</th>
                <th>แจ้ง</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['Reservation_Id'] ?></td>
                    <td><?= $row['Guest_name'] ?></td>
                    <td><?= $row['Number_of_rooms'] ?></td>
                    <td><?= $row['Number_of_adults'] ?></td>
                    <td><?= $row['Number_of_children'] ?></td>
                    <td><?= $row['Booking_date'] ?></td>
                    <td><?= $row['Check_out_date'] ?></td>
                    <td><?= $row['Province_name'] ?? "-" ?></td>
                    <td><?= $row['Officer_First'] . " " . $row['Officer_Last'] ?></td>
                    <td><?= $row['Officer_Phone'] ?></td>
                    <td>
                        <?php if ($row['Notified'] == 1): ?>
                            <button class="btn-action btn-disabled" disabled>แจ้งแล้ว</button>
                        <?php else: ?>
                            <a href="?notify_id=<?= $row['Reservation_Id'] ?>" class="btn-action btn-notify">แจ้งเจ้าหน้าที่</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <a href="logout.php" class="btn-logout">ออกจากระบบ</a>
        <a href="admin-home.php" class="btn-back">กลับหน้าผู้ดูแลระบบ</a>
    </div>

    <!-- ✅ Modal แจ้งเตือน -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <h3>✅ แจ้งเจ้าหน้าที่เรียบร้อยแล้ว</h3>
            <button onclick="closeModal()">ตกลง</button>
        </div>
    </div>

    <script>
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        // ✅ ถ้ามี success=1 ใน URL ให้โชว์ modal
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            document.getElementById('successModal').style.display = 'block';
        <?php endif; ?>
    </script>

</body>

</html>