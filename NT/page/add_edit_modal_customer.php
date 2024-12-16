<!-- customer/add_edit_modal.php -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่ม/แก้ไขข้อมูลลูกค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="customerForm" method="POST" action="customer.php">
                <div class="modal-body">
                    <!-- Hidden input สำหรับ customer ID (สำหรับการแก้ไข) -->
                    <input type="hidden" name="customer_id" id="customerId">

                    <!-- ชื่อลูกค้า -->
                    <div class="mb-3">
                        <label class="form-label">ชื่อลูกค้า</label>
                        <input type="text" class="form-control" name="customer_name" id="customerName" required>
                    </div>

                    <!-- ประเภทลูกค้า -->
                    <div class="mb-3">
                        <label class="form-label">ประเภท</label>
                        <select class="form-select" name="customer_type" id="customerType" required>
                            <option value="เทศบาลเมือง">เทศบาลเมือง</option>
                            <option value="เทศบาลตำบล">เทศบาลตำบล</option>
                            <option value="อบจ">อบจ</option>
                            <option value="อบต">อบต</option>
                            <option value="บริษัทเอกชน">บริษัทเอกชน</option>
                            <option value="โรงพยาบาล">โรงพยาบาล</option>
                            <option value="วัด">วัด</option>
                            <option value="มูลนิธิ">มูลนิธิ</option>
                            <option value="โรงเรียนประถม">โรงเรียนประถม</option>
                            <option value="โรงเรียนมัธยม">โรงเรียนมัธยม</option>
                            <option value="โรงเรียนเอกชน">โรงเรียนเอกชน</option>
                            <option value="วิทยาลัย">วิทยาลัย</option>
                            <option value="มหาวิทยาลัย">มหาวิทยาลัย</option>
                        </select>
                    </div>

                    <!-- เบอร์โทรศัพท์ -->
                    <div class="mb-3">
                        <label class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" name="customer_phone" id="customerPhone" required>
                    </div>

                    <!-- สถานะ -->
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
                        <select class="form-select" name="customer_status" id="customerStatus" required>
                            <option value="Active">ใช้งาน</option>
                            <option value="Inactive">ไม่ใช้งาน</option>
                        </select>
                    </div>

                    <!-- ที่อยู่ -->
                    <div class="mb-3">
                        <label class="form-label">ที่อยู่</label>
                        <textarea class="form-control" name="address_text" id="addressText" required></textarea>
                    </div>

                    <!-- อำเภอ -->
                    <div class="mb-3">
                        <label class="form-label">อำเภอ</label>
                        <select class="form-select" name="amphure_id" id="amphureId" required>
                            <option value="">เลือกอำเภอ</option>
                            <?php foreach($amphures as $amphure): ?>
                                <option value="<?php echo $amphure['amphure_id']; ?>">
                                    <?php echo htmlspecialchars($amphure['amphure_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- ตำบล -->
                    <div class="mb-3">
                        <label class="form-label">ตำบล</label>
                        <select class="form-select" name="thambon_id" id="thambonId" required>
                            <option value="">เลือกตำบล</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>
