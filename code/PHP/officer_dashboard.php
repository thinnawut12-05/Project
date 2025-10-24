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
$conn->set_charset("utf8"); // ตั้งค่า charset เพื่อรองรับภาษาไทย

// 3. ดึงข้อมูลเจ้าหน้าที่ที่เข้าสู่ระบบจาก session
$loggedInOfficerEmail = $_SESSION['Email_Officer'];
$officerProvinceId = $_SESSION['Province_id'];

// *** ส่วนที่แก้ไข: ดึงชื่อจังหวัดจาก Province_id ***
$officerProvinceName = 'ไม่ระบุสาขา'; // กำหนดค่าเริ่มต้น
if ($officerProvinceId !== null) {
    $stmt_province = $conn->prepare("SELECT Province_name FROM province WHERE Province_Id = ?");
    if ($stmt_province) {
        $stmt_province->bind_param("i", $officerProvinceId);
        $stmt_province->execute();
        $stmt_province->bind_result($provinceNameFromDB);
        if ($stmt_province->fetch()) {
            $officerProvinceName = $provinceNameFromDB;
        }
        $stmt_province->close();
    } else {
        error_log("Error preparing province name statement: " . $conn->error);
    }
}
// *** จบส่วนที่แก้ไข ***

$message = '';
$error = '';

if ($officerProvinceId !== null) {
    // 4. แจ้งห้องไม่พร้อมใช้งาน
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_unavailable'])) {
        $roomId = $_POST['room_id'];
        $reason = !empty($_POST['unavailable_reason']) ? $_POST['unavailable_reason'] : 'ไม่มีเหตุผลระบุ';
        $newRoomDetails = "ไม่พร้อมใช้งาน: " . htmlspecialchars($reason) . " (แจ้งโดย: " . htmlspecialchars($loggedInOfficerEmail) . ")";
        $newStatus = "UNAVL";

        // UPDATE พร้อม Email_Officer ด้วย
        $stmt = $conn->prepare("UPDATE room SET room_details = ?, Status = ?, Email_Officer = ? WHERE Room_id = ? AND Province_id = ?");
        $stmt->bind_param("ssssi", $newRoomDetails, $newStatus, $loggedInOfficerEmail, $roomId, $officerProvinceId);

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
        $defaultStatus = "AVL";

        // Email_Officer set เป็น NULL เมื่อกลับมาพร้อมใช้งาน
        $stmt = $conn->prepare("UPDATE room SET room_details = ?, Status = ?, Email_Officer = NULL WHERE Room_id = ? AND Province_id = ?");
        $stmt->bind_param("sssi", $defaultRoomDetails, $defaultStatus, $roomId, $officerProvinceId);

        if ($stmt->execute()) {
            $message = "ห้อง " . htmlspecialchars($roomId) . " ถูกแจ้งว่าพร้อมใช้งานแล้ว.";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
        $stmt->close();
    }
}

// 6. ดึงข้อมูลห้องพักทั้งหมด
$rooms = [];
if ($officerProvinceId !== null) {
    $sql = "SELECT Room_id, Room_number, room_details, Status, Email_Officer FROM room WHERE Province_id = ? ORDER BY Room_id ASC";
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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Adjust to flex-start to keep content at top */
            min-height: 100vh; /* Ensure full viewport height */
            padding-top: 20px; /* Add some padding at the top */
            box-sizing: border-box; /* Include padding in element's total width and height */
        }

        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 1200px;
            margin-bottom: 20px; /* Add margin at the bottom */
        }

        h1, h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        p {
            text-align: center;
            color: #555;
            font-size: 16px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        th, td {
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: #e6e6f3;
            color: #333;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        td {
            background-color: #f9f9f9;
            color: #444;
            font-size: 14px;
        }
        
        tr:nth-child(even) td {
            background-color: #f2f2f2;
        }

        .status-available {
            color: #28a745; /* Green */
            font-weight: bold;
        }

        .status-unavailable {
            color: #dc3545; /* Red */
            font-weight: bold;
        }

        .reason-input {
            width: 150px; /* Increased width for better input */
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Ensure padding doesn't increase total width */
            margin-right: 5px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-unavailable {
            background: #dc3545; /* Red */
            color: #fff;
        }

        .btn-unavailable:hover {
            background: #c82333;
        }

        .btn-available {
            background: #28a745; /* Green */
            color: #fff;
        }

        .btn-available:hover {
            background: #218838;
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
            margin-top: 0px; /* Adjusted margin */
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }
        
        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
        }

        .modal-content {
            background-color: #fefefe;
            padding: 30px;
            border: 1px solid #888;
            width: 80%; /* Could be adjusted */
            max-width: 500px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
            animation: fadeIn 0.3s ease-out; /* Simple fade-in animation */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        /* Keyframes for the pulse animation */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1); /* Slightly enlarge */
            }
            100% {
                transform: scale(1);
            }
        }

        .exclamation-icon {
            font-size: 60px;
            color: #ffc107; /* Orange, similar to the image */
            line-height: 1;
            margin-bottom: 20px;
            border: 4px solid #ffc107;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex; /* Use flexbox to center '!' vertically and horizontally */
            justify-content: center;
            align-items: center;
            font-weight: bold;
            margin: 0 auto 20px auto; /* Center the icon horizontally */
            animation: pulse 1.5s infinite ease-in-out; /* Apply the pulse animation */
        }

        .modal-content h2 {
            margin-top: 15px;
            margin-bottom: 10px;
            color: #333;
            font-size: 24px;
        }

        .modal-content p {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
        }

        .modal-footer button {
            margin: 0 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            min-width: 100px;
            transition: background-color 0.3s ease;
        }

        #confirmBtn {
            background-color: #dc3545; /* Red, similar to the image */
            color: white;
        }

        #confirmBtn:hover {
            background-color: #c82333;
        }

        #cancelBtn {
            background-color: #6c757d; /* Grey, similar to the image */
            color: white;
        }

        #cancelBtn:hover {
            background-color: #5a6268;
        }
        /* Style for the action column to give enough space */
        th:last-child,
        td:last-child {
            width: 280px; /* Adjust as needed to prevent wrapping */
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="officer.php" class="btn-back">กลับเจ้าหน้าที่ดูแลระบบ</a>
        <h1>ระบบแจ้งห้องไม่พร้อมใช้งานสำหรับเจ้าหน้าที่</h1>
        
        <?php if (!empty($loggedInOfficerEmail) && $officerProvinceId !== null): ?>
            <!-- *** ส่วนที่แก้ไข: แสดงชื่อสาขาแทนรหัสสาขา *** -->
            <p>เจ้าหน้าที่: <strong><?php echo htmlspecialchars($loggedInOfficerEmail); ?> (สาขา: <?php echo htmlspecialchars($officerProvinceName); ?>)</strong></p>
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
                    <th>อีเมลเจ้าหน้าที่</th>
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
                                if ($room['Status'] === 'AVL') {
                                    echo '<span class="status-available">พร้อมใช้งาน</span>';
                                } else {
                                    echo '<span class="status-unavailable">ไม่พร้อมใช้งาน</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($room['Email_Officer']); ?></td>
                            <td>
                                <?php if ($room['Status'] === 'AVL'): ?>
                                    <form method="POST" action="" class="unavailable-form" style="display:inline-block;">
                                        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['Room_id']); ?>">
                                        <input type="text" name="unavailable_reason" placeholder="ระบุเหตุผล (ถ้ามี)" class="reason-input">
                                        <!-- เปลี่ยน type เป็น button และเพิ่ม class สำหรับ JS -->
                                        <button type="button" class="btn btn-unavailable trigger-unavailable-modal">แจ้งห้องไม่พร้อมใช้งาน</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="" class="available-form" style="display:inline-block;">
                                        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['Room_id']); ?>">
                                        <!-- เปลี่ยน type เป็น button และเพิ่ม class สำหรับ JS -->
                                        <button type="button" class="btn btn-available trigger-available-modal">แจ้งห้องพร้อมใช้งาน</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">ไม่พบข้อมูลห้องพัก</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- The confirmation modal/popup -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-body">
                <div class="exclamation-icon">!</div>
                <h2 id="modalTitle"></h2> <!-- จะถูกเติมด้วย JS -->
                <p id="modalMessage"></p> <!-- จะถูกเติมด้วย JS -->
            </div>
            <div class="modal-footer">
                <button id="confirmBtn" class="btn btn-danger">ใช่, แจ้งเลย</button>
                <button id="cancelBtn" class="btn btn-secondary">ยกเลิก</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const unavailableButtons = document.querySelectorAll('.trigger-unavailable-modal');
            const availableButtons = document.querySelectorAll('.trigger-available-modal');
            const confirmationModal = document.getElementById('confirmationModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('confirmBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            let roomIdToProcess = null;
            let reasonToProcess = null;
            let actionType = null; // 'unavailable' or 'available'

            // Function to hide the modal
            function hideModal() {
                confirmationModal.style.display = 'none';
                roomIdToProcess = null;
                reasonToProcess = null;
                actionType = null;
            }

            // Event listener for "แจ้งห้องไม่พร้อมใช้งาน" buttons
            unavailableButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();

                    const form = this.closest('form');
                    roomIdToProcess = form.querySelector('input[name="room_id"]').value;
                    reasonToProcess = form.querySelector('input[name="unavailable_reason"]').value.trim();
                    actionType = 'unavailable';

                    modalTitle.textContent = "ยืนยันการแจ้งห้องไม่พร้อมใช้งาน?";
                    modalMessage.textContent = "เหตุผล: " + (reasonToProcess ? reasonToProcess : 'ไม่มีเหตุผลระบุ');
                    confirmationModal.style.display = 'flex';
                });
            });

            // Event listener for "แจ้งห้องพร้อมใช้งาน" buttons
            availableButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();

                    const form = this.closest('form');
                    roomIdToProcess = form.querySelector('input[name="room_id"]').value;
                    reasonToProcess = null; // No reason for available action
                    actionType = 'available';

                    modalTitle.textContent = "ยืนยันการแจ้งห้องพร้อมใช้งาน?";
                    modalMessage.textContent = "คุณแน่ใจหรือไม่ว่าต้องการเปลี่ยนสถานะห้องนี้ให้พร้อมใช้งาน?";
                    confirmationModal.style.display = 'flex';
                });
            });

            // Event listener for the "ใช่, แจ้งเลย" (Confirm) button in the modal
            confirmBtn.addEventListener('click', function() {
                if (!roomIdToProcess || !actionType) {
                    console.error("Missing room ID or action type for confirmation.");
                    hideModal();
                    return;
                }

                // Create a temporary form to submit the data
                const tempForm = document.createElement('form');
                tempForm.method = 'POST';
                tempForm.action = ''; // Submit to the current page

                // Add room_id
                let roomIdInput = document.createElement('input');
                roomIdInput.type = 'hidden';
                roomIdInput.name = 'room_id';
                roomIdInput.value = roomIdToProcess;
                tempForm.appendChild(roomIdInput);

                if (actionType === 'unavailable') {
                    // Add unavailable_reason
                    let reasonInput = document.createElement('input');
                    reasonInput.type = 'hidden';
                    reasonInput.name = 'unavailable_reason';
                    reasonInput.value = reasonToProcess || ''; // Send empty string if no reason
                    tempForm.appendChild(reasonInput);

                    // Add mark_unavailable flag
                    let markUnavailableInput = document.createElement('input');
                    markUnavailableInput.type = 'hidden';
                    markUnavailableInput.name = 'mark_unavailable';
                    markUnavailableInput.value = '1';
                    tempForm.appendChild(markUnavailableInput);

                } else if (actionType === 'available') {
                    // Add mark_available flag
                    let markAvailableInput = document.createElement('input');
                    markAvailableInput.type = 'hidden';
                    markAvailableInput.name = 'mark_available';
                    markAvailableInput.value = '1';
                    tempForm.appendChild(markAvailableInput);
                }

                // Append the form to the body and submit it
                document.body.appendChild(tempForm);
                tempForm.submit();

                hideModal(); // Hide modal after submission
            });

            // Event listener for the "ยกเลิก" (Cancel) button in the modal
            cancelBtn.addEventListener('click', hideModal);

            // Optional: Hide modal if user clicks outside of modal-content
            confirmationModal.addEventListener('click', function(event) {
                if (event.target === confirmationModal) {
                    hideModal();
                }
            });
        });
    </script>
</body>

</html>