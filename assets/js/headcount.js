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

    // --- Dropdown Change Chain (เหมือน main.js) ---

    // 1. Plant Change -> Reload ALL below
    $('#filterPlant').on('change', function() {
        const f = getFilters();
        loadEmployeeTypeOptions(f.plant);
        loadFunctionOptions(f.plant, f.emp_type);
        loadEmployeeCategoryOptions(f.plant, f.emp_type, f.function);
        loadDepartmentOptions(f.plant, f.emp_type, f.function, f.emp_category);
        refreshData();
    });

    // 2. Employee Type Change -> Reload Function, Category, Dept
    $('#filterEmpType').on('change', function() {
        const f = getFilters();
        loadFunctionOptions(f.plant, f.emp_type);
        loadEmployeeCategoryOptions(f.plant, f.emp_type, f.function);
        loadDepartmentOptions(f.plant, f.emp_type, f.function, f.emp_category);
        refreshData();
    });

    // 3. Function Change -> Reload Category, Dept
    $('#filterFunction').on('change', function() {
        const f = getFilters();
        loadEmployeeCategoryOptions(f.plant, f.emp_type, f.function);
        loadDepartmentOptions(f.plant, f.emp_type, f.function, f.emp_category);
        refreshData();
    });

    // 4. Employee Category Change -> Reload Dept
    $('#filterEmpCategory').on('change', function() {
        const f = getFilters();
        loadDepartmentOptions(f.plant, f.emp_type, f.function, f.emp_category);
        refreshData();
    });

    // 5. Department Change -> Just refresh data
    $('#filterDepartment').on('change', function() {
        refreshData();
    });

    // 5. Trend Year Change -> Refresh data
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

function getFilters() {
    const plantSelect = document.getElementById('filterPlant');
    const plantText = plantSelect ? plantSelect.options[plantSelect.selectedIndex].text : '';
    return {
        plant:        (plantText === 'เลือกทั้งหมด' || plantText === '') ? '' : plantText,
        emp_type:     $('#filterEmpType').val()     || '',
        emp_category: $('#filterEmpCategory').val() || '',
        function:     $('#filterFunction').val()     || '',
        dept:         $('#filterDepartment').val()   || ''
    };
}

// --- OPTION CLOAD FUNCTIONS (จาก main.js เพื่อให้ Chain ทำงานได้) ---

function loadEmployeeTypeOptions(plant = '') {
    const params = new URLSearchParams();
    if (plant) params.set('plant', plant);
    fetch(`api/get_employee_types.php?${params.toString()}`)
        .then(r => r.json()).then(data => populateSelect('filterEmpType', data, 'TYPE_NAME'));
}

function loadFunctionOptions(plant = '', empType = '') {
    const params = new URLSearchParams();
    if (plant)   params.set('plant', plant);
    if (empType) params.set('emp_type', empType);
    fetch(`api/get_functions.php?${params.toString()}`)
        .then(r => r.json()).then(data => populateSelect('filterFunction', data, 'FUNC_NAME'));
}

function loadEmployeeCategoryOptions(plant = '', empType = '', func = '') {
    const params = new URLSearchParams();
    if (plant)   params.set('plant', plant);
    if (empType) params.set('emp_type', empType);
    if (func)    params.set('function', func);
    fetch(`api/get_employee_categories.php?${params.toString()}`)
        .then(r => r.json()).then(data => populateSelect('filterEmpCategory', data, 'EMP_CATEGORY_FULL'));
}

function loadDepartmentOptions(plant = '', empType = '', func = '', empCat = '') {
    const params = new URLSearchParams();
    if (plant)   params.set('plant', plant);
    if (empType) params.set('emp_type', empType);
    if (func)    params.set('function', func);
    if (empCat)  params.set('emp_category', empCat);
    fetch(`api/get_departments.php?${params.toString()}`)
        .then(r => r.json()).then(data => populateSelect('filterDepartment', data, 'DEPT_NAME'));
}

function populateSelect(id, data, key) {
    const select = document.getElementById(id);
    if (!select || !Array.isArray(data)) return;
    const currentVal = select.value;
    select.innerHTML = (id.includes('Plant') || id.includes('EmpCategory') || id.includes('EmpType')) ? '<option value="">เลือกทั้งหมด</option>' : '<option value="">ทั้งหมด</option>';
    data.forEach(item => {
        const val = item[key] || item[key.toLowerCase()] || item.TYPE_NAME || item.FUNC_NAME || item.DEPT_NAME || item.EMP_CATEGORY_FULL;
        if (val) {
            const opt = document.createElement('option');
            opt.value = opt.textContent = val;
            if (val === currentVal) opt.selected = true;
            select.appendChild(opt);
        }
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
    const colorMap = { 'PERM': PALETTE.blue.solid, 'PWC': PALETTE.blue.stop2, 'SUB Thai': PALETTE.red.solid, 'SUB Myanmar': PALETTE.orange.solid, 'SUB Cambodia': PALETTE.green.solid, 'OTHER': PALETTE.gray };
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
                    display: true, anchor: 'end', align: 'end', 
                    offset: window.innerWidth < 768 ? 2 : (window.innerWidth < 1500 ? 5 : 12),
                    color: '#64748b', font: { weight: '600', size: 11 }, formatter: (val) => val.toFixed(1) + '%' }
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
