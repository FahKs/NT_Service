<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/config.php';
require_once '../function/functions.php';

// 📌 ดึง email ของ User ปัจจุบัน
$user_email = $_SESSION['email'];

$sql = "
    SELECT bc.number_bill, bc.end_date, bc.type_bill, bc.status_bill, c.name_customer, c.phone_customer 
    FROM bill_customer bc
    JOIN customers c ON bc.id_customer = c.id_customer
";
$result = $conn->query($sql);

$bills = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bills[] = $row;
    }
}
// ดึงข้อมูลวิเคราะห์สำหรับ Dashboard
$sql_total_customers = "SELECT COUNT(*) AS total_customers FROM customers";
$result_total_customers = $conn->query($sql_total_customers);
$total_customers = $result_total_customers->fetch_assoc()['total_customers'];

$sql_total_bills = "SELECT COUNT(*) AS total_bills FROM bill_customer WHERE status_bill = 'ใช้งาน'";
$result_total_bills = $conn->query($sql_total_bills);
$total_bills = $result_total_bills->fetch_assoc()['total_bills'];

$sql_total_income = "SELECT COALESCE(SUM(all_price), 0) AS total_income FROM overide";
$result_total_income = $conn->query($sql_total_income);
$total_income = $result_total_income->fetch_assoc()['total_income'];

$sql_monthly_analysis = "
    SELECT DATE_FORMAT(end_date, '%Y-%m') AS month, 
           COUNT(*) AS total_bills, 
           SUM(CASE WHEN status_bill = 'completed' THEN 1 ELSE 0 END) AS completed_bills,
           SUM(CASE WHEN status_bill = 'pending' THEN 1 ELSE 0 END) AS pending_bills
    FROM bill_customer
    GROUP BY DATE_FORMAT(end_date, '%Y-%m')
    ORDER BY month DESC
";
$result_monthly_analysis = $conn->query($sql_monthly_analysis);

$monthly_analysis = [];
if ($result_monthly_analysis->num_rows > 0) {
    while ($row = $result_monthly_analysis->fetch_assoc()) {
        $monthly_analysis[] = $row;
    }
}

// ดึงข้อมูลเฉพาะ phone_customer จากตาราง customers
$sql_customers = "SELECT phone_customer,name_customer FROM customers";
$result_customers = $conn->query($sql_customers);

$customers = [];
if ($result_customers->num_rows > 0) {
    while ($row = $result_customers->fetch_assoc()) {
        $customers[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #ffffff;
            color: #333333;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-top: auto;
            text-align: center;
        }
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            padding: 20px;
        }
        .calendar-section {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        #calendar {
            max-width: 100%;
            height: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <?php include './components/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="bg-gray-900 text-yellow-500 py-24">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl font-bold mb-4">ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
            <p class="text-lg max-w-2xl">
                นี่คือแดชบอร์ดของคุณ คุณสามารถใช้เมนูด้านบนเพื่อนำทาง
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-12 main-content">
        <!-- Summary Cards and Monthly Analysis --> 
        <div>
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800">Total Customers</h3>
                    <p class="text-2xl text-gray-900"><?php echo $total_customers; ?></p>
                </div>
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800">Total Bills</h3>
                    <p class="text-2xl text-gray-900"><?php echo $total_bills; ?></p>
                </div>
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800">Total Income</h3>
                    <p class="text-2xl text-gray-900"><?php echo number_format($total_income, 2); ?> THB</p>
                </div>
            </div>

            <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Contact Customers</h3>
    <div id="userListContainer" class="flex flex-wrap gap-4">
    </div>

    <button id="addContactButton" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 mt-4">
        Add Contact/Customer
    </button>
</div>

<!-- Modal -->
<div id="addContactModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Add Contact</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Select customer:</p>
                <select id="customerDropdown" class="border rounded-lg p-2 mt-2 w-full">
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo htmlspecialchars($customer['phone_customer']); ?>">
                            <?php echo htmlspecialchars($customer['name_customer'] . ' - ' . $customer['phone_customer']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="items-center px-4 py-3">
                <button id="closeModalButton" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Close</button>
                <button id="submitPhoneButton" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Submit</button>
            </div>
        </div>
    </div>
</div>


            <!-- Monthly Analysis -->
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Monthly Bill Analysis</h3>
                <canvas id="monthlyAnalysisChart"></canvas>
            </div>
        </div>

        <!-- Calendar Section -->
        <div class="calendar-section">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">Calendar</h2>
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer bg-gray-800 text-white">
        <div class="container mx-auto px-4">
            <span>© 2023 บริษัทโทรคมนาคมแห่งชาติ จำกัด สงวนลิขสิทธิ์.</span>
        </div>
    </footer>
</body>
</html>

<!-- Include Modal -->
<?php include './components/info_calender.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: [
                {
                    title: 'Event 1',
                    start: '2023-10-01'
                },
                {
                    title: 'Event 2',
                    start: '2023-10-07',
                    end: '2023-10-10'
                },
                {
                    title: 'Event 3',
                    start: '2023-10-14T12:00:00'
                }
            ]
        });

        calendar.render();
    });
    
    // Data for the chart
    const monthlyAnalysisData = {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['month'] . "'"; }, $monthly_analysis)); ?>],
        datasets: [{
            label: 'Total Bills',
            data: [<?php echo implode(',', array_map(function($item) { return $item['total_bills']; }, $monthly_analysis)); ?>],
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }, {
            label: 'Completed Bills',
            data: [<?php echo implode(',', array_map(function($item) { return $item['completed_bills']; }, $monthly_analysis)); ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }, {
            label: 'Pending Bills',
            data: [<?php echo implode(',', array_map(function($item) { return $item['pending_bills']; }, $monthly_analysis)); ?>],
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    };

    // Config for the chart
    const config = {
        type: 'bar',
        data: monthlyAnalysisData,
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    };

    // Render the chart
    const monthlyAnalysisChart = new Chart(
        document.getElementById('monthlyAnalysisChart'),
        config
    );

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: [
                <?php foreach ($bills as $bill): ?>
                {
                    title: 'หมดสัญญาบิล',
                    start: '<?php echo $bill['end_date']; ?>',
                    extendedProps: {
                        phone: '<?php echo htmlspecialchars($bill['phone_customer']); ?>',
                        billnum: '<?php echo $bill['number_bill']; ?>',
                        billtype: '<?php echo $bill['type_bill']; ?>',
                        customername: '<?php echo htmlspecialchars($bill['name_customer']); ?>',
                        billstatus: '<?php echo htmlspecialchars($bill['status_bill']); ?>'
                    }
                },
                <?php endforeach; ?>
            ],
            eventClick: function(info) {
                // เติมข้อมูลลงใน modal
                document.getElementById('modalCustomerName').innerText = 'ชื่อลูกค้า: ' + info.event.extendedProps.customername;
                document.getElementById('modalBillCode').innerText = 'Bill Code: ' + info.event.extendedProps.billnum;
                document.getElementById('modalBillType').innerText = 'ประเภทบิล: ' + info.event.extendedProps.billtype;
                document.getElementById('modalPhone').innerText = 'เบอร์ติดต่อ: ' + info.event.extendedProps.phone;
                document.getElementById('modalBillStatus').innerText = 'สถานะบิล: ' + info.event.extendedProps.billstatus;
                // แสดง modal
                document.getElementById('eventModal').classList.remove('hidden');
            }
        });
        calendar.render();

        // ปิด modal เมื่อคลิกปุ่ม OK
        document.getElementById('okBtn').addEventListener('click', function() {
            document.getElementById('eventModal').classList.add('hidden');
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const userEmail = "<?php echo $user_email; ?>"; // 📌 ดึง email จาก PHP
        const addContactButton = document.getElementById('addContactButton');
        const closeModalButton = document.getElementById('closeModalButton');
        const submitPhoneButton = document.getElementById('submitPhoneButton');
        const addContactModal = document.getElementById('addContactModal');
        const customerDropdown = document.getElementById('customerDropdown');
        const userListContainer = document.getElementById('userListContainer');

        // ✅ โหลดข้อมูล Contact ของ User ที่ล็อกอิน
        function loadContacts() {
            userListContainer.innerHTML = ''; // เคลียร์ค่าเดิม
            let savedContacts = JSON.parse(localStorage.getItem(`contacts_${userEmail}`)) || [];

            if (savedContacts.length === 0) {
                userListContainer.innerHTML = '<p class="text-gray-500">ยังไม่มี Contact</p>';
            } else {
                savedContacts.forEach(contact => {
                    addContactToList(contact, false);
                });
            }
        }

        // ✅ บันทึก Contact ลงใน Local Storage (แยกตาม User)
        function saveContacts() {
            let contacts = [];
            document.querySelectorAll('.user-contact p').forEach(contact => {
                contacts.push(contact.textContent);
            });
            localStorage.setItem(`contacts_${userEmail}`, JSON.stringify(contacts));
        }

        // ✅ เปิด Modal
        addContactButton.addEventListener('click', function() {
            addContactModal.classList.remove('hidden');
        });

        // ✅ ปิด Modal
        closeModalButton.addEventListener('click', function() {
            addContactModal.classList.add('hidden');
        });

        // ✅ เมื่อกดปุ่ม Submit -> เพิ่ม Contact ลงใน User List
        submitPhoneButton.addEventListener('click', function() {
            const selectedContact = customerDropdown.value;
            if (!selectedContact) {
                alert("กรุณาเลือกเบอร์โทรลูกค้า");
                return;
            }

            // ตรวจสอบว่ามีเบอร์นี้อยู่แล้วหรือไม่
            let existingContacts = JSON.parse(localStorage.getItem(`contacts_${userEmail}`)) || [];
            if (existingContacts.includes(selectedContact)) {
                alert("เบอร์โทรนี้มีอยู่ในรายชื่อแล้ว!");
                return;
            }

            // ✅ เพิ่ม Contact ใหม่ลงใน User List และบันทึก
            addContactToList(selectedContact, true);
            addContactModal.classList.add('hidden'); // ปิด Modal หลังจากเพิ่ม Contact
        });

        // ✅ เพิ่ม Contact ลงใน User List (ชื่อ, บันทึกลง LocalStorage ไหม)
        function addContactToList(contactText, saveToLocal = false) {
            if (userListContainer.innerHTML.includes("ยังไม่มี Contact")) {
                userListContainer.innerHTML = ''; // ล้างข้อความว่างเปล่าออก
            }

            const newContact = document.createElement('div');
            newContact.classList.add('bg-gray-100', 'shadow-lg', 'rounded-lg', 'p-4', 'flex-1', 'min-w-[200px]', 'relative', 'user-contact');
            newContact.innerHTML = `
                <p class="text-gray-600">${contactText}</p>
                <button class="menu-button w-10 h-10 flex items-center justify-center rounded-full border-2 text-gray-600 hover:bg-gray-100 absolute top-2 right-2">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="menu-dropdown hidden absolute right-0 mt-12 w-40 bg-white border rounded-lg shadow-lg z-10">
                    <ul>
                        <li class="p-2 hover:bg-gray-200 cursor-pointer text-red-500 delete-contact">Delete</li>
                    </ul>
                </div>
            `;

            userListContainer.appendChild(newContact);

            // ✅ เปิด/ปิดเมนูจุดสามจุด
            const menuButton = newContact.querySelector('.menu-button');
            const dropdownMenu = newContact.querySelector('.menu-dropdown');

            menuButton.addEventListener('click', function(event) {
                event.stopPropagation();
                closeAllDropdowns(); // ปิดเมนูอื่นก่อน
                dropdownMenu.classList.toggle('hidden');
            });

            // ✅ ฟังก์ชันลบ Contact
            const deleteButton = newContact.querySelector('.delete-contact');
            deleteButton.addEventListener('click', function() {
                newContact.remove();
                saveContacts(); // อัปเดตข้อมูลหลังจากลบ
                if (userListContainer.innerHTML.trim() === '') {
                    userListContainer.innerHTML = '<p class="text-gray-500">ยังไม่มี Contact</p>';
                }
            });

            // ✅ ปิด dropdown เมื่อคลิกที่อื่น
            document.addEventListener('click', function() {
                closeAllDropdowns();
            });

            // ✅ บันทึกลง Local Storage ถ้าจำเป็น
            if (saveToLocal) {
                let savedContacts = JSON.parse(localStorage.getItem(`contacts_${userEmail}`)) || [];
                savedContacts.push(contactText);
                localStorage.setItem(`contacts_${userEmail}`, JSON.stringify(savedContacts));
            }
        }

        // ✅ ฟังก์ชันปิดทุก dropdown
        function closeAllDropdowns() {
            document.querySelectorAll('.menu-dropdown').forEach(menu => menu.classList.add('hidden'));
        }

        // ✅ โหลดข้อมูล Contact เมื่อเปิดหน้าเว็บ
        loadContacts();
    });
    </script>
</body>
</html>
