<?php

require_once '../config/config.php';
require_once '../function/functions.php';

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
?>

<style>
    .notification-dropdown {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        width: 300px;
        max-height: 400px;
        overflow-y: auto;
    }

    .notification-item {
        padding: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        transition: background-color 0.2s ease-in-out;
    }

    .notification-item:hover {
        background-color: #f7fafc;
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    .notification-title {
        font-size: 1rem;
        font-weight: 600;
        color: #2d3748;
    }

    .notification-days-left {
        font-size: 0.875rem;
        color: #4a5568;
    }

    .notification-date {
        font-size: 0.75rem;
        color: #718096;
    }

    .notification-badge {
        background-color: #dc2626;
        color: #ffffff;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
    }
    .sticky-nav {
        position: sticky;
        top: 0;
        z-index: 1000; /* ให้ Navbar อยู่ด้านบนสุดของหน้าเว็บ */
    }
        
</style>
<nav class="bg-white shadow-md sticky-nav">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <!-- Logo and Brand Name -->
            <div class="flex items-center">
                <img src="https://lh5.googleusercontent.com/proxy/dFSvkaJ3s6GRq3Idd5YLpPVIKmOewgsaR0OrEg0-yXWnQO-HME3H4Yg8kRtfKPwD0UiIsObjAobdvx3bicht" 
                    alt="Logo" 
                    class="h-10 w-auto">
                <a href="#" class="ml-2 text-xl font-semibold text-gray-800">โทรคมนาคมแห่งชาติ</a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="index.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">Main</a>
                <a href="customer.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">Customer</a>
                <a href="bill.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">Billing</a>
                <a href="report.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">Report</a>
                
                <!-- ไอคอนกระดิ่งและรายการแจ้งเตือน -->
                <div class="relative">
                    <div class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300 relative cursor-pointer">
                        <i class="fas fa-bell"></i>
                        <?php if ($near_expiry_count > 0): ?>
                            <span class="absolute top-0 right-0 bg-red-600 text-white text-xs rounded-full px-1.5 py-0.5"><?= $near_expiry_count ?></span>
                        <?php endif; ?>
                    </div>
                    <!-- Dropdown Menu สำหรับแสดงรายการแจ้งเตือน -->
                    <?php if ($near_expiry_count > 0): ?>
                        <div class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden notification-dropdown" id="notificationDropdown">
                            <div class="p-4">
                                <h3 class="text-lg font-semibold mb-2">การแจ้งเตือน</h3>
                                <ul>
                                    <?php foreach ($near_expiry_contracts as $contract): ?>
                                        
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-300">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>
<script>
    function updateNotificationDropdown(contracts) {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;

        const ul = dropdown.querySelector('ul');
        if (!ul) return;

        // Clear existing notifications
        ul.innerHTML = '';

        // Add new notifications
        contracts.forEach(contract => {
            const li = document.createElement('li');
            li.className = 'mb-2';
            li.innerHTML = `
                <div class="text-sm text-gray-700">
                    <p class="notification-title">สัญญาใกล้หมดอายุ</p>
                    <p>ลูกค้า ${contract.name_customer} หมายเลขบิล ${contract.number_bill}</p>
                    <p class="text-xs text-gray-500">สัญญาจะหมดอายุใน ${contract.days_left} วัน</p>
                    <p class="text-xs text-gray-500">วันที่สิ้นสุด: ${contract.end_date}</p>
                    <a href="bill.php?id_customer=${contract.id_customer}&id_bill=${contract.id_bill}" 
                        class="mt-2 inline-block bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600 transition duration-300">ดูบิล
                    </a>
                </div>
            `;
            ul.appendChild(li);
        });
    }

    function checkNearExpiryContracts() {
        fetch('../function/check_near_expiry.php')
            .then(response => response.json())
            .then(data => {
                const bellIcon = document.querySelector('.fa-bell');
                const notificationCount = bellIcon.nextElementSibling;
                if (data.near_expiry_count > 0) {
                    if (!notificationCount) {
                        const span = document.createElement('span');
                        span.className = 'absolute top-0 right-0 bg-red-600 text-white text-xs rounded-full px-1.5 py-0.5';
                        span.textContent = data.near_expiry_count;
                        bellIcon.parentElement.appendChild(span);
                    } else {
                        notificationCount.textContent = data.near_expiry_count;
                    }
                    // Update dropdown content
                    updateNotificationDropdown(data.contracts);
                } else if (notificationCount) {
                    notificationCount.remove();
                }
            });
    }

    // ตรวจสอบทุก 5 นาที
    setInterval(checkNearExpiryContracts, 300000);
    checkNearExpiryContracts(); // ตรวจสอบทันทีเมื่อโหลดหน้า

     // เปิด/ปิด dropdown menu เมื่อคลิกที่ไอคอนกระดิ่ง
    document.addEventListener('DOMContentLoaded', function() {
        const bellIcon = document.querySelector('.fa-bell');
        const notificationDropdown = document.getElementById('notificationDropdown');

        if (bellIcon && notificationDropdown) {
            bellIcon.addEventListener('click', function(e) {
                e.preventDefault();
                notificationDropdown.classList.toggle('hidden');
            });

            // ปิด dropdown เมื่อคลิกนอกพื้นที่
            document.addEventListener('click', function(e) {
                if (!bellIcon.contains(e.target) && !notificationDropdown.contains(e.target)) {
                    notificationDropdown.classList.add('hidden');
                }
            });
        }
    });
</script>