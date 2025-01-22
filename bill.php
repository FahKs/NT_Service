<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/config.php';
require_once '../function/functions.php';

$id_customer = isset($_GET['id_customer']) ? intval($_GET['id_customer']) : 0;
$bill_id = isset($_GET['id_bill']) ? intval($_GET['id_bill']) : 0;
$customer_name = '';
if ($id_customer > 0) {
    $sql = "SELECT name_customer FROM customers WHERE id_customer = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_customer);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $customer_name = $customer['name_customer'];
}

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_bill'])) {
        // รับข้อมูลจากฟอร์ม
        $id_customer = $_POST['id_customer'];
        $number_bill = $_POST['number_bill'];
        $type_bill = $_POST['type_bill'];
        $status_bill = $_POST['status_bill'];
        $create_at = $_POST['create_at'];
        $date_count = $_POST['date_count'];

        // คำนวณวันที่สิ้นสุดสัญญาจากเดือน
        $end_date = date('Y-m-d', strtotime($create_at . " + $date_count months"));

        // สร้างบิลใหม่
        $sql = "INSERT INTO bill_customer (id_customer, number_bill, type_bill, status_bill, create_at, update_at, date_count, end_date) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssss", $id_customer, $number_bill, $type_bill, $status_bill, $create_at, $date_count, $end_date);
        $stmt->execute();
        $id_bill = $stmt->insert_id;

        // สร้างบริการตามจำนวนที่ระบุ
        if (isset($_POST['code_service'])) {
            foreach ($_POST['code_service'] as $index => $code_service) {
                $type_service = $_POST['type_service'][$index];
                $type_gadget = $_POST['type_gadget'][$index];
                $status_service = $_POST['status_service'][$index];

                $sql = "INSERT INTO service_customer (code_service, type_service, type_gadget, status_service, id_bill, create_at, update_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $code_service, $type_service, $type_gadget, $status_service, $id_bill);
                $stmt->execute();
            }
        }

        echo "<script>alert('บิลและบริการถูกสร้างเรียบร้อยแล้ว');</script>";
    } elseif (isset($_POST['update_bill'])) {
        // รับข้อมูลจากฟอร์ม
        $id_bill = $_POST['id_bill'];
        $id_customer = $_POST['id_customer'];
        $number_bill = $_POST['number_bill'];
        $type_bill = $_POST['type_bill'];
        $status_bill = $_POST['status_bill'];
        $create_at = $_POST['create_at'];
        $date_count = $_POST['date_count'];

        // คำนวณวันที่สิ้นสุดสัญญาจากเดือน
        $end_date = date('Y-m-d', strtotime($create_at . " + $date_count months"));

        // อัปเดตข้อมูลบิล
        $sql = "UPDATE bill_customer SET id_customer = ?, number_bill = ?, type_bill = ?, 
                status_bill = ?, create_at = ?, date_count = ?, end_date = ?, update_at = NOW() 
            WHERE id_bill = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssi", $id_customer, $number_bill, $type_bill, $status_bill, $create_at, $date_count, $end_date, $id_bill);
        $stmt->execute();

        echo "<script>alert('บิลถูกอัปเดตเรียบร้อยแล้ว');</script>";
    }   
}

$sql = "SELECT 
            c.name_customer AS customer_name,
            b.id_bill,
            b.number_bill AS bill_number,
            b.type_bill AS bill_type,
            b.status_bill AS bill_status,
            b.create_at AS bill_start_date,
            b.end_date AS end_date,
            b.contact_status as contact_status,
            SUM(CASE WHEN s.status_service = 'ใช้งาน' THEN 1 ELSE 0 END) AS active_services,
            SUM(CASE WHEN s.status_service = 'ยกเลิก' THEN 1 ELSE 0 END) AS canceled_services
        FROM bill_customer b
        JOIN customers c ON b.id_customer = c.id_customer
        LEFT JOIN service_customer s ON b.id_bill = s.id_bill
        WHERE 1=1";

if ($id_customer > 0) {
    $sql .= " AND b.id_customer = ?";
}

if ($bill_id > 0) {
    $sql .= " AND b.id_bill = ?";
}

$sql .= " GROUP BY b.id_bill ORDER BY b.create_at DESC";

$stmt = $conn->prepare($sql);

if ($id_customer > 0 && $bill_id > 0) {
    $stmt->bind_param("ii", $id_customer, $bill_id);
} elseif ($id_customer > 0) {
    $stmt->bind_param("i", $id_customer);
} elseif ($bill_id > 0) {
    $stmt->bind_param("i", $bill_id);
}

$stmt->execute();
$result = $stmt->get_result();
$bills = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <style>
        /* ซ่อนปุ่มค้นหาของ DataTables */
        .dataTables_filter {
            display: none;
        }
        .flex-wrap {
            gap: 0.5rem; /* ระยะห่างระหว่างองค์ประกอบ */
        }
        .border {
            border: 1px solid #e2e8f0; /* สีเส้นขอบ */
        }
        .rounded-md {
            border-radius: 0.375rem; /* มุมโค้ง */
        }
          /* เพิ่มสีพื้นหลังให้หัวตาราง */
        #billTable thead th {
            background-color: #4a5568; /* สีเทาเข้ม */
            color: white; /* สีตัวอักษรขาว */
        }

        /* ปรับสีพื้นหลังของแถวตาราง */
        #billTable tbody tr {
            background-color: rgb(255, 255, 255); /* สีขาว */
        }

        /* ปรับสีพื้นหลังของแถวตารางเมื่อโฮเวอร์ */
        #billTable tbody tr:hover {
            background-color: rgb(198, 198, 198); /* สีเทาอ่อน */
        }

        /* ปรับขนาดและสีของไอคอนสถานะ */
        .fa-circle {
            font-size: 12px; /* ปรับขนาดไอคอน */
        }
            .flex-wrap {
            gap: 0.5rem; /* ระยะห่างระหว่างองค์ประกอบ */
        }
        .border {
            border: 1px solid #e2e8f0; /* สีเส้นขอบ */
        }
        .rounded-md {
            border-radius: 0.375rem; /* มุมโค้ง */
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include './components/navbar.php'; ?>
        <!-- ตารางแสดงข้อมูลบิล -->
        <div class="mt-6">
            <div class="container mx-auto px-4 py-8">
            <h1 class="text-2xl font-bold mb-4">จัดการบิลสำหรับลูกค้า: <?= $id_customer > 0 ? htmlspecialchars($customer_name) : 'ทั้งหมด' ?></h1>
                <!-- ปุ่มและตัวค้นหา -->
                <div class="flex justify-between items-center mb-4">
                    <!-- ปุ่มสร้างบิลใหม่ -->
                    <button onclick="openCreateBillModal()" class="bg-blue-500 text-white px-4 py-2 rounded-md">สร้างบิลใหม่</button>
                    
                    <!-- ตัวค้นหาและตัวกรอง -->
                    <div class="flex items-center">
                        <div class="relative">
                            <input type="text" id="searchNumberBill" placeholder="ค้นหาเลขบิล" class="border p-2 rounded-md pl-10 mr-2">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i> <!-- ไอคอนค้นหา -->
                        </div>
                        <select id="filterTypeBill" class="p-2 border rounded-md mr-2">
                            <option value="">ทั้งหมด</option>
                            <option value="CIP+">CIP+</option>
                            <option value="Special Bill">Special Bill</option>
                            <option value="Nt1">Nt1</option>
                        </select>
                        <select id="filterStatusBill" class="p-2 border rounded-md mr-2">
                            <option value="">ทั้งหมด</option>
                            <option value="ใช้งาน">ใช้งาน</option>
                            <option value="ยกเลิกใช้งาน">ยกเลิกใช้งาน</option>
                        </select>
                        <button onclick="resetFilters()" class="bg-gray-500 text-white px-4 py-2 rounded-md"> <i class="fas fa-sync-alt"></i></button>
                    </div>
                </div>
            <table id="billTable" class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">ลำดับที่</th>
                        <th class="py-2 px-4 border-b">ชื่อลูกค้า</th>
                        <th class="py-2 px-4 border-b">บิลลูกค้า</th>
                        <th class="py-2 px-4 border-b">ประเภทบิล</th>
                        <th class="py-2 px-4 border-b">สถานะบิล</th>
                        <th class="py-2 px-4 border-b">จำนวนบริการที่ใช้งาน</th>
                        <th class="py-2 px-4 border-b">จำนวนบริการที่ยกเลิก</th>
                        <th class="py-2 px-4 border-b">วันที่เริ่มบิล</th>
                        <th class="py-2 px-4 border-b">วันที่สิ้นสุดสัญญา</th>
                        <th class="py-2 px-4 border-b">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bills)): ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td class="py-2 px-4 border-b text-center"><?php echo $counter; ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($bill['customer_name']); ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($bill['bill_type']); ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <?php if ($bill['bill_status'] === 'ใช้งาน'): ?>
                                        <i class="fas fa-circle text-green-500"></i><?= $bill['bill_status'] ?>
                                    <?php else: ?>
                                        <i class="fas fa-circle text-red-500"></i><?= $bill['bill_status'] ?>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border-b text-center"><?php echo $bill['active_services']; ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo $bill['canceled_services']; ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($bill['bill_start_date']); ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($bill['end_date']); ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <button onclick="openEditBillModal(<?php echo $bill['id_bill']; ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded-md">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="service_bill.php?id_bill=<?php echo $bill['id_bill']; ?>" class="bg-blue-500 text-white px-2 py-1 rounded-md">
                                        <i class="fas fa-info-circle"></i> Info
                                    </a>
                                    <div>
                                        <?php
                                        // ตรวจสอบว่า end_date น้อยกว่า 60 วันหรือไม่
                                        $end_date = new DateTime($bill['end_date']);
                                        $current_date = new DateTime();
                                        $interval = $current_date->diff($end_date);
                                        $days_left = $interval->days;

                                        if ($days_left < 60 && $bill['contact_status'] !== 'ยกเลิกสัญญา') {
                                            echo '<button onclick="openContractModal(' . $bill['id_bill'] . ')" class="bg-green-500 text-white px-2 py-1 rounded-md"><i class="fas fa-file-contract"></i> สัญญา</button>';
                                        }
                                        ?>
                                    </div>
                                    
                                </td>
                            </tr>
                            <?php $counter++; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="py-2 px-4 border-b text-center">ไม่มีข้อมูลบิล</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Include Modal -->
    <?php include './components/bill_modal.php'; ?>

    <!-- jQuery และ DataTables JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <script>
    // เรียกใช้ DataTables
    function resetFilters() {
        const table = $('#billTable').DataTable();
        $('#searchNumberBill').val('');
        $('#filterTypeBill').val('');
        $('#filterStatusBill').val('');
        table.search('').columns().search('').draw();
    }

    $(document).ready(function() {
        const table = $('#billTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [[10, 20, 50, 100, -1], [10, 20, 50, 100, "All"]],
            "language": {
                "search": "ค้นหา:",
                "lengthMenu": "แสดง _MENU_ แถวต่อหน้า",
                "info": "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ แถว",
                "paginate": {
                    "first": "แรก",
                    "last": "สุดท้าย",
                    "next": "ถัดไป",
                    "previous": "ก่อนหน้า"
                }
            },
        });

        // Event listeners for filters
        $('#searchNumberBill').on('keyup', function() {
            table.column(2).search(this.value).draw();
        });

        $('#filterTypeBill').on('change', function() {
            table.column(3).search(this.value).draw();
        });

        $('#filterStatusBill').on('change', function() {
            table.column(4).search(this.value).draw();
        });
    });;

    function openEditBillModal(id_bill) {
        // ดึงข้อมูลบิลและบริการจากฐานข้อมูล
        fetch(`../function/get_bill.php?id_bill=${id_bill}`)
            .then(response => response.json())
            .then(data => {
                // เตรียมข้อมูลสำหรับฟอร์มแก้ไข
                document.getElementById('id_bill').value = data.bill.id_bill;
                document.getElementById('id_customer').value = data.bill.id_customer;
                document.getElementById('number_bill').value = data.bill.number_bill;
                document.getElementById('type_bill').value = data.bill.type_bill;
                document.getElementById('status_bill').value = data.bill.status_bill;
                document.getElementById('create_at').value = data.bill.create_at;
                document.getElementById('date_count').value = data.bill.date_count;

                // ซ่อนส่วนของบริการ
                document.getElementById('services-container').innerHTML = '';
                document.getElementById('addServiceButton').classList.add('hidden');

                // เปิด Modal
                document.getElementById('createBillModal').classList.remove('hidden');
                document.getElementById('createBillButton').classList.add('hidden');
                document.getElementById('updateBillButton').classList.remove('hidden');
            });
    }
  
    function removeServiceField(button) {
        const serviceDiv = button.parentElement;
        serviceDiv.remove();
    }

    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const idCustomer = urlParams.get('id_customer');

        if (idCustomer) {
            document.getElementById('id_customer').value = idCustomer;
        }
    });
    function openContractModal(id_bill) {
        document.getElementById('contract_id_bill').value = id_bill;
        document.getElementById('contractModal').classList.remove('hidden');
    }

    function closeContractModal() {
        document.getElementById('contractModal').classList.add('hidden');
    }

    document.getElementById('contract_action').addEventListener('change', function() {
        const durationField = document.getElementById('contract_duration_field');
        if (this.value === 'ต่อสัญญา') {
            durationField.classList.remove('hidden');
        } else {
            durationField.classList.add('hidden');
        }
    });
    </script>
</body>
</html>