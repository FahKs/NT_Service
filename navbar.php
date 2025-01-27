<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../function/functions.php';

// ดึง user_id จาก session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// ดึงการแจ้งเตือนจากตาราง notifications ที่ยังไม่ได้อ่าน (is_read = 0)
$sql_notifications = "SELECT n.id_notifications, n.message, n.created_at, n.is_read, bc.number_bill, bc.id_bill, c.name_customer, c.id_customer
                      FROM notifications n
                      INNER JOIN bill_customer bc ON n.id_bill = bc.id_bill
                      INNER JOIN customers c ON bc.id_customer = c.id_customer
                      WHERE n.id_user = ? AND n.is_read = 0
                      ORDER BY n.created_at DESC";
$stmt_notifications = $conn->prepare($sql_notifications);
$stmt_notifications->bind_param("i", $user_id);
$stmt_notifications->execute();
$notifications = $stmt_notifications->get_result()->fetch_all(MYSQLI_ASSOC);

$near_expiry_count = count($notifications);
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
        position: relative; /* กำหนดให้เป็น relative เพื่อให้ปุ่มอ่านแล้วอยู่ภายใน */
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

    /* สไตล์สำหรับปุ่มอ่านแล้ว (ไอคอนกากบาท) */
    .mark-as-read-button {
        position: absolute; /* กำหนดให้อยู่ที่มุมขวาบน */
        top: 0.5rem;
        right: 0.5rem;
        background: none;
        border: none;
        color: #718096; /* สีเทา */
        font-size: 1.5rem; /* ปรับขนาดให้ใหญ่ขึ้น (ค่าเริ่มต้นคือ 1rem) */
        cursor: pointer;
        transition: color 0.2s ease-in-out;
    }

    .mark-as-read-button:hover {
        color: #dc2626; /* สีแดงเมื่อ hover */
    }
    .sticky-nav {
        position: sticky;
        top: 0;
        z-index: 1000; /* ให้ Navbar อยู่ด้านบนสุดของหน้าเว็บ */
    }

    /* สไตล์สำหรับหน้าจอเล็ก (Mobile) */
    @media (max-width: 768px) {
        .md\\:flex {
            display: none;
        }

        .mobile-menu {
            display: block;
        }

        .mobile-menu-button {
            display: block;
        }

        .mobile-menu-items {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            z-index: 1000;
        }

        .mobile-menu-items.active {
            display: block;
        }

        .mobile-menu-items a {
            display: block;
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
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

            <!-- Hamburger Menu สำหรับหน้าจอเล็ก -->
            <div class="mobile-menu md:hidden">
                <button class="mobile-menu-button text-gray-700 hover:text-blue-600 focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
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
                                    <?php foreach ($notifications as $notification): ?>
                                        <li class="notification-item" data-notification-id="<?= $notification['id_notifications'] ?>">
                                            <div class="text-sm text-gray-700">
                                                <button onclick="markAsRead(<?= $notification['id_notifications'] ?>)" 
                                                        class="mark-as-read-button">
                                                    ×
                                                </button>
                                                <p class="notification-title"><?= $notification['message'] ?></p>
                                                <p class="notification-date"><?= date('Y-m-d H:i:s', strtotime($notification['created_at'])) ?></p>
                                                <a href="bill.php?id_customer=<?= $notification['id_customer'] ?>&id_bill=<?= $notification['id_bill'] ?>" 
                                                class="mt-2 inline-block bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600 transition duration-300">
                                                    ดูบิล
                                                </a>
                                            </div>
                                        </li>
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

            <!-- Mobile Menu Items -->
            <div class="mobile-menu-items md:hidden">
                <a href="index.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">Main</a>
                <a href="customer.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">Customer</a>
                <a href="bill.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">Billing</a>
                <a href="report.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">Report</a>
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
            if (!contract.is_read) {  // เพิ่มเงื่อนไขให้แสดงเฉพาะการแจ้งเตือนที่ยังไม่ได้อ่าน
                const li = document.createElement('li');
                li.className = 'notification-item';
                li.setAttribute('data-notification-id', contract.id_notifications); // เพิ่ม data-notification-id
                li.innerHTML = `
                     <div class="text-sm text-gray-700">
                        <p class="notification-title">สัญญาใกล้หมดอายุ</p>
                        <p class="notification-title">ลูกค้า ${contract.name_customer}</p>
                        <p class="notification-days-left">เหลือเวลาอีก ${contract.days_left} วัน</p>
                        <p class="notification-date">หมายเลขบิล: ${contract.number_bill}</p>
                        <a href="bill.php?id_customer=${contract.id_customer}&id_bill=${contract.id_bill}" 
                        class="mt-2 inline-block bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600 transition duration-300">
                            ดูบิล
                        </a>
                        <button onclick="markAsRead(${contract.id_notifications})" 
                                class="mark-as-read-button"">
                            ×
                        </button>
                    </div>
                `;
                ul.appendChild(li);
            }
        });
    }

    function checkNotifications() {
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
    setInterval(checkNotifications, 300000);
    checkNotifications(); // ตรวจสอบทันทีเมื่อโหลดหน้า

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

    // เปิด/ปิดเมนูบนมือถือ
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenuItems = document.querySelector('.mobile-menu-items');

        if (mobileMenuButton && mobileMenuItems) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenuItems.classList.toggle('active');
            });

            // ปิดเมนูเมื่อคลิกนอกพื้นที่
            document.addEventListener('click', function(e) {
                if (!mobileMenuButton.contains(e.target) && !mobileMenuItems.contains(e.target)) {
                    mobileMenuItems.classList.remove('active');
                }
            });
        }
    });

    function markAsRead(notificationId) {
        fetch('../function/mark_as_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // หา element ที่มี data-notification-id เท่ากับ notificationId
                const notificationItem = document.querySelector(`.notification-item[data-notification-id="${notificationId}"]`);
                if (notificationItem) {
                    // ลบรายการแจ้งเตือนที่ถูกอ่านแล้วออกจาก dropdown
                    notificationItem.remove();
                }

                // อัปเดตจำนวนการแจ้งเตือนที่แสดงบนไอคอนกระดิ่ง
                const notificationCount = document.querySelector('.fa-bell').nextElementSibling;
                if (notificationCount) {
                    const newCount = parseInt(notificationCount.textContent) - 1;
                    if (newCount > 0) {
                        notificationCount.textContent = newCount;
                    } else {
                        notificationCount.remove();
                    }
                }
            }
        });
    }
</script>