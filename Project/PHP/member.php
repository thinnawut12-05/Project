<?php
include 'db.php'; // เชื่อมต่อฐานข้อมูล

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $Title_name  = trim($_POST["Title_name"]);
  $Gender  = trim($_POST["Gender"]);
  $First_name = trim($_POST["First_name"]);
  $Last_name = trim($_POST["Last_name"]);
  $Email_member = trim($_POST["Email_member"]);
  $Phone_Number = trim($_POST["Phone_Number"]);
  $Password = $_POST["password"];
  $confirmPassword = $_POST["confirm-password"];
  
  // ======== ✨ โค้ดที่แก้ไขแล้ว ========
  // เงื่อนไขเพศ: อนุญาตให้ นาย, นาง, นางสาว เลือกเพศ "อื่นๆ" ได้
  if (
    ($Title_name === 'นาย' && $Gender === 'หญิง') ||
    (($Title_name === 'นาง' || $Title_name === 'นางสาว') && $Gender === 'ชาย')
  ) {
    $error = "คำนำหน้า {$Title_name} ไม่สามารถเลือกเพศ {$Gender} ได้";
  }
  // ======== จบส่วนที่แก้ไข ========

  // เงื่อนไขอื่น ๆ ...
  if (empty($error) && (!preg_match("/^[ก-๙\s]+$/u", $First_name) || !preg_match("/^[ก-๙\s]+$/u", $Last_name))) {
    $error = "ชื่อและนามสกุลต้องเป็นภาษาไทยเท่านั้น";
  } elseif (empty($error) && !preg_match("/^[0-9]{10}$/", $Phone_Number)) {
    $error = "เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลักเท่านั้น";
  } elseif (empty($error) && $Password !== $confirmPassword) {
    $error = "รหัสผ่านไม่ตรงกัน";
  }

  // ✨ ตรวจสอบว่ามี error หรือไม่ก่อน insert
  if (empty($error)) {
    // เช็คอีเมลซ้ำ
    $stmt_check = $conn->prepare("SELECT Email_member FROM member WHERE Email_member = ?");
    $stmt_check->bind_param("s", $Email_member);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
      $error = "มีอีเมลนี้ในระบบแล้ว";
      $stmt_check->close();
    } else {
      $stmt_check->close();
      $hashedPassword = password_hash($Password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO member (First_name, Last_name, Email_member, Phone_Number, Title_name, Gender, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssssss", $First_name, $Last_name, $Email_member, $Phone_Number, $Title_name, $Gender, $hashedPassword);

      if ($stmt->execute()) {
        $success = "สมัครสมาชิกสำเร็จแล้ว!";
      } else {
        $error = "เกิดข้อผิดพลาด: " . $stmt->error;
      }

      $stmt->close();
    }
    $conn->close();
  }

}
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <title>สมัครสมาชิก | Dom inn</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../CSS/css/member.css">
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
  <div class="register-container">
    <div class="register-logo">
      <span class="dom">Dom</span><span class="inn">inn</span>
    </div>
    <form class="register-form" action="member.php" method="post">
      <h2>สมัครสมาชิก</h2>

      <div id="php-alert">
        <?php if ($error): ?>
          <script>
            window.onload = function() {
              showPopup("<?= addslashes($error) ?>", "red");
            }
          </script>
        <?php elseif ($success): ?>
          <script>
            window.onload = function() {
              showPopup("<?= addslashes($success) ?>", "green");
            }
          </script>
        <?php endif; ?>
      </div>

      <div class="input-group">
        <label>คำนำหน้าชื่อ</label><br>
        <label>
          <input type="radio" name="Title_name" value="นาย"
            <?= (($_POST['Title_name'] ?? '') == 'นาย') ? 'checked' : '' ?> required> นาย
        </label>
        <label>
          <input type="radio" name="Title_name" value="นาง"
            <?= (($_POST['Title_name'] ?? '') == 'นาง') ? 'checked' : '' ?> required> นาง
        </label>
        <label>
          <input type="radio" name="Title_name" value="นางสาว"
            <?= (($_POST['Title_name'] ?? '') == 'นางสาว') ? 'checked' : '' ?> required> นางสาว
        </label>
      </div>

      <div class="input-group">
        <label>เพศ</label><br>
        <label>
          <input type="radio" name="Gender" value="ชาย"
            <?= (($_POST['Gender'] ?? '') == 'ชาย') ? 'checked' : '' ?> required> ชาย
        </label>
        <label>
          <input type="radio" name="Gender" value="หญิง"
            <?= (($_POST['Gender'] ?? '') == 'หญิง') ? 'checked' : '' ?> required> หญิง
        </label>
        <label>
          <input type="radio" name="Gender" value="อื่นๆ"
            <?= (($_POST['Gender'] ?? '') == 'อื่นๆ') ? 'checked' : '' ?> required> อื่นๆ
        </label>
      </div>

      <div class="input-group">
        <label for="First_name">ชื่อ</label>
        <input
          type="text"
          id="First_name"
          name="First_name"
          value="<?= htmlspecialchars($_POST['First_name'] ?? '') ?>"
          required
          pattern="^[ก-๙\s]+$"
          title="กรุณากรอกเฉพาะอักษรภาษาไทยเท่านั้น"
          oninput="this.value = this.value.replace(/[^ก-๙\s]/g, '')">
      </div>

      <div class="input-group">
        <label for="Last_name">นาสกุล</label>
        <input
          type="text"
          id="Last_name"
          name="Last_name"
          value="<?= htmlspecialchars($_POST['Last_name'] ?? '') ?>"
          required
          pattern="^[ก-๙\s]+$"
          title="กรุณากรอกเฉพาะอักษรภาษาไทยเท่านั้น"
          oninput="this.value = this.value.replace(/[^ก-๙\s]/g, '')">
      </div>

      <div class="input-group">
        <label for="Email_member">อีเมล</label>
        <input
          type="Email_member"
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
        <label for="Phone_Number">เบอร์โทรศัพท์</label>
        <input
          type="tel"
          id="Phone_Number"
          name="Phone_Number"
          value="<?= htmlspecialchars($_POST['Phone_Number'] ?? '') ?>"
          required
          placeholder="เช่น 0812345678"
          pattern="^[0-9]+$"
          title="กรุณากรอกเฉพาะตัวเลขเท่านั้น"
          oninput="this.value = this.value.replace(/[^0-9]/g, '')">
      </div>

      <div class="input-group">
        <label for="Password">รหัสผ่าน</label>
        <div style="display: flex; align-items: center;">
          <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="new-password"
            pattern="^[A-Za-z0-9]+$"
            title="กรุณากรอกเฉพาะตัวอักษรภาษาอังกฤษและตัวเลขเท่านั้น"
            oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g, '')"
            style="flex: 1;">
          <button type="button" id="togglePassword" style="background: none; border: none; margin-left: 8px; cursor: pointer; padding: 0;">
            <!-- ไอคอนรูปตา SVG (เริ่มต้น "ปิดตา" มีขีดทับ) -->
            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <ellipse cx="12" cy="12" rx="10" ry="6" />
              <circle cx="12" cy="12" r="2" />
              <line x1="4" y1="20" x2="20" y2="4" />
            </svg>
          </button>
        </div>
      </div>
   

      <div class="input-group">
        <label for="confirm-password">ยืนยันรหัสผ่าน</label>
        <div style="display: flex; align-items: center;">
          <input
            type="password"
            id="confirm-password"
            name="confirm-password"
            required
            autocomplete="new-password"
            pattern="^[A-Za-z0-9]+$"
            title="กรุณากรอกเฉพาะตัวอักษรภาษาอังกฤษและตัวเลขเท่านั้น"
            oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g, '')"
            style="flex: 1;">
          <button type="button" id="toggleConfirmPassword" style="background: none; border: none; margin-left: 8px; cursor: pointer; padding: 0;">
            <!-- ไอคอนรูปตา SVG (เริ่มต้นปิดตา ขีดทับ) -->
            <svg id="eyeIconConfirm" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <ellipse cx="12" cy="12" rx="10" ry="6" />
              <circle cx="12" cy="12" r="2" />
              <line x1="4" y1="20" x2="20" y2="4" />
            </svg>
          </button>
        </div>
      </div>
    

      <button type="submit" class="btn-register">สมัครสมาชิก</button>
      <div class="register-footer">
        <span>มีบัญชีอยู่แล้ว?</span>
        <a href="./login.php">เข้าสู่ระบบ</a>
      </div>
    </form>
  </div>
  <script src="../JS/js/me.js"></script>
</body>

</html>