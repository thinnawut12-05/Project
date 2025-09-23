<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

include 'db.php'; // เชื่อมต่อฐานข้อมูล
$error = '';
$_SESSION['First_name'] = "";
$_SESSION['Last_name'] = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST['Email_member'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $error = "กรุณากรอกอีเมลและรหัสผ่านให้ครบถ้วน";
  } else {
    $stmt = $conn->prepare("SELECT Email_member, Password, First_name, Last_name FROM member WHERE Email_member = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    // bind ทั้งหมดจาก statement เดียว
    $stmt->bind_result($emailDB, $hashedPassword, $first, $last);
    $stmt->fetch();

    if (password_verify($password, $hashedPassword)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['email'] = $emailDB;
        $_SESSION['First_name'] = $first;
        $_SESSION['Last_name'] = $last;

        header("Location: home.php");
        exit;
    } else {
        $error = "รหัสผ่านไม่ถูกต้อง";
    }
} else {
    $error = "ไม่พบอีเมลในระบบ";
}

$stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <title>เข้าสู่ระบบ | Dom inn</title>
   <link rel="icon" type="image/png" href="../src/images/logo.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../CSS/css/lo.css" />
  <script>
    function showPopup(message, color = 'red') {
      const popup = document.createElement('div');
      popup.innerText = message;
      popup.style.position = 'fixed';
      popup.style.top = '20px';
      popup.style.left = '50%';
      popup.style.transform = 'translateX(-50%)';
      popup.style.background = color === 'red' ? '#f44336' : '#4CAF50';
      popup.style.color = 'white';
      popup.style.padding = '16px 32px';
      popup.style.borderRadius = '8px';
      popup.style.zIndex = '9999';
      popup.style.fontSize = '18px';
      popup.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
      document.body.appendChild(popup);
      setTimeout(() => {
        popup.style.transition = 'opacity 0.5s';
        popup.style.opacity = 0;
        setTimeout(() => document.body.removeChild(popup), 500);
      }, 2000);
    }
  </script>
</head>

<body>
  <div class="login-container">
    <div class="login-logo">
      <span class="dom">Dom</span><span class="inn">inn</span>
    </div>
    <form class="login-form" action="" method="post">
      <h2>เข้าสู่ระบบ</h2>

      <?php if ($error): ?>
        <script>
          window.onload = function() {
            showPopup("<?= $error ?>", "red");
          }
        </script>
      <?php endif; ?>

      <div class="input-group">
        <label for="Email_member">อีเมล</label>
        <input
          type="email"
          id="Email_member"
          name="Email_member"
          value="<?= htmlspecialchars($_POST['Email_member'] ?? '') ?>"
          required
          autocomplete="email"
          pattern="^[A-Za-z0-9@._\-]+$"
          title="กรุณากรอกเป็นภาษาอังกฤษเท่านั้น"
          oninput="this.value = this.value.replace(/[^A-Za-z0-9@._\-]/g, '')">
      </div>

      <div class="input-group">
        <label for="password">รหัสผ่าน</label>
        <div style="display: flex; align-items: center;">
          <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="current-password"
            pattern="^[A-Za-z0-9]+$"
            title="กรุณากรอกเฉพาะตัวอักษรภาษาอังกฤษและตัวเลขเท่านั้น"
            oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g, '')"
            style="flex: 1;">
          <button type="button" id="togglePassword" style="background: none; border: none; margin-left: 8px; cursor: pointer; padding: 0;">
            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <ellipse cx="12" cy="12" rx="10" ry="6" />
              <circle cx="12" cy="12" r="2" />
              <line x1="4" y1="20" x2="20" y2="4" />
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-login">เข้าสู่ระบบ</button>

      <div class="login-footer">
        <a href="#">ลืมรหัสผ่าน?</a>
        <span>หรือ</span>
        <a href="member.php">สมัครสมาชิกใหม่</a>
      </div>
    </form>
  </div>
  <script src="../JS/js/me.js"></script>
</body>

</html>