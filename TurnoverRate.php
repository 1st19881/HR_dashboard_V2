<?php
// TurnoverRate.php
require_once 'includes/auth_check.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'HR Dashboard - Turnover Rate';
$currentPage = 'turnover';
$pageScript = 'assets/js/turnover_rate.js';

include 'includes/header.php';
?>

<div class="dashboard-container">

    <!-- Row 1: Filters -->
    <div class="row">
        <!-- Month Filter (Optional for Turnover, but good for consistency) -->
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
                        <select class="form-select form-select-sm shadow-none" id="filterPlant">
                            <option value="">เลือกทั้งหมด</option>
                            <option value="SAAB">SAAB</option>
                            <option value="SAB">SAB</option>
                            <option value="SAM">SAM</option>
                            <option value="SATC">SATC</option>
                            <option value="SDC">SDC</option>
                            <option value="SLAB">SLAB</option>
                            <option value="SRAB">SRAB</option>
                            <option value="SRDC">SRDC</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-xl-4 col-xxl">
                        <label class="form-label small text-muted mb-1 fw-bold">Employee Type</label>
                        <select class="form-select form-select-sm shadow-none" id="filterEmpType">
                            <option value="">เลือกทั้งหมด</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-xl-4 col-xxl">
                        <label class="form-label small text-muted mb-1 fw-bold">Function</label>
                        <select class="form-select form-select-sm shadow-none" id="filterFunction">
                            <option value="">ทั้งหมด</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-xl-4 col-xxl">
                        <label class="form-label small text-muted mb-1 fw-bold">Employee Category</label>
                        <select class="form-select form-select-sm shadow-none" id="filterEmpCategory">
                            <option value="">เลือกทั้งหมด</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-xl-4 col-xxl">
                        <label class="form-label small text-muted mb-1 fw-bold">Department</label>
                        <select class="form-select form-select-sm shadow-none" id="filterDepartment">
                            <option value="">ทั้งหมด</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3 g-3">
        <div class="col-12">
            <div class="chart-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="chart-card-title mb-0">Turnover Rate & Resignations</div>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm shadow-none" id="filterYear" style="width: 100px;">
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
                <div id="turnoverChart-wrap" style="height: 350px;"><canvas id="turnoverChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Row 3: Color Legend/Note -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center justify-content-center gap-4 py-3 bg-white rounded border shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <span style="width: 14px; height: 14px; background: #f59e0b; border-radius: 4px; box-shadow: 0 2px 4px rgba(245,158,11,0.3);"></span>
                    <span class="fw-bold small text-secondary">SUB (Orange)</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="width: 14px; height: 14px; background: #2563eb; border-radius: 4px; box-shadow: 0 2px 4px rgba(37,99,235,0.3);"></span>
                    <span class="fw-bold small text-secondary">PERM (Blue)</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="width: 14px; height: 14px; background: #8b5cf6; border-radius: 4px; box-shadow: 0 2px 4px rgba(139,92,246,0.3);"></span>
                    <span class="fw-bold small text-secondary">PWC (Purple)</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="width: 14px; height: 14px; background: #94a3b8; border-radius: 4px; box-shadow: 0 2px 4px rgba(148,163,184,0.3);"></span>
                    <span class="fw-bold small text-secondary">OTHER (Gray)</span>
                </div>
                <div class="ms-md-4 border-start ps-4 d-none d-md-block">
                    <small class="text-muted italic"><i class="fa-solid fa-circle-info me-1"></i> กราฟแยกหมวดหมู่ใช้สีตามที่ระบุเพื่อความชัดเจนในการวิเคราะห์</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 4: Category Breakdowns -->
    <div class="row mb-3 g-3">
        <div class="col-12 col-xl-6">
            <div class="chart-card">
                <div class="chart-card-title mb-3">Turnover - SUB</div>
                <div id="subChart-wrap" style="height: 320px;"><canvas id="subChart"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="chart-card">
                <div class="chart-card-title mb-3">Turnover - PERM</div>
                <div id="permChart-wrap" style="height: 320px;"><canvas id="permChart"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="chart-card">
                <div class="chart-card-title mb-3">Turnover - PWC</div>
                <div id="pwcChart-wrap" style="height: 320px;"><canvas id="pwcChart"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="chart-card">
                <div class="chart-card-title mb-3">Turnover - OTHER</div>
                <div id="otherChart-wrap" style="height: 320px;"><canvas id="otherChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Row 5: Turnover By Reason -->
    <div class="row mb-3 g-3">
        <div class="col-12">
            <div class="chart-card">
                <div class="chart-card-title mb-3">Turnover By Reason</div>
                <div id="reasonChart-wrap" style="height: 350px;"><canvas id="reasonChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Row 6: Turnover Details Table (Detailed Employee List) -->
    <div class="row">
        <div class="col-12">
            <div class="data-table-container">
                <div class="chart-card-title mb-3 fs-6 d-flex align-items-center justify-content-between">
                    <span><i class="fa-solid fa-users-slash me-2"></i>Turnover Details (Employee List)</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-normal" id="detailsCount">0 records</span>
                </div>
                <div class="table-responsive">
                    <table id="turnoverDetailsTable" class="table table-hover align-middle mb-0 small" style="width:100%">
                        <thead>
                            <tr class="bg-light">
                                <th class="ps-3">Plant</th>
                                <th>Emp ID</th>
                                <th>Full Name</th>
                                <th>Category</th>
                                <th>Position</th>
                                <th>Function</th>
                                <th>Department</th>
                                <th style="color: var(--red);">Exit Date</th>
                                <th style="color: var(--blue);">Reason</th>
                            </tr>
                        </thead>
                        <tbody id="turnoverDetailsBody">
                            <!-- Data will be loaded here via JS -->
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
