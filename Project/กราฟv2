<?php
session_start();
include 'db.php'; // ตรวจสอบว่า db.php เชื่อมต่อฐานข้อมูลเรียบร้อย

// *** ปรับปรุง: กำหนด Timezone ของ PHP ให้ชัดเจนที่สุดที่จุดเริ่มต้นของสคริปต์ ***
date_default_timezone_set('Asia/Bangkok'); // หรือโซนเวลาที่เหมาะสมกับสาขาโรงแรมของคุณ

// เปิดการแสดง error เพื่อช่วยในการ Debug (ควรปิดเมื่อใช้งานจริงบน Production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- ตรวจสอบการเชื่อมต่อฐานข้อมูล ---
if (!$conn) {
    die("❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . mysqli_connect_error());
}
$conn->set_charset("utf8");

// --- ตรวจสอบสถานะการล็อกอินและกำหนดบทบาทผู้ใช้ ---
$is_admin = false;
$is_officer = false;
$logged_in_user_name = 'ผู้ใช้งาน';
$logged_in_user_role = 'ไม่ระบุ';
$user_province_id_filter = null;
$user_province_name = '';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    $is_admin = true;
    $logged_in_user_name = $_SESSION['admin_name'] ?? 'Admin';
    $logged_in_user_role = 'Admin';
} elseif (isset($_SESSION['officer_logged_in']) && $_SESSION['officer_logged_in']) {
    $is_officer = true;
    $logged_in_user_name = $_SESSION['officer_name'] ?? 'Officer';
    $logged_in_user_role = 'Officer';
    $user_province_id_filter = $_SESSION['officer_province_id'] ?? null;

    if ($user_province_id_filter) {
        $sql_officer_province = "SELECT Province_name FROM province WHERE Province_Id = ?";
        $stmt_officer_province = $conn->prepare($sql_officer_province);
        if ($stmt_officer_province) {
            $stmt_officer_province->bind_param('i', $user_province_id_filter);
            $stmt_officer_province->execute();
            $stmt_officer_province->bind_result($province_name_db);
            $stmt_officer_province->fetch();
            $user_province_name = $province_name_db;
            $stmt_officer_province->close();
        }
    }
} else {
    // หากไม่มีใครล็อกอิน ให้เปลี่ยนเส้นทางไปหน้าล็อกอินหลัก
    header("Location: index.php"); // สมมติว่า index.php คือหน้าล็อกอิน
    exit();
}


// --- ตรรกะการกรองข้อมูลและดึงสถิติ ---
$chart_title = "สรุปยอดเข้าพักและจำนวนห้องที่จอง";
$filter_type = $_GET['filter_type'] ?? 'this_month'; // ตัวกรองเริ่มต้น

// ตัวแปรสำหรับ Custom Date Filter
$custom_year = $_GET['custom_year'] ?? '';
$custom_month = $_GET['custom_month'] ?? '';
$selected_province_id = $_GET['province_filter'] ?? ''; // สำหรับ Admin เท่านั้น

// *** แก้ไขตรงนี้: รวม Booking_status_Id = 7 (เช็คเอาท์แล้ว) เข้าไปด้วย ***
$sql_conditions_array = ["r.Booking_status_Id IN (3, 6, 7)"]; // 3: ชำระเงินสำเร็จ, 6: เช็คอินแล้ว, 7: เช็คเอาท์แล้ว


// กำหนดค่า bind_params สำหรับ Province_Id (ถ้ามี) ก่อนเงื่อนไขวันที่
$bind_types = "";
$bind_params = [];
$province_condition_added = false; // Flag to ensure province param is only added once

if ($is_officer && $user_province_id_filter !== null) {
    $sql_conditions_array[] = "r.Province_Id = ?";
    $bind_types .= "i";
    $bind_params[] = (int)$user_province_id_filter;
    $province_condition_added = true;
} elseif ($is_admin && !empty($selected_province_id)) {
    $sql_conditions_array[] = "r.Province_Id = ?";
    $bind_types .= "i";
    $bind_params[] = (int)$selected_province_id;
    $province_condition_added = true;
}


// SQL parts that will be dynamic
$sql_select_date_part = "";
$sql_group_by_date = "";
$sql_order_by_date = "";


// --- สร้างเงื่อนไขและส่วนของ SQL ตาม filter_type ---
if ($filter_type == 'today') {
    $chart_title .= " วันนี้ (" . date('d/m/Y') . ")";
    $sql_conditions_array[] = "r.Booking_date = CURDATE()";
    $sql_select_date_part = "DATE_FORMAT(r.Booking_date, '%H:00') AS label_period_time, p.Province_name, r.Province_Id";
    $sql_group_by_date = "GROUP BY DATE_FORMAT(r.Booking_date, '%H:00'), p.Province_name, r.Province_Id";
    $sql_order_by_date = "ORDER BY label_period_time ASC, p.Province_name ASC";
} elseif ($filter_type == 'this_month') {
    $chart_title .= " เดือนนี้ (" . date('m/Y') . ")";
    $sql_conditions_array[] = "MONTH(r.Booking_date) = MONTH(CURDATE()) AND YEAR(r.Booking_date) = YEAR(CURDATE())";
    $sql_select_date_part = "DAY(r.Booking_date) AS label_period_day, p.Province_name, r.Province_Id";
    $sql_group_by_date = "GROUP BY DAY(r.Booking_date), p.Province_name, r.Province_Id";
    $sql_order_by_date = "ORDER BY DAY(r.Booking_date) ASC, p.Province_name ASC";
} elseif ($filter_type == 'this_year') {
    $chart_title .= " ปีนี้ (" . date('Y') . ")";
    $sql_conditions_array[] = "YEAR(r.Booking_date) = YEAR(CURDATE())";
    $sql_select_date_part = "MONTH(r.Booking_date) AS label_period_month_num, p.Province_name, r.Province_Id";
    $sql_group_by_date = "GROUP BY MONTH(r.Booking_date), p.Province_name, r.Province_Id";
    $sql_order_by_date = "ORDER BY MONTH(r.Booking_date) ASC, p.Province_name ASC";
} elseif ($filter_type == 'custom') {
    // ต้องตรวจสอบว่ามีพารามิเตอร์ custom_year/month หรือไม่ มิฉะนั้นจะ fallback
    if (!empty($custom_year) && !empty($custom_month)) {
        $chart_title .= " เดือน " . $custom_month . " ปี " . $custom_year;
        $sql_conditions_array[] = "YEAR(r.Booking_date) = ? AND MONTH(r.Booking_date) = ?";
        $bind_types .= "ii";
        $bind_params[] = (int)$custom_year;
        $bind_params[] = (int)$custom_month;
        $sql_select_date_part = "DAY(r.Booking_date) AS label_period_day, p.Province_name, r.Province_Id";
        $sql_group_by_date = "GROUP BY DAY(r.Booking_date), p.Province_name, r.Province_Id";
        $sql_order_by_date = "ORDER BY DAY(r.Booking_date) ASC, p.Province_name ASC";
    } elseif (!empty($custom_year)) {
        $chart_title .= " ปี " . $custom_year;
        $sql_conditions_array[] = "YEAR(r.Booking_date) = ?";
        $bind_types .= "i";
        $bind_params[] = (int)$custom_year;
        $sql_select_date_part = "MONTH(r.Booking_date) AS label_period_month_num, p.Province_name, r.Province_Id";
        $sql_group_by_date = "GROUP BY MONTH(r.Booking_date), p.Province_name, r.Province_Id";
        $sql_order_by_date = "ORDER BY MONTH(r.Booking_date) ASC, p.Province_name ASC";
    } elseif (!empty($custom_month)) {
        $month_name_for_title = (new DateTime('2000-' . $custom_month . '-01'))->format('F');
        $chart_title .= " เดือน " . $month_name_for_title . " (ทุกปี)";
        $sql_conditions_array[] = "MONTH(r.Booking_date) = ?";
        $bind_types .= "i";
        $bind_params[] = (int)$custom_month;
        $sql_select_date_part = "YEAR(r.Booking_date) AS label_period_year, p.Province_name, r.Province_Id";
        $sql_group_by_date = "GROUP BY YEAR(r.Booking_date), p.Province_name, r.Province_Id";
        $sql_order_by_date = "ORDER BY YEAR(r.Booking_date) ASC, p.Province_name ASC";
    } else {
        // หากเลือก Custom แต่ไม่ได้ระบุอะไรเลย ให้กลับไปเริ่มต้นที่เดือนนี้
        $filter_type = 'this_month';
        $chart_title = "สรุปยอดเข้าพักและจำนวนห้องที่จอง เดือนนี้ (" . date('m/Y') . ")";
        $sql_conditions_array = ["r.Booking_status_Id IN (3, 6, 7)", "MONTH(r.Booking_date) = MONTH(CURDATE())", "YEAR(r.Booking_date) = YEAR(CURDATE())"];
        // ถ้าเป็น officer ให้กรองข้อมูลตามจังหวัดของตัวเอง
        if ($is_officer && $user_province_id_filter !== null) {
            $sql_conditions_array[] = "r.Province_Id = ?";
            // bind_params และ bind_types สำหรับ Province_Id ถูกเพิ่มไปแล้วด้านบนสุด
        } elseif ($is_admin && !empty($selected_province_id)) {
             $sql_conditions_array[] = "r.Province_Id = ?";
            // bind_params และ bind_types สำหรับ Province_Id ถูกเพิ่มไปแล้วด้านบนสุด
        }
        $sql_select_date_part = "DAY(r.Booking_date) AS label_period_day, p.Province_name, r.Province_Id";
        $sql_group_by_date = "GROUP BY DAY(r.Booking_date), p.Province_name, r.Province_Id";
        $sql_order_by_date = "ORDER BY DAY(r.Booking_date) ASC, p.Province_name ASC";
    }
}

$sql_conditions = implode(" AND ", $sql_conditions_array);


// --- ดึงข้อมูลสำหรับกราฟและตารางสรุป ---
$sql_summary = "SELECT
                    " . $sql_select_date_part . ",
                    SUM(r.Number_of_adults + r.Number_of_children) AS total_occupancy,
                    SUM(r.Number_of_rooms) AS total_rooms
                FROM reservation r
                LEFT JOIN province p ON r.Province_Id = p.Province_Id
                WHERE " . $sql_conditions . "
                " . $sql_group_by_date . "
                " . $sql_order_by_date;


$stmt_summary = $conn->prepare($sql_summary);

if ($stmt_summary === false) {
    die("Error preparing summary statement: " . $conn->error);
}

// ผูกพารามิเตอร์ (แก้ไขเพื่อส่งเป็น reference)
if (!empty($bind_types)) {
    $bind_params_with_references = [];
    $bind_params_with_references[] = $bind_types; // Argument แรกคือ string ชนิดข้อมูล
    foreach ($bind_params as $key => $value) {
        $bind_params_with_references[] = &$bind_params[$key]; // ส่งแต่ละ parameter เป็น reference
    }
    call_user_func_array([$stmt_summary, 'bind_param'], $bind_params_with_references);
}
$stmt_summary->execute();
$result_summary = $stmt_summary->get_result();

$summary_data = []; // เก็บข้อมูลสรุปสำหรับตาราง
$chart_labels = [];
$chart_occupancy_data = [];
$chart_rooms_data = [];

$month_names_for_chart = [
    1 => "ม.ค.",
    2 => "ก.พ.",
    3 => "มี.ค.",
    4 => "เม.ย.",
    5 => "พ.ค.",
    6 => "มิ.ย.",
    7 => "ก.ค.",
    8 => "ส.ค.",
    9 => "ก.ย.",
    10 => "ต.ค.",
    11 => "พ.ย.",
    12 => "ธ.ค."
];

while ($row = $result_summary->fetch_assoc()) {
    $summary_data[] = $row; // เก็บทุกแถวสำหรับตารางแสดงผล

    $occupancy_count = $row['total_occupancy'] ?? 0;
    $rooms_count = $row['total_rooms'] ?? 0;

    $label_text = '';
    if (isset($row['label_period_day'])) {
        $label_text = "วันที่ " . $row['label_period_day'];
    } elseif (isset($row['label_period_month_num'])) {
        $label_text = $month_names_for_chart[$row['label_period_month_num']];
    } elseif (isset($row['label_period_year'])) {
        $label_text = "ปี " . $row['label_period_year'];
    } elseif (isset($row['label_period_time'])) {
        $label_text = $row['label_period_time'];
    } else {
        $label_text = "ไม่ระบุ";
    }

    $full_label = $label_text;
    if ($is_admin && empty($selected_province_id) && isset($row['Province_name'])) {
        // ถ้าเป็น Admin และไม่ได้เลือกจังหวัดเฉพาะ ให้รวมชื่อจังหวัดเข้ากับ label
        $full_label .= " (" . htmlspecialchars($row['Province_name']) . ")";
    }

    $chart_labels[] = $full_label;
    $chart_occupancy_data[] = $occupancy_count;
    $chart_rooms_data[] = $rooms_count;
}
$stmt_summary->close();


// --- ดึงปีและเดือนที่มีข้อมูลสำหรับการกรองแบบ Custom (สำหรับ Admin) ---
// ถ้าเป็น officer ไม่ต้องแสดงตัวเลือกจังหวัดใน filter form
$all_years = [];
$all_months = [];
$all_provinces = []; // สำหรับ Admin

// ควรดึงปีและเดือนจากข้อมูลที่มี Booking_status_Id = 3, 6, 7 เท่านั้น
$sql_years_months_provinces_base_conditions = "WHERE Booking_status_Id IN (3, 6, 7)";
if ($is_officer && $user_province_id_filter !== null) {
    $sql_years_months_provinces_base_conditions .= " AND Province_Id = " . (int)$user_province_id_filter;
} elseif ($is_admin && !empty($selected_province_id)) {
    $sql_years_months_provinces_base_conditions .= " AND Province_Id = " . (int)$selected_province_id;
}


// ดึงปี
$sql_years = "SELECT DISTINCT YEAR(Booking_date) AS year FROM reservation " . $sql_years_months_provinces_base_conditions . " ORDER BY year DESC";
$res_years = $conn->query($sql_years);
if ($res_years) {
    while ($row_year = $res_years->fetch_assoc()) {
        $all_years[] = $row_year['year'];
    }
}

// ดึงเดือน
$sql_months = "SELECT DISTINCT MONTH(Booking_date) AS month FROM reservation " . $sql_years_months_provinces_base_conditions . " ORDER BY month ASC";
$res_months = $conn->query($sql_months);
if ($res_months) {
    while ($row_month = $res_months->fetch_assoc()) {
        $all_months[] = $row_month['month'];
    }
}

// ดึงจังหวัดทั้งหมด (สำหรับ Admin เท่านั้น)
if ($is_admin) {
    $sql_provinces = "SELECT Province_Id, Province_name FROM province ORDER BY Province_name ASC";
    $res_provinces = $conn->query($sql_provinces);
    if ($res_provinces) {
        while ($row_province = $res_provinces->fetch_assoc()) {
            $all_provinces[] = $row_province;
        }
    }
}


// --- ปรับปรุง: ดึงข้อมูลความเสียหายของห้องพัก (room_damages) พร้อมการกรองตามสาขา ---
$room_damages_data = [];
$sql_room_damages = "SELECT
    rd.Damage_Id,
    rd.Stay_Id,
    rd.Room_Id,
    rd.Damage_item,
    rd.Damage_description,
    rd.Damage_value,
    rd.Damage_date,
    rd.Officer_Email,
    rm.Province_Id,
    p.Province_name
FROM
    room_damages rd
JOIN
    room rm ON rd.Room_Id = rm.Room_ID
JOIN
    province p ON rm.Province_Id = p.Province_Id
WHERE 1=1"; // Base condition

$damage_bind_types = "";
$damage_bind_params = [];

// Apply province filter to room damages query
if ($is_officer && $user_province_id_filter !== null) {
    $sql_room_damages .= " AND rm.Province_Id = ?";
    $damage_bind_types .= "i";
    $damage_bind_params[] = (int)$user_province_id_filter;
} elseif ($is_admin && !empty($selected_province_id)) {
    $sql_room_damages .= " AND rm.Province_Id = ?";
    $damage_bind_types .= "i";
    $damage_bind_params[] = (int)$selected_province_id;
}
// ถ้า Admin และ $selected_province_id ว่างเปล่า (เลือก "ทุกสาขา") จะไม่มีเงื่อนไข Province_Id เพิ่มเติม
// ทำให้ดึงความเสียหายทั้งหมดมาแสดง (แต่จะใส่ชื่อสาขาใน label กราฟ)


$sql_room_damages .= " ORDER BY rd.Damage_date DESC";

$stmt_room_damages = $conn->prepare($sql_room_damages);

if ($stmt_room_damages === false) {
    error_log("Error preparing room_damages statement: " . $conn->error);
    // $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูลความเสียหายของห้องพัก: " . $conn->error; // อาจแสดงให้ผู้ใช้เห็น
} else {
    if (!empty($damage_bind_types)) {
        $damage_bind_params_with_references = [];
        $damage_bind_params_with_references[] = $damage_bind_types;
        foreach ($damage_bind_params as $key => $value) {
            $damage_bind_params_with_references[] = &$damage_bind_params[$key];
        }
        call_user_func_array([$stmt_room_damages, 'bind_param'], $damage_bind_params_with_references);
    }
    $stmt_room_damages->execute();
    $result_room_damages = $stmt_room_damages->get_result();

    if ($result_room_damages) {
        while ($row_damage = $result_room_damages->fetch_assoc()) {
            $room_damages_data[] = $row_damage;
        }
    }
    $stmt_room_damages->close();
}


// --- เตรียมข้อมูลสำหรับกราฟความเสียหาย ---
$damage_chart_labels = [];
$damage_chart_values = [];
$damage_chart_title = 'มูลค่าความเสียหายของห้องพักแต่ละรายการ';

// Adjust chart title based on selection
if ($is_officer && $user_province_name) {
    $damage_chart_title .= " (สาขา" . htmlspecialchars($user_province_name) . ")";
} elseif ($is_admin && !empty($selected_province_id)) {
    // หาชื่อจังหวัดสำหรับ $selected_province_id
    $selected_province_name_for_damage_chart = '';
    foreach($all_provinces as $prov) {
        if ($prov['Province_Id'] == $selected_province_id) {
            $selected_province_name_for_damage_chart = $prov['Province_name'];
            break;
        }
    }
    if ($selected_province_name_for_damage_chart) {
        $damage_chart_title .= " (สาขา" . htmlspecialchars($selected_province_name_for_damage_chart) . ")";
    }
}


foreach ($room_damages_data as $damage_row) {
    $label_prefix = "";
    // ถ้าเป็น Admin และ $selected_province_id ว่างเปล่า (เลือก "ทุกสาขา") ให้ใส่ชื่อสาขาใน label
    if ($is_admin && empty($selected_province_id) && isset($damage_row['Province_name'])) {
        $label_prefix = "(" . htmlspecialchars($damage_row['Province_name']) . ") ";
    }
    $damage_chart_labels[] = $label_prefix . "ID: " . htmlspecialchars($damage_row['Damage_Id']) . " (ห้อง " . htmlspecialchars($damage_row['Room_Id']) . ")";
    $damage_chart_values[] = (float)$damage_row['Damage_value'];
}

$damage_chart_data = [
    'labels' => $damage_chart_labels,
    'datasets' => [
        [
            'label' => 'มูลค่าความเสียหาย (บาท)',
            'backgroundColor' => 'rgba(255, 159, 64, 0.6)', // สีส้ม
            'borderColor' => 'rgba(255, 159, 64, 1)',
            'borderWidth' => 1,
            'data' => $damage_chart_values,
        ]
    ]
];

// ปิดการเชื่อมต่อฐานข้อมูล
if (isset($conn) && $conn->ping()) {
    $conn->close();
}


// แปลงข้อมูล PHP เป็น JSON เพื่อส่งให้ JavaScript
$chart_data = [
    'labels' => $chart_labels,
    'datasets' => [
        [
            'label' => 'จำนวนผู้เข้าพัก',
            'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
            'borderColor' => 'rgba(54, 162, 235, 1)',
            'borderWidth' => 1,
            'data' => $chart_occupancy_data,
        ],
        [
            'label' => 'จำนวนห้องที่จอง',
            'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
            'borderColor' => 'rgba(255, 99, 132, 1)',
            'borderWidth' => 1,
            'data' => $chart_rooms_data,
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>สรุปยอดเข้าพัก - Dom Inn Hotel</title>
    <link rel="icon" type="image/png" href="../src/images/logo.png" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .admin-navbar {
            background-color: #34495e;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .admin-navbar ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }

        .admin-navbar ul li {
            margin-right: 20px;
        }

        .admin-navbar ul li a {
            color: #ecf0f1;
            text-decoration: none;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .admin-navbar ul li a:hover,
        .admin-navbar ul li a.active {
            background-color: #1abc9c;
        }

        .welcome-text {
            color: #ecf0f1;
            font-weight: bold;
            margin-right: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h2, h3 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .filter-form {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #eaf3f8;
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .filter-form select,
        .filter-form input[type="radio"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .filter-form input[type="radio"] {
            margin-right: 5px;
        }

        .filter-form button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .filter-form button:hover {
            background-color: #0056b3;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .filter-buttons label {
            display: flex;
            align-items: center;
            background-color: #f0f0f0;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
            border: 1px solid #ddd;
        }

        .filter-buttons label:hover {
            background-color: #e0e0e0;
        }

        .filter-buttons input[type="radio"]:checked+span {
            color: #007bff;
            font-weight: bold;
        }

        .chart-container {
            width: 100%;
            height: 500px;
            /* กำหนดความสูงของกราฟ */
            margin-bottom: 30px;
        }

        .logout-link {
            text-decoration: none;
            color: #e74c3c;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 5px;
            background-color: #fff;
            transition: background-color 0.3s ease;
            float: right;
            margin-top: 5px;
        }

        .logout-link:hover {
            background-color: #fdd;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #eee;
            padding: 10px;
            text-align: center;
        }

        .summary-table thead th {
            background-color: #3498db;
            color: #fff;
        }

        .summary-table tbody tr:nth-child(even) {
            background-color: #f8f8f8;
        }

        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }
    </style>
</head>

<body>
    <div class="admin-navbar">
        <span class="welcome-text">สวัสดี, <?= htmlspecialchars($logged_in_user_name) ?> (<?= htmlspecialchars($logged_in_user_role) ?>)
            <?php if ($is_officer && $user_province_name): ?>
                - สาขา: <?= htmlspecialchars($user_province_name) ?>
            <?php endif; ?>
        </span>
        <div>
            <a href="index.php" class="logout-link">ออกจากระบบ</a>
            <?php if ($is_officer): ?>
                <a href="officer.php" class="btn-back">กลับหน้าหลักเจ้าหน้าที่</a>
            <?php elseif ($is_admin): ?>
                <a href="admin.php" class="btn-back">กลับหน้าหลักผู้ดูแลระบบ</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <h2><?= htmlspecialchars($chart_title) ?> <?= ($is_officer && $user_province_name) ? "ของสาขา" . htmlspecialchars($user_province_name) : "" ?></h2>

        <form method="GET" class="filter-form">
            <div class="filter-group filter-buttons">
                <label>
                    <input type="radio" name="filter_type" value="today" <?= ($filter_type == 'today' ? 'checked' : '') ?>>
                    <span>วันนี้</span>
                </label>
                <label>
                    <input type="radio" name="filter_type" value="this_month" <?= ($filter_type == 'this_month' ? 'checked' : '') ?>>
                    <span>เดือนนี้</span>
                </label>
                <label>
                    <input type="radio" name="filter_type" value="this_year" <?= ($filter_type == 'this_year' ? 'checked' : '') ?>>
                    <span>ปีนี้</span>
                </label>
                <label>
                    <input type="radio" name="filter_type" value="custom" <?= ($filter_type == 'custom' ? 'checked' : '') ?>>
                    <span>กำหนดเอง</span>
                </label>
            </div>

            <div class="filter-group">
                <label for="custom_year">ปี:</label>
                <select name="custom_year" id="custom_year">
                    <option value="">เลือกปี</option>
                    <?php foreach ($all_years as $year): ?>
                        <option value="<?= htmlspecialchars($year) ?>" <?= ($custom_year == $year ? 'selected' : '') ?>>
                            <?= htmlspecialchars($year) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="custom_month">เดือน:</label>
                <select name="custom_month" id="custom_month">
                    <option value="">เลือกเดือน</option>
                    <?php
                    $month_names_full = [
                        1 => "มกราคม",
                        2 => "กุมภาพันธ์",
                        3 => "มีนาคม",
                        4 => "เมษายน",
                        5 => "พฤษภาคม",
                        6 => "มิถุนายน",
                        7 => "กรกฎาคม",
                        8 => "สิงหาคม",
                        9 => "กันยายน",
                        10 => "ตุลาคม",
                        11 => "พฤศจิกายน",
                        12 => "ธันวาคม"
                    ];
                    foreach ($all_months as $month_num): ?>
                        <option value="<?= htmlspecialchars($month_num) ?>" <?= ($custom_month == $month_num ? 'selected' : '') ?>>
                            <?= htmlspecialchars($month_names_full[$month_num]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($is_admin): // Admin สามารถเลือกจังหวัดได้
            ?>
                <div class="filter-group">
                    <label for="province_filter">สาขา:</label>
                    <select name="province_filter" id="province_filter">
                        <option value="">เลือกทุกสาขา</option>
                        <?php foreach ($all_provinces as $province): ?>
                            <option value="<?= htmlspecialchars($province['Province_Id']) ?>" <?= (isset($_GET['province_filter']) && $_GET['province_filter'] == $province['Province_Id'] ? 'selected' : '') ?>>
                                <?= htmlspecialchars($province['Province_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <button type="submit">แสดงสถิติ</button>
        </form>

        <div class="chart-container">
            <canvas id="occupancyChart"></canvas>
        </div>

        <h3>ตารางสรุปข้อมูล</h3>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>ช่วงเวลา</th>
                    <?php if ($is_admin || ($is_officer && $user_province_name)): // แสดงคอลัมน์สาขา ถ้าเป็น Admin หรือ Officer ที่มีชื่อจังหวัด
                    ?>
                        <th>สาขา</th>
                    <?php endif; ?>
                    <th>จำนวนผู้เข้าพักทั้งหมด</th>
                    <th>จำนวนห้องที่จอง</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summary_data)): ?>
                    <tr>
                        <td colspan="<?= ($is_admin || ($is_officer && $user_province_name)) ? '4' : '3' ?>">ไม่พบข้อมูลในช่วงเวลาที่เลือก</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($summary_data as $data_row): ?>
                        <tr>
                            <td>
                                <?php
                                // กำหนด Label สำหรับตาราง
                                if (isset($data_row['label_period_day'])) {
                                    echo "วันที่ " . htmlspecialchars($data_row['label_period_day']);
                                } elseif (isset($data_row['label_period_month_num'])) {
                                    echo htmlspecialchars($month_names_full[$data_row['label_period_month_num']]);
                                } elseif (isset($data_row['label_period_year'])) {
                                    echo "ปี " . htmlspecialchars($data_row['label_period_year']);
                                } elseif (isset($data_row['label_period_time'])) {
                                    echo htmlspecialchars($data_row['label_period_time']);
                                } else {
                                    echo "ไม่ระบุ";
                                }
                                ?>
                            </td>
                            <?php if ($is_admin || ($is_officer && $user_province_name)): ?>
                                <td><?= htmlspecialchars($data_row['Province_name'] ?? 'ไม่ระบุ') ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($data_row['total_occupancy']) ?> คน</td>
                            <td><?= htmlspecialchars($data_row['total_rooms']) ?> ห้อง</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- เพิ่มส่วนนี้สำหรับแสดงกราฟมูลค่าความเสียหายของห้องพัก -->
        <h3 style="margin-top: 50px;">กราฟแสดงมูลค่าความเสียหายของห้องพักแต่ละรายการ</h3>
        <div class="chart-container">
            <canvas id="damageValueChart"></canvas>
        </div>
        <!-- สิ้นสุดส่วนกราฟมูลค่าความเสียหาย -->

    </div>

    <script>
        // กราฟสรุปยอดเข้าพักและจำนวนห้องที่จอง
        var ctx = document.getElementById('occupancyChart').getContext('2d');
        var chartData = <?= json_encode($chart_data); ?>;
        var chartTitle = "<?= htmlspecialchars($chart_title); ?>";

        var occupancyChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: chartTitle + "<?= ($is_officer && $user_province_name) ? " (สาขา" . htmlspecialchars($user_province_name) . ")" : "" ?>",
                        font: {
                            size: 18,
                            family: 'Kanit' // ใช้ font Kanit
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Kanit' // ใช้ font Kanit
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'ช่วงเวลา',
                            font: {
                                family: 'Kanit' // ใช้ font Kanit
                            }
                        },
                        ticks: {
                            font: {
                                family: 'Kanit' // ใช้ font Kanit
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'จำนวน',
                            font: {
                                family: 'Kanit' // ใช้ font Kanit
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                if (value % 1 === 0) { // แสดงเฉพาะตัวเลขเต็ม
                                    return value;
                                }
                            },
                            font: {
                                family: 'Kanit' // ใช้ font Kanit
                            }
                        }
                    }
                }
            }
        });

        // กราฟมูลค่าความเสียหายของห้องพัก
        var ctxDamage = document.getElementById('damageValueChart').getContext('2d');
        var damageChartData = <?= json_encode($damage_chart_data); ?>;
        var damageChartTitle = "<?= htmlspecialchars($damage_chart_title); ?>"; // รับ title ที่ถูกปรับแล้วจาก PHP

        var damageValueChart = new Chart(ctxDamage, {
            type: 'bar',
            data: damageChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: damageChartTitle, // ใช้ title ที่ปรับแล้ว
                        font: {
                            size: 18,
                            family: 'Kanit'
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Kanit'
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'รายการความเสียหาย',
                            font: {
                                family: 'Kanit'
                            }
                        },
                        ticks: {
                            font: {
                                family: 'Kanit'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'มูลค่าความเสียหาย (บาท)',
                            font: {
                                family: 'Kanit'
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' บาท'; // แสดงเป็นสกุลเงิน
                            },
                            font: {
                                family: 'Kanit'
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>