// assets/js/main.js
// Modern AI-Enhanced Professional Admin Style (Chart.js 4.x)

Chart.register(ChartDataLabels);

let chartInstances = {};

// Palette สีแนว Premium Light
const PALETTE = {
    blue:   { stop1: '#2563eb', stop2: '#60a5fa', solid: '#2563eb' },
    orange: { stop1: '#f59e0b', stop2: '#fbbf24', solid: '#f59e0b' },
    purple: { stop1: '#8b5cf6', stop2: '#a78bfa', solid: '#8b5cf6' },
    red:    { stop1: '#ef4444', stop2: '#fca5a5', solid: '#ef4444' },
    teal:   { stop1: '#0d9488', stop2: '#5eead4', solid: '#0d9488' },
    gray:   '#94a3b8'
};

document.addEventListener('DOMContentLoaded', function () {
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
    Chart.defaults.plugins.tooltip.shadowBlur = 10;
    Chart.defaults.plugins.tooltip.shadowColor = 'rgba(0,0,0,0.1)';

    loadEmployeeTypeOptions();
    loadFunctionOptions();
    loadEmployeeCategoryOptions();
    loadDepartmentOptions();
    fetchDashboardData();

    // Initialize DataTables
    const empTable = $('#employeeTable').DataTable({
        "ajax": {
            "url": "api/get_employee_list.php",
            "data": function(d) {
                const plantSelect = document.getElementById('filterPlant');
                d.plant = plantSelect ? plantSelect.options[plantSelect.selectedIndex].text : '';
                if (d.plant === 'เลือกทั้งหมด') d.plant = '';
                
                d.emp_type = $('#filterEmpType').val();
                d.emp_category = $('#filterEmpCategory').val();
                d.function = $('#filterFunction').val();
                d.dept = $('#filterDepartment').val();
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
    
    // ดึง SQL Debug มาแสดงผล
    empTable.on('xhr.dt', function(e, settings, json, xhr) {
        const sqlEl = document.getElementById('sqlDisplay');
        if (sqlEl && json && json.debug_sql) {
            sqlEl.innerHTML = `<div class="mb-3 text-secondary p-3 bg-light border rounded" style="word-break: break-all;">
                                <strong class="text-primary">Employee List SQL:</strong><br>
                                ${json.debug_sql.replace(/\n/g, '<br>')}
                                <br><br>
                                <strong class="text-primary">Binds:</strong><br>
                                ${json.binds || '{}'}
                               </div>`;
        }
    });

    // Handle Filter Changes (The Chain)
    
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

    // Handle Year Change
    $('#trendYear').on('change', function() {
        refreshData();
    });

    function refreshData() {
        empTable.ajax.reload();
        const activeBtn = document.querySelector('.month-btn.active');
        const month = activeBtn ? activeBtn.getAttribute('data-month') : '';
        fetchDashboardData(month, getFilters());
    }

    const monthBtns = document.querySelectorAll('.month-btn');
    monthBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            monthBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const month = this.getAttribute('data-month');
            fetchDashboardData(month, getFilters());
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

// Helper: รวบรวมค่า filter ปัจจุบัน
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

function loadEmployeeTypeOptions(plant = '') {
    const params = new URLSearchParams();
    if (plant) params.set('plant', plant);

    fetch(`api/get_employee_types.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('filterEmpType');
            const currentVal = select.value;
            select.innerHTML = '<option value="">เลือกทั้งหมด</option>';
            data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = opt.textContent = item.TYPE_NAME;
                if (item.TYPE_NAME === currentVal) opt.selected = true;
                select.appendChild(opt);
            });
        });
}

function loadFunctionOptions(plant = '', empType = '') {
    const params = new URLSearchParams();
    if (plant)   params.set('plant', plant);
    if (empType) params.set('emp_type', empType);

    fetch(`api/get_functions.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('filterFunction');
            const currentVal = select.value;
            select.innerHTML = '<option value="">ทั้งหมด</option>';
            
            if (!Array.isArray(data)) return;

            data.forEach(item => {
                const name = item.FUNC_NAME || item.func_name;
                if (name && name !== '') {
                    const opt = document.createElement('option');
                    opt.value = opt.textContent = name;
                    if (name === currentVal) opt.selected = true;
                    select.appendChild(opt);
                }
            });
        });
}

function loadEmployeeCategoryOptions(plant = '', empType = '', func = '') {
    const params = new URLSearchParams();
    if (plant)   params.set('plant', plant);
    if (empType) params.set('emp_type', empType);
    if (func)    params.set('function', func);

    fetch(`api/get_employee_categories.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('filterEmpCategory');
            const currentVal = select.value;
            select.innerHTML = '<option value="">เลือกทั้งหมด</option>';
            if (!Array.isArray(data)) return;

            data.forEach(item => {
                const name = item.EMP_CATEGORY_FULL || item.emp_category_full;
                if (name && name !== '') {
                    const opt = document.createElement('option');
                    opt.value = opt.textContent = name;
                    if (name === currentVal) opt.selected = true;
                    select.appendChild(opt);
                }
            });
        });
}

function loadDepartmentOptions(plant = '', empType = '', func = '', empCat = '') {
    const params = new URLSearchParams();
    if (plant)   params.set('plant', plant);
    if (empType) params.set('emp_type', empType);
    if (func)    params.set('function', func);
    if (empCat)  params.set('emp_category', empCat);

    fetch(`api/get_departments.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('filterDepartment');
            const currentVal = select.value;
            select.innerHTML = '<option value="">ทั้งหมด</option>';

            if (!Array.isArray(data)) return;

            data.forEach(item => {
                const name = item.DEPT_NAME || item.dept_name;
                if (name && name !== '') {
                    const opt = document.createElement('option');
                    opt.value = opt.textContent = name;
                    if (name === currentVal) opt.selected = true;
                    select.appendChild(opt);
                }
            });
        });
}

function fetchDashboardData(month = '', filters = {}) {
    const trendYear = $('#trendYear').val() || new Date().getFullYear();
    const params = new URLSearchParams({ month, year: trendYear });
    if (filters.plant)        params.set('plant',    filters.plant);
    if (filters.emp_type)     params.set('emp_type', filters.emp_type);
    if (filters.emp_category) params.set('emp_category', filters.emp_category);
    if (filters.function)     params.set('function', filters.function);
    if (filters.dept)         params.set('dept',     filters.dept);
    
    console.log('[MonthFilter] Sending:', { month, year: trendYear, url: `api/get_dashboard_data.php?${params.toString()}` });
    
    fetch(`api/get_dashboard_data.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            console.log('[MonthFilter] Response debug:', data.debug?.['Month Param'], data.debug?.['Snapshot Mode'], 'KPI:', data.kpi);
            
            // General Trends & KPIs (Available on both pages)
            if (document.getElementById('trendChart')) renderTrendChart(data.trendData);
            
            // KPI Values
            const kpiIds = {
                'kpi-perm': data.kpi.perm,
                'kpi-pwc': data.kpi.pwc,
                'kpi-sub-thai': data.kpi.sub_thai,
                'kpi-sub-mm': data.kpi.sub_myanmar,
                'kpi-sub-cam': data.kpi.sub_cambodia,
                'kpi-other': data.kpi.other
            };
            
            Object.keys(kpiIds).forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = kpiIds[id] !== null ? formatNumber(kpiIds[id]) : '--';
            });

            if (document.getElementById('kpi-male-pct')) document.getElementById('kpi-male-pct').textContent = data.kpi.male_pct + '%';
            if (document.getElementById('kpi-female-pct')) document.getElementById('kpi-female-pct').textContent = data.kpi.female_pct + '%';

            // Page Specific Charts
            if (document.getElementById('typeChart')) renderTypeChart(data.typeData, data.kpi.headcount_all);
            if (document.getElementById('functionChart')) renderFunctionChart(data.functionData);
            if (document.getElementById('turnoverChart')) renderTurnoverChart(data.turnoverData);
            if (document.getElementById('plantChart')) renderPlantChart(data.plantData);
            if (document.getElementById('ageChart')) renderAgeChart(data.ageData);
            if (document.getElementById('typeMetricTiles')) renderTypeDistChart(data.typeCountData || []);

            // Populate Debug SQL
            const sqlEl = document.getElementById('sqlDisplay');
            if (sqlEl && data.debug) {
                // เก็บค่าเก่าไว้เผื่อกรณี DataTable โหลดเสร็จก่อน
                let currentHtml = sqlEl.innerHTML;
                if (currentHtml === 'Loading...') currentHtml = '';
                
                let newHtml = '';
                Object.keys(data.debug).forEach(key => {
                    const sqlStr = String(data.debug[key] ?? '');
                    newHtml += `<div class="mb-3 text-secondary p-3 bg-light border rounded" style="word-break: break-all;">
                                    <strong class="text-primary">${key}:</strong><br>
                                    ${sqlStr.replace(/\n/g, '<br>')}
                                  </div>`;
                });
                
                // เราจะไม่ทับตัว Employee List SQL ถ้ามีอยู่แล้วให้ต่อท้าย (หรือเอาไว้บนสุด)
                sqlEl.innerHTML = newHtml + '<hr class="border-secondary">' + currentHtml;
            }
        })
        .catch(error => console.error('Error fetching dashboard data:', error));
}

function formatNumber(num) {
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}

function getOrCreateChart(canvasId, config) {
    if (chartInstances[canvasId]) chartInstances[canvasId].destroy();
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    const ctx = canvas.getContext('2d');
    chartInstances[canvasId] = new Chart(ctx, config);
    return chartInstances[canvasId];
}

// 1. Headcount Trend (Area Gradient)
function renderTrendChart(dataSeries) {
    getOrCreateChart('trendChart', {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
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
                padding: { top: 30 }
            },
            plugins: {
                legend: { display: false },
                datalabels: {
                    align: 'top',
                    color: '#2563eb',
                    offset: 8,
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
                x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#64748b' } },
                y: { 
                    grace: '10%',
                    grid: { borderDash: [5, 5], color: '#e2e8f0' },
                    ticks: { color: '#64748b' }
                }
            }
        }
    });
}

// 2. Headcount By Employee Type (Premium Donut)
function renderTypeChart(dataSeries, totalHeadcount) {
    const labels = dataSeries.map(d => d.name);
    const values = dataSeries.map(d => d.y);
    
    const colorMap = {
        'PERM':         '#2563eb',
        'PWC':          '#60a5fa',
        'SUB Thai':     '#ef4444',
        'SUB Myanmar':  '#f59e0b',
        'SUB Cambodia': '#10b981',
        'OTHER':        '#94a3b8'
    };
    const colors = dataSeries.map(d => colorMap[d.name] || PALETTE.gray);

    getOrCreateChart('typeChart', {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 15,
                borderRadius: 8, // ขอบมน
                spacing: 5       // เว้นระยะ
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
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
                    display: true,
                    position: window.innerWidth < 1500 ? 'bottom' : 'right',
                    labels: {
                        padding: window.innerWidth < 1500 ? 20 : 15,
                        usePointStyle: true,
                        font: { size: 12 },
                        color: '#111111',
                        generateLabels: function(chart) {
                            const data     = chart.data;
                            const total    = data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const isMobile = window.innerWidth < 768;
                            return data.labels.map((label, i) => {
                                const val = data.datasets[0].data[i];
                                const pct = total > 0 ? (val / total * 100).toFixed(1) : 0;
                                return {
                                    text: label,
                                    fillStyle:   data.datasets[0].backgroundColor[i],
                                    strokeStyle: data.datasets[0].backgroundColor[i],
                                    lineWidth: 0,
                                    pointStyle: 'circle',
                                    fontColor: '#111111',
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                    }
                },
                datalabels: {
                    display: true,
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
                const centerX = (left + right) / 2;
                const centerY = (top + bottom) / 2;

                chart.data.datasets.forEach((dataset, i) => {
                    chart.getDatasetMeta(i).data.forEach((datapoint, index) => {
                        const { startAngle, endAngle, outerRadius } = datapoint;
                        const midAngle = startAngle + (endAngle - startAngle) / 2;
                        
                        if (dataset.data[index] < 1) return;

                        const startX = centerX + Math.cos(midAngle) * outerRadius;
                        const startY = centerY + Math.sin(midAngle) * outerRadius;
                        const midX = centerX + Math.cos(midAngle) * (outerRadius + 12);
                        const midY = centerY + Math.sin(midAngle) * (outerRadius + 12);
                        const endX = midX + (Math.cos(midAngle) > 0 ? 8 : -8);

                        // วาดเส้นชี้ (L-shape)
                        ctx.beginPath();
                        ctx.moveTo(startX, startY);
                        ctx.lineTo(midX, midY);
                        ctx.lineTo(endX, midY);
                        ctx.strokeStyle = '#cbd5e1';
                        ctx.lineWidth = 1;
                        ctx.stroke();

                        // วาดจุดปลายเส้น
                        ctx.beginPath();
                        ctx.arc(endX, midY, 2, 0, 2 * Math.PI);
                        ctx.fillStyle = '#cbd5e1';
                        ctx.fill();
                    });
                });
            }
        }, {
            id: 'centerText',
            beforeDraw(chart) {
                const { ctx, chartArea } = chart;
                if (!chartArea) return;
                const centerX = (chartArea.left + chartArea.right) / 2;
                const centerY = (chartArea.top + chartArea.bottom) / 2;
                
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                
                // จำนวน (ตัวใหญ่)
                const isMobile = window.innerWidth < 768;
                ctx.font = `bold ${isMobile ? '18px' : '26px'} Inter`; ctx.fillStyle = '#1e293b'; ctx.fillText(formatNumber(totalHeadcount), centerX, centerY - (isMobile ? 5 : 8));
                ctx.font = `500 ${isMobile ? '10px' : '12px'} Inter`; ctx.fillStyle = '#64748b'; ctx.fillText('Headcount', centerX, centerY + (isMobile ? 10 : 15)); ctx.restore();
            }
        }]
    });
}

// 3. Headcount By Function (Rounded Bar + Gradient)
function renderFunctionChart(dataSeries) {
    const labels = dataSeries.map(d => d.name);
    const values = dataSeries.map(d => d.y);
    const maxValue = Math.max(...values, 0);
    const yMax = Math.min(100, Math.max(10, Math.ceil((maxValue * 1.15) / 10) * 10));

    getOrCreateChart('functionChart', {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return PALETTE.red.solid;
                    return createVerticalGradient(ctx, chartArea, PALETTE.red);
                },
                borderRadius: 5,
                maxBarThickness: 35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: { top: 24 }
            },
            plugins: {
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    clamp: true,
                    clip: false,
                    color: PALETTE.red.solid,
                    font: { weight: 'bold' },
                    formatter: val => val + '%'
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#64748b' } },
                y: {
                    max: yMax,
                    beginAtZero: true,
                    grid: { borderDash: [5, 5], color: '#e2e8f0' },
                    ticks: { stepSize: 20, callback: v => v + '%', color: '#64748b' }
                }
            }
        }
    });
}

// 4. Turnover Rate (Professional Gradient Bar)
function renderTurnoverChart(dataSeries) {
    const labels = dataSeries.map(d => d[0]);
    const values = dataSeries.map(d => d[1]);

    getOrCreateChart('turnoverChart', {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: function(context) {
                    if (context.raw === null) return 'transparent';
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return PALETTE.blue.solid;
                    return createVerticalGradient(ctx, chartArea, PALETTE.blue);
                },
                borderRadius: 5,
                maxBarThickness: 45
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    color: PALETTE.blue.solid,
                    font: { weight: 'bold' },
                    display: function(context) {
                        return context.dataset.data[context.dataIndex] !== null;
                    },
                    formatter: function(val) {
                        return val !== null ? val + '%' : '';
                    }
                }
            },
            layout: {
                padding: { top: 25 }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#64748b' } },
                y: { 
                    suggestedMax: 10,
                    beginAtZero: true,
                    grid: { borderDash: [5, 5], color: '#e2e8f0' }, 
                    ticks: { stepSize: 2, callback: v => v + '%', color: '#64748b' } 
                }
            },
            onClick: (event, elements, chart) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const monthLabel = chart.data.labels[index];
                    showTurnoverDetails(monthLabel);
                }
            },
            onHover: (event, elements) => {
                event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
            }
        }
    });
}

/**
 * แสดง Modal รายละเอียดคนลาออกประจำเดือน
 */
function showTurnoverDetails(monthName) {
    const filters = getFilters();
    const trendYear = $('#trendYear').val() || new Date().getFullYear();
    const modalEl = document.getElementById('turnoverModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    
    document.getElementById('modalMonthName').textContent = monthName + ' ' + trendYear;
    const body = document.getElementById('turnoverModalBody');
    const loading = document.getElementById('modalLoading');
    
    body.innerHTML = '';
    loading.classList.remove('d-none');
    
    modal.show();

    const params = new URLSearchParams({ month: monthName, year: trendYear });
    if (filters.plant) params.set('plant', filters.plant);
    if (filters.emp_type) params.set('emp_type', filters.emp_type);
    if (filters.emp_category) params.set('emp_category', filters.emp_category);
    if (filters.function) params.set('function', filters.function);
    if (filters.dept) params.set('dept', filters.dept);
    
    fetch(`api/get_turnover_details.php?${params.toString()}`)
        .then(response => response.json())
        .then(res => {
            loading.classList.add('d-none');
            if (res.error) {
                body.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">
                                     <strong>Error:</strong> ${res.error}<br>
                                     <small class="text-muted">${res.debug_sql || ''}</small>
                                  </td></tr>`;
                return;
            }

            const turnoverData = res.data || [];
            if (turnoverData.length === 0) {
                body.innerHTML = '<tr><td colspan="9" class="text-center py-4">ไม่พบข้อมูลการลาออกในเดือนนี้</td></tr>';
                return;
            }
            
            turnoverData.forEach(emp => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="ps-3 small">${emp.PLANTNO}</td>
                    <td class="fw-bold">${emp.CODEMPID}</td>
                    <td>${emp.NAMEMPT}</td>
                    <td><span class="badge bg-light text-dark border">${emp.EMP_CATEGORY_FULL}</span></td>
                    <td class="small text-muted">${emp.POS_NAME}</td>
                    <td class="small">${emp.FUNC_NAME}</td>
                    <td class="small">${emp.DEPT}</td>
                    <td class="text-danger fw-bold">${emp.EXIT_DATE}</td>
                    <td class="small text-info">${emp.RESIGN_REASON || '-'}</td>
                `;
                body.appendChild(tr);
            });
        })
        .catch(err => {
            console.error(err);
            loading.classList.add('d-none');
            body.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
        });
}

// 5. Headcount By Plant (Pie Chart)
function renderPlantChart(dataSeries) {
    const labels = dataSeries.map(d => d.name);
    const values = dataSeries.map(d => d.y);
    
    // Modern High-Contrast Palette for Plants
    const plantColors = [
        '#0d6efd', '#2563eb', '#1d4ed8', '#1e40af', '#1e3a8a', 
        '#6366f1', '#4f46e5', '#4338ca', '#3730a3', '#312e81'
    ];

    getOrCreateChart('plantChart', {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: plantColors,
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { padding: 20, usePointStyle: true, font: { size: 11 } }
                },
                datalabels: {
                    color: '#fff',
                    font: { weight: 'bold', size: 10 },
                    formatter: (val) => val > 3 ? val.toFixed(1) + '%' : '' 
                }
            }
        }
    });
}

// 6. Headcount By Age (Green Bar Chart)
function renderAgeChart(dataSeries) {
    const labels = dataSeries.map(d => d.name);
    const values = dataSeries.map(d => d.y);

    getOrCreateChart('ageChart', {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return '#22c55e';
                    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                    gradient.addColorStop(0, '#22c55e');
                    gradient.addColorStop(1, '#4ade80');
                    return gradient;
                },
                borderRadius: 5,
                maxBarThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    color: '#15803d',
                    font: { weight: 'bold' },
                    formatter: val => val + '%'
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#64748b' } },
                y: { max: 30, grid: { borderDash: [5, 5], color: 'rgba(255,255,255,0.06)' }, ticks: { stepSize: 10, callback: v => v + '%', color: '#64748b' } }
            }
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
            XLSX.utils.book_append_sheet(wb, ws, 'Employee Data');
            XLSX.writeFile(wb, `Employee_Data_${dateStr}.xlsx`);

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

// ─── Employee Type Overview (Metric Tiles) ───

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

function renderTypeMetricTiles(dataSeries) {
    const container = document.getElementById('typeMetricTiles');
    if (!container || !dataSeries || dataSeries.length === 0) return;

    const circumference = 2 * Math.PI * 20;

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

    requestAnimationFrame(() => {
        setTimeout(() => {
            container.querySelectorAll('.type-ring-fill').forEach(ring => {
                ring.style.strokeDashoffset = ring.dataset.targetOffset;
            });
        }, 200);
    });

    container.querySelectorAll('.type-metric-tile').forEach(tile => {
        const bar = tile.querySelector('.tile-accent-bar');
        tile.addEventListener('mouseenter', () => { if (bar) bar.style.opacity = '1'; });
        tile.addEventListener('mouseleave', () => { if (bar) bar.style.opacity = '0'; });
    });
}

function renderTypeDistChart(dataSeries) {
    const totalEl = document.getElementById('typeOverviewTotalNum');
    if (totalEl && dataSeries && dataSeries.length > 0) {
        const total = dataSeries.reduce((sum, d) => sum + d.qty, 0);
        totalEl.textContent = formatNumber(total);
    }
    renderTypeMetricTiles(dataSeries);
}
