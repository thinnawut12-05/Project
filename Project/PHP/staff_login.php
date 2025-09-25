<?php
session_start();
include 'db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    // ดึงข้อมูลจาก DB
    $stmt = $conn->prepare("SELECT Password FROM staff WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($hashed_password);

    if ($stmt->fetch() && password_verify($password, $hashed_password)) {
        $_SESSION["staff_logged_in"] = true;
        header("Location: staff_dashboard.php");
        exit();
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบเจ้าหน้าที่ - Dom Inn Hotel</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #74b9ff, #a29bfe);
            font-family: 'Segoe UI', sans-serif;
        }

        .login-box {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 350px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #2d3436;
        }

        .error {
            color: #e74c3c;
            margin-bottom: 15px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            /* ทำให้มีพื้นที่ด้านบน-ล่างพอดี */
            margin: 10px 0;
            border: 1px solid #b2bec3;
            border-radius: 8px;
            font-size: 1rem;
            line-height: normal;
            /* แก้ปัญหาข้อความลอย */
            box-sizing: border-box;
            /* กันการเกินขอบ */
        }

        input:focus {
            border-color: #00cec9;
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
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

        a {
            display: block;
            margin-top: 15px;
            color: #636e72;
            text-decoration: none;
        }

        a:hover {
            color: #2d3436;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h2>เข้าสู่ระบบเจ้าหน้าที่</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
        <a href="index.php">⬅ กลับหน้าหลัก</a>
    </div>
</body>

</html>