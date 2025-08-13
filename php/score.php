<?php
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "hotel_db");
$conn->set_charset("utf8");

// รับค่าจากฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room = $_POST['room'];
    $rating = $_POST['rating'];
    $review = $_POST['review'];

    $stmt = $conn->prepare("INSERT INTO room_reviews (room, rating, review) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $room_id, $rating, $review);

    if ($stmt->execute()) {
        $message = "ขอบคุณสำหรับการให้คะแนน!";
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ให้คะแนนห้องพัก</title>
<link rel="stylesheet" href="./score.css" />
</head>
<body>

<div class="container">
    <h2>ให้คะแนนห้องพัก</h2>
    <div class="hotel-info">
        <img src="./src/images/1.jpg" alt="ห้องพักตัวอย่าง">
        <p>ห้อง Deluxe Sea View</p>
    </div>

    <?php if (!empty($message)) { echo "<p class='message'>$message</p>"; } ?>

    <form method="POST">
        <input type="hidden" name="room_id" value="1">

        <div class="rating" id="rating-stars">
            <span data-value="1">&#9733;</span>
            <span data-value="2">&#9733;</span>
            <span data-value="3">&#9733;</span>
            <span data-value="4">&#9733;</span>
            <span data-value="5">&#9733;</span>
        </div>
        <input type="hidden" name="rating" id="rating-value" required>

        <textarea name="review" placeholder="เขียนรีวิวของคุณที่นี่..." required></textarea>
        <div style="text-align: center;">
            <button type="submit">ส่งคะแนน</button>
        </div>
    </form>
</div>

<script>
    const stars = document.querySelectorAll('#rating-stars span');
    const ratingValue = document.getElementById('rating-value');

    stars.forEach(star => {
        star.addEventListener('click', () => {
            let value = star.getAttribute('data-value');
            ratingValue.value = value;
            stars.forEach(s => s.classList.remove('active'));
            for (let i = 0; i < value; i++) {
                stars[i].classList.add('active');
            }
        });
    });
</script>

</body>
</html>
