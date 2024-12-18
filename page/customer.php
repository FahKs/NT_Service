<?php
// customer/customer.php
require_once '../config/condb.php';
require_once '../functions/customer_functions.php';
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;


// ตรวจสอบการ Import Excel
if (isset($_POST['import'])) {
    if (isset($_FILES['excel']) && $_FILES['excel']['error'] == 0) {
        $fileName = $_FILES['excel']['tmp_name'];

        try {
            $pdo->beginTransaction(); // เริ่ม Transaction

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

                // ตรวจสอบข้อมูลก่อน
                if (empty($customer_name) || empty($amphure_name) || empty($thambon_name)) {
                    echo "<div class='alert alert-danger'>ข้อมูลไม่ครบถ้วนในแถวที่ $index</div>";
                    continue;
                }
                    // ค้นหา amphure_id
                    $stmt = $pdo->prepare("SELECT amphure_id FROM amphure WHERE amphure_name = ?");
                    $stmt->execute([$amphure_name]);
                    $amphure = $stmt->fetch(PDO::FETCH_ASSOC);
                    $amphure_id = $amphure['amphure_id'] ?? null;

                    if (!$amphure_id) {
                        echo "<div class='alert alert-warning'>ไม่พบข้อมูลอำเภอ: $amphure_name</div>";
                        continue; // ข้ามแถวนี้ไป
                    }

                    // ค้นหา thambon_id
                    $stmt = $pdo->prepare("SELECT thambon_id FROM thambon WHERE thambon_name = ? AND amphure_id = ?");
                    $stmt->execute([$thambon_name, $amphure_id]);
                    $thambon = $stmt->fetch(PDO::FETCH_ASSOC);
                    $thambon_id = $thambon['thambon_id'] ?? null;

                    if (!$thambon_id) {
                        echo "<div class='alert alert-warning'>ไม่พบข้อมูลตำบล: $thambon_name ในอำเภอ $amphure_name</div>";
                        continue; // ข้ามแถวนี้ไป
                    }

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

            $pdo->commit(); // ยืนยัน Transaction
            echo "<div class='alert alert-success'>นำเข้าข้อมูลสำเร็จ!</div>";
        } catch (Exception $e) {
            $pdo->rollBack(); // ยกเลิกหากเกิดข้อผิดพลาด
            error_log("Import Excel error: " . $e->getMessage());
            echo "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการนำเข้าไฟล์: " . $e->getMessage() . "</div>";
        }
    }
}

// จัดการการลบลูกค้า
if (isset($_GET['delete_customer_id']) && !empty($_GET['delete_customer_id'])) {
    $customer_id = $_GET['delete_customer_id'];
    try {
        deleteCustomer($pdo, $customer_id);
        header("Location: customer.php?success=2");
        exit();
    } catch (Exception $e) {
        error_log("Customer delete error: " . $e->getMessage());
        header("Location: customer.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// จัดการการเพิ่ม/แก้ไขลูกค้า
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // บันทึกที่อยู่ก่อน
        $address_id = saveAddress(
            $pdo,
            $_POST['address_text'],
            $_POST['amphure_id'],
            $_POST['thambon_id']
        );

        // บันทึกข้อมูลลูกค้า
        saveCustomer($pdo, [
            'customer_id' => $_POST['customer_id'] ?? null,
            'customer_name' => $_POST['customer_name'],
            'customer_type' => $_POST['customer_type'],
            'customer_phone' => $_POST['customer_phone'],
            'customer_status' => $_POST['customer_status'],
            'address_id' => $address_id
        ]);

        $pdo->commit();
        header("Location: customer.php?success=1");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Customer save error: " . $e->getMessage());
        header("Location: customer.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// ดึงข้อมูลลูกค้าพร้อมตัวกรอง
$filters = [
  'search_name' => $_GET['search_name'] ?? null,
  'filter_amphure' => $_GET['filter_amphure'] ?? null,
  'filter_thambon' => $_GET['filter_thambon'] ?? null,
  'filter_customer_type' => $_GET['filter_customer_type'] ?? null 
];
$customer_types = [
  "เทศบาลเมือง",
  "เทศบาลตำบล",
  "อบจ",
  "อบต",
  "บริษัทเอกชน",
  "โรงพยาบาล",
  "วัด",
  "มูลนิธิ",
  "โรงเรียนประถม",
  "โรงเรียนมัธยม",
  "โรงเรียนเอกชน",
  "วิทยาลัย",
  "มหาวิทยาลัย"
];
$customers = getCustomers($pdo, $filters);
$amphures = getAmphures($pdo);
$current_thambons = !empty($filters['filter_amphure']) ? getThambons($pdo, $filters['filter_amphure']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลลูกค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include '../components/header.php'; ?>
</head>
<body>
<div class="container mt-5">
    <h2>รายชื่อลูกค้า</h2>
    
    <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <!-- ปุ่มเพิ่มลูกค้า -->
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">เพิ่มลูกค้า</button>
    </div>
    
    <!-- ฟอร์มอัปโหลดไฟล์ -->
    <form action="" method="post" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
        <input type="file" name="excel" class="form-control form-control-sm" id="excelFile" accept=".xls, .xlsx" required>
        <button type="submit" name="import" class="btn btn-primary btn-sm">Import Excel</button>
    </form>
    
    <!-- ฟอร์มกรองและค้นหา -->
    <div class="mb-3">
    <form method="get" class="row gx-2 gy-2 align-items-center">
        <div class="col-md-3">
            <input type="text" name="search_name" class="form-control form-control-sm" placeholder="ค้นหาชื่อลูกค้า"
                   value="<?php echo htmlspecialchars($filters['search_name'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <select name="filter_amphure" class="form-select form-select-sm" id="filterAmphure">
                <option value="">เลือกอำเภอ</option>
                <?php foreach($amphures as $amphure): ?>
                    <option value="<?php echo $amphure['amphure_id']; ?>" 
                        <?php echo ($filters['filter_amphure'] == $amphure['amphure_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($amphure['amphure_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filter_thambon" class="form-select form-select-sm" id="filterThambon">
                <option value="">เลือกตำบล</option>
                <?php foreach($current_thambons as $thambon): ?>
                    <option value="<?php echo $thambon['thambon_id']; ?>" 
                        <?php echo ($filters['filter_thambon'] == $thambon['thambon_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($thambon['thambon_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filter_customer_type" class="form-select form-select-sm" id="filterCustomerType">
                <option value="">เลือกประเภทลูกค้า</option>
                <?php foreach($customer_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" 
                        <?php echo ($filters['filter_customer_type'] == $type) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-secondary btn-sm">ค้นหา</button>
            <a href="customer.php" class="btn btn-outline-secondary btn-sm">รีเซ็ต</a>
        </div>
    </form>
</div>


    
    <!-- แสดงข้อความสำเร็จหรือผิดพลาด -->
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php 
                if($_GET['success'] == 1){
                    echo 'บันทึกข้อมูลสำเร็จ';
                } elseif($_GET['success'] == 2){
                    echo 'ลบข้อมูลลูกค้าสำเร็จ';
                }
            ?>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            เกิดข้อผิดพลาด: <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- ตารางแสดงรายชื่อลูกค้า -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>ชื่อลูกค้า</th>
                <th>ประเภท</th>
                <th>เบอร์โทรศัพท์</th>
                <th>สถานะ</th>
                <th>ที่อยู่</th>
                <th>อำเภอ</th>
                <th>ตำบล</th>
                <th>วันที่เพิ่ม</th>
                <th>วันที่แก้ไข</th>
                <th>การดำเนินการ</th>
            </tr>
        </thead>
        <tbody>
    <?php foreach($customers as $index => $customer): ?>
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
            <td><?php echo htmlspecialchars($customer['update_at']); ?></td>
            <td>
                <button class="btn btn-sm btn-warning edit-customer" 
                    data-bs-toggle="modal" 
                    data-bs-target="#customerModal"
                    data-customer-id="<?php echo $customer['customer_id']; ?>"
                    data-customer-name="<?php echo htmlspecialchars($customer['customer_name']); ?>"
                    data-customer-type="<?php echo htmlspecialchars($customer['customer_type']); ?>"
                    data-customer-phone="<?php echo htmlspecialchars($customer['customer_phone']); ?>"
                    data-customer-status="<?php echo htmlspecialchars($customer['customer_status']); ?>"
                    data-address-text="<?php echo htmlspecialchars($customer['address_text']); ?>"
                    data-amphure-id="<?php echo $customer['amphure_id']; ?>"
                    data-thambon-id="<?php echo $customer['thambon_id']; ?>"
                >
                    แก้ไข
                </button>
                <a href="customer.php?delete_customer_id=<?php echo $customer['customer_id']; ?>" 
                   class="btn btn-sm btn-danger" 
                   onclick="return confirm('คุณต้องการลบข้อมูลลูกค้าหรือไม่?')">ลบ</a>
                <a href="bill.php?customer_id=<?php echo $customer['customer_id']; ?>" 
                   class="btn btn-sm btn-info">ดูบิล</a>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

    </table>
    
    <!-- แสดงข้อความถ้าไม่พบข้อมูลลูกค้า -->
    <?php if(empty($customers)): ?>
        <div class="alert alert-info text-center">
            ไม่พบข้อมูลลูกค้า
        </div>
    <?php endif; ?>
</div>

<!-- โมดัลสำหรับเพิ่ม/แก้ไขลูกค้า -->
<?php include 'add_edit_modal_customer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/customer.js"></script>
</body>
</html>
