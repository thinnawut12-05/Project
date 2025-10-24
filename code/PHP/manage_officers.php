<?php
include 'db.php'; // ตรวจสอบให้แน่ใจว่า db.php อยู่ในตำแหน่งที่ถูกต้อง
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// --- ขั้นตอนที่ 1: แก้ไขการตรวจสอบการล็อกอิน ---
// ตรวจสอบว่าแอดมินล็อกอินอยู่หรือไม่ โดยใช้เซสชันที่ตั้งค่าจาก login.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php"); // ถ้าไม่ได้ล็อกอินเป็นแอดมิน ให้เปลี่ยนเส้นทางไปหน้า login
    exit();
}
// --- สิ้นสุดการแก้ไขการตรวจสอบการล็อกอิน ---


if (!$conn) {
    die("❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . mysqli_connect_error());
}
$conn->set_charset("utf8");

// --- ขั้นตอนที่ 2: แก้ไขการกำหนดอีเมลผู้ใช้งานปัจจุบัน ---
// กำหนดอีเมลของผู้ใช้งานปัจจุบัน (แอดมินที่ล็อกอินอยู่)
// เพื่อใช้ในการป้องกันการลบบัญชีตัวเอง และการอัปเดตเซสชันเมื่อแก้ไขอีเมลตัวเอง
$current_logged_in_officer_email = $_SESSION['Email_Admin'] ?? '';
$First_name = $_SESSION['First_name'] ?? ''; // ชื่อของแอดมิน
$Last_name = $_SESSION['Last_name'] ?? '';   // นามสกุลของแอดมิน
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

// --- ส่วนจัดการการลบเจ้าหน้าที่ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_officer') {
    $email_to_delete = $_POST['email_to_delete'] ?? '';

    if (empty($email_to_delete)) {
        $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> ไม่พบอีเมลเจ้าหน้าที่ที่ต้องการลบ.</div>';
    } else {
        // ตรวจสอบว่าไม่ได้พยายามลบตัวเอง (ซึ่งตอนนี้คือ Admin ที่ล็อกอินอยู่)
        if ($email_to_delete === $current_logged_in_officer_email) {
            $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> คุณไม่สามารถลบบัญชีของตัวเองได้!</div>';
        } else {
            $sql_delete = "DELETE FROM officer WHERE Email_Officer = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            if ($stmt_delete) {
                $stmt_delete->bind_param('s', $email_to_delete);
                if ($stmt_delete->execute()) {
                    if ($stmt_delete->affected_rows > 0) {
                        $_SESSION['manage_officers_message'] = '<div class="alert success"><i class="fas fa-check-circle"></i> ลบเจ้าหน้าที่ <strong>' . htmlspecialchars($email_to_delete) . '</strong> สำเร็จแล้ว.</div>';
                    } else {
                        $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> ไม่พบเจ้าหน้าที่ <strong>' . htmlspecialchars($email_to_delete) . '</strong> ในระบบ หรือไม่สามารถลบได้.</div>';
                    }
                } else {
                    $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการลบเจ้าหน้าที่: ' . $stmt_delete->error . '</div>';
                }
                $stmt_delete->close();
            } else {
                $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับการลบ: ' . $conn->error . '</div>';
            }
        }
    }
    header("Location: manage_officers.php"); // Redirect เพื่อป้องกันการส่งซ้ำและล้าง POST data
    exit();
}

// --- ส่วนจัดการการแก้ไขเจ้าหน้าที่ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_officer') {
    $original_email = $_POST['original_email'] ?? '';
    $email_officer = $_POST['edit_Email_Officer'] ?? '';
    $password = $_POST['edit_Password'] ?? ''; // รหัสผ่านใหม่ (อาจว่าง)
    $confirm_password = $_POST['edit_Confirm_Password'] ?? ''; // ยืนยันรหัสผ่านใหม่ (อาจว่าง)
    $title_name = $_POST['edit_Title_name'] ?? '';
    $first_name = $_POST['edit_First_name'] ?? '';
    $last_name = $_POST['edit_Last_name'] ?? '';
    $gender = $_POST['edit_Gender'] ?? '';
    $phone_number = $_POST['edit_Phone_number'] ?? '';
    $admin_field_value = $_POST['edit_Admin'] ?? '';
    $province_id = $_POST['edit_Province_id'] ?? '';

    // ตรวจสอบข้อมูลเบื้องต้น
    if (empty($original_email) || empty($email_officer) || empty($first_name) || empty($last_name) || empty($phone_number) || empty($province_id)) {
        $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน.</div>';
        header("Location: manage_officers.php");
        exit();
    }
    if (!filter_var($email_officer, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> รูปแบบอีเมลเจ้าหน้าที่ไม่ถูกต้อง.</div>';
        header("Location: manage_officers.php");
        exit();
    }
    if (!empty($admin_field_value) && !filter_var($admin_field_value, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> รูปแบบอีเมลผู้ดูแลระบบ (Admin) ไม่ถูกต้อง.</div>';
        header("Location: manage_officers.php");
        exit();
    }
    if (!empty($password) && $password !== $confirm_password) {
        $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> รหัสผ่านใหม่ไม่ตรงกัน.</div>';
        header("Location: manage_officers.php");
        exit();
    }
    if (!empty($password) && strlen($password) < 6) {
        $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-exclamation-triangle"></i> รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร.</div>';
        header("Location: manage_officers.php");
        exit();
    }

    // ตรวจสอบว่าอีเมลใหม่ซ้ำกับผู้อื่นหรือไม่ (ยกเว้นตัวเอง)
    if ($email_officer !== $original_email) {
        $sql_check_email = "SELECT Email_Officer FROM officer WHERE Email_Officer = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param('s', $email_officer);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> อีเมลใหม่ <strong>' . htmlspecialchars($email_officer) . '</strong> มีอยู่ในระบบแล้ว.</div>';
            $stmt_check_email->close();
            header("Location: manage_officers.php");
            exit();
        }
        $stmt_check_email->close();
    }

    // สร้าง SQL query สำหรับ UPDATE
    $sql_update = "UPDATE officer SET Email_Officer = ?, Title_name = ?, First_name = ?, Last_name = ?, Gender = ?, Phone_number = ?, Email_Admin = ?, Province_id = ?";
    $params_update = [$email_officer, $title_name, $first_name, $last_name, $gender, $phone_number, $admin_field_value, $province_id];
    $param_types_update = 'sssssssi'; // s for strings, i for integer (Province_id)

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql_update .= ", Password = ?";
        $params_update[] = $hashed_password;
        $param_types_update .= 's';
    }

    $sql_update .= " WHERE Email_Officer = ?";
    $params_update[] = $original_email;
    $param_types_update .= 's';

    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update) {
        // Dynamic binding using call_user_func_array
        $bind_args = [$param_types_update];
        foreach ($params_update as &$value) {
            $bind_args[] = &$value;
        }
        call_user_func_array([$stmt_update, 'bind_param'], $bind_args);

        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0 || $email_officer === $original_email) {
                $_SESSION['manage_officers_message'] = '<div class="alert success"><i class="fas fa-check-circle"></i> อัปเดตข้อมูลเจ้าหน้าที่ <strong>' . htmlspecialchars($original_email) . '</strong> สำเร็จแล้ว.</div>';
                // --- ขั้นตอนที่ 3: แก้ไขการอัปเดตเซสชันเมื่อแก้ไขอีเมลตัวเอง ---
                // ถ้าแก้ไขอีเมลของตัวเอง (ซึ่งตอนนี้คือ Admin ที่ล็อกอินอยู่) ต้องอัปเดต $_SESSION['Email_Admin'] ด้วย
                if ($original_email === $current_logged_in_officer_email) {
                    $_SESSION['Email_Admin'] = $email_officer; // อัปเดต Email_Admin ใน session
                }
                // --- สิ้นสุดการแก้ไขการอัปเดตเซสชัน ---
            } else {
                $_SESSION['manage_officers_message'] = '<div class="alert info"><i class="fas fa-info-circle"></i> ไม่มีการเปลี่ยนแปลงข้อมูลสำหรับเจ้าหน้าที่ <strong>' . htmlspecialchars($original_email) . '</strong>.</div>';
            }
        } else {
            $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการอัปเดตข้อมูลเจ้าหน้าที่: ' . $stmt_update->error . '</div>';
        }
        $stmt_update->close();
    } else {
        $_SESSION['manage_officers_message'] = '<div class="alert error"><i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL สำหรับการแก้ไข: ' . $conn->error . '</div>';
    }
    header("Location: manage_officers.php");
    exit();
}


// ถ้ามี message จาก $_SESSION ให้แสดง
if (isset($_SESSION['manage_officers_message'])) {
    $message = $_SESSION['manage_officers_message'];
    unset($_SESSION['manage_officers_message']); // เคลียร์ session message หลังจากแสดงแล้ว
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

        h1, h2 {
            text-align: center;
            color: #007bff; /* เปลี่ยนกลับเป็นสีน้ำเงินหลัก */
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

        .alert.info { /* เพิ่ม alert สำหรับ info */
            background-color: #cfe2ff;
            color: #052c65;
            border: 1px solid #b6d4fe;
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
            align-items: flex-end;
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
            overflow: hidden;
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
            white-space: nowrap;
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

        /* Back Button */
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .modal.show {
            display: flex;
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
            position: relative;
            transform: translateY(-20px);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-content .close-button {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-content .close-button:hover,
        .modal-content .close-button:focus {
            color: #333;
        }

        .modal-content h3 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.8em;
            text-align: center; /* จัดให้อยู่ตรงกลาง */
        }

        .modal-content p {
            font-size: 1.1em;
            text-align: center;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .modal-content strong {
            color: #dc3545; /* สีแดงสำหรับเน้นอีเมลที่จะลบ */
        }

        .modal-content .form-group {
            margin-bottom: 15px;
        }

        .modal-content .form-group label {
            font-size: 0.9em;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .modal-content input[type="email"],
        .modal-content input[type="password"],
        .modal-content input[type="text"],
        .modal-content select {
            width: calc(100% - 24px); /* Full width minus padding */
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
            margin-top: 5px;
        }

        .modal-content .radio-group {
            display: flex;
            gap: 15px;
            margin-top: 5px;
        }

        .modal-content .radio-group input[type="radio"] {
            margin-right: 5px;
        }

        .modal-actions {
            display: flex;
            justify-content: center; /* เปลี่ยนเป็น center เพื่อให้อยู่ตรงกลาง */
            gap: 15px; /* เพิ่มระยะห่างระหว่างปุ่ม */
            margin-top: 25px;
        }

        .modal-actions button {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 0.95em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            border: none;
            min-width: 100px;
        }

        .modal-actions .btn-cancel-modal {
            background-color: #6c757d;
            color: white;
        }

        .modal-actions .btn-cancel-modal:hover {
            background-color: #5a6268;
        }

        .modal-actions .btn-save-changes,
        .modal-actions .btn-confirm-delete { /* เพิ่มสำหรับปุ่มยืนยันการลบ */
            background-color: #28a745;
            color: white;
        }
        .modal-actions .btn-save-changes:hover {
            background-color: #218838;
        }
        .modal-actions .btn-confirm-delete {
            background-color: #dc3545; /* สีแดงสำหรับการลบ */
        }
        .modal-actions .btn-confirm-delete:hover {
            background-color: #c82333;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>จัดการเจ้าหน้าที่</h1>

        <?= $message ?>

        <!-- Officer List and Search Section -->
        <div class="officer-list-section">
            <h2>ค้นหา, แก้ไข และลบเจ้าหน้าที่</h2>
            <form action="manage_officers.php" method="GET" class="filter-officer-form">
                <div class="form-group-inline">
                    <label for="filter_province_id">สาขา:</label>
                    <select id="filter_province_id" name="filter_province_id">
                        <option value="all" <?= ($filter_province_id === 'all') ? 'selected' : '' ?>>-- ทั้งหมด --</option>
                        <?php foreach ($provinces as $province): ?>
                            <option value="<?= htmlspecialchars($province['Province_Id']) ?>"
                                <?= ((string)$filter_province_id === (string)$province['Province_Id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($province['Province_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-inline">
                    <label for="search_query">ค้นหา (ชื่อ, อีเมล, โทรศัพท์):</label>
                    <input type="text" id="search_query" name="search_query" value="<?= htmlspecialchars($search_query) ?>" placeholder="ป้อนคำค้นหา...">
                </div>
                <div class="form-group-inline">
                    <button type="submit" class="btn-search-officer"><i class="fas fa-search"></i> ค้นหา</button>
                </div>
            </form>

            <table class="officer-table">
                <thead>
                    <tr>
                        <th>อีเมล</th>
                        <th>คำนำหน้า</th>
                        <th>ชื่อ</th>
                        <th>นามสกุล</th>
                        <th>เพศ</th>
                        <th>เบอร์โทรศัพท์</th>
                        <th>ผู้ดูแลระบบ</th>
                        <th>สาขา</th>
                        <th>ดำเนินการ</th>
                    </tr>
</thead>
<tbody>
                    <?php if (empty($officers)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px;">ไม่พบเจ้าหน้าที่ตามเงื่อนไขที่เลือก.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($officers as $officer): ?>
                            <tr>
                                <td><?= htmlspecialchars($officer['Email_Officer']) ?></td>
                                <td><?= htmlspecialchars($officer['Title_name']) ?></td>
                                <td><?= htmlspecialchars($officer['First_name']) ?></td>
                                <td><?= htmlspecialchars($officer['Last_name']) ?></td>
                                <td><?= htmlspecialchars($officer['Gender']) ?></td>
                                <td><?= htmlspecialchars($officer['Phone_number']) ?></td>
                                <td><?= htmlspecialchars($officer['Email_Admin'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($officer['Province_name'] ?? '-') ?></td>
                                <td class="action-buttons">
                                    <!-- Edit Button -->
                                    <button type="button" class="btn-edit"
                                        data-officer='<?= json_encode($officer, JSON_UNESCAPED_UNICODE) ?>'
                                        onclick="openEditOfficerModal(this)">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </button>

                                    <!-- Delete Button (triggers custom popup) -->
                                    <button type="button" class="btn-delete"
                                        onclick="openDeleteConfirmationModal('<?= htmlspecialchars($officer['Email_Officer']) ?>')"
                                        <?= ($officer['Email_Officer'] === $current_logged_in_officer_email) ? 'disabled title="ไม่สามารถลบบัญชีตัวเองได้"' : '' ?>>
                                        <i class="fas fa-trash-alt"></i> ลบ
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="admin-home.php" class="btn-back"><i class="fas fa-arrow-left"></i> กลับหน้าผู้ดูแลระบบ</a>
        </div>
    </div>

    <!-- Modal for Edit Officer -->
    <div id="editOfficerModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('editOfficerModal')">&times;</span>
            <h3>แก้ไขข้อมูลเจ้าหน้าที่ <span id="editModalOfficerEmailDisplay"></span></h3>
            <form action="manage_officers.php" method="POST" id="editOfficerForm">
                <input type="hidden" name="action" value="edit_officer">
                <input type="hidden" name="original_email" id="editOriginalEmail">

                <div class="form-group">
                    <label for="edit_Email_Officer">อีเมลเจ้าหน้าที่:</label>
                    <input type="email" id="edit_Email_Officer" name="edit_Email_Officer" required>
                </div>

                <div class="form-group">
                    <label for="edit_Password">รหัสผ่านใหม่ (เว้นว่างหากไม่เปลี่ยน):</label>
                    <input type="password" id="edit_Password" name="edit_Password">
                </div>

                <div class="form-group">
                    <label for="edit_Confirm_Password">ยืนยันรหัสผ่านใหม่:</label>
                    <input type="password" id="edit_Confirm_Password" name="edit_Confirm_Password">
                </div>

                <div class="form-group">
                    <label for="edit_Title_name">คำนำหน้าชื่อ:</label>
                    <select id="edit_Title_name" name="edit_Title_name" required>
                        <option value="นาย">นาย</option>
                        <option value="นาง">นาง</option>
                        <option value="นางสาว">นางสาว</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_First_name">ชื่อ:</label>
                    <input type="text" id="edit_First_name" name="edit_First_name" required>
                </div>

                <div class="form-group">
                    <label for="edit_Last_name">นามสกุล:</label>
                    <input type="text" id="edit_Last_name" name="edit_Last_name" required>
                </div>

                <div class="form-group">
                    <label>เพศ:</label>
                    <div class="radio-group">
                        <input type="radio" id="edit_Gender_Male" name="edit_Gender" value="ชาย" required>
                        <label for="edit_Gender_Male">ชาย</label>
                        <input type="radio" id="edit_Gender_Female" name="edit_Gender" value="หญิง" required>
                        <label for="edit_Gender_Female">หญิง</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_Phone_number">เบอร์โทรศัพท์:</label>
                    <input type="text" id="edit_Phone_number" name="edit_Phone_number" required>
                </div>

                <div class="form-group">
                    <label for="edit_Admin">อีเมลผู้ดูแลระบบ:</label>
                    <input type="email" id="edit_Admin" name="edit_Admin">
                </div>

                <div class="form-group">
                    <label for="edit_Province_id">สาขา (จังหวัด):</label>
                    <select id="edit_Province_id" name="edit_Province_id" required>
                        <?php foreach ($provinces as $province): ?>
                            <option value="<?= htmlspecialchars($province['Province_Id']) ?>">
                                <?= htmlspecialchars($province['Province_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="closeModal('editOfficerModal')">ยกเลิก</button>
                    <button type="submit" class="btn-save-changes"><i class="fas fa-save"></i> บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>

    <!-- NEW: Modal for Delete Confirmation -->
    <div id="deleteConfirmationModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('deleteConfirmationModal')">&times;</span>
            <h3>ยืนยันการลบเจ้าหน้าที่</h3>
            <p>คุณแน่ใจหรือไม่ที่จะลบเจ้าหน้าที่: <br> <strong><span id="officerEmailToDelete"></span></strong>?</p>
            <p style="color: #dc3545; font-weight: bold;">การดำเนินการนี้ไม่สามารถย้อนกลับได้!</p>
            <form action="manage_officers.php" method="POST" id="deleteOfficerForm">
                <input type="hidden" name="action" value="delete_officer">
                <input type="hidden" name="email_to_delete" id="deleteEmailOfficer">
                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="closeModal('deleteConfirmationModal')">ยกเลิก</button>
                    <button type="submit" class="btn-confirm-delete"><i class="fas fa-trash-alt"></i> ยืนยันการลบ</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        // Function to open any modal
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        // Function to close any modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
            // Reset form fields when closing the modal
            if (modalId === 'editOfficerModal') {
                const form = document.getElementById('editOfficerForm');
                if (form) {
                    form.reset();
                    // Clear password fields manually as form.reset() might not clear them reliably
                    document.getElementById('edit_Password').value = '';
                    document.getElementById('edit_Confirm_Password').value = '';
                }
            }
            // For delete modal, no form reset is typically needed as it's just confirmation
        }

        // Open Edit Officer Modal and populate data
        function openEditOfficerModal(button) {
            const officer = JSON.parse(button.dataset.officer);
            
            document.getElementById('editModalOfficerEmailDisplay').textContent = officer.Email_Officer;
            document.getElementById('editOriginalEmail').value = officer.Email_Officer;
            document.getElementById('edit_Email_Officer').value = officer.Email_Officer;
            document.getElementById('edit_Title_name').value = officer.Title_name;
            document.getElementById('edit_First_name').value = officer.First_name;
            document.getElementById('edit_Last_name').value = officer.Last_name;
            document.getElementById('edit_Phone_number').value = officer.Phone_number;
            document.getElementById('edit_Admin').value = officer.Email_Admin;
            document.getElementById('edit_Province_id').value = officer.Province_id; // Province_id จากข้อมูล officer

            // Set radio button for Gender
            if (officer.Gender === 'ชาย') {
                document.getElementById('edit_Gender_Male').checked = true;
            } else if (officer.Gender === 'หญิง') {
                document.getElementById('edit_Gender_Female').checked = true;
            }

            // Clear password fields for security (don't pre-fill old password)
            document.getElementById('edit_Password').value = '';
            document.getElementById('edit_Confirm_Password').value = '';

            openModal('editOfficerModal');
        }

        // NEW: Function to open Delete Confirmation Modal
        function openDeleteConfirmationModal(email) {
            document.getElementById('officerEmailToDelete').textContent = email;
            document.getElementById('deleteEmailOfficer').value = email; // Set hidden input value for form submission
            openModal('deleteConfirmationModal');
        }


        // Close modal when clicking outside (on the overlay)
        window.addEventListener('click', function(event) {
            document.querySelectorAll('.modal.show').forEach(modal => {
                if (event.target == modal) {
                    closeModal(modal.id);
                }
            });
        });

        // Add client-side validation for password fields in edit modal
        document.getElementById('editOfficerForm').addEventListener('submit', function(event) {
            const newPassword = document.getElementById('edit_Password').value;
            const confirmPassword = document.getElementById('edit_Confirm_Password').value;

            if (newPassword && newPassword !== confirmPassword) {
                alert('รหัสผ่านใหม่ไม่ตรงกัน!');
                event.preventDefault();
            } else if (newPassword && newPassword.length < 6) {
                alert('รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร!');
                event.preventDefault();
            }
        });
    </script>
</body>

</html>