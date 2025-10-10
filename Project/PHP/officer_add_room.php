<?php
session_start(); // เริ่มต้น session

// 1. การตรวจสอบสิทธิ์ (Authentication Check)
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

// 3. ดึงข้อมูลเจ้าหน้าที่ที่เข้าสู่ระบบจาก session
$loggedInOfficerEmail = $_SESSION['Email_Officer'];
$officerProvinceId = $_SESSION['Province_id'];

$message = '';
$error = '';

// ดึงข้อมูลประเภทห้องพักสำหรับ dropdown
$roomTypes = [];
$sqlRoomTypes = "SELECT Room_type_id, Room_type_name FROM room_type ORDER BY Room_type_name ASC";
$resultRoomTypes = $conn->query($sqlRoomTypes);
if ($resultRoomTypes->num_rows > 0) {
    while ($row = $resultRoomTypes->fetch_assoc()) {
        $roomTypes[] = $row;
    }
} else {
    $error = "ไม่พบข้อมูลประเภทห้องพักในฐานข้อมูล กรุณาเพิ่มข้อมูลประเภทห้องพักก่อน.";
}

// 4. จัดการการเพิ่มห้องพักใหม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_room_confirmed'])) { // เปลี่ยนชื่อปุ่มเป็น add_room_confirmed
    $roomId = trim($_POST['room_id']);
    $roomNumber = trim($_POST['room_number']);
    $numOfPeople = trim($_POST['number_of_people_staying']);
    $roomDetails = trim($_POST['room_details']);
    $roomTypeId = trim($_POST['room_type_id']);
    $price = trim($_POST['price']); // รับค่า price

    // ค่าเริ่มต้นสำหรับห้องใหม่
    $status = "AVL"; // สถานะเริ่มต้นคือ "พร้อมใช้งาน"
    $emailOfficer = NULL; // เมื่อพร้อมใช้งาน ไม่ต้องระบุอีเมลเจ้าหน้าที่

    // การตรวจสอบข้อมูล (Validation)
    if (empty($roomId) || empty($roomNumber) || empty($numOfPeople) || empty($roomTypeId) || empty($price)) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน: รหัสห้องพัก, หมายเลขห้อง, จำนวนคนเข้าพัก, ประเภทห้องพัก และ ราคา.";
    } elseif (!preg_match('/^R\d{5}$/', $roomId)) { // ตรวจสอบรูปแบบ Room_id เช่น R00001
        $error = "รหัสห้องพักต้องเริ่มต้นด้วย 'R' ตามด้วยตัวเลข 5 หลัก (เช่น R00001).";
    } elseif (!is_numeric($roomNumber) || $roomNumber <= 0) {
        $error = "หมายเลขห้องไม่ถูกต้อง ต้องเป็นตัวเลขบวก.";
    } elseif (!is_numeric($numOfPeople) || $numOfPeople <= 0) {
        $error = "จำนวนคนเข้าพักไม่ถูกต้อง ต้องเป็นตัวเลขบวก.";
    } elseif (!is_numeric($price) || $price <= 0) { // ตรวจสอบ price
        $error = "ราคาไม่ถูกต้อง ต้องเป็นตัวเลขบวก.";
    } else {
        // ตรวจสอบ Room_id ซ้ำกัน
        $stmtCheck = $conn->prepare("SELECT Room_id FROM room WHERE Room_id = ? AND Province_id = ?");
        $stmtCheck->bind_param("si", $roomId, $officerProvinceId);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows > 0) {
            $error = "ไม่สามารถเพิ่มห้องพักได้: รหัสห้องพัก <strong>" . htmlspecialchars($roomId) . "</strong> มีอยู่ในระบบแล้วสำหรับสาขานี้.";
        }
        $stmtCheck->close();
    }

    if (empty($error)) { // หากไม่มีข้อผิดพลาดจากการตรวจสอบ
        $stmt = $conn->prepare("INSERT INTO room (Room_id, Price, Room_number, Number_of_people_staying, Room_details, Status, Email_Officer, Province_id, Room_type_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        // เพิ่ม Price เข้าไปใน bind_param: s (Room_id), d (Price), i (Room_number), i (NumOfPeople), s (Room_details), s (Status), s (Email_Officer), i (Province_id), i (Room_type_id)
        $stmt->bind_param("sdiisssii", $roomId, $price, $roomNumber, $numOfPeople, $roomDetails, $status, $emailOfficer, $officerProvinceId, $roomTypeId);

        if ($stmt->execute()) {
            $message = "เพิ่มห้องพัก <strong>" . htmlspecialchars($roomId) . "</strong> สำเร็จแล้ว!";
            // เคลียร์ค่าในฟอร์มหลังจากเพิ่มสำเร็จ (ถ้าต้องการ)
            // หรือสามารถ redirect ไปหน้า dashboard ได้
            // header("Location: officer_dashboard.php?message=" . urlencode($message));
            // exit;
        } else {
            $error = "เกิดข้อผิดพลาดในการเพิ่มห้องพัก: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <title>เจ้าหน้าที่: เพิ่มห้องพักใหม่</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding-top: 20px;
            box-sizing: border-box;
        }

        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 800px;
            margin-bottom: 20px;
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

        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.3s ease;
            margin-top: 0px;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        /* Form styling */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: calc(100% - 22px); /* Full width minus padding and border */
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box; /* Include padding in width */
        }

        .form-group textarea {
            resize: vertical; /* Allow vertical resizing */
            min-height: 80px;
        }

        .form-group select {
            width: 100%; /* Ensure select takes full width */
        }

        .form-actions {
            text-align: center;
            margin-top: 20px;
        }

        .form-actions .btn-submit {
            background-color: #007bff; /* Blue for submit */
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }

        .form-actions .btn-submit:hover {
            background-color: #0056b3;
        }

        /* Modal styles (from previous task) */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
            display: flex; /* Use flexbox for centering */
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
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        .exclamation-icon {
            font-size: 60px;
            color: #ffc107;
            line-height: 1;
            margin-bottom: 20px;
            border: 4px solid #ffc107;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            margin: 0 auto 20px auto;
            animation: pulse 1.5s infinite ease-in-out;
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
            text-align: left; /* Make message left-aligned for better readability of details */
            word-wrap: break-word; /* Ensure long details wrap */
        }
        /* Specifically for showing details in the modal */
        .modal-details {
            font-size: 15px;
            color: #444;
            line-height: 1.6;
            margin-top: 10px;
            padding-left: 15px; /* Indent details */
            text-align: left;
            border-left: 3px solid #eee;
        }
        .modal-details strong {
            color: #000;
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
            background-color: #28a745; /* Green for confirmation */
            color: white;
        }

        #confirmBtn:hover {
            background-color: #218838;
        }

        #cancelBtn {
            background-color: #6c757d; /* Grey, similar to the image */
            color: white;
        }

        #cancelBtn:hover {
            background-color: #5a6268;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="officer.php" class="btn-back">กลับหน้าเจ้าหน้าที่ดูแลระบบ</a>
        <h1>เพิ่มห้องพักใหม่</h1>
        
        <?php if (!empty($loggedInOfficerEmail) && $officerProvinceId !== null): ?>
            <p>เจ้าหน้าที่: <strong><?php echo htmlspecialchars($loggedInOfficerEmail); ?> (สาขา: <?php echo htmlspecialchars($officerProvinceId); ?>)</strong></p>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="success-message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <h2>กรอกข้อมูลห้องพักใหม่</h2>
        <!-- Form ที่จะเก็บข้อมูล แต่จะถูก submit โดย JavaScript ผ่าน popup -->
        <form id="addRoomForm" method="POST" action="">
            <div class="form-group">
                <label for="room_id">รหัสห้องพัก (เช่น R00001):</label>
                <input type="text" id="room_id" name="room_id" required value="<?php echo isset($_POST['room_id']) ? htmlspecialchars($_POST['room_id']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="room_number">หมายเลขห้อง:</label>
                <input type="number" id="room_number" name="room_number" min="1" required value="<?php echo isset($_POST['room_number']) ? htmlspecialchars($_POST['room_number']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="number_of_people_staying">จำนวนคนเข้าพักสูงสุด:</label>
                <input type="number" id="number_of_people_staying" name="number_of_people_staying" min="1" required value="<?php echo isset($_POST['number_of_people_staying']) ? htmlspecialchars($_POST['number_of_people_staying']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="price">ราคาต่อคืน (บาท):</label>
                <input type="number" id="price" name="price" min="0" step="0.01" required value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="room_type_id">ประเภทห้องพัก:</label>
                <select id="room_type_id" name="room_type_id" required>
                    <option value="">เลือกประเภทห้องพัก</option>
                    <?php foreach ($roomTypes as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['Room_type_id']); ?>"
                            <?php echo (isset($_POST['room_type_id']) && $_POST['room_type_id'] == $type['Room_type_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['Room_type_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="room_details">รายละเอียดห้องพัก (ไม่บังคับ):</label>
                <textarea id="room_details" name="room_details" placeholder="เช่น ห้องพักปกติ (พร้อมใช้งาน)"><?php echo isset($_POST['room_details']) ? htmlspecialchars($_POST['room_details']) : 'ห้องพักปกติ (พร้อมใช้งาน)'; ?></textarea>
            </div>
            <!-- Province_id จะถูกส่งมาจาก session โดยอัตโนมัติ ไม่ต้องให้ผู้ใช้กรอก -->
            <input type="hidden" name="province_id" value="<?php echo htmlspecialchars($officerProvinceId); ?>">

            <div class="form-actions">
                <!-- เปลี่ยนเป็น type="button" เพื่อให้ JavaScript จัดการการ submit -->
                <button type="button" id="triggerAddRoomModal" class="btn-submit">เพิ่มห้องพัก</button>
            </div>
        </form>
    </div>

    <!-- The confirmation modal/popup -->
    <div id="confirmationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-body">
                <div class="exclamation-icon">!</div>
                <h2 id="modalTitle">ยืนยันการเพิ่มห้องพัก?</h2>
                <p>คุณต้องการเพิ่มห้องพักด้วยข้อมูลดังต่อไปนี้ใช่หรือไม่?</p>
                <div class="modal-details">
                    <strong>รหัสห้องพัก:</strong> <span id="modal_room_id"></span><br>
                    <strong>หมายเลขห้อง:</strong> <span id="modal_room_number"></span><br>
                    <strong>จำนวนคนเข้าพัก:</strong> <span id="modal_number_of_people_staying"></span><br>
                    <strong>ราคาต่อคืน:</strong> <span id="modal_price"></span><br>
                    <strong>ประเภทห้องพัก:</strong> <span id="modal_room_type_name"></span><br>
                    <strong>รายละเอียด:</strong> <span id="modal_room_details"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button id="confirmBtn" class="btn">ยืนยันการเพิ่ม</button>
                <button id="cancelBtn" class="btn">ยกเลิก</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addRoomForm = document.getElementById('addRoomForm');
            const triggerAddRoomModal = document.getElementById('triggerAddRoomModal');
            const confirmationModal = document.getElementById('confirmationModal');
            const confirmBtn = document.getElementById('confirmBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            // Elements in the modal to display room details
            const modalRoomId = document.getElementById('modal_room_id');
            const modalRoomNumber = document.getElementById('modal_room_number');
            const modalNumberOfPeople = document.getElementById('modal_number_of_people_staying');
            const modalPrice = document.getElementById('modal_price');
            const modalRoomTypeName = document.getElementById('modal_room_type_name');
            const modalRoomDetails = document.getElementById('modal_room_details');
            const roomTypeSelect = document.getElementById('room_type_id');

            let formData = {}; // Object to store form data temporarily

            // Function to hide the modal
            function hideModal() {
                confirmationModal.style.display = 'none';
            }

            // Event listener for the "เพิ่มห้องพัก" button
            triggerAddRoomModal.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default form submission

                // Basic client-side validation (can be more robust)
                if (!addRoomForm.checkValidity()) {
                    addRoomForm.reportValidity(); // Show browser's default validation messages
                    return;
                }

                // Collect all form data
                formData = {
                    room_id: document.getElementById('room_id').value,
                    room_number: document.getElementById('room_number').value,
                    number_of_people_staying: document.getElementById('number_of_people_staying').value,
                    price: parseFloat(document.getElementById('price').value).toFixed(2), // Format price
                    room_type_id: roomTypeSelect.value,
                    room_type_name: roomTypeSelect.options[roomTypeSelect.selectedIndex].text,
                    room_details: document.getElementById('room_details').value || 'ห้องพักปกติ (พร้อมใช้งาน)',
                    province_id: document.querySelector('input[name="province_id"]').value
                };

                // Populate modal with collected data
                modalRoomId.textContent = formData.room_id;
                modalRoomNumber.textContent = formData.room_number;
                modalNumberOfPeople.textContent = formData.number_of_people_staying;
                modalPrice.textContent = formData.price + ' บาท';
                modalRoomTypeName.textContent = formData.room_type_name;
                modalRoomDetails.textContent = formData.room_details;

                confirmationModal.style.display = 'flex'; // Show the modal
            });

            // Event listener for the "ยืนยันการเพิ่ม" (Confirm) button in the modal
            confirmBtn.addEventListener('click', function() {
                // Create a temporary form to submit the data
                const tempForm = document.createElement('form');
                tempForm.method = 'POST';
                tempForm.action = ''; // Submit to the current page

                // Add all collected data as hidden inputs
                for (const key in formData) {
                    if (formData.hasOwnProperty(key)) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = formData[key];
                        tempForm.appendChild(input);
                    }
                }

                // Add a hidden input to indicate that the submission is confirmed
                const confirmedInput = document.createElement('input');
                confirmedInput.type = 'hidden';
                confirmedInput.name = 'add_room_confirmed'; // This name is used in PHP to trigger insertion
                confirmedInput.value = '1';
                tempForm.appendChild(confirmedInput);

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