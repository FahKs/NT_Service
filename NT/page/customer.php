<?php
// customer/customer.php
require_once '../config/condb.php';
require_once '../functions/customer_functions.php';

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
    
     <!-- ปุ่มเพิ่มลูกค้า และ ฟอร์มกรอง/ค้นหา -->
     <div class="mb-3 d-flex justify-content-between align-items-center">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">เพิ่มลูกค้า</button>
        
        <!-- ฟอร์มกรองและค้นหา -->
        <form method="get" class="d-flex align-items-center">
            <div class="me-2">
                <input type="text" name="search_name" class="form-control" placeholder="ค้นหาชื่อลูกค้า" 
                       value="<?php echo htmlspecialchars($filters['search_name'] ?? ''); ?>">
            </div>
            
            <div class="me-2">
                <select name="filter_amphure" class="form-select" id="filterAmphure">
                    <option value="">เลือกอำเภอ</option>
                    <?php foreach($amphures as $amphure): ?>
                        <option value="<?php echo $amphure['amphure_id']; ?>" 
                            <?php echo ($filters['filter_amphure'] == $amphure['amphure_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($amphure['amphure_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="me-2">
                <select name="filter_thambon" class="form-select" id="filterThambon">
                    <option value="">เลือกตำบล</option>
                    <?php foreach($current_thambons as $thambon): ?>
                        <option value="<?php echo $thambon['thambon_id']; ?>" 
                            <?php echo ($filters['filter_thambon'] == $thambon['thambon_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($thambon['thambon_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- เพิ่มฟิลด์กรอง customer_type -->
            <div class="me-2">
                <select name="filter_customer_type" class="form-select" id="filterCustomerType">
                    <option value="">เลือกประเภทลูกค้า</option>
                    <?php foreach($customer_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" 
                            <?php echo ($filters['filter_customer_type'] == $type) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-secondary me-2">ค้นหา</button>
            <a href="customer.php" class="btn btn-outline-secondary">รีเซ็ต</a>
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
            <?php foreach($customers as $customer): ?>
                <tr>
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
