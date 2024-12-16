// assets/js/customer.js
document.addEventListener('DOMContentLoaded', function() {
    // ฟังก์ชันสำหรับโหลดตำบล
    function fetchThambons(amphureId, selectedThambonId = null) {
        const thambonSelect = document.getElementById('thambonId');
        thambonSelect.innerHTML = '<option value="">เลือกตำบล</option>';
        
        if (!amphureId) return;

        fetch(`../ajax/get_thambons.php?amphure_id=${amphureId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(thambon => {
                    const option = document.createElement('option');
                    option.value = thambon.thambon_id;
                    option.textContent = thambon.thambon_name;
                    
                    if (thambon.thambon_id == selectedThambonId) {
                        option.selected = true;
                    }
                    
                    thambonSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching thambons:', error));
    }

    // จัดการเมื่อคลิกปุ่มแก้ไขลูกค้า
    const editButtons = document.querySelectorAll('.edit-customer');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            // เติมข้อมูลลงในฟอร์ม
            document.getElementById('customerId').value = this.dataset.customerId;
            document.getElementById('customerName').value = this.dataset.customerName;
            document.getElementById('customerType').value = this.dataset.customerType;
            document.getElementById('customerPhone').value = this.dataset.customerPhone;
            document.getElementById('customerStatus').value = this.dataset.customerStatus;
            document.getElementById('addressText').value = this.dataset.addressText;
            
            const amphureId = this.dataset.amphureId;
            const thambonId = this.dataset.thambonId;

            // ตั้งค่าอำเภอในฟอร์ม
            document.getElementById('amphureId').value = amphureId;

            // โหลดตำบลที่เกี่ยวข้อง
            fetchThambons(amphureId, thambonId);
        });
    });

    // จัดการเมื่อเลือกอำเภอในฟอร์มเพิ่ม/แก้ไข
    document.getElementById('amphureId').addEventListener('change', function() {
        const amphureId = this.value;
        fetchThambons(amphureId);
    });

    // รีเซ็ตฟอร์มเมื่อปิดโมดัล
    const customerModal = document.getElementById('customerModal');
    customerModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('customerForm').reset();
        document.getElementById('thambonId').innerHTML = '<option value="">เลือกตำบล</option>';
    });

    // จัดการการเปลี่ยนแปลงในตัวกรองอำเภอบนหน้าหลัก
    const filterAmphure = document.getElementById('filterAmphure');
    const filterThambon = document.getElementById('filterThambon');

    filterAmphure.addEventListener('change', function() {
        const amphureId = this.value;
        filterThambon.innerHTML = '<option value="">เลือกตำบล</option>';

        if (!amphureId) return;

        fetch(`../ajax/get_thambons.php?amphure_id=${amphureId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(thambon => {
                    const option = document.createElement('option');
                    option.value = thambon.thambon_id;
                    option.textContent = thambon.thambon_name;
                    filterThambon.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching thambons:', error));
    });
});
