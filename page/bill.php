<?php
require_once '../config/condb.php';

// เริ่ม session เพื่อใช้สำหรับข้อความแสดงผล (success/error)
session_start();

// ประมวลผลการส่งฟอร์มสร้างบิลใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_bill') {
    try {
        // ตรวจสอบและรับข้อมูลจากฟอร์ม
        if (empty($_POST['bill_number'])) {
            throw new Exception("กรุณากรอกหมายเลขบิล");
        }
        $bill_number = trim($_POST['bill_number']);

        // ตรวจสอบความไม่ซ้ำของหมายเลขบิล
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM bill WHERE bill_number = :bill_number");
        $check_stmt->execute([':bill_number' => $bill_number]);
        if ($check_stmt->fetchColumn() > 0) {
            throw new Exception("หมายเลขบิลนี้มีอยู่แล้ว กรุณาใช้หมายเลขอื่น");
        }

        // รับข้อมูลอื่นๆ
        $bill_type = $_POST['bill_type'];
        $customer_id = $_POST['customer_id'];
        $total_price = 0;

        // เริ่มการทำธุรกรรม
        $pdo->beginTransaction();

        // แทรกข้อมูลบิล
        $bill_stmt = $pdo->prepare("
            INSERT INTO bill 
            (bill_number, bill_type, all_price, customer_id) 
            VALUES (:bill_number, :bill_type, :all_price, :customer_id)
        ");

        $bill_stmt->execute([
            ':bill_number' => $bill_number,
            ':bill_type' => $bill_type,
            ':customer_id' => $customer_id,
            ':all_price' => $total_price // จะอัปเดตภายหลัง
        ]);

        $bill_id = $pdo->lastInsertId();

        // ประมวลผลกลุ่มบิล
        if (isset($_POST['groups']) && is_array($_POST['groups'])) {
            $group_stmt = $pdo->prepare("
                INSERT INTO bill_group 
                (group_name, group_type, group_price_a, group_price_b, group_price, bill_id) 
                VALUES (:group_name, :group_type, :group_price_a, :group_price_b, :group_price, :bill_id)
            ");

            $group_info_stmt = $pdo->prepare("
                INSERT INTO bill_group_info 
                (group_info_name, bill_group_id) 
                VALUES (:group_info_name, :bill_group_id)
            ");

            foreach ($_POST['groups'] as $index => $group) {
                // ตรวจสอบและทำความสะอาดข้อมูล
                $group_name = trim($group['name']);
                $group_type = trim($group['type']);
                $group_price_a = floatval($group['price_a']);
                $group_price_b = floatval($group['price_b']);
                $group_price = $group_price_a + $group_price_b;

                if (empty($group_name)) {
                    throw new Exception("กรุณากรอกชื่อกลุ่มสำหรับกลุ่มที่ " . ($index + 1));
                }

                // แทรกข้อมูลกลุ่มบิล
                $group_stmt->execute([
                    ':group_name' => $group_name,
                    ':group_type' => $group_type,
                    ':group_price_a' => $group_price_a,
                    ':group_price_b' => $group_price_b,
                    ':group_price' => $group_price,
                    ':bill_id' => $bill_id
                ]);

                $bill_group_id = $pdo->lastInsertId();

                // แทรกข้อมูลเพิ่มเติมถ้ามี
                if (isset($group['info']) && is_array($group['info'])) {
                    foreach ($group['info'] as $info) {
                        $info = trim($info);
                        if (!empty($info)) {
                            $group_info_stmt->execute([
                                ':group_info_name' => $info,
                                ':bill_group_id' => $bill_group_id
                            ]);
                        }
                    }
                }

                // อัปเดตยอดรวม
                $total_price += $group_price;
            }

            // อัปเดตบิลด้วยยอดรวมที่คำนวณได้
            $update_price_stmt = $pdo->prepare("UPDATE bill SET all_price = :total_price WHERE bill_id = :bill_id");
            $update_price_stmt->execute([
                ':total_price' => $total_price,
                ':bill_id' => $bill_id
            ]);
        }

        // ยืนยันการทำธุรกรรม
        $pdo->commit();

        // ตั้งข้อความแสดงความสำเร็จ
        $_SESSION['success'] = "สร้างบิลใหม่เรียบร้อยแล้ว";

        // รีไดเรกไปยังหน้าเดียวกันเพื่อรีเฟรชข้อมูล
        header("Location: bill.php");
        exit();
    } catch (Exception $e) {
        // ยกเลิกการทำธุรกรรมในกรณีมีข้อผิดพลาด
        $pdo->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ดึงข้อมูลลูกค้าเพื่อใช้ใน dropdown
try {
    $customer_stmt = $pdo->query("SELECT customer_id, customer_name FROM customer ORDER BY customer_name");
    $customers = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลลูกค้า: " . $e->getMessage());
}

// ตรวจสอบว่ามี customer_id จาก GET หรือไม่
$customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;

try {
    // หากมี customer_id ให้ดึงชื่อลูกค้าก่อน
    $customer_name = 'ไม่พบข้อมูลลูกค้า';
    if ($customer_id) {
        $customer_stmt = $pdo->prepare("SELECT customer_name FROM customer WHERE customer_id = :customer_id");
        $customer_stmt->execute([':customer_id' => $customer_id]);
        $customer_result = $customer_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer_result) {
            $customer_name = $customer_result['customer_name'];
        }
    }

    // สร้าง SQL พื้นฐานสำหรับดึงข้อมูลบิล
    $sql = "
        SELECT 
            b.bill_id, 
            b.bill_number, 
            b.bill_type, 
            b.bill_create_at, 
            b.bill_update_at, 
            b.all_price, 
            c.customer_name,
            bg.group_name, 
            bg.group_price, 
            bgi.group_info_name
        FROM 
            bill b
        LEFT JOIN 
            customer c ON b.customer_id = c.customer_id
        LEFT JOIN 
            bill_group bg ON b.bill_id = bg.bill_id
        LEFT JOIN 
            bill_group_info bgi ON bg.bill_group_id = bgi.bill_group_id
    ";

    // ตรวจสอบว่ามี customer_id หรือไม่
    if ($customer_id) {
        $sql .= " WHERE b.customer_id = :customer_id";
    }

    $sql .= " ORDER BY b.bill_create_at DESC";

    // เตรียมและประมวลผล SQL
    $stmt = $pdo->prepare($sql);

    if ($customer_id) {
        $stmt->execute([':customer_id' => $customer_id]);
    } else {
        $stmt->execute();
    }

    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการบิล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include '../components/header.php'; ?>
</head>
<body>
<div class="container mt-5">
    <h2>
    <?php 
    if ($customer_id) {
        // กรณีระบุ customer_id
        $bill_count = count($bills);
        echo "บิลของลูกค้า: " . htmlspecialchars($customer_name) . " (ทั้งหมด $bill_count บิล)";
    } else {
        // กรณีไม่ระบุ customer_id ให้แสดงรายการบิลทั้งหมด
        echo "รายการบิลทั้งหมด";
    }
    ?>
    </h2>

    <!-- แสดงข้อความสำเร็จหรือข้อผิดพลาด -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['success']); 
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['error']); 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
        </div>
    <?php endif; ?>

    <!-- ปุ่มเปิดโมดัลสร้างบิลใหม่ -->
    <div class="mb-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBillModal">
            สร้างบิลใหม่
        </button>
    </div>
    
    <!-- ปุ่มย้อนกลับ -->
    <div class="mb-3">
        <a href="customer.php" class="btn btn-secondary">ย้อนกลับ</a>
    </div>
    

    <!-- ตารางแสดงบิล -->
    <?php if (!empty($bills)): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>รหัสบิล</th>
                    <th>เลขบิล</th>
                    <th>ประเภทบิล</th>
                    <th>วันที่สร้าง</th>
                    <th>วันที่อัปเดต</th>
                    <th>ยอดรวม (บาท)</th>
                    <th>ชื่อลูกค้า</th>
                    <th>ชื่อกลุ่ม</th>
                    <th>ราคากลุ่ม</th>
                    <th>ข้อมูลเพิ่มเติม</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bills as $bill): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bill['bill_id']); ?></td>
                        <td><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                        <td><?php echo htmlspecialchars($bill['bill_type']); ?></td>
                        <td><?php echo htmlspecialchars($bill['bill_create_at']); ?></td>
                        <td><?php echo htmlspecialchars($bill['bill_update_at']); ?></td>
                        <td><?php echo htmlspecialchars(number_format($bill['all_price'], 2)); ?></td>
                        <td><?php echo htmlspecialchars($bill['customer_name'] ?? $customer_name); ?></td>
                        <td><?php echo htmlspecialchars($bill['group_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars(number_format($bill['group_price'] ?? 0, 2)); ?></td>
                        <td><?php echo htmlspecialchars($bill['group_info_name'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <?php 
            // กรณีไม่มีบิลสำหรับลูกค้านี้
            if ($customer_id) {
                echo "ไม่มีบิลสำหรับลูกค้า: " . htmlspecialchars($customer_name);
            } else {
                echo "ไม่มีข้อมูลบิล";
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- โมดัลสำหรับสร้างบิลใหม่ -->
    <div class="modal fade" id="addBillModal" tabindex="-1" aria-labelledby="addBillModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="billForm" method="POST" action="">
                    <input type="hidden" name="action" value="add_bill">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBillModalLabel">สร้างบิลใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>
                    <div class="modal-body">
                        <!-- หมายเลขบิล -->
                        <div class="mb-3">
                            <label for="bill_number" class="form-label">หมายเลขบิล</label>
                            <input type="text" class="form-control" id="bill_number" name="bill_number" required>
                        </div>

                        <!-- ลูกค้า -->
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">เลือกลูกค้า</label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">เลือกลูกค้า</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['customer_id']; ?>">
                                        <?php echo htmlspecialchars($customer['customer_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- ประเภทบิล -->
                        <div class="mb-3">
                            <label class="form-label">ประเภทบิล</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bill_type" id="type1" value="ประเภท1" required>
                                    <label class="form-check-label" for="type1">ประเภท 1</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bill_type" id="type2" value="ประเภท2">
                                    <label class="form-check-label" for="type2">ประเภท 2</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bill_type" id="type3" value="ประเภท3">
                                    <label class="form-check-label" for="type3">ประเภท 3</label>
                                </div>
                            </div>
                        </div>

                        <!-- จำนวนกลุ่ม -->
                        <div class="mb-3">
                            <label for="group_count" class="form-label">จำนวนกลุ่ม</label>
                            <select class="form-select" id="group_count" required>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> กลุ่ม</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- คอนเทนเนอร์สำหรับกลุ่ม -->
                        <div id="groupContainer"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="submit" class="btn btn-primary">สร้างบิล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- Bootstrap JS และ dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const groupCountSelect = document.getElementById('group_count');
    const groupContainer = document.getElementById('groupContainer');

    // ฟังก์ชันสร้างกลุ่ม
    function generateGroups() {
        const groupCount = parseInt(groupCountSelect.value, 10);
        groupContainer.innerHTML = ''; // ล้างคอนเทนเนอร์

        for (let i = 0; i < groupCount; i++) {
            const groupDiv = document.createElement('div');
            groupDiv.classList.add('card', 'mb-3', 'p-3');
            groupDiv.innerHTML = `
                <h5>กลุ่มที่ ${i + 1}</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ชื่อกลุ่ม</label>
                        <input type="text" name="groups[${i}][name]" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ประเภทกลุ่ม</label>
                        <select name="groups[${i}][type]" class="form-select" required>
                            <option value="1">ประเภท 1</option>
                            <option value="2">ประเภท 2</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ราคากลุ่ม A</label>
                        <input type="number" name="groups[${i}][price_a]" class="form-control" required min="0" step="0.01">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ราคากลุ่ม B</label>
                        <input type="number" name="groups[${i}][price_b]" class="form-control" required min="0" step="0.01">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">ข้อมูลเพิ่มเติม</label>
                        <div id="infoContainer_${i}">
                            <div class="input-group mb-2 info-group">
                                <input type="text" name="groups[${i}][info][]" class="form-control" placeholder="กรอกข้อมูลเพิ่มเติม">
                                <button type="button" class="btn btn-danger remove-info-btn">ลบ</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary add-info-btn" data-group-index="${i}">เพิ่มข้อมูล</button>
                    </div>
                </div>
            `;
            groupContainer.appendChild(groupDiv);
        }

        // เพิ่ม Event Listener สำหรับปุ่มเพิ่มข้อมูลกลุ่ม
        const addInfoButtons = document.querySelectorAll('.add-info-btn');
        addInfoButtons.forEach(button => {
            button.addEventListener('click', function() {
                const groupIndex = this.getAttribute('data-group-index');
                addInfoField(groupIndex);
            });
        });

        // เพิ่ม Event Listener สำหรับปุ่มลบข้อมูลกลุ่ม
        const removeInfoButtons = document.querySelectorAll('.remove-info-btn');
        removeInfoButtons.forEach(button => {
            button.addEventListener('click', function() {
                this.parentElement.remove();
            });
        });
    }

    // ฟังก์ชันเพิ่มฟิลด์ข้อมูลกลุ่ม
    function addInfoField(groupIndex) {
        const infoContainer = document.getElementById(`infoContainer_${groupIndex}`);
        const infoGroupDiv = document.createElement('div');
        infoGroupDiv.classList.add('input-group', 'mb-2', 'info-group');
        infoGroupDiv.innerHTML = `
            <input type="text" name="groups[${groupIndex}][info][]" class="form-control" placeholder="ชื่อแพคเก็จ">
            <button type="button" class="btn btn-danger remove-info-btn">ลบ</button>
        `;
        infoContainer.appendChild(infoGroupDiv);

        // เพิ่ม Event Listener สำหรับปุ่มลบ
        const removeButton = infoGroupDiv.querySelector('.remove-info-btn');
        removeButton.addEventListener('click', function() {
            this.parentElement.remove();
        });
    }

    // สร้างกลุ่มเริ่มแรก
    generateGroups();

    // เปลี่ยนแปลงจำนวนกลุ่ม
    groupCountSelect.addEventListener('change', generateGroups);
});
</script>
</body>
</html>