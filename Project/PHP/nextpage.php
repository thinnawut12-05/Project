<?php 
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role = $_POST['role'] ?? '';

    if ($role === "admin") {
        header("Location: admin.php");
        exit();
    } elseif ($role === "staff") {
        header("Location: staff.php"); // หรือไฟล์เจ้าหน้าที่ของคุณ
        exit();
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Dom Inn Hotel</title>
  <link rel="icon" type="image/png" href="../src/images/logo.png" />
  <title>เลือกสิทธิ์การใช้งาน</title>
  <link rel="stylesheet" href="../CSS/css/admin.css">
  <style>
    /* ... CSS เดิม ... */
  </style>
</head>
<body>
  <div class="container">
    <h2>เลือกสิทธิ์การใช้งาน</h2>
    <?php
      if (isset($_GET['error'])) {
        echo '<div class="error">กรุณาเลือกบทบาท</div>';
      }
    ?>
    <hr>
    <a href="./admin_login.php"><button type="button">เข้าสู่ระบบแอดมิน</button></a>
    <a href="./staff_login.php"><button type="button">เข้าสู่ระบบเจ้าหน้าที่</button></a>

  </div>
</body>
</html>
<style>
/* Reset เบื้องต้น */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Background และ container */
body {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    /* background: linear-gradient(135deg, #74b9ff, #a29bfe); */
}

.container {
    background: #fff;
    padding: 40px 50px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    width: 350px;
    text-align: center;
}

/* หัวข้อ */
.container h2 {
    margin-bottom: 25px;
    color: #2d3436;
}

/* ข้อความ error */
.error {
    background-color: #ffe6e6;
    color: #e74c3c;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 5px;
    font-size: 0.9rem;
}

/* Form */
form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2d3436;
}

form select {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 8px;
    border: 1px solid #b2bec3;
    font-size: 1rem;
    transition: border 0.3s;
}

form select:focus {
    border-color: #0984e3;
    outline: none;
}

/* ปุ่ม */
button {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border: none;
    border-radius: 8px;
    background: #0984e3;
    color: #fff;
    font-size: 1rem;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background: #74b9ff;
}

/* ปุ่มลิงก์ */
a button {
    background: #6c5ce7;
}

a button:hover {
    background: #a29bfe;
}

/* เส้นแบ่ง */
hr {
    margin: 20px 0;
    border: none;
    border-top: 1px solid #dfe6e9;
}
</style>
