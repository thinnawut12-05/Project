<?php
session_start();
include 'db.php'; // ตรวจสอบว่า db.php เชื่อมต่อฐานข้อมูลเรียบร้อย

date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php");
    exit();
}

$officer_email = $_SESSION['Email_Officer'];
$officer_fname = $_SESSION['First_name'];
$officer_lname = $_SESSION['Last_name'];
$current_province_id = $_SESSION['Province_id'];

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

$reservation_id = $_GET['reservation_id'] ?? '';
$amount = $_GET['amount'] ?? 0;
$selected_room_id = $_GET['room_id'] ?? ''; // รับ Room_ID ที่เลือกมา

if (empty($reservation_id) || $amount <= 0 || empty($selected_room_id)) {
    $_SESSION['error'] = "ไม่พบข้อมูลการจอง Walk-in ที่ถูกต้องสำหรับการชำระเงิน.";
    header("Location: counter_operations.php");
    exit();
}

// ดึงรายละเอียดการจองสำหรับแสดงผล
$guest_name = '';
$checkin_date = '';
$checkout_date = '';
$num_adults = 0;
$num_children = 0;
$room_number_display = '';
$room_type_name_display = '';

$stmt_reservation_details = $conn->prepare("
    SELECT r.Guest_name, r.Booking_date, r.Check_out_date, r.Number_of_adults, r.Number_of_children,
           rm.Room_number, rt.Room_type_name
    FROM reservation r
    JOIN room rm ON rm.Room_ID = ? AND rm.Province_id = r.Province_Id
    JOIN room_type rt ON rm.Room_type_Id = rt.Room_type_Id
    WHERE r.Reservation_Id = ? AND r.Province_Id = ?
");
if ($stmt_reservation_details) {
    $stmt_reservation_details->bind_param("ssi", $selected_room_id, $reservation_id, $current_province_id);
    $stmt_reservation_details->execute();
    $result_res_details = $stmt_reservation_details->get_result();
    if ($row_res = $result_res_details->fetch_assoc()) {
        $guest_name = $row_res['Guest_name'];
        $checkin_date = $row_res['Booking_date'];
        $checkout_date = $row_res['Check_out_date'];
        $num_adults = $row_res['Number_of_adults'];
        $num_children = $row_res['Number_of_children'];
        $room_number_display = $row_res['Room_number'];
        $room_type_name_display = $row_res['Room_type_name'];
    }
    $stmt_reservation_details->close();
}

$conn->close();

$page_title = "การชำระเงินการจอง Walk-in";
$detail_header = "รายละเอียดการจอง Walk-in";
$info_html = "
    <p><strong>รหัสการจอง:</strong> " . htmlspecialchars($reservation_id) . "</p>
    <p><strong>ชื่อลูกค้า:</strong> " . htmlspecialchars($guest_name) . "</p>
    <p><strong>ห้องพัก:</strong> " . htmlspecialchars($room_number_display) . " (" . htmlspecialchars($room_type_name_display) . ")</p>
    <p><strong>เช็คอิน:</strong> " . htmlspecialchars($checkin_date) . "</p>
    <p><strong>เช็คเอาท์:</strong> " . htmlspecialchars($checkout_date) . "</p>
    <p><strong>ผู้ใหญ่:</strong> " . htmlspecialchars($num_adults) . " ท่าน, <strong>เด็ก:</strong> " . htmlspecialchars($num_children) . " ท่าน</p>
";

// === สร้าง QR Code PromptPay ===
$phone = "0967501732"; // เบอร์ PromptPay ที่คุณต้องการใช้
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
        /* CSS สามารถคัดลอกมาจาก payment_adjustment.php หรือจาก counter_operations.php ได้เลย */
        body { font-family: 'Kanit', sans-serif; margin: 0; padding: 0; background-color: #f4f7f6; color: #333; line-height: 1.6; }
        header { background-color: #007bff; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        header h1 { margin: 0; font-size: 1.8em; }
        .user-info { font-size: 1em; }
        .user-info a { color: #ffc107; text-decoration: none; margin-left: 15px; font-weight: bold; transition: color 0.3s ease; }
        .user-info a:hover { color: #fff; }
        .container { max-width: 800px; margin: 30px auto; background-color: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); text-align: center; }
        h2 { text-align: center; color: #007bff; margin-bottom: 30px; font-size: 2em; }
        .adjustment-details { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 25px; text-align: left; }
        .adjustment-details h3 { color: #007bff; margin-top: 0; margin-bottom: 15px; font-size: 1.5em; text-align: center; }
        .adjustment-details p { margin-bottom: 10px; font-size: 1.1em; }
        .total-amount { font-size: 2em; font-weight: bold; color: #dc3545; margin-top: 20px; margin-bottom: 30px; text-align: center; }
        .qr-section { margin: 20px 0; text-align: center; }
        .qr-section img { border: 1px solid #ccc; padding: 10px; border-radius: 10px; }
        .payment-options button { padding: 12px 25px; font-size: 1.1em; font-weight: bold; border: none; border-radius: 5px; cursor: pointer; margin: 10px; transition: background-color 0.3s ease; }
        .payment-options .btn-confirm { background-color: #28a745; color: white; }
        .payment-options .btn-confirm:hover { background-color: #218838; }
        .payment-options .btn-cancel { background-color: #6c757d; color: white; }
        .payment-options .btn-cancel:hover { background-color: #5a6268; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: bold; text-align: center; }
        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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
            <p><strong>ยอดที่ต้องชำระ:</strong></p>
            <div class="total-amount">฿<?= number_format($amount, 2) ?></div>
        </div>

        <div class="qr-section">
            <p><strong>สแกนเพื่อชำระเงิน (PromptPay):</strong></p>
            <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code ชำระเงิน" width="220" height="220">
            <p style="font-size: 0.9em; color: #666;">เบอร์โทรศัพท์สำหรับ PromptPay: <?= htmlspecialchars($phone) ?></p>
        </div>

        <div class="payment-options">
            <p><strong>ดำเนินการชำระเงินแล้วหรือไม่?</strong></p>
            <form action="process_walkin_payment.php" method="POST">
                <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($reservation_id) ?>">
                <input type="hidden" name="amount" value="<?= htmlspecialchars($amount) ?>">
                <input type="hidden" name="selected_room_id" value="<?= htmlspecialchars($selected_room_id) ?>">
                <input type="hidden" name="guest_name" value="<?= htmlspecialchars($guest_name) ?>">
                <input type="hidden" name="checkin_date" value="<?= htmlspecialchars($checkin_date) ?>">
                <input type="hidden" name="checkout_date" value="<?= htmlspecialchars($checkout_date) ?>">
                <!-- ไม่จำเป็นต้องส่ง num_adults, num_children ไปที่ process_walkin_payment.php โดยตรงหากไม่ได้ใช้ แต่สามารถส่งไปได้ -->
                <!-- <input type="hidden" name="num_adults" value="<?= htmlspecialchars($num_adults) ?>"> -->
                <!-- <input type="hidden" name="num_children" value="<?= htmlspecialchars($num_children) ?>"> -->

                <button type="submit" name="action" value="confirm_walkin_payment" class="btn-confirm">
                    <i class="fas fa-check-circle"></i> ยืนยันการชำระเงินและเช็คอิน
                </button>
                <button type="button" onclick="window.location.href='counter_operations.php'" class="btn-cancel">
                    <i class="fas fa-times-circle"></i> ยกเลิกการจอง Walk-in / กลับไป
                </button>
            </form>
        </div>
    </main>
</body>
</html>