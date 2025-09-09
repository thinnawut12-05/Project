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

// ดึงข้อมูลจังหวัด
$sql = "SELECT Province_name FROM province";
$result = $conn->query($sql);

// สมมุติข้อมูลติดต่อ (ที่อยู่ + เบอร์โทร)
$branch_info = [
    "เชียงใหม่" => ["45 ถนนนิมมานเหมินทร์ ต.สุเทพ อ.เมือง เชียงใหม่ 50200", "053-555-678"],
    "พะเยา" => ["21 ถนนพหลโยธิน ต.เวียง อ.เมือง พะเยา 56000", "054-222-333"],
    "กรุงเทพ" => ["123 ถนนสุขุมวิท เขตวัฒนา เขตคลองเตย กรุงเทพฯ 10110", "02-123-4567"],
    "อ่างทอง" => ["88 ถนนโพธิ์ทอง ต.บางพลี อ.เมือง อ่างทอง 14000", "035-555-888"],
    "ขอนแก่น" => ["200 ถนนมิตรภาพ ต.ในเมือง อ.เมือง ขอนแก่น 40000", "043-222-444"],
    "นครราชสีมา" => ["150 ถนนราชดำเนิน ต.ในเมือง อ.เมือง นครราชสีมา 30000", "044-333-777"],
    "กาญจนบุรี" => ["12 ถนนแสงชูโต ต.บ้านใต้ อ.เมือง กาญจนบุรี 71000", "034-222-666"],
    "เพชรบุรี" => ["76 ถนนราชวิถี ต.คลองกระแชง อ.เมือง เพชรบุรี 76000", "032-444-555"],
    "สุราษฎร์ธานี" => ["99 ถนนตลาดใหม่ ต.ตลาด อ.เมือง สุราษฎร์ธานี 84000", "077-123-999"],
    "นครศรีธรรมราช" => ["66 ถนนราชดำเนิน ต.คลัง อ.เมือง นครศรีธรรมราช 80000", "075-555-111"],
];
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
                $address = $branch_info[$province][0] ?? "ไม่พบข้อมูลที่อยู่";
                $phone   = $branch_info[$province][1] ?? "ไม่พบเบอร์โทร";

                echo "
                <div class='branch-card'>
                    <h2>Dom inn สาขา ".$province." </h2>
                    <p>ที่อยู่: ".$address."</p>
                    <p>โทร: ".$phone."</p>
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
