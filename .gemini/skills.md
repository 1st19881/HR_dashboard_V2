# Agent Skills — HR Dashboard V1

> คู่มือแนวทางการพัฒนาสำหรับ AI Agent ทำงานร่วมกับโปรเจค HR Dashboard
> อัปเดตล่าสุด: 8 มิถุนายน 2026

---

## 📌 Project Overview

โปรเจค **HR Dashboard** เป็นระบบแสดงผลข้อมูลพนักงานแบบ Real-time สำหรับฝ่ายบุคคล (HR)
เชื่อมต่อฐานข้อมูล Oracle HRMS ผ่าน PHP 8.0 + OCI8 และแสดงผลด้วย Chart.js 4.x

**Workspace**: `z:\Intranet\PHP80\hr_dashboard_v1`

---

## 🏗️ Architecture & File Structure

```
hr_dashboard_v1/
├── api/                        # Backend PHP APIs (JSON response)
│   ├── auth_login.php          # Login → SAGDB > intra.users
│   ├── auth_logout.php         # Session destroy
│   ├── get_dashboard_data.php  # Overview page data
│   ├── get_headcount_data.php  # HeadCount page data
│   ├── get_turnover_rate.php   # Turnover Rate page data
│   ├── get_employee_list.php   # DataTable employee list
│   ├── get_turnover_details.php # Drill-down modal data
│   ├── get_functions.php       # Chained filter: functions
│   ├── get_departments.php     # Chained filter: departments
│   ├── get_employee_types.php  # Chained filter: emp types
│   ├── get_employee_categories.php # Chained filter: emp categories
│   └── manage_auth.php         # User management CRUD
├── assets/
│   ├── css/style.css           # Design System (Premium Light Theme)
│   └── js/
│       ├── main.js             # Overview page logic
│       ├── headcount.js        # HeadCount page logic
│       ├── turnover_rate.js    # Turnover Rate page logic
│       └── manage_auth.js      # Auth management page logic
├── config/
│   └── database.php            # Oracle DB connections (HRMS + SAGDB)
├── includes/
│   ├── auth_check.php          # Session middleware
│   ├── functions.php           # Helper functions (fetchAllAssoc, etc.)
│   ├── header.php              # Shared layout: sidebar + topbar
│   └── footer.php              # CDN scripts + page-specific JS
├── index.php                   # Overview Dashboard page
├── HeadCount.php               # HeadCount Analytics page
├── TurnoverRate.php            # Turnover Rate page
├── login.php                   # Login page
├── manage_auth.php             # User management page
├── test_api.php                # API debug/test page
├── DESIGN_THEME.md             # Design System documentation
└── README.md                   # Project documentation (comprehensive)
```

---

## 🔧 Tech Stack

| Layer       | Technology                                                        |
| ----------- | ----------------------------------------------------------------- |
| Frontend    | HTML5, CSS3 (Vanilla), Bootstrap 5.3.2, Inter Font, FontAwesome 6 |
| Charts      | Chart.js 4.4.4 + chartjs-plugin-datalabels 2.2.0                 |
| DataTable   | DataTables 1.13.7 (jQuery 3.7.1)                                 |
| Alerts      | SweetAlert2 v11                                                   |
| Backend     | PHP 8.0 (Procedural, OCI8 extension)                              |
| Database    | Oracle HRMS (employee data) + Oracle SAGDB (auth)                 |
| Encoding    | AL32UTF8 (HRMS) / WE8DEC (SAGDB)                                 |

---

## ⚠️ Critical Conventions — MUST FOLLOW

### 1. PHP Backend Pattern

- **Procedural PHP** — ไม่ใช้ OOP/Framework, ไม่ใช้ MVC
- API files ทั้งหมดอยู่ใน `api/` folder
- ทุก API file เริ่มด้วย:
  ```php
  <?php
  error_reporting(0);
  header('Content-Type: application/json; charset=utf-8');
  require_once '../config/database.php';
  require_once '../includes/functions.php';
  ```
- ใช้ `getDbConnection()` สำหรับ HRMS, `getSagDbConnection()` สำหรับ auth
- ใช้ `fetchAllAssoc($conn, $sql, $binds)` สำหรับ query ทั้งหมด
- **Bind variables** ใช้ OCI8 format: `:paramName` (ไม่ใช้ `?`)
- ทุก API ส่ง `debug` key พร้อม SQL ที่ execute (สำหรับ debug)
- Response format: `json_encode([...], JSON_UNESCAPED_UNICODE)`
- Error handling: `try/catch` → `http_response_code(500)` + JSON error

### 2. Frontend Pattern

- **Vanilla JS** — ไม่ใช้ React/Vue/Angular
- jQuery ใช้เฉพาะ DataTables และ event handling (`$('#id').on('change', ...)`)
- Chart.js instances เก็บใน `chartInstances = {}` object
- ใช้ `getOrCreateChart(canvasId, config)` สร้าง/อัปเดต chart (destroy เก่าก่อนสร้างใหม่)
- AJAX ใช้ `fetch()` API (ไม่ใช้ `$.ajax`)
- Filter chain logic: Plant → EmpType → Function → Category → Department
- Chart colors ใช้ `PALETTE` object (ไม่ hardcode hex ตรงๆ ในแต่ละ chart)
- DataTables ใช้ Thai language UI

### 3. Database Query Pattern

- Oracle SQL syntax (ไม่ใช่ MySQL)
- ใช้ Oracle-specific functions: `NVL()`, `SUBSTR()`, `TO_DATE()`, `TO_CHAR()`, `TRUNC()`, `MONTHS_BETWEEN()`, `SYSDATE`
- Employee category logic: 3-tier classification system
  - Tier 1: `emp_category_by_prefix` (from `SUBSTR(codempid, 1, 2)`)
  - Tier 2: `emp_category_full` (SUB split by nationality)
  - Tier 3: WHERE filter (user-selected)
- Plant mapping: `SUBSTR(namcent5, 1, 2)` หรือ `SUBSTR(namcent5, 1, 3)` → Plant code
- Active employees: `staemp < 9`
- Resigned employees: `staemp = 9`, effective date: `dteeffex`
- **Inner-Outer SQL pattern**: Inner query calculates fields → Outer query filters and aggregates
- **Exclusion lists**: Filter out specific companies (`codcomp1 NOT IN (...)`) and names (`LIKE '%จุฬางกูร%'`)

### 4. CSS / Design System

- **Premium Light Theme** — ไม่ใช่ Dark theme
- CSS Variables อยู่ใน `:root` (ดู `DESIGN_THEME.md`)
- Key variables: `--accent: #2563eb`, `--radius: 14px`, `--transition: all 0.3s cubic-bezier(.4,0,.2,1)`
- Component classes: `.kpi-card`, `.chart-card`, `.filter-card`, `.data-table-container`
- Glassmorphism: `backdrop-filter: blur()` ใช้กับ topbar, cards
- Animation: `fadeInUp` keyframes + staggered delays
- Responsive: Desktop sidebar 260px (collapsible → 80px), Mobile overlay
- Font: Inter (Google Fonts) — ทุก element
- **ไม่ใช้ Tailwind CSS**

### 5. Page Template Pattern

ทุกหน้า (ยกเว้น login) ใช้ pattern เดียวกัน:
```php
<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'HR Dashboard - [Page Name]';
$currentPage = '[page_key]';     // overview, headcount, turnover, manage_auth
$pageScript = 'assets/js/[file].js';

include 'includes/header.php';
?>

<div class="dashboard-container">
    <!-- Page content here -->
</div>

<?php include 'includes/footer.php'; ?>
```

### 6. Adding New Pages

เมื่อเพิ่มหน้าใหม่:
1. สร้างไฟล์ PHP หลัก (e.g., `NewPage.php`) ตาม Page Template Pattern
2. สร้าง API ใน `api/` folder
3. สร้าง JS ใน `assets/js/`
4. เพิ่ม sidebar link ใน `includes/header.php` (ภายใน `.sidebar-nav`)
5. เพิ่ม page title/subtitle ใน `header.php` arrays (`$pageTitles`, `$pageSubs`)
6. อัปเดต `README.md` ทุกครั้งที่เพิ่ม/แก้ feature

### 7. Session Variables (Available after login)

```php
$_SESSION['user_id']         // User ID
$_SESSION['user_code']       // User code
$_SESSION['user_name']       // Display name (TIS-620 encoded)
$_SESSION['codcomp']         // Company code
$_SESSION['department_code'] // Department code
$_SESSION['position']        // Position
$_SESSION['cost_center']     // Cost center
$_SESSION['plant_no']        // Plant number
$_SESSION['aut_level']       // Auth level (99 = admin)
```

---

## 🎨 Chart Creation Guidelines

### Standard Chart Configuration

```javascript
// 1. Register datalabels globally (once, at top of JS file)
Chart.register(ChartDataLabels);

// 2. Set global defaults in DOMContentLoaded
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#64748b';

// 3. Use getOrCreateChart() to manage lifecycle
function renderMyChart(data) {
    getOrCreateChart('myChart', {
        type: 'bar', // or 'line', 'doughnut', 'pie'
        data: { /* ... */ },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: { /* ... */ }
            },
            scales: { /* ... */ }
        }
    });
}
```

### Chart Color Palette

```javascript
const PALETTE = {
    blue:   { stop1: '#2563eb', stop2: '#60a5fa', solid: '#2563eb' },
    orange: { stop1: '#f59e0b', stop2: '#fbbf24', solid: '#f59e0b' },
    purple: { stop1: '#8b5cf6', stop2: '#a78bfa', solid: '#8b5cf6' },
    red:    { stop1: '#ef4444', stop2: '#fca5a5', solid: '#ef4444' },
    teal:   { stop1: '#0d9488', stop2: '#5eead4', solid: '#0d9488' },
    green:  { stop1: '#10b981', stop2: '#34d399', solid: '#10b981' },
    gray:   '#94a3b8'
};
```

### Gradient Helper

```javascript
function createVerticalGradient(ctx, area, colorObj) {
    const gradient = ctx.createLinearGradient(0, area.bottom, 0, area.top);
    gradient.addColorStop(0, colorObj.stop1);
    gradient.addColorStop(1, colorObj.stop2);
    return gradient;
}
```

---

## 🏭 Business Logic Reference

### Plant Mapping (from `tcenter.namcent5`)

| Prefix      | Plant  |
| ----------- | ------ |
| `10`, `91`  | SAB    |
| `11`        | SAAB   |
| `20`        | SLAB   |
| `21`        | SRAB   |
| `22`        | SLAB-2 |
| `23`        | SLAB-3 |
| `30`        | SRDC   |
| `40`        | SATC   |
| `50`, `60`  | SDC    |
| `800`       | SAB    |
| `801`–`890` | SAM    |
| `90`        | SC     |

### Employee Category Prefix Map

| Category | Prefixes                                                      |
| -------- | ------------------------------------------------------------- |
| PERM     | A1, A2, B1, B2, DB, H1, H3, H4, JM, L1, L3, L4, R1, R4, RA, RD, SA, T1 |
| PWC      | A7, B7, L7, R7, RE, T7                                       |
| SUB      | 11, 12, 55, 56, FK, K2, K5, KA, MA, PB, PW, SM, SP, ST, SW, TH, TR, TS, Y5, Y6 |
| OTHER    | Everything else                                               |

### Trend Exclusion (applies ONLY to Trend Charts)

- Name: `NOT LIKE '%จุฬางกูร%'`
- Companies: `AAA, AAS, FUJ, KTK, HHH, MMM, NJN, SWC, TJS, XYZ, TUS, TFE, TSM, TEP, TSL, SOG, SWG, ACM, TAI, GRE, EXC, SGV, SON`

### Turnover Exclusion Logic (Reason Codes)

- Reason Codes (CODEXEMP) ใน `HRMS.TTEXEMPT`: `'11', '12', '15', '16', '17'` จะถูกยกเว้นไม่นำมารวมในการคำนวณการลาออก (Turnover)
- การคัดกรองใช้ SQL `NOT EXISTS` ร่วมกับ `MAX(rowid)` เพื่อตรวจสอบประวัติเหตุผลลาออกล่าสุดของพนักงานคนนั้นๆ:
  ```sql
  NOT EXISTS (
      SELECT 1 
      FROM HRMS.TTEXEMPT tex 
      WHERE tex.CODEMPID = t1.codempid 
        AND tex.CODEXEMP IN ('11','12','15','16','17') 
        AND tex.rowid = (
            SELECT MAX(rowid) 
            FROM HRMS.TTEXEMPT 
            WHERE CODEMPID = t1.codempid
        )
  )
  ```
- ขอบเขตที่ต้องใช้:
  - `api/get_dashboard_data.php` (Overview page - Turnover chart & summary)
  - `api/get_turnover_rate.php` (Turnover Rate page - Main chart & Category breakdown charts)
  - `api/get_turnover_details.php` (Drill-down table/modal lists)

### Month Snapshot Logic

```
Month = empty  → Real-time: AND staemp < 9
Month = 01-12  → Snapshot: เข้าก่อนสิ้นเดือน AND ยังไม่ออก ณ สิ้นเดือน
```

---

## 🔗 Key Database Tables

| Table               | Purpose                             |
| ------------------- | ----------------------------------- |
| `temploy1`          | Main employee data                  |
| `temploy2`          | Extended data (nationality, etc.)   |
| `tcenter`           | Cost center → Plant mapping         |
| `tpostn`            | Position names                      |
| `tcodnatn`          | Nationality codes                   |
| `tcodcatg`          | Employee category codes             |
| `HRMS_MAIN_DEPART`  | Function/Dept mapping (COST_CENTER → ORG_SHORT) |
| `HRMS.v_emp01`      | Employee view (DEPT, COSTCT)        |
| `HRMS.TTEXEMPT`     | Resignation reasons (join TCODEXEM) |
| `intra.users` (SAGDB) | Login credentials                 |

---

## 📐 Responsive Breakpoints

| Width     | Device       | Sidebar                    |
| --------- | ------------ | -------------------------- |
| ≥ 1400px  | XL Desktop   | Fixed 260px                |
| ≤ 1440px  | Notebook     | Fixed 240px                |
| ≤ 1199px  | Tablet       | Overlay + Backdrop blur    |
| ≤ 767px   | Mobile       | Overlay, KPI 2-column      |
| ≤ 575px   | Small Mobile | Small buttons, reduced height |

---

## 🐛 Debugging

- ทุก API มี `debug` key ใน JSON response ที่แสดง SQL ที่ execute
- หน้า Frontend มีปุ่ม "View SQL Query" (collapsible) แสดง SQL ทั้งหมด
- `test_api.php` ใช้ทดสอบ API แยก
- Console logging ใช้ `console.log('[Feature]', data)` format
- Session encoding: Username จาก SAGDB เป็น TIS-620 ต้อง `iconv('TIS-620', 'UTF-8', ...)` ตอนแสดงผล

---

## 📝 Documentation Rules

- เมื่อเพิ่ม/แก้ feature → **อัปเดต `README.md`** (ส่วน file structure, chart specs, สถานะ)
- เมื่อเปลี่ยน design → **อัปเดต `DESIGN_THEME.md`**
- Comment ใน PHP/JS ให้เขียนเป็น**ภาษาไทย**ตาม convention ที่มีอยู่
- หมายเหตุล่าสุดที่ README: `_บันทึกล่าสุด: DD เดือน YYYY_`

---

## 🚫 Anti-Patterns — DO NOT

1. ❌ ห้ามใช้ MySQL syntax (เช่น `LIMIT`, `IFNULL`, `NOW()`, backtick quoting)
2. ❌ ห้ามใช้ PDO — ใช้ OCI8 (`oci_connect`, `oci_parse`, `oci_execute`) เท่านั้น
3. ❌ ห้ามใช้ Tailwind CSS — ใช้ Vanilla CSS + Bootstrap 5
4. ❌ ห้ามใช้ React/Vue/Angular — ใช้ Vanilla JS + jQuery (เฉพาะ DataTables)
5. ❌ ห้ามใช้ Composer / npm — ทุกอย่างใช้ CDN
6. ❌ ห้ามเปลี่ยน design theme เป็น Dark mode (ยกเว้นถูกร้องขอ)
7. ❌ ห้ามเพิ่ม authentication middleware ที่แตกต่างจาก `includes/auth_check.php`
8. ❌ ห้ามใช้ `$.ajax` — ใช้ `fetch()` API แทน
9. ❌ ห้ามเปลี่ยน encoding ของ database connection (HRMS = AL32UTF8, SAGDB = WE8DEC)
10. ❌ ห้ามลบ debug SQL output ออกจาก API response
