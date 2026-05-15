<?php
// manage_auth.php
session_start();
// ตรวจสอบสิทธิ์เบื้องต้น (ถ้ามีระบบ auth อยู่แล้ว)
// require_once 'includes/auth_check.php';

$pageTitle = "จัดการสิทธิ์การใช้งาน - HR Dashboard";
$currentPage = "manage_auth";
$pageScript = "assets/js/manage_auth.js";

include 'includes/header.php';
?>

<div class="dashboard-container auth-page">
    <!-- Header Page -->
    <div class="auth-page-header">
        <div class="auth-page-title">
            <div class="auth-eyebrow">
                <i class="fa-solid fa-shield-halved"></i>
                Access Control
            </div>
            <h1>จัดการสิทธิ์เข้าใช้งานระบบ</h1>
            <p>ควบคุมผู้ใช้งาน ระดับสิทธิ์ และสถานะของ HR Dashboard</p>
        </div>
        <button class="btn btn-primary auth-add-btn" onclick="openAddModal()">
            <i class="fa-solid fa-user-plus"></i>
            <span>เพิ่มผู้ใช้งาน</span>
        </button>
    </div>

    <!-- Stats Summary -->
    <div class="row g-2 g-md-3 mb-3 auth-stat-grid">
        <div class="col-6 col-xl-3">
            <div class="auth-stat-card">
                <div class="auth-stat-icon text-primary bg-primary-subtle">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="auth-stat-content">
                    <div class="auth-stat-label">ผู้ใช้งานทั้งหมด</div>
                    <div class="auth-stat-value" id="stat-total-users">0</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="auth-stat-card">
                <div class="auth-stat-icon text-success bg-success-subtle">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="auth-stat-content">
                    <div class="auth-stat-label">เปิดใช้งาน</div>
                    <div class="auth-stat-value" id="stat-active-users">0</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="auth-stat-card">
                <div class="auth-stat-icon text-danger bg-danger-subtle">
                    <i class="fa-solid fa-user-lock"></i>
                </div>
                <div class="auth-stat-content">
                    <div class="auth-stat-label">ปิดใช้งาน</div>
                    <div class="auth-stat-value" id="stat-inactive-users">0</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="auth-stat-card">
                <div class="auth-stat-icon text-info bg-info-subtle">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="auth-stat-content">
                    <div class="auth-stat-label">Admin</div>
                    <div class="auth-stat-value" id="stat-admin-users">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="auth-table-card">
        <div class="auth-table-card-header">
            <div>
                <div class="auth-section-title">รายการสิทธิ์ผู้ใช้งาน</div>
                <div class="auth-section-subtitle">ข้อมูลสิทธิ์สำหรับระบบ HR Dashboard</div>
            </div>
            <div class="auth-table-meta">
                <i class="fa-solid fa-lock"></i>
                <span>hr_dashboard</span>
            </div>
        </div>
        <div class="auth-table-card-body">
            <div class="table-responsive auth-table-responsive">
                <table id="authTable" class="table table-hover align-middle mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th class="ps-4">พนักงาน</th>
                            <th>ระดับการเข้าถึง</th>
                            <th>ประเภท</th>
                            <th>กลุ่มโปรแกรม</th>
                            <th>สถานะ</th>
                            <th>วันที่สร้าง</th>
                            <th class="text-end pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="authTableBody">
                        <!-- Data will be loaded via AJAX -->
                        <tr class="auth-loading-row">
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                กำลังโหลดข้อมูล...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg auth-modal-dialog">
        <div class="modal-content border-0 shadow auth-modal-content">
            <div class="modal-header auth-modal-header">
                <div>
                    <div class="auth-eyebrow mb-1">
                        <i class="fa-solid fa-user-gear"></i>
                        Permission
                    </div>
                    <h5 class="modal-title fw-bold" id="authModalTitle">เพิ่มผู้ใช้งานระบบ</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="authForm">
                <input type="hidden" id="aut_id" name="aut_id">
                <div class="modal-body auth-modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">รหัสพนักงาน <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-id-card text-muted"></i></span>
                            <input type="text" class="form-control bg-light border-start-0" id="codempid" name="codempid" placeholder="ระบุรหัสพนักงาน" required>
                            <button class="btn btn-outline-secondary" type="button" id="btn-search-emp" title="ค้นหาชื่อ">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อพนักงาน</label>
                        <input type="text" class="form-control" id="aut_name" name="aut_name" placeholder="ชื่อจะแสดงหลังจากใส่รหัสพนักงาน" readonly>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">ระดับสิทธิ์ (Level)</label>
                            <select class="form-select" id="aut_level" name="aut_level">
                                <option value="9">9 - เข้าใช้งานทั่วไป</option>
                                <option value="99">99 - ผู้ดูแลระบบ (Manage Auth)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">ประเภท</label>
                            <select class="form-select" id="aut_type" name="aut_type">
                                <option value="User">User</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">กลุ่มโปรแกรม (Privilege)</label>
                        <input type="text" class="form-control" id="aut_privilege" name="aut_privilege" value="hr_dashboard" readonly>
                    </div>
                    <div class="mb-0 mt-4">
                        <div class="form-check form-switch auth-status-switch">
                            <label class="form-check-label fw-bold mb-0" for="aut_active">สถานะการใช้งานเปิดใช้งาน</label>
                            <input class="form-check-input ms-0" type="checkbox" role="switch" id="aut_active" name="aut_active" checked value="Y">
                        </div>
                    </div>
                </div>
                <div class="modal-footer auth-modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
