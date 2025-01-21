<?php
require_once '../config/config.php';
require_once 'functions.php';


// ตรวจสอบสัญญาที่ใกล้หมดเวลา (ภายใน 30 วัน)
$sql = "SELECT COUNT(*) as near_expiry_count 
        FROM bill_customer bc
        INNER JOIN customers c ON bc.id_customer = c.id_customer
        WHERE bc.end_date IS NOT NULL 
        AND bc.end_date != '0000-00-00'
        AND bc.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
        AND bc.contact_status != 'ยกเลิกสัญญา'
        AND bc.status_bill = 'ใช้งาน';";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$near_expiry_count = $result->fetch_assoc()['near_expiry_count'];

// Query รายละเอียดสัญญาที่ใกล้หมด
$sql_details = "SELECT 
            c.id_customer,
            c.name_customer,
            bc.id_bill,
            bc.end_date,
            bc.number_bill,
            bc.type_bill,
            DATEDIFF(bc.end_date, CURDATE()) as days_left
        FROM bill_customer bc
        INNER JOIN customers c ON bc.id_customer = c.id_customer
        WHERE bc.end_date IS NOT NULL 
        AND bc.end_date != '0000-00-00'
        AND bc.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
        AND bc.contact_status != 'ยกเลิกสัญญา'
        AND bc.status_bill = 'ใช้งาน'
        ORDER BY bc.end_date ASC";
$stmt_details = $conn->prepare($sql_details);
$stmt_details->execute();
$near_expiry_contracts = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'near_expiry_count' => $near_expiry_count,
    'contracts' => $near_expiry_contracts
]);