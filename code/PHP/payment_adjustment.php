<?php
session_start();
include 'db.php'; // ตรวจสอบว่า db.php เชื่อมต่อฐานข้อมูลเรียบร้อย

// ตั้งค่า default timezone
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบการเข้าสู่ระบบเจ้าหน้าที่
if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php");
    exit();
}

$officer_email = $_SESSION['Email_Officer'];
$officer_fname = $_SESSION['First_name'];
$officer_lname = $_SESSION['Last_name'];
$current_province_id = $_SESSION['Province_id'];

// ดึงชื่อจังหวัด/สาขา
$province_name = '';
$stmt_province = $conn->prepare("SELECT Province_name FROM province WHERE Province_ID = ?");
if ($stmt_province) {
    $stmt_province->bind_param("i", $current_province_id);
    $stmt_province->execute();
    $result_province = $stmt_province->get_result();
    if ($result_province->num_rows > 0) {
        $province_data = $result_province->fetch_assoc();
        $province_name = $province_data['Province_name'];
    }
    $stmt_province->close();
}

$adjustment_type = $_GET['type'] ?? '';
$amount = $_GET['amount'] ?? 0;
$stay_id = $_GET['stay_id'] ?? '';
$room_id = $_GET['room_id'] ?? '';
$reservation_id = $_GET['reservation_id'] ?? '';

$page_title = "การชำระเงินค่าปรับ";
$detail_header = "รายละเอียดการปรับ";
$info_html = '';

if ($adjustment_type === 'damage') {
    $page_title = "การชำระเงินค่าเสียหายห้องพัก";
    $detail_header = "รายละเอียดค่าเสียหายห้องพัก";
    $info_html = "
        <p><strong>รหัสการเข้าพัก:</strong> " . htmlspecialchars($stay_id) . "</p>
        <p><strong>รหัสห้องพัก:</strong> " . htmlspecialchars($room_id) . "</p>
        <p><strong>ประเภทการปรับ:</strong> ค่าเสียหายห้องพัก</p>
    ";

    // ดึงรายละเอียดความเสียหาย
    $stmt_damage_info = $conn->prepare("SELECT Damage_item, Damage_description 
                                        FROM room_damages 
                                        WHERE Stay_Id = ? AND Room_Id = ? 
                                        ORDER BY Damage_date DESC LIMIT 1");
    if ($stmt_damage_info) {
        $stmt_damage_info->bind_param("ss", $stay_id, $room_id);
        $stmt_damage_info->execute();
        $result_damage_info = $stmt_damage_info->get_result();
        if ($row_damage = $result_damage_info->fetch_assoc()) {
            $info_html .= "
                <p><strong>รายการเสียหาย:</strong> " . htmlspecialchars($row_damage['Damage_item']) . "</p>
                <p><strong>รายละเอียด:</strong> " . htmlspecialchars($row_damage['Damage_description']) . "</p>
            ";
        }
        $stmt_damage_info->close();
    }
} elseif ($adjustment_type === 'penalty') {
    $page_title = "การชำระเงินค่าปรับ No-show";
    $detail_header = "รายละเอียดค่าปรับ No-show";
    $info_html = "
        <p><strong>รหัสการจอง:</strong> " . htmlspecialchars($reservation_id) . "</p>
        <p><strong>ประเภทการปรับ:</strong> ค่าปรับผู้เข้าพักไม่มาเช็คอิน (No-show)</p>
    ";
    // ดึงเหตุผลการปรับ
    $stmt_penalty_info = $conn->prepare("SELECT Penalty_reason FROM reservation WHERE Reservation_Id = ?");
    if ($stmt_penalty_info) {
        $stmt_penalty_info->bind_param("s", $reservation_id);
        $stmt_penalty_info->execute();
        $result_penalty_info = $stmt_penalty_info->get_result();
        if ($row_penalty = $result_penalty_info->fetch_assoc()) {
            $info_html .= "
                <p><strong>เหตุผลการปรับ:</strong> " . htmlspecialchars($row_penalty['Penalty_reason']) . "</p>
            ";
        }
        $stmt_penalty_info->close();
    }
} else {
    $_SESSION['error'] = "ไม่พบข้อมูลการปรับที่ถูกต้อง.";
    header("Location: counter_operations.php");
    exit();
}

$conn->close();

// === สร้าง QR Code PromptPay ===
$phone = "0967501732"; // เบอร์ PromptPay
$qr_url = "https://promptpay.io/$phone/$amount.png";
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Dom Inn Hotel</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f6;
            color: #333;
            line-height: 1.6;
        }

        header {
            background-color: #007bff;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            margin: 0;
            font-size: 1.8em;
        }

        .user-info {
            font-size: 1em;
        }

        .user-info a {
            color: #ffc107;
            text-decoration: none;
            margin-left: 15px;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .user-info a:hover {
            color: #fff;
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 30px;
            font-size: 2em;
        }

        .adjustment-details {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: left;
        }

        .adjustment-details h3 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.5em;
            text-align: center;
        }

        .adjustment-details p {
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .total-amount {
            font-size: 2em;
            font-weight: bold;
            color: #dc3545;
            margin-top: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .qr-section {
            margin: 20px 0;
            text-align: center;
        }

        .qr-section img {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 10px;
        }

        .payment-options button {
            padding: 12px 25px;
            font-size: 1.1em;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px;
            transition: background-color 0.3s ease;
        }

        .payment-options .btn-confirm {
            background-color: #28a745;
            color: white;
        }

        .payment-options .btn-confirm:hover {
            background-color: #218838;
        }

        .payment-options .btn-cancel {
            background-color: #6c757d;
            color: white;
        }

        .payment-options .btn-cancel:hover {
            background-color: #5a6268;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <header>
        <h1>ระบบจัดการโรงแรม - การปฏิบัติงานเคาน์เตอร์</h1>
        <div class="user-info">
            สวัสดี, <?= htmlspecialchars($officer_fname . " " . $officer_lname); ?>
            (สาขา: <?= htmlspecialchars($province_name); ?>)
            <a href="index.php">ออกจากระบบ</a>
            <a href="officer.php">กลับหน้าหลักเจ้าหน้าที่</a>
        </div>
    </header>

    <main class="container">
        <h2><?= htmlspecialchars($page_title) ?></h2>

        <?php
        if (isset($_SESSION['message'])) {
            echo '<div class="alert success">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <div class="adjustment-details">
            <h3><?= htmlspecialchars($detail_header) ?></h3>
            <?= $info_html ?>
            <p><strong>มูลค่าที่ต้องชำระ:</strong></p>
            <div class="total-amount">฿<?= number_format($amount, 2) ?></div>
        </div>

        <!-- ส่วนแสดง QR Code -->
        <div class="qr-section">
            <p><strong>สแกนเพื่อชำระเงิน:</strong></p>
            <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code ชำระเงิน" width="220" height="220">
        </div>

        <div class="payment-options">
            <p><strong>ยืนยันการชำระเงินค่าปรับ/ค่าเสียหาย?</strong></p>
            <form action="process_payment_adjustment.php" method="POST">
                <input type="hidden" name="type" value="<?= htmlspecialchars($adjustment_type) ?>">
                <input type="hidden" name="amount" value="<?= htmlspecialchars($amount) ?>">
                <input type="hidden" name="stay_id" value="<?= htmlspecialchars($stay_id) ?>">
                <input type="hidden" name="room_id" value="<?= htmlspecialchars($room_id) ?>">
                <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($reservation_id) ?>">

                <button type="submit" name="action" value="confirm_payment" class="btn-confirm">
                    <i class="fas fa-check-circle"></i> ยืนยันการชำระเงินแล้ว
                </button>
                <button type="button" onclick="window.location.href='counter_operations.php'" class="btn-cancel">
                    <i class="fas fa-times-circle"></i> ยกเลิก / กลับไป
                </button>
            </form>
        </div>
    </main>
</body>

</html>