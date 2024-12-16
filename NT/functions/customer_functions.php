<?php
// functions/customer_functions.php
require_once '../config/condb.php';

/**
 * เพิ่มหรืแก้ไขที่อยู่
 */
function saveAddress($pdo, $address_text, $amphure_id, $thambon_id) {
    $stmt = $pdo->prepare("
        INSERT INTO address (Address_text, amphure_id, thambon_id) 
        VALUES (:address_text, :amphure_id, :thambon_id)
        ON DUPLICATE KEY UPDATE 
            Address_text = VALUES(Address_text), 
            amphure_id = VALUES(amphure_id), 
            thambon_id = VALUES(thambon_id)
    ");
    $stmt->execute([
        ':address_text' => $address_text,
        ':amphure_id' => $amphure_id,
        ':thambon_id' => $thambon_id
    ]);
    return $pdo->lastInsertId();
}

/**
 * บันทึกข้อมูลลูกค้า (เพิ่มหรือแก้ไข)
 */
function saveCustomer($pdo, $data) {
    if (!empty($data['customer_id'])) {
        // อัปเดตลูกค้า
        $stmt = $pdo->prepare("
            UPDATE customer 
            SET customer_name = :customer_name, 
                customer_type = :customer_type, 
                customer_phone = :customer_phone, 
                customer_status = :customer_status, 
                address_id = :address_id, 
                update_at = CURRENT_DATE 
            WHERE customer_id = :customer_id
        ");
        $stmt->execute([
            ':customer_name' => $data['customer_name'],
            ':customer_type' => $data['customer_type'],
            ':customer_phone' => $data['customer_phone'],
            ':customer_status' => $data['customer_status'],
            ':address_id' => $data['address_id'],
            ':customer_id' => $data['customer_id']
        ]);
    } else {
        // เพิ่มลูกค้าใหม่
        $stmt = $pdo->prepare("
            INSERT INTO customer 
            (customer_name, customer_type, customer_phone, customer_status, address_id, create_at, update_at) 
            VALUES 
            (:customer_name, :customer_type, :customer_phone, :customer_status, :address_id, CURRENT_DATE, CURRENT_DATE)
        ");
        $stmt->execute([
            ':customer_name' => $data['customer_name'],
            ':customer_type' => $data['customer_type'],
            ':customer_phone' => $data['customer_phone'],
            ':customer_status' => $data['customer_status'],
            ':address_id' => $data['address_id']
        ]);
    }
}

/**
 * ลบลูกค้า
 */
function deleteCustomer($pdo, $customer_id) {
    // เริ่ม transaction
    $pdo->beginTransaction();
    try {
        // ดึง address_id ของลูกค้าก่อนลบ
        $stmt = $pdo->prepare("SELECT address_id FROM customer WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($address) {
            $address_id = $address['address_id'];
            // ลบลูกค้า
            $stmt = $pdo->prepare("DELETE FROM customer WHERE customer_id = ?");
            $stmt->execute([$customer_id]);

            // ลบที่อยู่ถ้าไม่มีลูกค้ารองรับ
            $stmt = $pdo->prepare("
                DELETE FROM address 
                WHERE address_id = ? 
                AND NOT EXISTS (SELECT 1 FROM customer WHERE address_id = ?)
            ");
            $stmt->execute([$address_id, $address_id]);
        }

        // ยืนยันการเปลี่ยนแปลง
        $pdo->commit();
    } catch (Exception $e) {
        // ยกเลิกการเปลี่ยนแปลงในกรณีเกิดข้อผิดพลาด
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * ดึงข้อมูลลูกค้าพร้อมที่อยู่
 */
function getCustomers($pdo, $filters = []) {
    $query = "
        SELECT c.customer_id, c.customer_name, c.customer_type, c.customer_phone, c.customer_status, 
               c.create_at, c.update_at, a.address_text, a.amphure_id, a.thambon_id, 
               am.amphure_name, t.thambon_name
        FROM customer c
        JOIN address a ON c.address_id = a.address_id
        JOIN amphure am ON a.amphure_id = am.amphure_id
        JOIN thambon t ON a.thambon_id = t.thambon_id
        WHERE 1=1
    ";

    $params = [];

    if (!empty($filters['search_name'])) {
        $query .= " AND c.customer_name LIKE :search_name";
        $params[':search_name'] = "%" . $filters['search_name'] . "%";
    }

    if (!empty($filters['filter_amphure'])) {
        $query .= " AND a.amphure_id = :amphure_id";
        $params[':amphure_id'] = $filters['filter_amphure'];
    }

    if (!empty($filters['filter_thambon'])) {
        $query .= " AND a.thambon_id = :thambon_id";
        $params[':thambon_id'] = $filters['filter_thambon'];
    }

    if (!empty($filters['filter_customer_type'])) {
        $query .= " AND c.customer_type = :customer_type";
        $params[':customer_type'] = $filters['filter_customer_type'];
    }

    $query .= " ORDER BY c.customer_id";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ดึงข้อมูลอำเภอทั้งหมด
 */
function getAmphures($pdo) {
    $stmt = $pdo->query("SELECT * FROM amphure ORDER BY amphure_name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ดึงข้อมูลตำบลตามอำเภอ
 */
function getThambons($pdo, $amphure_id) {
    $stmt = $pdo->prepare("SELECT * FROM thambon WHERE amphure_id = ? ORDER BY thambon_name");
    $stmt->execute([$amphure_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
