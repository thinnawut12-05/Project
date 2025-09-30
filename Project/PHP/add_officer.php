<?php
include 'db.php'; // ตรวจสอบให้แน่ใจว่า db.php อยู่ในตำแหน่งที่ถูกต้อง
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

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

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_officer = $_POST['Email_Officer'] ?? '';
    $password = $_POST['Password'] ?? '';
    $confirm_password = $_POST['Confirm_Password'] ?? '';
    $title_name = $_POST['Title_name'] ?? '';
    $first_name = $_POST['First_name'] ?? '';
    $last_name = $_POST['Last_name'] ?? '';
    $gender = $_POST['Gender'] ?? '';
    $phone_number = $_POST['Phone_number'] ?? '';
    $admin_field_value = $_POST['Admin'] ?? ''; // ค่าที่กรอกในฟอร์มสำหรับ Admin
    $province_id = $_POST['Province_id'] ?? '';

    // หากช่อง Admin ถูกปล่อยว่าง ให้ตั้งค่าเป็น NULL เพื่อให้เก็บใน DB เป็น NULL
    if (empty($admin_field_value)) {
        $admin_field_value = NULL;
    }

    // การตรวจสอบข้อมูลเบื้องต้น
    if (empty($email_officer) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name) || empty($phone_number) || empty($province_id)) {
        $message = '<div class="alert error">กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน.</div>';
    } elseif (!filter_var($email_officer, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert error">รูปแบบอีเมลเจ้าหน้าที่ไม่ถูกต้อง.</div>';
    } elseif (!empty($admin_field_value) && !filter_var($admin_field_value, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert error">รูปแบบอีเมลผู้ดูแลระบบ (Admin) ไม่ถูกต้อง.</div>';
    } elseif ($password !== $confirm_password) {
        $message = '<div class="alert error">รหัสผ่านไม่ตรงกัน.</div>';
    } elseif (strlen($password) < 6) {
        $message = '<div class="alert error">รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร.</div>';
    } else {
        // Hash รหัสผ่านก่อนบันทึกลงฐานข้อมูล
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ตรวจสอบว่า Email_Officer ซ้ำหรือไม่
        $sql_check_email = "SELECT Email_Officer FROM officer WHERE Email_Officer = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param('s', $email_officer);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();

        if ($stmt_check_email->num_rows > 0) {
            $message = '<div class="alert error">อีเมลเจ้าหน้าที่นี้มีอยู่ในระบบแล้ว.</div>';
        } else {
            $sql_insert = "INSERT INTO officer (Email_Officer, Password, Title_name, First_name, Last_name, Gender, Phone_number, Email_Admin, Province_id)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_insert);
            if ($stmt) {
                $stmt->bind_param(
                    'sssssssss',
                    $email_officer,
                    $hashed_password,
                    $title_name,
                    $first_name,
                    $last_name,
                    $gender,
                    $phone_number,
                    $admin_field_value, // ค่าจากฟอร์ม Admin จะถูกบันทึกลงใน Email_Admin
                    $province_id
                );

                if ($stmt->execute()) {
                    $message = '<div class="alert success"><i class="fas fa-check-circle"></i> เพิ่มเจ้าหน้าที่สำเร็จแล้ว! อีเมล: <strong>' . htmlspecialchars($email_officer) . '</strong></div>';
                    $_POST = array(); // ล้างค่า POST เพื่อไม่ให้ข้อมูลค้างในฟอร์ม
                } else {
                    $message = '<div class="alert error">เกิดข้อผิดพลาดในการเพิ่มเจ้าหน้าที่: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            } else {
                $message = '<div class="alert error">เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: ' . $conn->error . '</div>';
            }
        }
        $stmt_check_email->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <title>เพิ่มเจ้าหน้าที่</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../CSS/css/ino.css" />
    <link rel="stylesheet" href="../CSS/css/hotel_rooms.css" />
    <link rel="stylesheet" href="../CSS/css/modal_style.css" />
    <style>
        /* CSS ที่คุณต้องการตกแต่ง */
        body {
            font-family: 'Sarabun', sans-serif; /* ตัวอย่างการใช้ฟอนต์ไทย */
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box; /* รวม padding ใน width */
            font-size: 16px;
            -webkit-appearance: none; /* สำหรับซ่อนลูกศรใน select ในบางเบราว์เซอร์ */
            -moz-appearance: none;
            appearance: none;
        }

        .form-group select {
            background-color: #fff;
            cursor: pointer;
        }

        .form-group input[type="radio"] {
            margin-right: 5px;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-top: 5px;
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #35ad2aff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 30px;
        }

        .btn-submit:hover {
            background-color: #35ad2aff;
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

        /* *** สไตล์สำหรับข้อความแจ้งเตือนที่ปรับปรุงใหม่ *** */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px; /* เพิ่มความโค้งมน */
            font-weight: bold;
            text-align: center;
            position: relative; /* สำหรับการจัดวางไอคอน */
            display: flex; /* จัดเรียงไอคอนและข้อความ */
            align-items: center;
            justify-content: center;
            gap: 10px; /* ระยะห่างระหว่างไอคอนกับข้อความ */
            transition: opacity 0.5s ease-out, transform 0.5s ease-out; /* สำหรับการเฟดออก */
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2); /* เพิ่มเงา */
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2); /* เพิ่มเงา */
            font-size: 1.1em; /* เพิ่มขนาดตัวอักษร */
        }
        
        .alert.fade-out {
            opacity: 0;
            transform: translateY(-20px);
            pointer-events: none; /* ทำให้คลิกไม่โดนหลังจาก fade-out */
        }

        /* สำหรับ Header */
        header {
            background-color: #007bff; /* สีน้ำเงินเข้ม */
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header .logo img {
            vertical-align: middle;
        }

        header nav a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        header nav a:hover {
            color: #e2e6ea;
        }

        .user-display .profile-link {
            text-decoration: none;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .user-display .profile-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

    </style>
</head>

<body>
    <div class="container">
        <h1>เพิ่มเจ้าหน้าที่</h1>

        <?= $message ?>

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
                <label for="Admin">อีเมลผู้ดูแลระบบ:<span style="color:red;">*</label>
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

            <button type="submit" class="btn-submit">เพิ่มเจ้าหน้าที่</button>
        </form>

        <a href="admin-home.php" class="btn-back">กลับหน้าผู้ดูแลระบบ</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.querySelector('.alert.success');
            if (successAlert) {
                // ทำให้ข้อความแจ้งเตือนความสำเร็จหายไปเองหลังจาก 3 วินาที
                setTimeout(() => {
                    successAlert.classList.add('fade-out');
                    // ลบออกจาก DOM หลังจาก transition เสร็จสิ้น
                    successAlert.addEventListener('transitionend', () => {
                        successAlert.remove();
                    });
                }, 3000); // 3000 มิลลิวินาที = 3 วินาที
            }
        });
    </script>
</body>

</html>