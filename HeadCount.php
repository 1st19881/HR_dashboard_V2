<?php
// HeadCount.php
require_once 'includes/auth_check.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'HR Dashboard - HeadCount';
$currentPage = 'headcount';
$pageScript = 'assets/js/headcount.js';

include 'includes/header.php';
?>

<div class="dashboard-container">

    <!-- Row 1: Filters -->
    <div class="row">
        <!-- Month Filter -->
        <div class="col-xxl-4 col-xl-5 col-lg-12">
            <div class="filter-card">
                <div class="d-flex flex-wrap" id="monthFilter">
                    <button class="month-btn active" data-month="">
                        <i class="fa-solid fa-calendar-days" style="font-size:0.7rem;"></i> All
                    </button>
                    <button class="month-btn" data-month="01">Jan</button>
                    <button class="month-btn" data-month="02">Feb</button>
                    <button class="month-btn" data-month="03">Mar</button>
                    <button class="month-btn" data-month="04">Apr</button>
                    <button class="month-btn" data-month="05">May</button>
                    <button class="month-btn" data-month="06">Jun</button>
                    <button class="month-btn" data-month="07">Jul</button>
                    <button class="month-btn" data-month="08">Aug</button>
                    <button class="month-btn" data-month="09">Sep</button>
                    <button class="month-btn" data-month="10">Oct</button>
                    <button class="month-btn" data-month="11">Nov</button>
                    <button class="month-btn" data-month="12">Dec</button>
                </div>
            </div>
        </div>

        <!-- Dropdown Filters -->
        <div class="col-xxl-8 col-xl-7 col-lg-12">
            <div class="filter-card">
                <div class="row g-2">
                    <div class="col-6 col-sm-4 col-xl-4 col-xxl">
                        <label class="form-label small text-muted mb-1 fw-bold">Plant</label>
                        <div class="multi-select" id="filterPlant" data-placeholder="เลือกทั้งหมด">
                            <div class="multi-select-trigger">
                                <span class="ms-placeholder">เลือกทั้งหมด</span>
                                <i class="fa-solid fa-chevron-down ms-arrow"></i>
                            </div>
                            <div class="multi-select-dropdown">
                                <div class="ms-search-wrap"><input type="text" placeholder="ค้นหา..."></div>
                                <div class="ms-options">
                                    <div class="ms-option" data-value="SAAB"><div class="ms-checkbox"></div><span class="ms-option-text">SAAB</span></div>
                                    <div class="ms-option" data-value="SAB"><div class="ms-checkbox"></div><span class="ms-option-text">SAB</span></div>
                                    <div class="ms-option" data-value="SAM"><div class="ms-checkbox"></div><span class="ms-option-text">SAM</span></div>
                                    <div class="ms-option" data-value="SATC"><div class="ms-checkbox"></div><span class="ms-option-text">SATC</span></div>
                                    <div class="ms-option" data-value="SDC"><div class="ms-checkbox"></div><span class="ms-option-text">SDC</span></div>
                                    <div class="ms-option" data-value="SLAB"><div class="ms-checkbox"></div><span class="ms-option-text">SLAB</span></div>
                                    <div class="ms-option" data-value="SRAB"><div class="ms-checkbox"></div><span class="ms-option-text">SRAB</span></div>
                                    <div class="ms-option" data-value="SRDC"><div class="ms-checkbox"></div><span class="ms-option-text">SRDC</span></div>
                                </div>
                                <div class="ms-footer">
                                    <button class="ms-footer-btn ms-select-all" type="button">เลือกทั้งหมด</button>
                                    <span class="ms-count"></span>
                                    <button class="ms-footer-btn ms-clear-all" type="button">ล้าง</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-xl-4 col-xxl">
                        <label class="form-label small text-muted mb-1 fw-bold">Employee Type</label>
                        <div class="multi-select" id="filterEmpType" data-placeholder="เลือกทั้งหมด">
                            <div class="multi-select-trigger">
                                <span class="ms-placeholder">เลือกทั้งหมด</span>
                                <i class="fa-solid fa-chevron-down ms-arrow"></i>
                            </div>
                            <div class="multi-select-dropdown">
                                <div class="ms-search-wrap"><input type="text" placeholder="ค้นหา..."></div>
                                <div class="ms-options"></div>
                                <div class="ms-footer">
                                    <button class="ms-footer-btn ms-select-all" type="button">เลือกทั้งหมด</button>
                                    <span class="ms-count"></span>
                                    <button class="ms-footer-btn ms-clear-all" type="button">ล้าง</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-xl-4 col-xxl">
                        <label class="form-label small text-muted mb-1 fw-bold">Function</label>
                        <div class="multi-select" id="filterFunction" data-placeholder="ทั้งหมด">
                            <div class="multi-select-trigger">
                                <span class="ms-placeholder">ทั้งหมด</span>
                                <i class="fa-solid fa-chevron-down ms-arrow"></i>
                            </div>
                            <div class="multi-select-dropdown">
                                <div class="ms-search-wrap"><input type="text" placeholder="ค้นหา..."></div>
                                <div class="ms-options"></div>
                                <div class="ms-footer">
                                    <button class="ms-footer-btn ms-select-all" type="button">เลือกทั้งหมด</button>
                                    <span class="ms-count"></span>
                                    <button class="ms-footer-btn ms-clear-all" type="button">ล้าง</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-xl-4 col-xxl">
                        <label class="form-label small text-muted mb-1 fw-bold">Employee Category</label>
                        <div class="multi-select" id="filterEmpCategory" data-placeholder="เลือกทั้งหมด">
                            <div class="multi-select-trigger">
                                <span class="ms-placeholder">เลือกทั้งหมด</span>
                                <i class="fa-solid fa-chevron-down ms-arrow"></i>
                            </div>
                            <div class="multi-select-dropdown">
                                <div class="ms-search-wrap"><input type="text" placeholder="ค้นหา..."></div>
                                <div class="ms-options"></div>
                                <div class="ms-footer">
                                    <button class="ms-footer-btn ms-select-all" type="button">เลือกทั้งหมด</button>
                                    <span class="ms-count"></span>
                                    <button class="ms-footer-btn ms-clear-all" type="button">ล้าง</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-xl-4 col-xxl">
                        <label class="form-label small text-muted mb-1 fw-bold">Department</label>
                        <div class="multi-select" id="filterDepartment" data-placeholder="ทั้งหมด">
                            <div class="multi-select-trigger">
                                <span class="ms-placeholder">ทั้งหมด</span>
                                <i class="fa-solid fa-chevron-down ms-arrow"></i>
                            </div>
                            <div class="multi-select-dropdown">
                                <div class="ms-search-wrap"><input type="text" placeholder="ค้นหา..."></div>
                                <div class="ms-options"></div>
                                <div class="ms-footer">
                                    <button class="ms-footer-btn ms-select-all" type="button">เลือกทั้งหมด</button>
                                    <span class="ms-count"></span>
                                    <button class="ms-footer-btn ms-clear-all" type="button">ล้าง</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: KPI Summaries -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-sm-4 col-md-2 col-lg">
            <div class="kpi-card">
                <div class="kpi-title">PERM</div>
                <div class="kpi-value" id="kpi-perm">...</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2 col-lg">
            <div class="kpi-card">
                <div class="kpi-title">PWC</div>
                <div class="kpi-value" id="kpi-pwc">...</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2 col-lg">
            <div class="kpi-card">
                <div class="kpi-title">SUB Thai</div>
                <div class="kpi-value" id="kpi-sub-thai">...</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2 col-lg">
            <div class="kpi-card">
                <div class="kpi-title">SUB Myanmar</div>
                <div class="kpi-value" id="kpi-sub-mm">...</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2 col-lg">
            <div class="kpi-card">
                <div class="kpi-title">SUB Cambodia</div>
                <div class="kpi-value" id="kpi-sub-cam">...</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2 col-lg">
            <div class="kpi-card">
                <div class="kpi-title">SUB</div>
                <div class="kpi-value" id="kpi-sub">...</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2 col-lg">
            <div class="kpi-card">
                <div class="kpi-title">OTHER</div>
                <div class="kpi-value" id="kpi-other">...</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3 col-lg-auto">
            <!-- Gender KPI Card -->
            <div class="kpi-card flex-row align-items-center justify-content-around py-1">
                <div class="text-center px-2">
                    <i class="fa-solid fa-person fa-2x mb-1" style="color: var(--accent-light);"></i>
                    <div class="kpi-title mb-0">Male</div>
                    <div class="kpi-value fs-5" id="kpi-male-pct">...</div>
                </div>
                <div class="text-center px-2">
                    <i class="fa-solid fa-person-dress fa-2x mb-1" style="color: var(--purple);"></i>
                    <div class="kpi-title mb-0">Female</div>
                    <div class="kpi-value fs-5" id="kpi-female-pct">...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Headcount Trend -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="chart-card mb-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="chart-card-title mb-0">Headcount Trend</div>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm shadow-none" id="filterTrendYear" style="width: 100px;">
                            <?php 
                                $curYear = (int)date('Y');
                                for ($y = $curYear; $y >= $curYear - 2; $y--) {
                                    $sel = ($y == $curYear) ? 'selected' : '';
                                    echo "<option value=\"$y\" $sel>$y</option>";
                                }
                            ?>
                        </select>
                    </div>
                </div>
                <div id="trendChart-wrap" style="height: 300px;"><canvas id="trendChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Row: Employee Type Overview (Metric Tiles) -->
    <div class="row g-3 mb-1">
        <div class="col-12">
            <div class="chart-card mb-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="chart-card-title mb-0">
                        <i class="fa-solid fa-layer-group me-2" style="color: var(--accent);"></i>Employee Type Overview
                    </div>
                    <div id="typeOverviewTotal" class="d-flex align-items-center gap-2" style="font-size:0.82rem;color:var(--text-muted);font-weight:600;">
                        <i class="fa-solid fa-users" style="color:var(--accent);"></i>
                        <span>Total: <strong id="typeOverviewTotalNum" style="color:var(--text-primary);font-size:1rem;">...</strong></span>
                    </div>
                </div>
                <!-- Metric Tiles -->
                <div id="typeMetricTiles" class="type-metric-grid">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Row: Breakdown 1 -->
    <div class="row g-3 mb-1">
        <div class="col-12 col-xl-5">
            <div class="chart-card mb-3">
                <div class="chart-card-title">Headcount By Plant</div>
                <div id="plantChart-wrap" style="height: 280px;"><canvas id="plantChart"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="chart-card mb-3">
                <div class="chart-card-title">Headcount By Employee Type</div>
                <div id="typeChart-wrap" style="height: 360px; position: relative;"><canvas id="typeChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Row 5: Breakdown 2 -->
    <div class="row g-3 mb-1">
        <div class="col-12 col-xl-7">
            <div class="chart-card mb-3">
                <div class="chart-card-title">Headcount By Function</div>
                <div id="functionChart-wrap" style="height: 280px;"><canvas id="functionChart"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="chart-card mb-3">
                <div class="chart-card-title">Headcount By Age</div>
                <div id="ageChart-wrap" style="height: 280px;"><canvas id="ageChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Row 6: Employee Data Table -->
    <div class="row">
        <div class="col-12">
            <div class="data-table-container">
                <div class="data-table-header d-flex align-items-center justify-content-between px-3 py-2" style="border-bottom: 1px solid var(--border-color);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-table-list" style="color: var(--accent);"></i>
                        <span style="font-weight: 600; font-size: 0.88rem; color: var(--text-primary);">Employee Data</span>
                    </div>
                    <button class="btn btn-sm btn-export-excel" id="btnExportExcel" onclick="exportTableToExcel()">
                        <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                    </button>
                </div>
                <div class="table-responsive">
                    <table id="employeeTable" class="table table-hover mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th>Plant</th>
                                <th>Company</th>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug Section (Optional SQL View) -->
    <div class="row mt-4 mb-5">
        <div class="col-12 text-center">
            <button class="btn btn-sm btn-outline-secondary opacity-50" type="button" data-bs-toggle="collapse" data-bs-target="#queryDebug" aria-expanded="false">
                <i class="fa-solid fa-code me-2"></i>View SQL Query
            </button>
            <div class="collapse mt-3" id="queryDebug">
                <div class="card card-body text-start font-monospace small" style="max-height: 400px; overflow-y: auto; background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-secondary);">
                    <h6 style="color: var(--accent);" class="border-bottom pb-1">Executed Queries:</h6>
                    <div id="sqlDisplay">Loading...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
