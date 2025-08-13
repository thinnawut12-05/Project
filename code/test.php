<?php
    session_start();
    include 'db.php'; // เชื่อมต่อฐานข้อมูล
    header('Content-Type: application/json');

    // เตรียมคำสั่ง SQL
    $sql = "SELECT First_name, Last_name, Phone_number FROM member";
    $stmt = $conn->prepare($sql);

    // ตรวจสอบการเตรียมคำสั่ง SQL
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();

        // ดึงข้อมูลทั้งหมดในรูปแบบ associative array
        $data = $result->fetch_all(MYSQLI_ASSOC);

        // ส่งข้อมูลกลับเป็น JSON
        echo json_encode($data);

        $stmt->close();
    } else {
        echo json_encode(["error" => "Query failed"]);
    }

    $conn->close();
?>
