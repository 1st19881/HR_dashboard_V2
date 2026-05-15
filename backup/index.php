<?php
// index.php
// หน้าหลักสำหรับ HR Dashboard
require_once 'includes/auth_check.php';

require_once 'config/database.php';
require_once 'includes/functions.php';

// ดึงการเชื่อมต่อ (กรณีใช้งานจริง)
// $conn = getDbConnection();

// รวม Header (HTML Head, CSS)
include 'includes/header.php';
?>

<div class="dashboard-container">
    <!-- Row 1: Filters -->
    <div class="row">
        <!-- Month Filter -->
        <div class="col-lg-5 col-md-12">
            <div class="filter-card">
                <div class="d-flex flex-wrap">
                    <button class="month-btn">--</button>
                    <button class="month-btn">Jan</button>
                    <button class="month-btn">Feb</button>
                    <button class="month-btn">Mar</button>
                    <button class="month-btn">Apr</button>
                    <button class="month-btn">May</button>
                    <button class="month-btn">Jun</button>
                    <button class="month-btn">Jul</button>
                    <button class="month-btn">Aug</button>
                    <button class="month-btn">Sep</button>
                    <button class="month-btn">Oct</button>
                    <button class="month-btn">Nov</button>
                    <button class="month-btn">Dec</button>
                </div>
            </div>
        </div>

        <!-- Dropdown Filters -->
        <div class="col-lg-7 col-md-12">
            <div class="filter-card">
                <div class="row g-2">
                    <div class="col-md-4 col-lg">
                        <label class="form-label small text-muted mb-1 fw-bold">Plant</label>
                        <select class="form-select form-select-sm shadow-none" id="filterPlant">
                            <option value="">เลือกทั้งหมด</option>
                            <option value="SAAB">SAAB</option>
                            <option value="SAB">SAB</option>
                            <option value="SATC">SATC</option>
                            <option value="SDC">SDC</option>
                            <option value="SLAB">SLAB</option>
                            <option value="SRAB">SRAB</option>
                            <option value="SRDC">SRDC</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-lg">
                        <label class="form-label small text-muted mb-1 fw-bold">Employee Type</label>
                        <select class="form-select form-select-sm shadow-none" id="filterEmpType">
                            <option value="">เลือกทั้งหมด</option>
                            <option value="ADMIN">ADMIN</option>
                            <option value="DIRECT">DIRECT</option>
                            <option value="INDIRECT">INDIRECT</option>
                            <option value="MANAGER">MANAGER</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-lg">
                        <label class="form-label small text-muted mb-1 fw-bold">Function</label>
                        <select class="form-select form-select-sm shadow-none" id="filterFunction">
                            <option value="">ทั้งหมด</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg">
                        <label class="form-label small text-muted mb-1 fw-bold">Employee Category</label>
                        <select class="form-select form-select-sm shadow-none" id="filterEmpCategory">
                            <option value="">เลือกทั้งหมด</option>
                            <option value="PERM">PERM</option>
                            <option value="PWC">PWC</option>
                            <option value="SUB">SUB (ทั้งหมด)</option>
                            <option value="SUB Thai">SUB Thai</option>
                            <option value="SUB Myanmar">SUB Myanmar</option>
                            <option value="OTHER">OTHER</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg">
                        <label class="form-label small text-muted mb-1 fw-bold">Department</label>
                        <select class="form-select form-select-sm shadow-none">
                            <option>ทั้งหมด</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: KPI Summaries -->
    <div class="row g-2 mb-3">
        <div class="col">
            <div class="kpi-card">
                <div class="kpi-title">PERM</div>
                <div class="kpi-value" id="kpi-perm">...</div>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card">
                <div class="kpi-title">PWC</div>
                <div class="kpi-value" id="kpi-pwc">...</div>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card">
                <div class="kpi-title">SUB Thai</div>
                <div class="kpi-value" id="kpi-sub-thai">...</div>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card">
                <div class="kpi-title">SUB Myanmar</div>
                <div class="kpi-value" id="kpi-sub-mm">...</div>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card">
                <div class="kpi-title">SUB Cambodia</div>
                <div class="kpi-value" id="kpi-sub-cam">...</div>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card">
                <div class="kpi-title">OTHER</div>
                <div class="kpi-value" id="kpi-other">...</div>
            </div>
        </div>
        <div class="col-md-3">
            <!-- Gender KPI Card -->
            <div class="kpi-card flex-row align-items-center justify-content-around py-1">
                <div class="text-center">
                    <i class="fa-solid fa-person fa-2x mb-1" style="color: #333;"></i>
                    <div class="kpi-title mb-0">Male</div>
                    <div class="kpi-value fs-5" id="kpi-male-pct">...</div>
                </div>
                <div class="text-center">
                    <i class="fa-solid fa-person-dress fa-2x mb-1" style="color: #333;"></i>
                    <div class="kpi-title mb-0">Female</div>
                    <div class="kpi-value fs-5" id="kpi-female-pct">...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Headcount Trend (Line Chart) -->
    <div class="row mb-1">
        <div class="col-12">
            <div class="chart-card mb-3 pb-1">
                <div style="height: 250px;"><canvas id="trendChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Row 4: Breakdown Charts -->
    <div class="row g-3 mb-1">
        <!-- Donut Chart -->
        <div class="col-md-5">
            <div class="chart-card mb-3">
                <div class="chart-card-title">Headcount By Employee Type</div>
                <div style="height: 280px; position: relative;"><canvas id="typeChart"></canvas></div>
            </div>
        </div>
        <!-- Bar Chart (Function) -->
        <div class="col-md-7">
            <div class="chart-card mb-3">
                <div class="chart-card-title">Headcount By Function</div>
                <div style="height: 280px;"><canvas id="functionChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Row 5: Turnover Rate Chart -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="chart-card">
                <div class="chart-card-title mb-2">Turnover Rate</div>
                <div style="height: 250px;"><canvas id="turnoverChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Row 6: Employee Data Table -->
    <div class="row">
        <div class="col-12">
            <div class="data-table-container">
                <div class="table-responsive">
                    <table id="employeeTable" class="table table-hover mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th>Plant</th>
                                <th>Employee ID.</th>
                                <th>Full Name</th>
                                <th>Employee Category</th>
                                <th>Category(By Nationality)</th>
                                <th>Function(Short Name)</th>
                                <th>Department(Short Name)</th>
                                <th>Section</th>
                                <th>Cost Center</th>
                                <th>Band</th>
                                <th>Grade</th>
                                <th>Position Group</th>
                                <th>Years of Service</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX from api/get_employee_list.php -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// รวม Footer (JS Scripts)
include 'includes/footer.php';
?>