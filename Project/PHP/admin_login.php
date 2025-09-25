<?php
session_start();
include 'db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $sql = "SELECT * FROM admin WHERE Email_Admin=? AND Password=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $row['Email_Admin'];
        $_SESSION['admin_name'] = $row['First_name'] . " " . $row['Last_name'];
        // Redirect ไปหน้า dashboard
        header("Location: admin.php");
        exit();
    } else {
        $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง!";
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบแอดมิน - Dom Inn Hotel</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
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
            margin: 10px 0;
            border: 1px solid #b2bec3;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        input:focus {
            border-color: #0984e3;
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
        <h2>เข้าสู่ระบบแอดมิน</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="อีเมล" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
        <a href="index.php">⬅ กลับหน้าหลัก</a>
    </div>
</body>

</html>