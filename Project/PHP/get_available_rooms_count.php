<?php
// db.php ควรมีการเชื่อมต่อ $conn ไว้
include 'db.php'; 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$response = ["max_rooms" => 1]; // ค่าเริ่มต้นอย่างน้อย 1 ห้อง

if (isset($_GET['province_id'])) {
    $province_id = intval($_GET['province_id']);

    if ($province_id > 0) {
        if (isset($conn)) {
            $conn->set_charset("utf8"); // ตรวจสอบและตั้งค่า charset
        }
        
        $sql_count_rooms = "SELECT COUNT(*) AS total_count FROM room WHERE Province_Id = ? AND Status = 'AVL'";
        $stmt = $conn->prepare($sql_count_rooms);
        if ($stmt) {
            $stmt->bind_param('i', $province_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row) {
                $response["max_rooms"] = max(1, $row['total_count']); // ต้องไม่น้อยกว่า 1
            }
            $stmt->close();
        } else {
            // บันทึกข้อผิดพลาดในการเตรียม Statement
            error_log("Failed to prepare statement for room count: " . $conn->error);
        }
    }
}

echo json_encode($response);

if ($conn) {
    $conn->close();
}
?>