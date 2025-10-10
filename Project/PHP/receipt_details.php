<?php
session_start();
include 'db.php'; // ตรวจสอบให้แน่ใจว่า db.php อยู่ในตำแหน่งที่ถูกต้อง

// เปิดการแสดงข้อผิดพลาดทั้งหมดสำหรับการดีบัก
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection failed in receipt_details.php: " . ($conn->connect_error ?? 'Connection object not initialized.'));
    die("Error: ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
}
$conn->set_charset("utf8");

// ตรวจสอบว่าได้รับ receipt_id จาก URL หรือไม่
$receipt_id = $_GET['receipt_id'] ?? null;

if (!$receipt_id) {
    header("Location: home.php?error=no_receipt_id_specified"); // เปลี่ยนเส้นทางกลับหน้าหลักหากไม่มี ID
    exit();
}

// --- ดึงข้อมูลทั้งหมดที่จำเป็นจากตาราง `receipt`, `reservation`, `member`, `province` ---
$receipt_data = [];

$sql_fetch_all_details = "
    SELECT
        rc.Receipt_Id, rc.Guest_name AS Receipt_Guest_name, rc.Receipt_date, rc.Receipt_time,
        rc.Phone_number AS Receipt_Phone_number, rc.Payment_image_file, rc.Email_Admin, rc.Status AS Receipt_Status,
        res.Reservation_Id, res.Guest_name AS Res_Guest_name, res.Number_of_rooms,
        res.Number_of_adults, res.Number_of_children, res.Booking_date, res.Check_out_date,
        res.Total_price, res.Email_member,
        p.Province_name, p.Address AS Province_Address, p.Phone AS Province_Phone, p.Region_Id,
        m.Phone_number AS Member_Phone_number
    FROM receipt rc
    LEFT JOIN reservation res ON rc.Receipt_Id = res.Receipt_Id
    LEFT JOIN province p ON res.Province_Id = p.Province_Id
    LEFT JOIN member m ON res.Email_member = m.Email_member
    WHERE rc.Receipt_Id = ?
";

$stmt_fetch_details = $conn->prepare($sql_fetch_all_details);

if ($stmt_fetch_details === false) {
    error_log("Failed to prepare statement for fetching all details in receipt_details.php: " . $conn->error);
    die("<p class='error'>❌ เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับดึงรายละเอียด: " . $conn->error . "</p>");
}

// ผูกพารามิเตอร์ (ใช้ 'i' หาก Receipt_Id เป็น INT, หรือ 's' หากเป็น VARCHAR/BIGINT)
$stmt_fetch_details->bind_param("i", $receipt_id); // Receipt_Id เป็น INT ตามที่คุณแจ้ง
$stmt_fetch_details->execute();
$result_details = $stmt_fetch_details->get_result();

if ($row = $result_details->fetch_assoc()) {
    $receipt_data = $row;

    // --- DEBUGGING: Log raw time from DB and PHP timezone ---
    error_log("DEBUG: receipt_details.php - DB Receipt_date (raw): " . ($receipt_data['Receipt_date'] ?? 'N/A'));
    error_log("DEBUG: receipt_details.php - DB Receipt_time (raw): " . ($receipt_data['Receipt_time'] ?? 'N/A'));
    error_log("DEBUG: receipt_details.php - PHP Default Timezone: " . date_default_timezone_get());
    // --- END DEBUGGING ---

    // คำนวณจำนวนคืน
    $num_nights = 0;
    if (!empty($receipt_data['Booking_date']) && !empty($receipt_data['Check_out_date'])) {
        try {
            $checkin_obj = new DateTime($receipt_data['Booking_date']);
            $checkout_obj = new DateTime($receipt_data['Check_out_date']);
            $interval = $checkin_obj->diff($checkout_obj);
            $num_nights = max(1, (int)$interval->days);
        } catch (Exception $e) {
            $num_nights = 0;
            error_log("ERROR: receipt_details.php - Error calculating nights: " . $e->getMessage());
        }
    }
    $receipt_data['Num_Nights'] = $num_nights;

    // กำหนด base URL สำหรับรูปภาพ (สำคัญ: ต้องปรับให้ตรงกับโครงสร้างจริง)
    // ถ้า receipt_details.php อยู่ที่ C:\xampp\htdocs\dom-inn\Project\PHP\
    // และโฟลเดอร์ uploads/receipts/ อยู่ที่ C:\xampp\htdocs\dom-inn\Project\PHP\uploads\receipts\
    $base_web_path_for_images = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $image_full_url = $base_web_path_for_images . "/dom-inn/Project/PHP/uploads/receipts/" . ($receipt_data['Payment_image_file'] ?? '');
} else {
    // ไม่พบข้อมูลใบเสร็จ
    error_log("WARNING: receipt_details.php - Receipt details not found for ID: " . htmlspecialchars($receipt_id));
    header("Location: home.php?error=receipt_details_not_found&id=" . htmlspecialchars($receipt_id));
    exit();
}
$stmt_fetch_details->close();
$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูลเมื่อเสร็จสิ้น

// เตรียมข้อมูลสำหรับแสดงผล
$hotel_name = $receipt_data['Province_name'] ?? 'ไม่ระบุสาขา';
$hotel_address = $receipt_data['Province_Address'] ?? 'ไม่ระบุที่อยู่';
$hotel_phone = $receipt_data['Province_Phone'] ?? 'ไม่ระบุเบอร์โทร';
$guest_name_display = $receipt_data['Res_Guest_name'] ?? $receipt_data['Receipt_Guest_name'] ?? 'ไม่ระบุ';
$guest_email_display = $receipt_data['Email_member'] ?? 'ไม่ระบุ';
$guest_phone_display = $receipt_data['Member_Phone_number'] ?? $receipt_data['Receipt_Phone_number'] ?? 'ไม่ระบุ';

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จรับเงิน #<?= htmlspecialchars($receipt_id) ?></title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .receipt-container {
            width: 800px;
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            box-sizing: border-box;
            color: #333;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }

        .header-section .dom-inn-logo {
            /* เปลี่ยนชื่อ class */
            font-size: 3em;
            font-weight: bold;
            color: #008489;
            /* สีตามต้องการ */
        }

        .header-section .company-info {
            text-align: left;
            font-size: 0.9em;
            line-height: 1.4;
        }

        .header-section .contact-info {
            text-align: right;
            font-size: 0.9em;
            line-height: 1.4;
        }

        .receipt-title {
            text-align: center;
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 30px;
            color: #333;
        }

        .customer-address-section,
        .description-section {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 25px;
            background-color: #f9f9f9;
        }

        .customer-address-section h3,
        .description-section h3 {
            font-size: 1.2em;
            margin-top: 0;
            margin-bottom: 15px;
            color: #008489;
        }

        .customer-address-section p,
        .description-section p {
            margin: 5px 0;
        }

        .description-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .description-table th,
        .description-table td {
            border: 1px solid #eee;
            padding: 10px;
            text-align: left;
        }

        .description-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .amount-section {
            width: 40%;
            float: right;
            margin-top: 20px;
        }

        .amount-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .amount-section th,
        .amount-section td {
            border: none;
            padding: 8px 0;
            text-align: right;
        }

        .amount-section th {
            font-weight: normal;
        }

        .grand-total {
            font-size: 1.5em;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
            text-align: right;
        }

        .grand-total span {
            color: #008489;
        }

        .footer-section {
            clear: both;
            margin-top: 50px;
            text-align: center;
            font-size: 0.9em;
            color: #777;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            border-top: 1px dashed #ccc;
            padding-top: 20px;
        }

        .signature-section .left-signature,
        .signature-section .right-signature {
            text-align: center;
            width: 45%;
        }

        .signature-section .stamp-image {
            max-width: 150px;
            height: auto;
            margin-bottom: 10px;
        }

        .signature-section .dom-inn-logo-small {
            /* เปลี่ยนชื่อ class */
            width: 100px;
            /* Adjust size as needed */
            height: auto;
        }

        .payment-slip-image {
            margin-top: 30px;
            text-align: center;
        }

        .payment-slip-image img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            padding: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }

        .action-buttons a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            transition: background-color 0.3s;
        }

        .action-buttons a:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <div class="header-section">
            <div class="company-info">
                <div class="dom-inn-logo">Dom Inn</div> <!-- เปลี่ยนเป็นชื่อโรงแรมของคุณ -->
                <p><strong>Address:</strong><br>
                    <?= htmlspecialchars($hotel_address) ?><br>
                    <?= htmlspecialchars($hotel_name) ?>, Thailand</p>
            </div>
            <div class="contact-info">
                <p><strong>Contact Us/Mailing Address:</strong><br>
                    Dom Inn Hotel (Regional Operating Headquarters)<br>
                    Branch: <?= htmlspecialchars($hotel_name) ?><br>
                    Phone: <?= htmlspecialchars($hotel_phone) ?></p>
            </div>
        </div>

        <div class="receipt-title">RECEIPT</div>

        <div class="customer-address-section">
            <h3>Customer Details</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($guest_name_display) ?></p>
            <p><strong>Email Address:</strong> <?= htmlspecialchars($guest_email_display) ?></p>
            <p><strong>Phone Number:</strong> <?= htmlspecialchars($guest_phone_display) ?></p>
            <p><strong>Booking ID:</strong> #<?= htmlspecialchars($receipt_data['Reservation_Id'] ?? 'N/A') ?></p>
            <p><strong>Receipt ID:</strong> #<?= htmlspecialchars($receipt_data['Receipt_Id']) ?></p>
            <p><strong>Charge Date:</strong> <?= htmlspecialchars($receipt_data['Receipt_date'] ?? 'N/A') ?> <?= htmlspecialchars($receipt_data['Receipt_time'] ?? 'N/A') ?></p>
        </div>

        <div class="description-section">
            <h3>Booking Details</h3>
            <table class="description-table">
                <thead>
                    <tr>
                        <th>Hotel Branch</th>
                        <th>Period</th>
                        <th>Room Type</th> <!-- สามารถดึง Room_type_Id จาก reservation มาแล้วใช้ JOIN กับ room_type_name เพื่อแสดงได้ถ้ามี -->
                        <th># of Rms.</th>
                        <th># of Guests</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($hotel_name) ?></td>
                        <td><?= htmlspecialchars($receipt_data['Booking_date'] ?? 'N/A') ?> - <?= htmlspecialchars($receipt_data['Check_out_date'] ?? 'N/A') ?> (<?= htmlspecialchars($receipt_data['Num_Nights']) ?> Nights)</td>
                        <td>Standard Room</td> <!-- ตรงนี้อาจต้องดึงข้อมูลประเภทห้องจริงจากตาราง room_type มาแสดง -->
                        <td><?= htmlspecialchars($receipt_data['Number_of_rooms'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($receipt_data['Number_of_adults'] ?? 0) ?> Adult(s), <?= htmlspecialchars($receipt_data['Number_of_children'] ?? 0) ?> Child(ren)</td>
                        <td>฿ <?= number_format($receipt_data['Total_price'] ?? 0, 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="amount-section">
            <table>
                <tr>
                    <th>Total Room Charges</th>
                    <td>฿ <?= number_format($receipt_data['Total_price'] ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <th>Total Extra Charges</th>
                    <td>฿ 0.00</td> <!-- ไม่มีข้อมูลนี้ จึงใส่ 0 -->
                </tr>
                <tr class="grand-total">
                    <th>GRAND TOTAL</th>
                    <td><span>฿ <?= number_format($receipt_data['Total_price'] ?? 0, 2) ?></span></td>
                </tr>
            </table>
        </div>

        <div class="footer-section">

        </div>

        <div class="signature-section">
            <div class="left-signature">
                <!-- คุณสามารถใส่รูปภาพลายเซ็นหรือตราประทับตรงนี้ได้ -->
                <!-- <img src="path/to/company_stamp.png" alt="Company Stamp" class="stamp-image"> -->
                <p>Authorized Stamp & Signature</p>
                <p>.......................................</p>
            </div>
            <div class="right-signature">
                <img src="../src/images/4.png" alt="Dom Inn Logo" class="dom-inn-logo-small"> <!-- โลโก้โรงแรมของคุณ -->
                <p>Dom Inn Hotel</p>
            </div>
        </div>

        <div class="action-buttons">
            <a href="booking_status_pending.php">กลับสู่สถานะการจองของฉัน</a>
            <a href="home.php">กลับหน้าหลัก</a>
        </div>
    </div>
</body>

</html>