// assets/js/turnover_rate.js

Chart.register(ChartDataLabels);

let chartInstances = {};
let detailsTable;
let msPlant, msEmpType, msFunction, msEmpCategory, msDepartment;

// Palette สีแนว Premium Light
const PALETTE = {
    blue:   { stop1: '#2563eb', stop2: '#60a5fa', solid: '#2563eb' },
    orange: { stop1: '#f59e0b', stop2: '#fbbf24', solid: '#f59e0b' },
    purple: { stop1: '#8b5cf6', stop2: '#a78bfa', solid: '#8b5cf6' },
    red:    { stop1: '#ef4444', stop2: '#fca5a5', solid: '#ef4444' },
    teal:   { stop1: '#0d9488', stop2: '#5eead4', solid: '#0d9488' },
    green:  { stop1: '#10b981', stop2: '#34d399', solid: '#10b981' },
    gray:   { stop1: '#94a3b8', stop2: '#cbd5e1', solid: '#94a3b8' }
};

// Category Color Mapping Reference:
// SUB:   Orange
// PERM:  Blue
// PWC:   Purple
// OTHER: Gray


document.addEventListener('DOMContentLoaded', function () {
    // Chart.js defaults
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#64748b';
    
    // Initialize MultiSelect instances
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

    // Initial loads
    loadEmployeeTypeOptions();
    loadFunctionOptions();
    loadEmployeeCategoryOptions();
    loadDepartmentOptions();
    fetchTurnoverData();

    $('#filterYear').on('change', function() {
        refreshData();
    });

    function refreshData() {
        fetchTurnoverData();
    }

    // Month filter buttons
    const monthBtns = document.querySelectorAll('.month-btn');
    monthBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            monthBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            refreshData();
        });
    });

    // Initialize Turnover Details DataTable
    detailsTable = $('#turnoverDetailsTable').DataTable({
        "ajax": {
            "url": "api/get_turnover_details.php",
            "data": function(d) {
                const f = getFilters();
                d.plant = f.plant;
                d.emp_type = f.emp_type;
                d.emp_category = f.emp_category;
                d.function = f.function;
                d.dept = f.dept;
                d.year = f.year;
                
                if (f.month) {
                    const monthNames = ["", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                    d.month = monthNames[parseInt(f.month)];
                } else {
                    d.month = 'All';
                }
            }
        },
        "columns": [
            { "data": "PLANTNO", "className": "ps-3 small text-muted" },
            { "data": "CODEMPID", "className": "fw-bold" },
            { "data": "NAMEMPT" },
            { 
                "data": "EMP_CATEGORY_FULL",
                "render": function(data) {
                    return `<span class="badge bg-light text-dark border fw-normal">${data}</span>`;
                }
            },
            { "data": "POS_NAME", "className": "small text-muted" },
            { "data": "FUNC_NAME", "className": "small" },
            { "data": "DEPT", "className": "small" },
            { 
                "data": "EXIT_DATE",
                "className": "text-danger fw-medium"
            },
            { 
                "data": "RESIGN_REASON",
                "className": "small text-info",
                "render": function(data) { return data || '-'; }
            }
        ],
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "lengthChange": true,
        "language": {
            "search": "ค้นหา:",
            "lengthMenu": "แสดง _MENU_ รายการ",
            "info": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
            "paginate": { "next": "ถัดไป", "previous": "ก่อนหน้า" }
        },
        "pageLength": 10,
        "order": [[7, "desc"]], // Default sort by Exit Date
        "responsive": true,
        "dom": '<"d-flex justify-content-between align-items-center mb-2"f>t<"d-flex justify-content-between align-items-center mt-2"ip>'
    });

    // Update count and SQL debug on load
    detailsTable.on('xhr.dt', function(e, settings, json, xhr) {
        if (json && json.data) {
            document.getElementById('detailsCount').textContent = json.data.length + ' records';
            
            // Append SQL to debug view
            const sqlEl = document.getElementById('sqlDisplay');
            if (sqlEl && json.debug_sql) {
                const currentHtml = sqlEl.innerHTML;
                const newHtml = `<div class="mb-3 text-secondary p-3 bg-light border rounded" style="word-break: break-all;">
                                    <strong class="text-primary">Turnover Details SQL:</strong><br>
                                    ${json.debug_sql.replace(/\n/g, '<br>')}
                                 </div>`;
                sqlEl.innerHTML = currentHtml + '<hr>' + newHtml;
            }
        }
    });
});

function getFilters() {
    const monthFilter = document.querySelector('.month-btn.active');
    return {
        plant:        msPlant       ? msPlant.getValuesString()       : '',
        emp_type:     msEmpType     ? msEmpType.getValuesString()     : '',
        emp_category: msEmpCategory ? msEmpCategory.getValuesString() : '',
        function:     msFunction    ? msFunction.getValuesString()    : '',
        dept:         msDepartment  ? msDepartment.getValuesString()  : '',
        year:         $('#filterYear').val()         || new Date().getFullYear(),
        month:        monthFilter ? monthFilter.getAttribute('data-month') : ''
    };
}

function fetchTurnoverData() {
    const filters = getFilters();
    const params = new URLSearchParams(filters);

    fetch(`api/get_turnover_rate.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                const sqlEl = document.getElementById('sqlDisplay');
                if (sqlEl) sqlEl.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }
            renderTurnoverChart(data.turnoverData, data.categoryTurnover || {});
            renderReasonChart(data.reasonData);
            
            // Render Category Charts
            if (data.categoryTurnover) {
                renderCategoryChart('subChart', 'SUB', data.categoryTurnover.SUB, PALETTE.orange);
                renderCategoryChart('permChart', 'PERM', data.categoryTurnover.PERM, PALETTE.blue);
                renderCategoryChart('pwcChart', 'PWC', data.categoryTurnover.PWC, PALETTE.purple);
                renderCategoryChart('otherChart', 'OTHER', data.categoryTurnover.OTHER, PALETTE.gray);
            }

            fetchTurnoverDetails(); // Fetch detailed employee list

            // Populate Debug SQL
            const sqlEl = document.getElementById('sqlDisplay');
            if (data.debug && sqlEl) {
                let debugHtml = '';
                Object.keys(data.debug).forEach(key => {
                    const sqlStr = typeof data.debug[key] === 'string' ? data.debug[key] : JSON.stringify(data.debug[key]);
                    debugHtml += `<div class="mb-3 text-secondary p-3 bg-light border rounded" style="word-break: break-all;">
                                    <strong class="text-primary">${key}:</strong><br>
                                    ${sqlStr.replace(/\n/g, '<br>')}
                                  </div>`;
                });
                sqlEl.innerHTML = debugHtml;
            }
        })
        .catch(err => console.error('Fetch Error:', err));
}

function renderTurnoverChart(turnoverData, categoryTurnover) {
    const labels = turnoverData.map(d => d.month);

    const catKeys = ['SUB', 'PERM', 'PWC', 'OTHER'];
    const catColors = {
        SUB:   PALETTE.orange.solid,
        PERM:  PALETTE.blue.solid,
        PWC:   PALETTE.purple.solid,
        OTHER: PALETTE.gray.solid
    };

    // Build 4 bar datasets with count + rate
    const datasets = catKeys.map(key => {
        const catData = (categoryTurnover && categoryTurnover[key]) ? categoryTurnover[key] : [];
        const counts = labels.map(lbl => {
            const found = catData.find(d => d.month === lbl);
            return found ? (found.resignations || 0) : 0;
        });
        const rates = labels.map(lbl => {
            const found = catData.find(d => d.month === lbl);
            return found ? (found.rate || 0) : 0;
        });

        return {
            label: key,
            data: counts,
            _rates: rates,   // custom: store rates for datalabel/tooltip
            backgroundColor: catColors[key] + 'CC',
            borderColor: catColors[key],
            borderWidth: 1,
            borderRadius: 6,
            borderSkipped: 'bottom',
            maxBarThickness: 32,
            datalabels: {
                anchor: 'end',
                align: 'top',
                offset: 2,
                color: catColors[key],
                font: { weight: '700', size: 11, family: "'Inter', sans-serif" },
                formatter: (val) => val > 0 ? val : '',
                display: (ctx) => ctx.dataset.data[ctx.dataIndex] > 0
            }
        };
    });

    if (chartInstances['turnoverChart']) chartInstances['turnoverChart'].destroy();

    const ctx = document.getElementById('turnoverChart').getContext('2d');
    chartInstances['turnoverChart'] = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 30 } },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        pointStyleWidth: 14,
                        padding: 18,
                        font: { size: 12, weight: '600', family: "'Inter', sans-serif" },
                        color: '#334155'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#f8fafc',
                    bodyColor: '#e2e8f0',
                    titleFont: { size: 13, weight: '700' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 8,
                    boxPadding: 5,
                    callbacks: {
                        label: function(context) {
                            const rate = context.dataset._rates[context.dataIndex];
                            return ` ${context.dataset.label}: ${context.parsed.y} คน (${rate}%)`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 12, weight: '600' }, color: '#475569' }
                },
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [4, 4], color: '#e2e8f0' },
                    ticks: { precision: 0, font: { size: 12, weight: '600' }, color: '#64748b' },
                    title: { display: true, text: 'จำนวนลาออก (คน)', color: '#64748b', font: { size: 12, weight: '700' } }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

function renderReasonChart(reasonData) {
    if (!reasonData) return;
    
    // Sort data (API already does it, but just in case)
    reasonData.sort((a, b) => b.y - a.y);

    const labels = reasonData.map(d => d.name);
    const values = reasonData.map(d => d.y);

    if (chartInstances['reasonChart']) chartInstances['reasonChart'].destroy();
    
    const ctx = document.getElementById('reasonChart').getContext('2d');
    chartInstances['reasonChart'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Qty',
                data: values,
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return PALETTE.blue.solid;
                    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                    gradient.addColorStop(0, PALETTE.blue.stop1);
                    gradient.addColorStop(1, PALETTE.blue.stop2);
                    return gradient;
                },
                borderRadius: 6,
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
                    color: PALETTE.blue.solid,
                    font: { weight: 'bold', size: 12 },
                    formatter: (val) => val > 0 ? val : ''
                }
            },
            layout: {
                padding: { top: 25, bottom: 10 }
            },
            scales: {
                x: { 
                    grid: { display: false },
                    ticks: { 
                        autoSkip: false,
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 12 },
                        color: '#64748b'
                    }
                },
                y: { 
                    beginAtZero: true,
                    grid: { borderDash: [5, 5], color: '#e2e8f0' },
                    ticks: { precision: 0, color: '#64748b', font: { size: 12 } }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

function renderCategoryChart(canvasId, label, data, colorObj) {
    if (!data) return;
    const labels = data.map(d => d.month);
    const rates = data.map(d => d.rate);
    const counts = data.map(d => d.resignations || 0);
    const solidColor = colorObj.solid || colorObj;

    if (chartInstances[canvasId]) chartInstances[canvasId].destroy();
    
    const ctx = document.getElementById(canvasId).getContext('2d');
    chartInstances[canvasId] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    type: 'line',
                    label: `Rate (%)`,
                    data: rates,
                    borderColor: solidColor,
                    backgroundColor: 'transparent',
                    borderWidth: 3,
                    pointBackgroundColor: solidColor,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2.5,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointHoverBorderWidth: 3,
                    pointStyle: 'circle',
                    tension: 0.3,
                    fill: false,
                    yAxisID: 'yRate',
                    order: 0,
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        offset: 6,
                        color: '#fff',
                        backgroundColor: solidColor,
                        borderRadius: 4,
                        padding: { top: 2, bottom: 2, left: 5, right: 5 },
                        font: { weight: 'bold', size: 11, family: "'Inter', sans-serif" },
                        formatter: (val) => val > 0 ? val + '%' : '',
                        display: (ctx) => ctx.dataset.data[ctx.dataIndex] > 0
                    }
                },
                {
                    type: 'bar',
                    label: `จำนวน (คน)`,
                    data: counts,
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) return solidColor;
                        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                        gradient.addColorStop(0, (colorObj.stop1 || solidColor) + '55');
                        gradient.addColorStop(1, (colorObj.stop2 || solidColor) + '88');
                        return gradient;
                    },
                    borderRadius: 4,
                    maxBarThickness: 26,
                    yAxisID: 'yCount',
                    order: 1,
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        offset: 2,
                        color: '#475569',
                        font: { weight: '700', size: 10, family: "'Inter', sans-serif" },
                        formatter: (val) => val > 0 ? val : '',
                        display: (ctx) => ctx.dataset.data[ctx.dataIndex] > 0
                    }
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 30 } },
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        pointStyleWidth: 12,
                        padding: 14,
                        font: { size: 11, weight: '600', family: "'Inter', sans-serif" },
                        color: '#334155'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.92)',
                    titleColor: '#f8fafc',
                    bodyColor: '#e2e8f0',
                    titleFont: { size: 12, weight: '700' },
                    bodyFont: { size: 11 },
                    padding: 12,
                    cornerRadius: 8,
                    boxPadding: 5
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11, weight: '500' }, color: '#475569' } },
                yRate: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    suggestedMax: Math.max(...rates, 5) + 2,
                    grid: { borderDash: [4, 4], color: solidColor + '15' },
                    ticks: { callback: (v) => v + '%', font: { size: 11, weight: '600' }, color: solidColor },
                    title: { display: true, text: 'Rate (%)', color: solidColor, font: { size: 11, weight: '700' } }
                },
                yCount: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: { precision: 0, font: { size: 11, weight: '600' }, color: '#475569' },
                    title: { display: true, text: 'จำนวน (คน)', color: '#475569', font: { size: 11, weight: '700' } }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}


// --- OPTION LOAD FUNCTIONS ---
function loadEmployeeTypeOptions(plant = '') {
    const params = new URLSearchParams();
    if (plant) params.set('plant', plant);
    fetch(`api/get_employee_types.php?${params.toString()}`)
        .then(r => r.json()).then(data => {
            if (msEmpType) msEmpType.setOptions(data, 'TYPE_NAME');
        });
}

function loadFunctionOptions(plant = '', empType = '') {
    const params = new URLSearchParams();
    if (plant)   params.set('plant', plant);
    if (empType) params.set('emp_type', empType);
    fetch(`api/get_functions.php?${params.toString()}`)
        .then(r => r.json()).then(data => {
            if (msFunction) msFunction.setOptions(data, 'FUNC_NAME');
        });
}

function loadEmployeeCategoryOptions(plant = '', empType = '', func = '') {
    const params = new URLSearchParams();
    if (plant)   params.set('plant', plant);
    if (empType) params.set('emp_type', empType);
    if (func)    params.set('function', func);
    fetch(`api/get_employee_categories.php?${params.toString()}`)
        .then(r => r.json()).then(data => {
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
        .then(r => r.json()).then(data => {
            if (msDepartment) msDepartment.setOptions(data, 'DEPT_NAME');
        });
}

function fetchTurnoverDetails() {
    if (detailsTable) detailsTable.ajax.reload();
}
