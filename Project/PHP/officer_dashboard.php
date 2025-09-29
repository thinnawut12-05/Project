<?php
session_start(); // เริ่มต้น session

// ** 1. การตรวจสอบสิทธิ์ (Authentication Check) **
if (!isset($_SESSION['Email_Officer']) || !isset($_SESSION['Province_id'])) {
    header("Location: officer_login.php");
    exit;
}


// 2. เชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. ดึงข้อมูลเจ้าหน้าที่
$loggedInOfficerEmail = $_SESSION['Email_Officer'];
$officerProvinceId = $_SESSION['Province_id'];

$message = '';
$error = '';

if ($officerProvinceId !== null) {
    // 4. แจ้งห้องไม่พร้อมใช้งาน
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_unavailable'])) {
        $roomId = $_POST['room_id'];
        $reason = !empty($_POST['unavailable_reason']) ? $_POST['unavailable_reason'] : 'ไม่มีเหตุผลระบุ';
        $newRoomDetails = "ไม่พร้อมใช้งาน: " . htmlspecialchars($reason) . " (แจ้งโดย: " . htmlspecialchars($loggedInOfficerEmail) . ")";

        $stmt = $conn->prepare("UPDATE room SET room_details = ? WHERE Room_id = ? AND Province_id = ?");
        $stmt->bind_param("ssi", $newRoomDetails, $roomId, $officerProvinceId);

        if ($stmt->execute()) {
            $message = "ห้อง " . htmlspecialchars($roomId) . " ถูกแจ้งว่าไม่พร้อมใช้งานแล้ว.";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
        $stmt->close();
    }

    // 5. แจ้งห้องพร้อมใช้งาน
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_available'])) {
        $roomId = $_POST['room_id'];
        $defaultRoomDetails = "ห้องพักปกติ (พร้อมใช้งาน)";

        $stmt = $conn->prepare("UPDATE room SET room_details = ? WHERE Room_id = ? AND Province_id = ?");
        $stmt->bind_param("ssi", $defaultRoomDetails, $roomId, $officerProvinceId);

        if ($stmt->execute()) {
            $message = "ห้อง " . htmlspecialchars($roomId) . " ถูกแจ้งว่าพร้อมใช้งานแล้ว.";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
        $stmt->close();
    }
}

// 6. ดึงข้อมูลห้องพักทั้งหมด (ตัด Number_of_people_staying ออก)
$rooms = [];
if ($officerProvinceId !== null) {
    $sql = "SELECT Room_id, Room_number, room_details FROM room WHERE Province_id = ? ORDER BY Room_id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $officerProvinceId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <title>ระบบแจ้งห้องไม่พร้อมใช้งานสำหรับเจ้าหน้าที่</title>
    <link rel="stylesheet" href="../CSS/css/unavailable_rooms.css">
</head>

<body>
    <div class="container">
        <h1>ระบบแจ้งห้องไม่พร้อมใช้งานสำหรับเจ้าหน้าที่</h1>
        <?php if (!empty($loggedInOfficerEmail) && $officerProvinceId !== null): ?>
            <p>เจ้าหน้าที่: <strong><?php echo htmlspecialchars($loggedInOfficerEmail); ?> (สาขา: <?php echo htmlspecialchars($officerProvinceId); ?>)</strong></p>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="success-message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <h2>รายการห้องพัก</h2>
        <table>
            <thead>
                <tr>
                    <th>รหัสห้องพัก</th>
                    <th>หมายเลขห้อง</th>
                    <th>รายละเอียดห้องพัก</th>
                    <th>สถานะ</th>
                    <th>ดำเนินการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rooms) > 0): ?>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($room['Room_id']); ?></td>
                            <td><?php echo htmlspecialchars($room['Room_number']); ?></td>
                            <td><?php echo htmlspecialchars($room['room_details']); ?></td>
                            <td>
                                <?php
                                if (strpos($room['room_details'], 'ไม่พร้อมใช้งาน') !== false) {
                                    echo '<span class="status-unavailable">ไม่พร้อมใช้งาน</span>';
                                } else {
                                    echo '<span class="status-available">พร้อมใช้งาน</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if (strpos($room['room_details'], 'ไม่พร้อมใช้งาน') === false): ?>
                                    <form method="POST" action="" style="display:inline-block;">
                                        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['Room_id']); ?>">
                                        <input type="text" name="unavailable_reason" placeholder="ระบุเหตุผล (ถ้ามี)" class="reason-input">
                                        <button type="submit" name="mark_unavailable" class="btn btn-unavailable">แจ้งห้องไม่พร้อมใช้งาน</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="" style="display:inline-block;">
                                        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['Room_id']); ?>">
                                        <button type="submit" name="mark_available" class="btn btn-available">แจ้งห้องพร้อมใช้งาน</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">ไม่พบข้อมูลห้องพัก</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>