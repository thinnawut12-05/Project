<?php
session_start();
include 'db.php';

if (!isset($_SESSION["admin"]) || empty($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูลจาก officer (ไม่ใช้ ORDER BY ก่อน)
$result = $conn->query("SELECT * FROM officer");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Manage Officer</title>
    <style>
        body { font-family: Arial; background: #f7f7f7; }
        h2 { text-align: center; margin: 20px; }
        table {
            width: 80%; margin: 20px auto;
            border-collapse: collapse; background: white;
        }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: center; }
        th { background: #007BFF; color: white; }
        a.btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; color: white; }
        .edit { background: orange; }
        .delete { background: red; }
        .add { display: block; width: 200px; margin: 20px auto; text-align: center; background: green; }
        .logout { text-align: center; margin-top: 20px; }
        .logout a { color: red; font-weight: bold; text-decoration: none; }
    </style>
</head>
<body>
    <h2>👨‍💼 จัดการเจ้าหน้าที่</h2>
    <a href="add_officer.php" class="btn add">+ เพิ่มเจ้าหน้าที่</a>
    <table>
        <tr>
            <th>รหัส</th>
            <th>ชื่อ-นามสกุล</th>
            <th>Email</th>
            <th>สาขา</th>
            <th>การจัดการ</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= reset($row); ?></td> <!-- ใช้คอลัมน์แรกของตาราง -->
                <td><?= $row['First_name'] ?? '' ?> <?= $row['Last_name'] ?? '' ?></td>
                <td><?= $row['Email'] ?? '' ?></td>
                <td><?= $row['Branch_Id'] ?? '' ?></td>
                <td>
                    <a href="edit_officer.php?id=<?= reset($row); ?>" class="btn edit">แก้ไข</a>
                    <a href="delete_officer.php?id=<?= reset($row); ?>" class="btn delete" onclick="return confirm('ยืนยันการลบ?');">ลบ</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <div class="logout">
        <a href="logout.php">ออกจากระบบ</a>
    </div>
</body>
</html>
