<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

// ตรวจสอบว่ามีการเลือกประเภทรายงานหรือไม่
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : '';

// รับค่าตัวกรองจากฟอร์ม
$filter_type_customer = isset($_GET['filter_type_customer']) ? $_GET['filter_type_customer'] : '';
$filter_amphure = isset($_GET['filter_amphure']) ? $_GET['filter_amphure'] : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php include './components/navbar.php'; ?>

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Reports</h1>
        <form method="GET" action="">
            <!-- เลือกรายงาน -->
            <div class="mb-4">
                <label for="report_type" class="block mb-2">Select Report Type:</label>
                <select name="report_type" id="report_type" class="p-2 border rounded" onchange="this.form.submit()">
                    <option value="">-- Select Report --</option>
                    <option value="1" <?php echo $report_type == '1' ? 'selected' : ''; ?>>Report 1: Customers and Total Bills</option>
                    <option value="2" <?php echo $report_type == '2' ? 'selected' : ''; ?>>Report 2: Customers and Related Bill Numbers</option>
                    <option value="3" <?php echo $report_type == '3' ? 'selected' : ''; ?>>Report 3: Customers and Related Gadgets</option>
                </select>  
        <button 
            onclick="exportToExcel()"
            class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300">
            <i class="fas fa-file-excel mr-2"></i>
            Export to Excel </button>
        </div>

            <!-- ฟิลด์กรองข้อมูล (แสดงเฉพาะเมื่อเลือกรายงานแล้ว) -->
            <?php if ($report_type): ?>
                <div class="flex flex-wrap items-end gap-4 mb-4">
                    <!-- ตัวกรอง type_customer -->
                    <div>
                        <label for="filter_type_customer" class="block mb-2">Filter by Customer Type:</label>
                        <select name="filter_type_customer" id="filter_type_customer" class="p-2 border rounded">
                            <option value="">All</option>
                            <option value="อบต" <?php echo $filter_type_customer == 'อบต' ? 'selected' : ''; ?>>อบต</option>
                            <option value="อบจ" <?php echo $filter_type_customer == 'อบจ' ? 'selected' : ''; ?>>อบจ</option>
                            <option value="เทศบาล" <?php echo $filter_type_customer == 'เทศบาล' ? 'selected' : ''; ?>>เทศบาล</option>
                            <option value="โรงแรม" <?php echo $filter_type_customer == 'โรงแรม' ? 'selected' : ''; ?>>โรงแรม</option>
                        </select>
                    </div>

                    <!-- ตัวกรอง amphure -->
                    <div>
                        <label for="filter_amphure" class="block mb-2">Filter by Amphure:</label>
                        <select name="filter_amphure" id="filter_amphure" class="p-2 border rounded">
                            <option value="">All</option>
                            <?php
                            // ดึงข้อมูล amphures จากฐานข้อมูล
                            $sql_amphures = "SELECT * FROM amphures";
                            $result_amphures = $conn->query($sql_amphures);
                            while ($row_amphures = $result_amphures->fetch_assoc()) {
                                $selected = $filter_amphure == $row_amphures['id_amphures'] ? 'selected' : '';
                                echo "<option value='" . $row_amphures['id_amphures'] . "' $selected>" . $row_amphures['name_amphures'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- ตัวกรอง type_service และ type_gadget (แสดงเฉพาะใน Report 2) -->
                    <?php if ($report_type == '2'): ?>
                        <!-- ตัวกรอง type_service -->
                        <div>
                            <label for="filter_type_service" class="block mb-2">Filter by Service Type:</label>
                            <select name="filter_type_service" id="filter_type_service" class="p-2 border rounded">
                                <option value="">All</option>
                                <option value="Fttx" <?php echo isset($_GET['filter_type_service']) && $_GET['filter_type_service'] == 'Fttx' ? 'selected' : ''; ?>>Fttx</option>
                                <option value="Fttx+ICT solution" <?php echo isset($_GET['filter_type_service']) && $_GET['filter_type_service'] == 'Fttx+ICT solution' ? 'selected' : ''; ?>>Fttx+ICT solution</option>
                                <option value="Fttx 2+ICT solution" <?php echo isset($_GET['filter_type_service']) && $_GET['filter_type_service'] == 'Fttx 2+ICT solution' ? 'selected' : ''; ?>>Fttx 2+ICT solution</option>
                                <option value="SI service" <?php echo isset($_GET['filter_type_service']) && $_GET['filter_type_service'] == 'SI service' ? 'selected' : ''; ?>>SI service</option>
                                <option value="วงจเช่า" <?php echo isset($_GET['filter_type_service']) && $_GET['filter_type_service'] == 'วงจเช่า' ? 'selected' : ''; ?>>วงจเช่า</option>
                                <option value="IP phone" <?php echo isset($_GET['filter_type_service']) && $_GET['filter_type_service'] == 'IP phone' ? 'selected' : ''; ?>>IP phone</option>
                                <option value="Smart City" <?php echo isset($_GET['filter_type_service']) && $_GET['filter_type_service'] == 'Smart City' ? 'selected' : ''; ?>>Smart City</option>
                                <option value="WiFi" <?php echo isset($_GET['filter_type_service']) && $_GET['filter_type_service'] == 'WiFi' ? 'selected' : ''; ?>>WiFi</option>
                                <option value="อื่นๆ" <?php echo isset($_GET['filter_type_service']) && $_GET['filter_type_service'] == 'อื่นๆ' ? 'selected' : ''; ?>>อื่นๆ</option>
                            </select>
                        </div>

                        <!-- ตัวกรอง type_gadget -->
                        <div>
                            <label for="filter_type_gadget" class="block mb-2">Filter by Gadget Type:</label>
                            <select name="filter_type_gadget" id="filter_type_gadget" class="p-2 border rounded">
                                <option value="">All</option>
                                <option value="เช่า" <?php echo isset($_GET['filter_type_gadget']) && $_GET['filter_type_gadget'] == 'เช่า' ? 'selected' : ''; ?>>เช่า</option>
                                <option value="ขาย" <?php echo isset($_GET['filter_type_gadget']) && $_GET['filter_type_gadget'] == 'ขาย' ? 'selected' : ''; ?>>ขาย</option>
                                <option value="เช่าและขาย" <?php echo isset($_GET['filter_type_gadget']) && $_GET['filter_type_gadget'] == 'เช่าและขาย' ? 'selected' : ''; ?>>เช่าและขาย</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="p-2 bg-blue-500 text-white rounded">Apply Filters</button>
                </div>
            <?php endif; ?>
        </form>

        <?php
        if ($report_type == '1') {
            // Report 1: แสดง customer ทั้งหมดและจำนวนบิลทั้งหมดที่เกี่ยวข้อง
            $sql = "SELECT 
                        c.id_customer, 
                        c.name_customer, 
                        c.phone_customer, 
                        CONCAT(a.info_address, ' ต.', t.name_tambons, ' อ.', am.name_amphures) AS full_address, 
                        COUNT(DISTINCT b.id_bill) AS total_bills,
                        COALESCE(SUM(CASE WHEN sc.status_service = 'ใช้งาน' AND pl.status_package = 'ใช้งาน' AND pr.status_product = 'ใช้งาน' THEN o.mainpackage_price ELSE 0 END), 0) AS mainpackage_price,
                        COALESCE(SUM(CASE WHEN sc.status_service = 'ใช้งาน' AND pl.status_package = 'ใช้งาน' AND pr.status_product = 'ใช้งาน' THEN o.ict_price ELSE 0 END), 0) AS ict_price,
                        COALESCE(SUM(CASE WHEN sc.status_service = 'ใช้งาน' AND pl.status_package = 'ใช้งาน' AND pr.status_product = 'ใช้งาน' THEN o.all_price ELSE 0 END), 0) AS all_price
                    FROM customers c 
                    LEFT JOIN bill_customer b ON c.id_customer = b.id_customer 
                    LEFT JOIN address a ON c.id_address = a.id_address 
                    LEFT JOIN tambons t ON a.id_tambons = t.id_tambons 
                    LEFT JOIN amphures am ON a.id_amphures = am.id_amphures 
                    LEFT JOIN service_customer sc ON b.id_bill = sc.id_bill 
                    LEFT JOIN package_list pl ON sc.id_service = pl.id_service 
                    LEFT JOIN product_list pr ON pl.id_package = pr.id_package 
                    LEFT JOIN overide o ON pr.id_product = o.id_product 
                    WHERE b.status_bill = 'ใช้งาน'";
        
            // เพิ่มเงื่อนไขกรอง type_customer
            if (!empty($filter_type_customer)) {
                $sql .= " AND c.type_customer = '$filter_type_customer'";
            }
        
            // เพิ่มเงื่อนไขกรอง amphure
            if (!empty($filter_amphure)) {
                $sql .= " AND a.id_amphures = $filter_amphure";
            }
        
            $sql .= " GROUP BY c.id_customer, c.name_customer, c.phone_customer, a.info_address, t.name_tambons, am.name_amphures";
        
            $result = $conn->query($sql);
        
            if ($result->num_rows > 0) {
                echo "<h2 class='text-xl font-bold mt-4'>Report 1: Customers and Total Bills</h2>";
                echo "<div class='overflow-x-auto mt-4'>";
                echo "<table class='w-full border-collapse border border-gray-300'>";
                echo "<thead><tr class='bg-gray-200'>
                        <th class='p-2 border border-gray-300'>No.</th>
                        <th class='p-2 border border-gray-300'>Customer Name</th>
                        <th class='p-2 border border-gray-300'>Phone</th>
                        <th class='p-2 border border-gray-300'>Address</th>
                        <th class='p-2 border border-gray-300'>Total Bills</th>
                        <th class='p-2 border border-gray-300'>Main Package Price</th>
                        <th class='p-2 border border-gray-300'>ICT Price</th>
                        <th class='p-2 border border-gray-300'>All Price</th>
                      </tr></thead>";
                echo "<tbody>";
            
                $counter = 1; // เพิ่มตัวแปรนับจำนวน
                $total_customers = 0;
                $total_mainpackage = 0;
                $total_ict = 0;
                $total_allprice = 0;
            
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td class='p-2 border border-gray-300'>" . $counter . "</td>
                            <td class='p-2 border border-gray-300'>" . $row['name_customer'] . "</td>
                            <td class='p-2 border border-gray-300'>" . $row['phone_customer'] . "</td>
                            <td class='p-2 border border-gray-300'>" . $row['full_address'] . "</td>
                            <td class='p-2 border border-gray-300'>" . $row['total_bills'] . "</td>
                            <td class='p-2 border border-gray-300'>" . $row['mainpackage_price'] . "</td>
                            <td class='p-2 border border-gray-300'>" . $row['ict_price'] . "</td>
                            <td class='p-2 border border-gray-300'>" . $row['all_price'] . "</td>
                          </tr>";
            
                    $counter++; // เพิ่มค่าลำดับ
                    $total_customers++;
                    $total_mainpackage += $row['mainpackage_price'];
                    $total_ict += $row['ict_price'];
                    $total_allprice += $row['all_price'];
                }
            
                echo "<tr class='bg-gray-100 font-bold'>
                        <td class='p-2 border border-gray-300' colspan='4'>Total</td>
                        <td class='p-2 border border-gray-300'>$total_customers Customers</td>
                        <td class='p-2 border border-gray-300'>$total_mainpackage</td>
                        <td class='p-2 border border-gray-300'>$total_ict</td>
                        <td class='p-2 border border-gray-300'>$total_allprice</td>
                      </tr>";
            
                echo "</tbody></table></div>";
            } else {
                echo "<p class='mt-4'>No data found for Report 1.</p>";
            }
        } elseif ($report_type == '2') {
            // Report 2: แสดง customer และหมายเลขบิลที่เกี่ยวข้องทั้งหมด โดยไม่สนใจว่ามีบริการหรือไม่
            $sql = "SELECT 
                    c.id_customer, 
                    c.name_customer, 
                    b.number_bill, 
                    b.type_bill, 
                    b.create_at, 
                    b.end_date, 
                    sc.type_service, 
                    sc.code_service,
                    sc.type_gadget,
                    COALESCE(SUM(CASE WHEN pl.status_package = 'ใช้งาน' AND pr.status_product = 'ใช้งาน' THEN o.mainpackage_price ELSE 0 END), 0) AS mainpackage_price,
                    COALESCE(SUM(CASE WHEN pl.status_package = 'ใช้งาน' AND pr.status_product = 'ใช้งาน' THEN o.ict_price ELSE 0 END), 0) AS ict_price,
                    COALESCE(SUM(CASE WHEN pl.status_package = 'ใช้งาน' AND pr.status_product = 'ใช้งาน' THEN o.all_price ELSE 0 END), 0) AS all_price
                FROM customers c 
                LEFT JOIN bill_customer b ON c.id_customer = b.id_customer 
                LEFT JOIN service_customer sc ON b.id_bill = sc.id_bill 
                LEFT JOIN package_list pl ON sc.id_service = pl.id_service 
                LEFT JOIN product_list pr ON pl.id_package = pr.id_package 
                LEFT JOIN overide o ON pr.id_product = o.id_product 
                LEFT JOIN address a ON c.id_address = a.id_address 
                WHERE b.status_bill = 'ใช้งาน'";
        
            // เพิ่มเงื่อนไขกรอง type_customer
            if (!empty($filter_type_customer)) {
                $sql .= " AND c.type_customer = '$filter_type_customer'";
            }
        
            // เพิ่มเงื่อนไขกรอง amphure
            if (!empty($filter_amphure)) {
                $sql .= " AND a.id_amphures = $filter_amphure";
            }
        
            // เพิ่มเงื่อนไขกรอง type_service
            if (!empty($_GET['filter_type_service'])) {
                $sql .= " AND sc.type_service = '{$_GET['filter_type_service']}'";
            }
        
            // เพิ่มเงื่อนไขกรอง type_gadget
            if (!empty($_GET['filter_type_gadget'])) {
                $sql .= " AND sc.type_gadget = '{$_GET['filter_type_gadget']}'";
            }
        
            $sql .= " GROUP BY c.id_customer, b.number_bill, sc.id_service
                      ORDER BY c.id_customer, b.number_bill, sc.id_service";
        
            $result = $conn->query($sql);
        
            if ($result->num_rows > 0) {
                echo "<h2 class='text-xl font-bold mt-4'>Report 2: Customers and Related Bill Numbers</h2>";
                echo "<div class='overflow-x-auto mt-4'>";
                echo "<table class='w-full border-collapse border border-gray-300'>";
                echo "<thead><tr class='bg-gray-200'>
                        <th class='p-2 border border-gray-300'>No.</th>
                        <th class='p-2 border border-gray-300'>Customer Name</th>
                        <th class='p-2 border border-gray-300'>Bill Number</th>
                        <th class='p-2 border border-gray-300'>Type Bill</th>
                        <th class='p-2 border border-gray-300'>Create At</th>
                        <th class='p-2 border border-gray-300'>End Date</th>
                        <th class='p-2 border border-gray-300'>Type Service</th>
                        <th class='p-2 border border-gray-300'>Service Code</th>
                        <th class='p-2 border border-gray-300'>Type Gadget</th>
                        <th class='p-2 border border-gray-300'>Main Package Price</th>
                        <th class='p-2 border border-gray-300'>ICT Price</th>
                        <th class='p-2 border border-gray-300'>All Price</th>
                    </tr></thead>";
                echo "<tbody>";
            
                $counter = 1; // เพิ่มตัวแปรนับจำนวน
                $current_customer_id = null;
                $current_customer_name = null;
                $current_bill_number = null;
                $total_mainpackage = 0;
                $total_ict = 0;
                $total_allprice = 0;
                $total_customers = 0;
                $unique_customers = [];
            
                while ($row = $result->fetch_assoc()) {
                    if (!in_array($row['id_customer'], $unique_customers)) {
                        $unique_customers[] = $row['id_customer'];
                        $total_customers++;
                    }
            
                    $total_mainpackage += $row['mainpackage_price'];
                    $total_ict += $row['ict_price'];
                    $total_allprice += $row['all_price'];
            
                    if ($current_customer_id !== $row['id_customer']) {
                        $current_customer_id = $row['id_customer'];
                        $current_customer_name = $row['name_customer'];
                        $current_bill_number = $row['number_bill'];
                        echo "<tr>
                                <td class='p-2 border border-gray-300'>" . $counter . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['name_customer'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['number_bill'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['type_bill'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['create_at'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['end_date'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['type_service'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['code_service'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['type_gadget'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['mainpackage_price'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['ict_price'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['all_price'] . "</td>
                              </tr>";
                        $counter++; // เพิ่มค่าลำดับ
                    } else {
                        if ($current_bill_number !== $row['number_bill']) {
                            $current_bill_number = $row['number_bill'];
                            echo "<tr>
                                    <td class='p-2 border border-gray-300' colspan='2'></td>
                                    <td class='p-2 border border-gray-300'>" . $row['number_bill'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['type_bill'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['create_at'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['end_date'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['type_service'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['code_service'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['type_gadget'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['mainpackage_price'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['ict_price'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['all_price'] . "</td>
                                  </tr>";
                        } else {
                            echo "<tr>
                                    <td class='p-2 border border-gray-300' colspan='6'></td>
                                    <td class='p-2 border border-gray-300'>" . $row['type_service'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['code_service'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['type_gadget'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['mainpackage_price'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['ict_price'] . "</td>
                                    <td class='p-2 border border-gray-300'>" . $row['all_price'] . "</td>
                                  </tr>";
                        }
                    }
                }
                echo "<tr class='bg-gray-100 font-bold'>
                        <td class='p-2 border border-gray-300' colspan='8'>Total</td>
                        <td class='p-2 border border-gray-300'>$total_customers Customers</td>
                        <td class='p-2 border border-gray-300'>$total_mainpackage</td>
                        <td class='p-2 border border-gray-300'>$total_ict</td>
                        <td class='p-2 border border-gray-300'>$total_allprice</td>
                      </tr>";
            } else {
                echo "<p class='mt-4'>No data found for Report 2.</p>";
            }
        } elseif ($report_type == '3') {
            // Report 3: แสดงข้อมูลลูกค้าและอุปกรณ์ที่เกี่ยวข้อง โดยที่บิลมีสถานะเป็น "ใช้งาน"
            $sql = "SELECT 
                        c.id_customer, 
                        c.name_customer, 
                        c.phone_customer, 
                        CONCAT(a.info_address, ' ต.', t.name_tambons, ' อ.', am.name_amphures) AS full_address, 
                        g.name_gedget, 
                        g.status_gedget,
                        g.create_at,
                        g.note
                    FROM customers c 
                    LEFT JOIN bill_customer b ON c.id_customer = b.id_customer 
                    INNER JOIN gedget g ON b.id_bill = g.id_bill 
                    LEFT JOIN address a ON c.id_address = a.id_address 
                    LEFT JOIN tambons t ON a.id_tambons = t.id_tambons 
                    LEFT JOIN amphures am ON a.id_amphures = am.id_amphures 
                    WHERE b.status_bill = 'ใช้งาน'";
        
            // เพิ่มเงื่อนไขกรอง type_customer
            if (!empty($filter_type_customer)) {
                $sql .= " AND c.type_customer = '$filter_type_customer'";
            }
        
            // เพิ่มเงื่อนไขกรอง amphure
            if (!empty($filter_amphure)) {
                $sql .= " AND a.id_amphures = $filter_amphure";
            }
        
            $sql .= " ORDER BY c.id_customer, g.name_gedget";
        
            $result = $conn->query($sql);
        
            if ($result->num_rows > 0) {
                echo "<h2 class='text-xl font-bold mt-4'>Report 3: Customers and Related Gadgets</h2>";
                echo "<div class='overflow-x-auto mt-4'>";
                echo "<table class='w-full border-collapse border border-gray-300'>";
                echo "<thead><tr class='bg-gray-200'>
                        <th class='p-2 border border-gray-300'>No.</th>
                        <th class='p-2 border border-gray-300'>Customer Name</th>
                        <th class='p-2 border border-gray-300'>Phone</th>
                        <th class='p-2 border border-gray-300'>Address</th>
                        <th class='p-2 border border-gray-300'>Gadget Name</th>
                        <th class='p-2 border border-gray-300'>Create_at</th>
                        <th class='p-2 border border-gray-300'>Gadget Status</th>
                        <th class='p-2 border border-gray-300'>Note</th>
                      </tr></thead>";
                echo "<tbody>";
            
                $counter = 1; // เพิ่มตัวแปรนับจำนวน
                $current_customer_id = null;
            
                while ($row = $result->fetch_assoc()) {
                    if ($current_customer_id !== $row['id_customer']) {
                        $current_customer_id = $row['id_customer'];
                        echo "<tr>
                                <td class='p-2 border border-gray-300'>" . $counter . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['name_customer'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['phone_customer'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['full_address'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['name_gedget'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['create_at'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['status_gedget'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['note'] . "</td>
                              </tr>";
                        $counter++; // เพิ่มค่าลำดับ
                    } else {
                        echo "<tr>
                                <td class='p-2 border border-gray-300' colspan='4'></td>
                                <td class='p-2 border border-gray-300'>" . $row['name_gedget'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['create_at'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['status_gedget'] . "</td>
                                <td class='p-2 border border-gray-300'>" . $row['note'] . "</td>
                              </tr>";
                    }
                }
            
                echo "</tbody></table></div>";
            } else {
                echo "<p class='mt-4'>No data found for Report 3.</p>";
            }
        }
        ?>
    </div>
    <script>
    function exportToExcel() {
        // เลือกตารางที่ต้องการส่งออก
        const table = document.querySelector('table');
        
        // สร้าง Workbook และ Worksheet จากตาราง
        const workbook = XLSX.utils.table_to_book(table, {sheet: "Sheet 1"});
        
        // สร้างไฟล์ Excel และดาวน์โหลด
        XLSX.writeFile(workbook, 'Report.xlsx');
    }
    </script>
</body>
</html>