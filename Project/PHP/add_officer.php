<?php
include 'db.php'; // ตรวจสอบให้แน่ใจว่า db.php อยู่ในตำแหน่งที่ถูกต้อง
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// ตรวจสอบการล็อกอินของแอดมินหรือเจ้าหน้าที่ที่ได้รับสิทธิ์
if (!isset($_SESSION['Email_Officer'])) {
    header("Location: login.php"); // เปลี่ยนเส้นทางไปหน้า login หากยังไม่ได้ล็อกอิน
    exit();
}

$First_name = $_SESSION['First_name'] ?? '';
$Last_name = $_SESSION['Last_name'] ?? '';
$full_name = trim($First_name . ' ' . $Last_name);

if (isset($conn)) {
    $conn->set_charset("utf8");
}

// ดึงข้อมูลจังหวัดสำหรับ dropdown
$provinces = [];
$sql_provinces = "SELECT Province_Id, Province_name FROM province ORDER BY Province_name ASC";
if ($result_provinces = $conn->query($sql_provinces)) {
    while ($row = $result_provinces->fetch_assoc()) {
        $provinces[] = $row;
    }
    $result_provinces->free();
}

$message = ''; // สำหรับแสดงข้อความแจ้งเตือน

// --- ส่วนจัดการการลบเจ้าหน้าที่ (ต้องทำก่อนการแสดงผลรายการ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_officer') {
    $email_to_delete = $_POST['email_to_delete'] ?? '';

    if (empty($email_to_delete)) {
        $_SESSION['delete_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> ไม่พบอีเมลเจ้าหน้าที่ที่ต้องการลบ.</div>';
    } else {
        $sql_delete = "DELETE FROM officer WHERE Email_Officer = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        if ($stmt_delete) {
            $stmt_delete->bind_param('s', $email_to_delete);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    $_SESSION['delete_message'] = '<div class="alert success"><i class="fas fa-check-circle"></i> ลบเจ้าหน้าที่ <strong>' . htmlspecialchars($email_to_delete) . '</strong> สำเร็จแล้ว.</div>';
                } else {
                    $_SESSION['delete_message'] = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> ไม่พบเจ้าหน้าที่ <strong>' . htmlspecialchars($email_to_delete) . '</strong> ในระบบ หรือไม่สามารถลบได้.</div>';
                }
            } else {
                $_SESSION['delete_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการลบเจ้าหน้าที่: ' . $stmt_delete->error . '</div>';
            }
            $stmt_delete->close();
        } else {
            $_SESSION['delete_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับการลบ: ' . $conn->error . '</div>';
        }
    }
    header("Location: admin.php"); // Redirect เพื่อป้องกันการส่งซ้ำและล้าง POST data
    exit();
}


// --- ส่วนจัดการการเพิ่มเจ้าหน้าที่ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Email_Officer']) && isset($_POST['Password'])) {
    // ตรวจสอบว่าเป็นการส่งฟอร์มเพิ่มเจ้าหน้าที่หรือไม่ (ตรวจสอบ action เพิ่มเติมถ้ามีฟอร์มอื่นในหน้าเดียวกัน)
    // ในที่นี้ไม่มีฟอร์มอื่นที่มี Email_Officer และ Password จึงสามารถใช้การตรวจสอบนี้ได้
    $email_officer = $_POST['Email_Officer'] ?? '';
    $password = $_POST['Password'] ?? '';
    $confirm_password = $_POST['Confirm_Password'] ?? '';
    $title_name = $_POST['Title_name'] ?? '';
    $first_name = $_POST['First_name'] ?? '';
    $last_name = $_POST['Last_name'] ?? '';
    $gender = $_POST['Gender'] ?? '';
    $phone_number = $_POST['Phone_number'] ?? '';
    $admin_field_value = $_POST['Admin'] ?? '';
    $province_id = $_POST['Province_id'] ?? '';

    if (empty($admin_field_value)) {
        $admin_field_value = NULL;
    }

    if (empty($email_officer) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name) || empty($phone_number) || empty($province_id)) {
        $message = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน.</div>';
    } elseif (!filter_var($email_officer, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> รูปแบบอีเมลเจ้าหน้าที่ไม่ถูกต้อง.</div>';
    } elseif (!empty($admin_field_value) && !filter_var($admin_field_value, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> รูปแบบอีเมลผู้ดูแลระบบ (Admin) ไม่ถูกต้อง.</div>';
    } elseif ($password !== $confirm_password) {
        $message = '<div class="alert error"><i class="fas fa-times-circle"></i> รหัสผ่านไม่ตรงกัน.</div>';
    } elseif (strlen($password) < 6) {
        $message = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร.</div>';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql_check_email = "SELECT Email_Officer FROM officer WHERE Email_Officer = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param('s', $email_officer);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();

        if ($stmt_check_email->num_rows > 0) {
            $message = '<div class="alert error"><i class="fas fa-times-circle"></i> อีเมลเจ้าหน้าที่นี้มีอยู่ในระบบแล้ว.</div>';
        } else {
            $sql_insert = "INSERT INTO officer (Email_Officer, Password, Title_name, First_name, Last_name, Gender, Phone_number, Email_Admin, Province_id)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_insert);
            if ($stmt) {
                $stmt->bind_param(
                    'ssssssssi', // 'i' สำหรับ Province_id
                    $email_officer,
                    $hashed_password,
                    $title_name,
                    $first_name,
                    $last_name,
                    $gender,
                    $phone_number,
                    $admin_field_value,
                    $province_id
                );

                if ($stmt->execute()) {
                    $message = '<div class="alert success"><i class="fas fa-check-circle"></i> เพิ่มเจ้าหน้าที่สำเร็จแล้ว! อีเมล: <strong>' . htmlspecialchars($email_officer) . '</strong></div>';
                    // ล้างค่า POST เพื่อไม่ให้ข้อมูลค้างในฟอร์มเมื่อเพิ่มสำเร็จ
                    $_POST = array(); 
                } else {
                    $message = '<div class="alert error"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการเพิ่มเจ้าหน้าที่: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            } else {
                $message = '<div class="alert error"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: ' . $conn->error . '</div>';
            }
        }
        $stmt_check_email->close();
    }
}

// ถ้ามี message จากการลบ (มาจาก $_SESSION) ให้แสดง
if (isset($_SESSION['delete_message'])) {
    $message = $_SESSION['delete_message'];
    unset($_SESSION['delete_message']); // เคลียร์ session message หลังจากแสดงแล้ว
}

// --- ดึงข้อมูลเจ้าหน้าที่ทั้งหมดเพื่อแสดงผล (พร้อม Filter) ---
$filter_province_id = $_GET['filter_province_id'] ?? 'all';
$search_query = $_GET['search_query'] ?? '';

$sql_select_officers = "SELECT Email_Officer, Title_name, First_name, Last_name, Gender, Phone_number, Email_Admin, p.Province_name 
                        FROM officer o
                        LEFT JOIN province p ON o.Province_id = p.Province_Id";
$conditions = [];
$params = [];
$param_types = '';

if ($filter_province_id !== 'all') {
    $conditions[] = "o.Province_id = ?";
    $params[] = (int)$filter_province_id; // Cast to int for parameter binding
    $param_types .= 'i';
}

if (!empty($search_query)) {
    $search_query_like = '%' . $search_query . '%';
    // ค้นหาใน Email_Officer, First_name, Last_name, Phone_number
    $conditions[] = "(o.Email_Officer LIKE ? OR o.First_name LIKE ? OR o.Last_name LIKE ? OR o.Phone_number LIKE ?)";
    $params[] = $search_query_like;
    $params[] = $search_query_like;
    $params[] = $search_query_like;
    $params[] = $search_query_like;
    $param_types .= 'ssss';
}

if (!empty($conditions)) {
    $sql_select_officers .= " WHERE " . implode(' AND ', $conditions);
}

$sql_select_officers .= " ORDER BY First_name ASC";

$officers = [];
$stmt_select_officers = $conn->prepare($sql_select_officers);
if ($stmt_select_officers === false) {
    error_log("ERROR: Failed to prepare select officers statement: " . $conn->error);
    $message = '<div class="alert error"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการดึงข้อมูลเจ้าหน้าที่: ' . $conn->error . '</div>';
} else {
    if (!empty($params)) {
        $bind_args = [];
        $bind_args[] = $param_types; // String of types
        foreach ($params as $key => $value) {
            $bind_args[] = &$params[$key]; // Pass each parameter by reference
        }
        // Use call_user_func_array to bind parameters dynamically
        call_user_func_array([$stmt_select_officers, 'bind_param'], $bind_args);
    }
    $stmt_select_officers->execute();
    $result_officers = $stmt_select_officers->get_result();
    while ($row_officer = $result_officers->fetch_assoc()) {
        $officers[] = $row_officer;
    }
    $stmt_select_officers->free_result();
    $stmt_select_officers->close();
}

// ปิดการเชื่อมต่อฐานข้อมูล
if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <title>จัดการเจ้าหน้าที่</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- <link rel="stylesheet" href="../CSS/css/ino.css" /> -->
    <!-- <link rel="stylesheet" href="../CSS/css/hotel_rooms.css" /> -->
    <!-- <link rel="stylesheet" href="../CSS/css/modal_style.css" /> -->
    <style>
        /* General Body & Container */
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 30px;
            font-weight: 600;
        }

        h1 {
            font-size: 2.5em;
        }

        h2 {
            font-size: 1.8em;
            margin-top: 50px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 15px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: fadeIn 0.5s ease-out;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form Group Styles */
        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
            font-size: 0.95em;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #fff;
        }

        .form-group select {
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23000%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13%205.1L146.2%20202.7%2018.8%2074.5a17.6%2017.6%200%200%0A0-25.3%2024.4c0%2010.3%205.1%2019.2%2013%2024.4l137.9%20137.9c5.1%205.1%2011.8%207.9%2019.2%207.9h.9c7.3%200%2014.1-2.8%2019.2-7.9L287%20118.3a24.2%2024.2%200%200%0A0%206.1c0%2010.3-5.1%2019.2-13%2024.4z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 12px;
            padding-right: 35px;
            cursor: pointer;
        }

        .radio-group {
            display: flex;
            gap: 25px;
            align-items: center;
            margin-top: 5px;
        }

        .radio-group label {
            margin-bottom: 0;
            font-weight: normal;
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 30px;
        }

        .btn-submit:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .btn-back {
            display: inline-block;
            padding: 12px 25px;
            background-color: #6c757d;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 30px;
            font-weight: 500;
        }

        .btn-back:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        /* Officer List Section */
        .officer-list-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
        }

        /* Search/Filter Form for Officers */
        .filter-officer-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            align-items: flex-end; /* Align items at the bottom */
        }

        .form-group-inline {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }

        .filter-officer-form label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            font-size: 0.9em;
        }

        .filter-officer-form select,
        .filter-officer-form input[type="text"] {
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 0.95em;
            box-sizing: border-box;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #fff;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23000%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13%205.1L146.2%20202.7%2018.8%2074.5a17.6%2017.6%200%200%0A0-25.3%2024.4c0%2010.3%205.1%2019.2%2013%2024.4l137.9%20137.9c5.1%205.1%2011.8%207.9%2019.2%207.9h.9c7.3%200%2014.1-2.8%2019.2-7.9L287%20118.3a24.2%2024.2%200%200%0A0%206.1c0%2010.3-5.1%2019.2-13%2024.4z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 10px;
            padding-right: 30px;
        }

        .btn-search-officer {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95em;
            transition: background-color 0.3s ease, transform 0.2s ease;
            min-width: 100px;
        }

        .btn-search-officer:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }

        /* Officer List Table */
        .officer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden; /* Ensures rounded corners on table */
        }

        .officer-table th,
        .officer-table td {
            border: 1px solid #e9ecef;
            padding: 12px 15px;
            text-align: left;
            font-size: 0.9em;
            vertical-align: middle;
        }

        .officer-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: bold;
            text-transform: uppercase;
        }

        .officer-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        .officer-table tr:hover {
            background-color: #e2f3ff;
        }

        .officer-table .action-buttons {
            white-space: nowrap; /* Keep buttons on one line */
        }

        .officer-table .action-buttons button {
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-size: 0.85em;
            margin-right: 5px;
            transition: background-color 0.2s ease, transform 0.2s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .officer-table .action-buttons button:hover {
            transform: translateY(-1px);
        }

        .officer-table .action-buttons .btn-delete {
            background-color: #dc3545;
        }

        .officer-table .action-buttons .btn-delete:hover {
            background-color: #c82333;
        }

        .officer-table .action-buttons .btn-edit {
            background-color: #007bff;
        }

        .officer-table .action-buttons .btn-edit:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>จัดการข้อมูลเจ้าหน้าที่</h1>

        <?= $message ?>

        <h2>เพิ่มเจ้าหน้าที่ใหม่</h2>
        <form action="" method="POST">
            <div class="form-group">
                <label for="Email_Officer">อีเมลเจ้าหน้าที่: <span style="color:red;">*</span></label>
                <input type="email" id="Email_Officer" name="Email_Officer" value="<?= htmlspecialchars($_POST['Email_Officer'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="Password">รหัสผ่าน: <span style="color:red;">*</span></label>
                <input type="password" id="Password" name="Password" required>
            </div>

            <div class="form-group">
                <label for="Confirm_Password">ยืนยันรหัสผ่าน: <span style="color:red;">*</span></label>
                <input type="password" id="Confirm_Password" name="Confirm_Password" required>
            </div>

            <div class="form-group">
                <label for="Title_name">คำนำหน้าชื่อ: <span style="color:red;">*</span></label>
                <select id="Title_name" name="Title_name" required>
                    <option value="">เลือกคำนำหน้า</option>
                    <option value="นาย" <?= (($_POST['Title_name'] ?? '') == 'นาย') ? 'selected' : '' ?>>นาย</option>
                    <option value="นาง" <?= (($_POST['Title_name'] ?? '') == 'นาง') ? 'selected' : '' ?>>นาง</option>
                    <option value="นางสาว" <?= (($_POST['Title_name'] ?? '') == 'นางสาว') ? 'selected' : '' ?>>นางสาว</option>
                </select>
            </div>

            <div class="form-group">
                <label for="First_name">ชื่อ: <span style="color:red;">*</span></label>
                <input type="text" id="First_name" name="First_name" value="<?= htmlspecialchars($_POST['First_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="Last_name">นามสกุล: <span style="color:red;">*</span></label>
                <input type="text" id="Last_name" name="Last_name" value="<?= htmlspecialchars($_POST['Last_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>เพศ: <span style="color:red;">*</span></label>
                <div class="radio-group">
                    <input type="radio" id="Gender_Male" name="Gender" value="ชาย" <?= (($_POST['Gender'] ?? '') == 'ชาย') ? 'checked' : '' ?> required>
                    <label for="Gender_Male">ชาย</label>
                    <input type="radio" id="Gender_Female" name="Gender" value="หญิง" <?= (($_POST['Gender'] ?? '') == 'หญิง') ? 'checked' : '' ?> required>
                    <label for="Gender_Female">หญิง</label>
                </div>
            </div>

            <div class="form-group">
                <label for="Phone_number">เบอร์โทรศัพท์: <span style="color:red;">*</span></label>
                <input type="text" id="Phone_number" name="Phone_number" value="<?= htmlspecialchars($_POST['Phone_number'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="Admin">อีเมลผู้ดูแลระบบ:</label>
                <input type="email" id="Admin" name="Admin" value="<?= htmlspecialchars($_POST['Admin'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="Province_id">สาขา (จังหวัด): <span style="color:red;">*</span></label>
                <select id="Province_id" name="Province_id" required>
                    <option value="">เลือกจังหวัด</option>
                    <?php foreach ($provinces as $province): ?>
                        <option value="<?= htmlspecialchars($province['Province_Id']) ?>"
                            <?= ((string)($province['Province_Id']) == (string)($_POST['Province_id'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($province['Province_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-submit"><i class="fas fa-user-plus"></i> เพิ่มเจ้าหน้าที่</button>
        </form>

        <!-- Officer List and Search Section -->


        <div style="text-align: center; margin-top: 40px;">
            <a href="admin-home.php" class="btn-back"><i class="fas fa-arrow-left"></i> กลับหน้าผู้ดูแลระบบ</a>
        </div>
    </div>
</body>

</html>