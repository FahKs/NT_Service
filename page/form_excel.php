<?php
require_once '../config/condb.php'; // เชื่อมต่อฐานข้อมูล
require_once '../vendor/autoload.php'; // โหลด PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

// ฟังก์ชันลบลูกค้า
if (isset($_GET['delete_customer_id']) && !empty($_GET['delete_customer_id'])) {
    $customer_id = $_GET['delete_customer_id'];
    $stmt = $pdo->prepare("DELETE FROM customer WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    header("Location: form_excel.php?success=deleted");
    exit();
}

// ตรวจสอบการ Import Excel
if (isset($_POST['import'])) {
    if (isset($_FILES['excel']) && $_FILES['excel']['error'] == 0) {
        $fileName = $_FILES['excel']['tmp_name'];

        // โหลดไฟล์ Excel
        $spreadsheet = IOFactory::load($fileName);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        foreach ($data as $index => $row) {
            if ($index == 0) continue; // ข้ามหัวตาราง Excel

            // ดึงข้อมูลจาก Excel
            $customer_name   = isset($row[0]) ? trim($row[0]) : null;
            $customer_type   = isset($row[1]) ? trim($row[1]) : null;
            $customer_phone  = isset($row[2]) ? trim($row[2]) : null;
            $customer_status = isset($row[3]) ? trim($row[3]) : null;
            $amphure_name    = isset($row[4]) ? trim($row[4]) : null;
            $thambon_name    = isset($row[5]) ? trim($row[5]) : null;
            $create_at       = date('Y-m-d');

            // ค้นหา amphure_id
            $stmt = $pdo->prepare("SELECT amphure_id FROM amphure WHERE amphure_name = ?");
            $stmt->execute([$amphure_name]);
            $amphure = $stmt->fetch(PDO::FETCH_ASSOC);
            $amphure_id = $amphure['amphure_id'] ?? null;

            // ค้นหา thambon_id
            $stmt = $pdo->prepare("SELECT thambon_id FROM thambon WHERE thambon_name = ? AND amphure_id = ?");
            $stmt->execute([$thambon_name, $amphure_id]);
            $thambon = $stmt->fetch(PDO::FETCH_ASSOC);
            $thambon_id = $thambon['thambon_id'] ?? null;

            // ตรวจสอบและบันทึกข้อมูล
            if ($amphure_id && $thambon_id) {
                // เพิ่มข้อมูลในตาราง address
                $stmt = $pdo->prepare("INSERT INTO address (amphure_id, thambon_id) VALUES (?, ?)");
                $stmt->execute([$amphure_id, $thambon_id]);
                $address_id = $pdo->lastInsertId();

                // เพิ่มข้อมูลลูกค้าในตาราง customer
                $stmt = $pdo->prepare("INSERT INTO customer 
                    (customer_name, customer_type, customer_phone, customer_status, address_id, create_at, update_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $customer_name, 
                    $customer_type, 
                    $customer_phone, 
                    $customer_status, 
                    $address_id, 
                    $create_at, 
                    $create_at
                ]);
            } else {
                echo "<div class='alert alert-warning'>ไม่พบข้อมูลอำเภอหรือตำบลสำหรับลูกค้า: $customer_name</div>";
            }
        }
        echo "<div class='alert alert-success'>นำเข้าข้อมูลสำเร็จ!</div>";
    }
}

// ดึงข้อมูลลูกค้าพร้อมที่อยู่ (อำเภอ และตำบล)
$sql = "SELECT c.customer_id, c.customer_name, c.customer_type, c.customer_phone, 
               c.customer_status, a.address_text, amp.amphure_name, t.thambon_name, 
               c.create_at, c.update_at
        FROM customer c
        LEFT JOIN address a ON c.address_id = a.address_id
        LEFT JOIN amphure amp ON a.amphure_id = amp.amphure_id
        LEFT JOIN thambon t ON a.thambon_id = t.thambon_id
        ORDER BY c.customer_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Excel - ข้อมูลลูกค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>นำเข้าข้อมูลลูกค้าจาก Excel</h2>
    <!-- ฟอร์มอัปโหลดไฟล์ -->
    <form action="" method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="excelFile" class="form-label">เลือกไฟล์ Excel (.xls, .xlsx)</label>
            <input type="file" name="excel" class="form-control" id="excelFile" accept=".xls, .xlsx" required>
        </div>
        <button type="submit" name="import" class="btn btn-primary">Import Excel</button>
    </form>

    <!-- ตารางข้อมูลลูกค้า -->
    <hr>
    <h3>รายชื่อลูกค้า</h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>ชื่อลูกค้า</th>
                <th>ประเภท</th>
                <th>เบอร์โทรศัพท์</th>
                <th>สถานะ</th>
                <th>ที่อยู่</th>
                <th>อำเภอ</th>
                <th>ตำบล</th>
                <th>วันที่เพิ่ม</th>
                <th>การดำเนินการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $index => $customer): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($customer['customer_type']); ?></td>
                <td><?php echo htmlspecialchars($customer['customer_phone']); ?></td>
                <td><?php echo htmlspecialchars($customer['customer_status']); ?></td>
                <td><?php echo htmlspecialchars($customer['address_text']); ?></td>
                <td><?php echo htmlspecialchars($customer['amphure_name']); ?></td>
                <td><?php echo htmlspecialchars($customer['thambon_name']); ?></td>
                <td><?php echo htmlspecialchars($customer['create_at']); ?></td>
                <td>
                    <a href="add_edit_modal_customer.php?id=<?php echo $customer['customer_id']; ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                    <a href="?delete_customer_id=<?php echo $customer['customer_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบข้อมูลลูกค้า?');">ลบ</a>
                    <a href="bill.php?customer_id=<?php echo $customer['customer_id']; ?>" class="btn btn-sm btn-info">ดูบิล</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>            
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
