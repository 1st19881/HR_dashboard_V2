// assets/js/headcount.js
// Specialized JS for HeadCount Page — Premium Admin Style (matching index.php)

Chart.register(ChartDataLabels);

let chartInstances = {};

// Palette สีแนว Premium Light (เหมือน main.js)
const PALETTE = {
    blue:   { stop1: '#2563eb', stop2: '#60a5fa', solid: '#2563eb' },
    orange: { stop1: '#f59e0b', stop2: '#fbbf24', solid: '#f59e0b' },
    purple: { stop1: '#8b5cf6', stop2: '#a78bfa', solid: '#8b5cf6' },
    red:    { stop1: '#ef4444', stop2: '#fca5a5', solid: '#ef4444' },
    teal:   { stop1: '#0d9488', stop2: '#5eead4', solid: '#0d9488' },
    green:  { stop1: '#10b981', stop2: '#34d399', solid: '#10b981' },
    gray:   '#94a3b8'
};

// สีประจำ Plant ตามที่ลูกค้ากำหนด
const PLANT_COLORS = {
    'SLAB':   '#3b82f6', // Blue
    'SLAB-2': '#3b82f6', // Blue
    'SLAB-3': '#3b82f6', // Blue
    'SAB':    '#8b5cf6', // Purple
    'SAAB':   '#f59e0b', // Orange
    'SRAB':   '#ef4444', // Red
    'SRDC':   '#10b981', // Green
    'SATC':   '#06b6d4', // Cyan
    'SDC':    '#eab308', // Yellow
    'SAM':    '#6366f1', // Indigo (ค่าเผื่อ)
    'SC':     '#64748b'  // Slate (ค่าเผื่อ)
};

function getPlantColor(name) {
    if (name.includes('SLAB')) return PLANT_COLORS['SLAB'];
    return PLANT_COLORS[name] || '#cbd5e1';
}

// ============================================================
// Global MultiSelect instances
// ============================================================
let msPlant, msEmpType, msFunction, msEmpCategory, msDepartment;

document.addEventListener('DOMContentLoaded', function () {
    // Chart.js defaults (เหมือน main.js)
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(255,255,255,0.95)';
    Chart.defaults.plugins.tooltip.borderColor = '#e2e8f0';
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.titleColor = '#1e293b';
    Chart.defaults.plugins.tooltip.bodyColor = '#475569';

    // Initialize MultiSelect instances with cascade onChange chain
    msPlant = new MultiSelect('filterPlant', {
        onChange: () => {
            const f = getFilters();
            loadEmployeeTypeOptions(f.plant);
            loadFunctionOptions(f.plant, f.emp_type);
            loadEmployeeCategoryOptions(f.plant, f.emp_type, f.function);
            loadDepartmentOptions(f.plant, f.emp_type, f.function, f.emp_category);
            refreshData();
        }
    });

    msEmpType = new MultiSelect('filterEmpType', {
        onChange: () => {
            const f = getFilters();
            loadFunctionOptions(f.plant, f.emp_type);
            loadEmployeeCategoryOptions(f.plant, f.emp_type, f.function);
            loadDepartmentOptions(f.plant, f.emp_type, f.function, f.emp_category);
            refreshData();
        }
    });

    msFunction = new MultiSelect('filterFunction', {
        onChange: () => {
            const f = getFilters();
            loadEmployeeCategoryOptions(f.plant, f.emp_type, f.function);
            loadDepartmentOptions(f.plant, f.emp_type, f.function, f.emp_category);
            refreshData();
        }
    });

    msEmpCategory = new MultiSelect('filterEmpCategory', {
        onChange: () => {
            const f = getFilters();
            loadDepartmentOptions(f.plant, f.emp_type, f.function, f.emp_category);
            refreshData();
        }
    });

    msDepartment = new MultiSelect('filterDepartment', {
        onChange: () => {
            refreshData();
        }
    });

    // Initial loads — ตัวเลือกแรกเริ่ม
    loadEmployeeTypeOptions();
    loadFunctionOptions();
    loadEmployeeCategoryOptions();
    loadDepartmentOptions();
    fetchHeadcountData();

    // Initialize DataTables
    const empTable = $('#employeeTable').DataTable({
        "ajax": {
            "url": "api/get_employee_list.php",
            "data": function(d) {
                const f = getFilters();
                d.plant = f.plant;
                d.emp_type = f.emp_type;
                d.emp_category = f.emp_category;
                d.function = f.function;
                d.dept = f.dept;
            }
        },
        "columns": [
            { "data": "PLANTNO" },
            { "data": "COMPANY" },
            { "data": "CODEMPID" },
            { "data": "NAMEMPT" },
            { "data": "EMP_CATEGORY_FULL" },
            { "data": "CODNATT" },
            { "data": "FUNC_NAME" },
            { "data": "DEPT_NAME" },
            { "data": "SEC_NAME" },
            { "data": "COSTCT" },
            { "data": "BAND" },
            { "data": "GRADE" },
            { "data": "POS_NAME" },
            { "data": "WORKING_YEAR" }
        ],
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "lengthChange": true,
        "language": {
            "search": "ค้นหา:",
            "lengthMenu": "แสดง _MENU_ รายการต่อหน้า",
            "info": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
            "paginate": {
                "first": "หน้าแรก",
                "last": "หน้าสุดท้าย",
                "next": "ถัดไป",
                "previous": "ก่อนหน้า"
            }
        },
        "pageLength": 10,
        "order": [[0, "asc"]],
        "responsive": true,
        "dom": '<"d-flex justify-content-between align-items-center mb-2"f>t<"d-flex justify-content-between align-items-center mt-2"ip>'
    });

    // Trend Year Change -> Refresh data
    $('#filterTrendYear').on('change', function() {
        refreshData();
    });

    function refreshData() {
        const monthFilter = document.querySelector('.month-btn.active');
        const month = monthFilter ? monthFilter.getAttribute('data-month') : '';
        empTable.ajax.reload();
        fetchHeadcountData(month, getFilters());
    }

    const monthBtns = document.querySelectorAll('.month-btn');
    monthBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            monthBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const month = this.getAttribute('data-month');
            fetchHeadcountData(month, getFilters());
        });
    });
});

// Helper: สร้าง Gradient
function createVerticalGradient(ctx, area, colorObj) {
    const gradient = ctx.createLinearGradient(0, area.bottom, 0, area.top);
    gradient.addColorStop(0, colorObj.stop1);
    gradient.addColorStop(1, colorObj.stop2);
    return gradient;
}

// Helper: รวบรวมค่า filter ปัจจุบัน (returns comma-separated strings)
function getFilters() {
    return {
        plant:        msPlant       ? msPlant.getValuesString()       : '',
        emp_type:     msEmpType     ? msEmpType.getValuesString()     : '',
        emp_category: msEmpCategory ? msEmpCategory.getValuesString() : '',
        function:     msFunction    ? msFunction.getValuesString()    : '',
        dept:         msDepartment  ? msDepartment.getValuesString()  : ''
    };
}

// --- OPTION LOAD FUNCTIONS (ใช้ MultiSelect.setOptions) ---

function loadEmployeeTypeOptions(plant = '') {
    const params = new URLSearchParams();
    if (plant) params.set('plant', plant);
    fetch(`api/get_employee_types.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (msEmpType) msEmpType.setOptions(data, 'TYPE_NAME');
        });
}

function loadFunctionOptions(plant = '', empType = '') {
    const params = new URLSearchParams();
    if (plant)   params.set('plant', plant);
    if (empType) params.set('emp_type', empType);
    fetch(`api/get_functions.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (!Array.isArray(data)) return;
            if (msFunction) msFunction.setOptions(data, 'FUNC_NAME');
        });
}

function loadEmployeeCategoryOptions(plant = '', empType = '', func = '') {
    const params = new URLSearchParams();
    if (plant)   params.set('plant', plant);
    if (empType) params.set('emp_type', empType);
    if (func)    params.set('function', func);
    fetch(`api/get_employee_categories.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (!Array.isArray(data)) return;
            if (msEmpCategory) msEmpCategory.setOptions(data, 'EMP_CATEGORY_FULL');
        });
}

function loadDepartmentOptions(plant = '', empType = '', func = '', empCat = '') {
    const params = new URLSearchParams();
    if (plant)   params.set('plant', plant);
    if (empType) params.set('emp_type', empType);
    if (func)    params.set('function', func);
    if (empCat)  params.set('emp_category', empCat);
    fetch(`api/get_departments.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (!Array.isArray(data)) return;
            if (msDepartment) msDepartment.setOptions(data, 'DEPT_NAME');
        });
}

function fetchHeadcountData(month = '', filters = {}) {
    const params = new URLSearchParams({ month });
    if (filters.plant)        params.set('plant',    filters.plant);
    if (filters.emp_type)     params.set('emp_type', filters.emp_type);
    if (filters.emp_category) params.set('emp_category', filters.emp_category);
    if (filters.function)     params.set('function', filters.function);
    if (filters.dept)         params.set('dept',     filters.dept);

    const trendYear = $('#filterTrendYear').val() || '';
    if (trendYear) params.set('trend_year', trendYear);

    fetch(`api/get_headcount_data.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            try {
                updateKPIs(data.kpi);
                renderTrendChart(data.trendData);
                renderPlantChart(data.plantData);
                renderTypeChart(data.typeData, data.kpi.headcount_all);
                renderTypeDistChart(data.typeCountData || []);
                renderFunctionChart(data.functionData);
                renderAgeChart(data.ageData);

                // Populate Debug SQL
                const sqlEl = document.getElementById('sqlDisplay');
                if (data.debug && sqlEl) {
                    let debugHtml = '';
                    Object.keys(data.debug).forEach(key => {
                        const sqlStr = data.debug[key] || '';
                        debugHtml += `<div class="mb-3 text-secondary p-3 bg-light border rounded" style="word-break: break-all;">
                                        <strong class="text-primary">${key}:</strong><br>
                                        ${sqlStr.replace(/\n/g, '<br>')}
                                      </div>`;
                    });
                    sqlEl.innerHTML = debugHtml;
                }
            } catch (err) {
                console.error("Layout Update Error:", err);
            }
        })
        .catch(err => console.error('Fetch Error:', err));
}

function updateKPIs(kpi) {
    const ids = {
        'kpi-perm': kpi.perm,
        'kpi-pwc': kpi.pwc,
        'kpi-sub-thai': kpi.sub_thai,
        'kpi-sub-mm': kpi.sub_myanmar,
        'kpi-sub-cam': kpi.sub_cambodia,
        'kpi-sub': kpi.sub,
        'kpi-other': kpi.other,
        'kpi-male-pct': kpi.male_pct + '%',
        'kpi-female-pct': kpi.female_pct + '%'
    };
    Object.keys(ids).forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            let val = ids[id];
            if (typeof val === 'number') {
                el.textContent = val > 0 ? formatNumber(val) : '--';
            } else {
                el.textContent = (val === '0%' || val === 'null%') ? '--' : val;
            }
        }
    });
}

function formatNumber(num) {
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}

function getOrCreateChart(canvasId, config) {
    if (chartInstances[canvasId]) chartInstances[canvasId].destroy();
    const el = document.getElementById(canvasId);
    if (!el) return null;
    const ctx = el.getContext('2d');
    chartInstances[canvasId] = new Chart(ctx, config);
    return chartInstances[canvasId];
}

// ─── 1. Employee Growth Trend (Gradient Area) ───
function renderTrendChart(dataSeries) {
    getOrCreateChart('trendChart', {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Count Employee',
                data: dataSeries,
                borderColor: PALETTE.blue.solid,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: PALETTE.blue.solid,
                pointBorderWidth: 2,
                pointRadius: function(context) {
                    return context.raw === null ? 0 : 4;
                },
                pointHoverRadius: function(context) {
                    return context.raw === null ? 0 : 6;
                },
                fill: true,
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return null;
                    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                    gradient.addColorStop(0, 'rgba(37, 99, 235, 0)');
                    gradient.addColorStop(1, 'rgba(37, 99, 235, 0.1)');
                    return gradient;
                },
                tension: 0.4,
                spanGaps: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 25,
                    bottom: 10
                }
            },
            plugins: {
                legend: { display: false },
                datalabels: {
                    align: 'top',
                    offset: 8,
                    color: '#2563eb',
                    font: { weight: 'bold', size: 10 },
                    display: function(context) {
                        return context.dataset.data[context.dataIndex] !== null;
                    },
                    formatter: function(v) {
                        return v !== null ? formatNumber(v) : '';
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { 
                    grid: { borderDash: [5, 5], color: '#e2e8f0' }, 
                    ticks: { stepSize: 100, color: '#64748b' },
                    grace: '10%'
                }
            }
        }
    });
}

// ─── 2. Headcount By Plant (Premium Pie with Callout Lines) ───
function renderPlantChart(dataSeries) {
    const labels = dataSeries.map(d => d.name);
    const values = dataSeries.map(d => d.y);

    const colors = labels.map(label => getPlantColor(label));

    getOrCreateChart('plantChart', {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 25, bottom: 25, left: 25, right: 25 } },
            plugins: {
                legend: { position: 'right', labels: { padding: 15, usePointStyle: true, font: { size: 11 } } },
                datalabels: {
                    display: true, anchor: 'end', align: 'end', offset: 15,
                    color: '#64748b', font: { weight: '600', size: 10 },
                    formatter: (value, ctx) => {
                        let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        let pct = (value * 100 / sum).toFixed(1);
                        return value > (sum * 0.03) ? pct + '%' : '';
                    }
                }
            }
        },
        plugins: [{
            id: 'pieCalloutLines',
            afterDraw(chart) {
                const { ctx, chartArea: { top, bottom, left, right } } = chart;
                const centerX = (left + right) / 2;
                const centerY = (top + bottom) / 2;
                chart.data.datasets.forEach((dataset, i) => {
                    chart.getDatasetMeta(i).data.forEach((datapoint, index) => {
                        const { startAngle, endAngle, outerRadius } = datapoint;
                        const midAngle = startAngle + (endAngle - startAngle) / 2;
                        const sum = dataset.data.reduce((a, b) => a + b, 0);
                        if (dataset.data[index] < (sum * 0.03)) return;
                        const startX = centerX + Math.cos(midAngle) * outerRadius;
                        const startY = centerY + Math.sin(midAngle) * outerRadius;
                        const midX = centerX + Math.cos(midAngle) * (outerRadius + 12);
                        const midY = centerY + Math.sin(midAngle) * (outerRadius + 12);
                        const endX = midX + (Math.cos(midAngle) > 0 ? 8 : -8);
                        ctx.beginPath();
                        ctx.moveTo(startX, startY); ctx.lineTo(midX, midY); ctx.lineTo(endX, midY);
                        ctx.strokeStyle = '#cbd5e1'; ctx.lineWidth = 1; ctx.stroke();
                        ctx.beginPath(); ctx.arc(endX, midY, 2, 0, 2 * Math.PI); ctx.fillStyle = '#cbd5e1'; ctx.fill();
                    });
                });
            }
        }]
    });
}

// ─── 3. Headcount By Employee Type (Premium Donut) ───
function renderTypeChart(dataSeries, totalHeadcount) {
    const labels = dataSeries.map(d => d.name);
    const values = dataSeries.map(d => d.y);
    const colorMap = { 'PERM': PALETTE.blue.solid, 'PWC': PALETTE.blue.stop2, 'SUB': '#a855f7', 'SUB Thai': PALETTE.red.solid, 'SUB Myanmar': PALETTE.orange.solid, 'SUB Cambodia': PALETTE.green.solid, 'OTHER': PALETTE.gray };
    const colors = dataSeries.map(d => colorMap[d.name] || PALETTE.gray);

    getOrCreateChart('typeChart', {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{ data: values, backgroundColor: colors, borderWidth: 0, hoverOffset: 15, borderRadius: 8, spacing: 5 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '75%',
            layout: { 
                padding: { 
                    top: window.innerWidth < 768 ? 20 : 40, 
                    bottom: window.innerWidth < 768 ? 40 : (window.innerWidth < 1500 ? 60 : 30), 
                    left: window.innerWidth < 768 ? 10 : 50, 
                    right: window.innerWidth < 768 ? 10 : 50 
                } 
            },
            plugins: {
                legend: {
                    display: true, position: window.innerWidth < 1500 ? 'bottom' : 'right',
                    labels: {
                        padding: window.innerWidth < 1500 ? 20 : 15, usePointStyle: true, font: { size: 12 }, color: '#1e293b',
                        generateLabels: (chart) => {
                            const data = chart.data;
                            return data.labels.map((label, i) => {
                                return { text: label, fillStyle: data.datasets[0].backgroundColor[i], strokeStyle: data.datasets[0].backgroundColor[i], lineWidth: 0, pointStyle: 'circle', fontColor: '#1e293b', hidden: false, index: i };
                            });
                        }
                    }
                },
                datalabels: { 
                    display: function(context) {
                        return context.dataset.data[context.dataIndex] >= 1.0;
                    },
                    anchor: 'end',
                    align: 'end', 
                    offset: window.innerWidth < 768 ? 2 : (window.innerWidth < 1500 ? 5 : 12),
                    color: '#64748b',
                    font: { weight: '600', size: 11 },
                    formatter: (val) => val.toFixed(1) + '%'
                }
            }
        },
        plugins: [{
            id: 'calloutLines',
            afterDraw(chart) {
                const { ctx, chartArea: { top, bottom, left, right } } = chart;
                const centerX = (left + right) / 2; const centerY = (top + bottom) / 2;
                chart.data.datasets.forEach((dataset, i) => {
                    chart.getDatasetMeta(i).data.forEach((datapoint, index) => {
                        const { startAngle, endAngle, outerRadius } = datapoint; const midAngle = startAngle + (endAngle - startAngle) / 2;
                        if (dataset.data[index] < 1) return;
                        const startX = centerX + Math.cos(midAngle) * outerRadius; const startY = centerY + Math.sin(midAngle) * outerRadius;
                        const midX = centerX + Math.cos(midAngle) * (outerRadius + 12); const midY = centerY + Math.sin(midAngle) * (outerRadius + 12);
                        const endX = midX + (Math.cos(midAngle) > 0 ? 8 : -8);
                        ctx.beginPath(); ctx.moveTo(startX, startY); ctx.lineTo(midX, midY); ctx.lineTo(endX, midY);
                        ctx.strokeStyle = '#cbd5e1'; ctx.lineWidth = 1; ctx.stroke();
                        ctx.beginPath(); ctx.arc(endX, midY, 2, 0, 2 * Math.PI); ctx.fillStyle = '#cbd5e1'; ctx.fill();
                    });
                });
            }
        }, {
            id: 'centerText',
            beforeDraw(chart) {
                const { ctx, chartArea } = chart; if (!chartArea) return;
                const centerX = (chartArea.left + chartArea.right) / 2; const centerY = (chartArea.top + chartArea.bottom) / 2;
                ctx.save(); ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                const isMobile = window.innerWidth < 768;
                ctx.font = `bold ${isMobile ? '18px' : '26px'} Inter`; ctx.fillStyle = '#1e293b'; ctx.fillText(formatNumber(totalHeadcount), centerX, centerY - (isMobile ? 5 : 8));
                ctx.font = `500 ${isMobile ? '10px' : '12px'} Inter`; ctx.fillStyle = '#64748b'; ctx.fillText('Headcount', centerX, centerY + (isMobile ? 10 : 15)); ctx.restore();
            }
        }]
    });
}

// ─── 3.5 Employee Type Overview (Segmented Bar + Metric Tiles) ───

// Color & icon config for employee types
const TYPE_CONFIG = {
    'DIRECT':   { color: '#2563eb', colorLight: '#dbeafe', gradient: ['#2563eb', '#60a5fa'], icon: 'fa-solid fa-helmet-safety',  label: 'Direct' },
    'INDIRECT': { color: '#8b5cf6', colorLight: '#ede9fe', gradient: ['#8b5cf6', '#a78bfa'], icon: 'fa-solid fa-briefcase',      label: 'Indirect' },
    'ADMIN':    { color: '#0d9488', colorLight: '#ccfbf1', gradient: ['#0d9488', '#2dd4bf'], icon: 'fa-solid fa-user-gear',      label: 'Admin' },
    'MANAGER':  { color: '#d97706', colorLight: '#fef3c7', gradient: ['#d97706', '#fbbf24'], icon: 'fa-solid fa-user-tie',       label: 'Manager' },
    'OTHER':    { color: '#64748b', colorLight: '#f1f5f9', gradient: ['#64748b', '#94a3b8'], icon: 'fa-solid fa-user',           label: 'Other' }
};

function getTypeConfig(name) {
    return TYPE_CONFIG[name] || TYPE_CONFIG['OTHER'];
}

// Segmented Composition Bar
function renderTypeSegmentBar(dataSeries) {
    const container = document.getElementById('typeSegmentBar');
    if (!container || !dataSeries || dataSeries.length === 0) return;

    const total = dataSeries.reduce((sum, d) => sum + d.qty, 0);
    if (total === 0) { container.innerHTML = ''; return; }

    let html = '';
    dataSeries.forEach((d, i) => {
        const cfg = getTypeConfig(d.name);
        const pct = (d.qty / total * 100);
        const isFirst = i === 0;
        const isLast = i === dataSeries.length - 1;
        const radius = isFirst ? '18px 0 0 18px' : (isLast ? '0 18px 18px 0' : '0');
        if (dataSeries.length === 1) var soloRadius = '18px';

        html += `<div class="type-segment" 
            style="flex-basis: 0%; background: linear-gradient(135deg, ${cfg.gradient[0]}, ${cfg.gradient[1]}); border-radius: ${soloRadius || radius};"
            data-target-basis="${pct}%"
            title="${d.name}: ${formatNumber(d.qty)} คน (${pct.toFixed(1)}%)">
            <span class="seg-label">
                <span class="seg-name">${d.name}</span>
                <span class="seg-pct">${pct >= 8 ? pct.toFixed(1) + '%' : ''}</span>
            </span>
        </div>`;
    });

    container.innerHTML = html;

    // Animate segment widths
    requestAnimationFrame(() => {
        setTimeout(() => {
            container.querySelectorAll('.type-segment').forEach(seg => {
                seg.style.flexBasis = seg.dataset.targetBasis;
            });
        }, 50);
    });
}

// Metric Tiles with SVG Circular Progress
function renderTypeMetricTiles(dataSeries) {
    const container = document.getElementById('typeMetricTiles');
    if (!container || !dataSeries || dataSeries.length === 0) return;

    const circumference = 2 * Math.PI * 20; // radius = 20

    let html = '';
    dataSeries.forEach((d) => {
        const cfg = getTypeConfig(d.name);
        const offset = circumference - (d.pct / 100) * circumference;

        html += `
        <div class="type-metric-tile" style="--tile-color: ${cfg.color};">
            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,${cfg.gradient[0]},${cfg.gradient[1]});opacity:0;transition:opacity 0.3s;" class="tile-accent-bar"></div>
            <div class="type-ring-wrap">
                <svg viewBox="0 0 48 48">
                    <circle class="type-ring-bg" cx="24" cy="24" r="20"></circle>
                    <circle class="type-ring-fill" cx="24" cy="24" r="20" 
                        stroke="${cfg.color}"
                        stroke-dasharray="${circumference}"
                        stroke-dashoffset="${circumference}"
                        data-target-offset="${offset}"></circle>
                </svg>
                <div class="type-ring-icon" style="color: ${cfg.color};">
                    <i class="${cfg.icon}"></i>
                </div>
            </div>
            <div class="type-metric-info">
                <div class="type-metric-name">${cfg.label}</div>
                <div class="type-metric-count" style="color: ${cfg.color};">${formatNumber(d.qty)}</div>
                <span class="type-metric-pct" style="background: ${cfg.colorLight}; color: ${cfg.color};">${d.pct.toFixed(1)}%</span>
            </div>
        </div>`;
    });

    container.innerHTML = html;

    // Animate ring fills + accent bars
    requestAnimationFrame(() => {
        setTimeout(() => {
            container.querySelectorAll('.type-ring-fill').forEach(ring => {
                ring.style.strokeDashoffset = ring.dataset.targetOffset;
            });
        }, 200);
    });

    // Hover accent bar logic (CSS handles via parent :hover)
    container.querySelectorAll('.type-metric-tile').forEach(tile => {
        const bar = tile.querySelector('.tile-accent-bar');
        tile.addEventListener('mouseenter', () => { if (bar) bar.style.opacity = '1'; });
        tile.addEventListener('mouseleave', () => { if (bar) bar.style.opacity = '0'; });
    });
}

// Combined call
function renderTypeDistChart(dataSeries) {
    // Update total
    const totalEl = document.getElementById('typeOverviewTotalNum');
    if (totalEl && dataSeries && dataSeries.length > 0) {
        const total = dataSeries.reduce((sum, d) => sum + d.qty, 0);
        totalEl.textContent = formatNumber(total);
    }
    renderTypeMetricTiles(dataSeries);
}

// ─── 4. Headcount By Function (Gradient Bar) ───
function renderFunctionChart(dataSeries) {
    const labels = dataSeries.map(d => d.name);
    const values = dataSeries.map(d => d.y);
    const maxVal = Math.max(...values, 10);
    const yMax = Math.ceil(maxVal * 1.25 / 10) * 10; // เผื่อ 25% สำหรับ label
    getOrCreateChart('functionChart', {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: function(context) { const chart = context.chart; const {ctx, chartArea} = chart; if (!chartArea) return PALETTE.red.solid; return createVerticalGradient(ctx, chartArea, PALETTE.red); },
                borderRadius: 5, maxBarThickness: 35
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            layout: { padding: { top: 20 } },
            plugins: { legend: { display: false }, datalabels: { anchor: 'end', align: 'top', color: PALETTE.red.solid, font: { weight: 'bold' }, formatter: val => val + '%' } },
            scales: { x: { grid: { display: false }, ticks: { color: '#64748b' } }, y: { max: yMax, grid: { borderDash: [5, 5], color: '#e2e8f0' }, ticks: { callback: v => v + '%', color: '#64748b' } } }
        }
    });
}

// ─── 5. Headcount By Age (Gradient Green Bar) ───
function renderAgeChart(dataSeries) {
    const labels = dataSeries.map(d => d.name);
    const values = dataSeries.map(d => d.y);
    const maxVal = Math.max(...values, 5);
    const yMax = Math.ceil(maxVal * 1.25 / 5) * 5; // เผื่อ 25% สำหรับ label
    getOrCreateChart('ageChart', {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: function(context) { const chart = context.chart; const {ctx, chartArea} = chart; if (!chartArea) return PALETTE.green.solid; return createVerticalGradient(ctx, chartArea, PALETTE.green); },
                borderRadius: 5, maxBarThickness: 40
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            layout: { padding: { top: 20 } },
            plugins: { legend: { display: false }, datalabels: { anchor: 'end', align: 'top', color: '#4ade80', font: { weight: 'bold' }, formatter: val => val + '%' } },
            scales: { x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#64748b' } }, y: { max: yMax, grid: { borderDash: [5, 5], color: '#e2e8f0' }, ticks: { callback: v => v + '%', color: '#64748b' } } }
        }
    });
}

/**
 * Export Employee Table to Excel (.xlsx)
 * ดึงข้อมูลทั้งหมดจาก server (ไม่ใช่แค่หน้าปัจจุบัน) แล้วสร้างไฟล์ Excel
 */
function exportTableToExcel() {
    const btn = document.getElementById('btnExportExcel');
    if (!btn) return;

    // Show loading state
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> กำลังส่งออก...';
    btn.classList.add('exporting');

    // Build params from current filters
    const filters = getFilters();
    const params = new URLSearchParams();
    if (filters.plant)        params.set('plant', filters.plant);
    if (filters.emp_type)     params.set('emp_type', filters.emp_type);
    if (filters.emp_category) params.set('emp_category', filters.emp_category);
    if (filters.function)     params.set('function', filters.function);
    if (filters.dept)         params.set('dept', filters.dept);
    params.set('export', '1'); // Flag to get all data (no pagination)

    fetch(`api/get_employee_list.php?${params.toString()}`)
        .then(response => response.json())
        .then(json => {
            const data = json.data || [];
            if (data.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่มีข้อมูล',
                    text: 'ไม่พบข้อมูลพนักงานสำหรับ Export',
                    confirmButtonColor: '#2563eb'
                });
                btn.innerHTML = originalHTML;
                btn.classList.remove('exporting');
                return;
            }

            // Define column headers
            const headers = [
                'Plant', 'Company', 'Employee ID', 'Full Name',
                'Employee Category', 'Category(By Nationality)',
                'Function', 'Department', 'Section',
                'Cost Center', 'Band', 'Grade',
                'Position Group', 'Years of Service'
            ];

            // Map data to rows
            const rows = data.map(emp => [
                emp.PLANTNO || '',
                emp.COMPANY || '',
                emp.CODEMPID || '',
                emp.NAMEMPT || '',
                emp.EMP_CATEGORY_FULL || '',
                emp.CODNATT || '',
                emp.FUNC_NAME || '',
                emp.DEPT_NAME || '',
                emp.SEC_NAME || '',
                emp.COSTCT || '',
                emp.BAND || '',
                emp.GRADE || '',
                emp.POS_NAME || '',
                emp.WORKING_YEAR || ''
            ]);

            // Create worksheet
            const wsData = [headers, ...rows];
            const ws = XLSX.utils.aoa_to_sheet(wsData);

            // Auto-width columns
            const colWidths = headers.map((h, i) => {
                let maxLen = h.length;
                rows.forEach(row => {
                    const cellLen = String(row[i] || '').length;
                    if (cellLen > maxLen) maxLen = cellLen;
                });
                return { wch: Math.min(maxLen + 2, 40) };
            });
            ws['!cols'] = colWidths;

            // Create workbook and export
            const wb = XLSX.utils.book_new();
            const today = new Date();
            const dateStr = today.getFullYear() + 
                           String(today.getMonth() + 1).padStart(2, '0') + 
                           String(today.getDate()).padStart(2, '0');
            XLSX.utils.book_append_sheet(wb, ws, 'HeadCount Data');
            XLSX.writeFile(wb, `HeadCount_Data_${dateStr}.xlsx`);

            // Success feedback
            Swal.fire({
                icon: 'success',
                title: 'Export สำเร็จ!',
                text: `ส่งออกข้อมูล ${formatNumber(data.length)} รายการ เรียบร้อยแล้ว`,
                timer: 2500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });

            btn.innerHTML = originalHTML;
            btn.classList.remove('exporting');
        })
        .catch(err => {
            console.error('Export Error:', err);
            Swal.fire({
                icon: 'error',
                title: 'Export ล้มเหลว',
                text: 'เกิดข้อผิดพลาดในการส่งออกข้อมูล',
                confirmButtonColor: '#2563eb'
            });
            btn.innerHTML = originalHTML;
            btn.classList.remove('exporting');
        });
}
