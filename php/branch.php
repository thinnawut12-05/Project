<?php
// เชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "hotel_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// ดึงข้อมูลจังหวัด + ที่อยู่ + เบอร์โทร
$sql = "SELECT Province_name, Address, Phone FROM province";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สาขาโรงแรม Dom inn</title>
    <link rel="stylesheet" href="branch.css">
</head>
<body>

    <div class="header">
        <h1>สาขาโรงแรม Dom inn</h1>
    </div>

    <div class="container">
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $province = $row["Province_name"];
                $address  = $row["Address"];
                $phone    = $row["Phone"];

                echo "
                <div class='branch-card'>
                    <h2>Dom inn สาขา {$province}</h2>
                    <p>ที่อยู่: {$address}</p>
                    <p>โทร: {$phone}</p>
                </div>
                ";
            }
        } else {
            echo "<p style='text-align:center;'>ไม่พบข้อมูล</p>";
        }
        $conn->close();
        ?>
    </div>

</body>
</html>
