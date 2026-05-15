# HR Dashboard

โปรเจค Dashboard สำหรับฝ่ายบุคคล (HR) เชื่อมต่อฐานข้อมูล Oracle HRMS และแสดงผลข้อมูลพนักงานแบบ Real-time ด้วย Chart.js  
ประกอบด้วย 3 หน้าหลัก: **Overview** (ภาพรวมองค์กร), **HeadCount Analytics** (วิเคราะห์กำลังคนเชิงลึก), และ **Turnover Rate** (วิเคราะห์อัตราการลาออก)

## 🚀 เทคโนโลยีที่ใช้

| ประเภท | รายละเอียด |
|---|---|
| **Frontend** | HTML5, CSS3, Bootstrap 5, Inter Font (Google Fonts), FontAwesome 6 |
| **Charts** | Chart.js 4.4.4 + chartjs-plugin-datalabels |
| **Data Table** | DataTables (jQuery) |
| **Backend** | PHP 8.0, OCI8 (Oracle) |
| **Database** | Oracle HRMS (ข้อมูลพนักงาน), Oracle SAGDB (สิทธิ์ Login) |

---

## 📁 โครงสร้างโปรเจค

```text
hr_dashboard/
├── api/
│   ├── auth_login.php              # ตรวจสอบสิทธิ์ Login (เชื่อมต่อ SAGDB > intra.users)
│   ├── auth_logout.php             # ล้าง Session และออกจากระบบ
│   ├── get_dashboard_data.php      # API สำหรับหน้า Overview — KPI, Trend, Type, Function, Turnover (JSON)
│   ├── get_headcount_data.php      # API สำหรับหน้า HeadCount — KPI, Trend, Plant, Type, Function, Age (JSON)
│   ├── get_turnover_rate.php       # API สำหรับหน้า Turnover Rate — Rate%, Count, Category, Reason (JSON)
│   ├── get_employee_list.php       # API สำหรับ DataTable รายชื่อพนักงาน (JSON)
│   ├── get_turnover_details.php    # API ดึงรายละเอียดพนักงานลาออกรายเดือน (Drill-down Modal/Table)
│   ├── get_functions.php           # API ดึง Distinct ORG_SHORT จาก HRMS_MAIN_DEPART (Chained)
│   ├── get_departments.php         # API ดึง Distinct DEPT จาก HRMS.v_emp01 (Chained)
│   ├── get_employee_types.php      # API ดึง Employee Type (ADMIN/DIRECT/INDIRECT/MANAGER)
│   ├── get_employee_categories.php # API ดึง Employee Category (Chained)
│   └── manage_auth.php             # API จัดการสิทธิ์ผู้ใช้ (CRUD)
├── assets/
│   ├── css/
│   │   └── style.css               # ดีไซน์ Dashboard (Premium Light Theme, Sidebar, Responsive)
│   └── js/
│       ├── main.js                 # Logic หน้า Overview — Chart.js, AJAX, DataTables, Filter
│       ├── headcount.js            # Logic หน้า HeadCount — Trend, Pie, Donut, Bar, Age Chart
│       ├── turnover_rate.js        # Logic หน้า Turnover Rate — Combo Line+Bar, Category, Reason Chart
│       └── manage_auth.js          # Logic หน้าจัดการสิทธิ์ — DataTable, CRUD, SweetAlert2
├── config/
│   └── database.php                # ตั้งค่าการเชื่อมต่อ Oracle (HRMS & SAGDB)
├── includes/
│   ├── auth_check.php              # Middleware ตรวจสอบ Session ก่อนเข้าหน้าหลัก
│   ├── functions.php               # ฟังก์ชันช่วยเหลือ (fetchAllAssoc ฯลฯ)
│   ├── header.php                  # Sidebar + Top Bar (Responsive: Desktop Collapse / Mobile Overlay)
│   └── footer.php                  # ส่วนท้ายเว็บและ Scripts (CDN)
├── index.php                       # หน้า Overview Dashboard
├── HeadCount.php                   # หน้า HeadCount Analytics
├── TurnoverRate.php                # หน้า Turnover Rate Analytics
├── login.php                       # หน้า Login
├── manage_auth.php                 # หน้าจัดการสิทธิ์ผู้ใช้
├── test_api.php                    # หน้าทดสอบ API (Debug SQL)
├── DESIGN_THEME.md                 # เอกสาร Design Theme & Color Palette
└── README.md                       # เอกสารโปรเจค (ไฟล์นี้)
```

---

## 🔐 ระบบ Authentication

- **หน้า Login**: `login.php` → ส่งข้อมูลไปที่ `api/auth_login.php`
- **ฐานข้อมูล**: `SAGDB` → ตาราง `intra.users`
- **เงื่อนไข**: ตรวจสอบ `UPPER(USERS_USERNAME)` และ `USERS_STATUS = '1'`
- **Session Timeout**: 900 วินาที (15 นาที)
- **Session Variables ที่บันทึก**: `user_id`, `user_code`, `user_name`, `codcomp`, `department_code`, `position`, `cost_center`, `plant_no`
- **Middleware**: `includes/auth_check.php` ตรวจสอบ Session ก่อนทุกหน้าที่ต้องการสิทธิ์

---

## 📊 ฐานข้อมูล (Database)

### HRMS DB — `getDbConnection()`
ใช้ดึงข้อมูลพนักงานทั้งหมด

**ตารางหลักที่ใช้:**
- `temploy1` — ข้อมูลพนักงาน (codempid, codpos, codcomp, staemp, codsex, typemp, dteempdb, dtereemp, dteempmt, dteeffex ฯลฯ)
- `temploy2` — ข้อมูลเพิ่มเติม (สัญชาติ codnatnl, เลขพาสปอร์ต numpasid ฯลฯ)
- `tcenter` — ศูนย์ต้นทุน (namcent5 → Plant mapping, namcent4 → Dept mapping)
- `tpostn` — ชื่อตำแหน่ง
- `tcodnatn` — รหัสสัญชาติ
- `tcodcatg` — ประเภทพนักงาน (ADMIN/DIRECT/INDIRECT)
- `HRMS_MAIN_DEPART` — ตาราง Function/Department mapping (COST_CENTER → ORG_SHORT)
- `HRMS.v_emp01` — View ข้อมูลพนักงาน (มีฟิลด์ DEPT, COSTCT, CODCOMP)
- `v_emp_education` — View การศึกษา (rn = max_rn)
- `hrms.GET_AGE()` — Function คำนวณอายุ/อายุงาน

### SAGDB DB — `getSagDbConnection()`
ใช้เฉพาะตอน Login ตรวจสอบสิทธิ์ผู้ใช้

---

## 🏭 Plant Mapping (จาก tcenter.namcent5)

| Prefix (2 หลักแรก) | Plant |
|---|---|
| `10` | SAB |
| `11` | SAAB |
| `20` | SLAB |
| `21` | SRAB |
| `22` | SLAB-2 |
| `23` | SLAB-3 |
| `30` | SRDC |
| `40` | SATC |
| `50`, `60` | SDC |
| `800` (3 หลัก) | SAB |
| `801`–`890` (3 หลัก) | SAM |
| `90` | SC |
| `91` | SAB |

### Pie Chart 7 กลุ่มหลัก (Consolidation)
| กลุ่ม | รวมจาก | สี |
|---|---|---|
| SLAB | SLAB, SLAB-2, SLAB-3 | 🔵 Blue |
| SAB | SAB | 🟣 Purple |
| SAAB | SAAB | 🟠 Orange |
| SRAB | SRAB | 🔴 Red |
| SRDC | SRDC | 🟢 Green |
| SATC | SATC | 🩵 Cyan |
| SDC | SDC | 🟡 Yellow |

> **หมายเหตุ**: กลุ่ม SAM, SC จะถูกแยกออกจาก Pie Chart เพื่อแสดงเฉพาะ 7 กลุ่มหลัก

---

## 👥 Employee Category Logic (3 ชั้น)

### ชั้นที่ 1 — `emp_category_by_prefix` (จาก 2 หลักแรกของ codempid)

| Prefix | Category |
|---|---|
| `A1`, `A2`, `B1`, `B2`, `DB`, `H1`, `H3`, `H4`, `JM`, `L1`, `L3`, `L4`, `R1`, `R4`, `RA`, `RD`, `SA`, `T1` | PERM |
| `A7`, `B7`, `L7`, `R7`, `RE`, `T7` | PWC |
| `11`, `12`, `55`, `56`, `FK`, `K2`, `K5`, `KA`, `MA`, `PB`, `PW`, `SM`, `SP`, `ST`, `SW`, `TH`, `TR`, `TS`, `Y5`, `Y6` | SUB |
| อื่นๆ | OTHER |

### ชั้นที่ 2 — `emp_category_full` (แยก SUB ตามสัญชาติ)

| เงื่อนไข | Category |
|---|---|
| SUB + สัญชาติ = 'ไทย' หรือ 'Thai' | SUB Thai |
| SUB + สัญชาติ LIKE '%พม่า%' หรือ '%Myanmar%' | SUB Myanmar |
| SUB + สัญชาติ LIKE '%กัมพูชา%' หรือ '%Cambodia%' | SUB Cambodia |
| อื่นๆ (รวม PERM, PWC, OTHER) | คงเดิมตามชั้น 1 |

### ชั้นที่ 3 — WHERE Filter (กรองตาม filter ที่ผู้ใช้เลือก)

- กรอง `PlantGroup`, `type_name`, `emp_category_full`, `func_name`, `dept`

---

## 🔎 ระบบ Filter แบบ 5 ชั้น (Cascading / Chained Dropdown)

Filter ทั้งหมดเป็นแบบ **Chained (ลูกโซ่)** — เมื่อเลือกตัวบน ตัวเลือกด้านล่างจะกรองตามทันที  
ส่งผลต่อทั้ง **KPI Cards**, **Charts (Trend, Pie, Bar, Age)**, และ **DataTable** พร้อมกัน

| ลำดับ | Filter | Element ID | API ที่ใช้โหลดตัวเลือก | ค่าที่ส่ง API |
|---|---|---|---|---|
| 1 | **Plant** | `filterPlant` | (Hardcoded) | SAB, SAAB, SLAB ฯลฯ |
| 2 | **Employee Type** | `filterEmpType` | `get_employee_types.php` | ADMIN / DIRECT / INDIRECT / MANAGER |
| 3 | **Function** | `filterFunction` | `get_functions.php` | ORG_SHORT (UPPER) จาก HRMS_MAIN_DEPART |
| 4 | **Employee Category** | `filterEmpCategory` | `get_employee_categories.php` | PERM / PWC / SUB Thai / SUB Myanmar / Other |
| 5 | **Department** | `filterDepartment` | `get_departments.php` | DEPT (UPPER) จาก HRMS.v_emp01 |

### กลไกการโหลด (Chain Logic)

```
เลือก Plant       → โหลดใหม่: EmpType, Function, EmpCategory, Department
เลือก EmpType     → โหลดใหม่: Function, EmpCategory, Department
เลือก Function    → โหลดใหม่: EmpCategory, Department
เลือก EmpCategory → โหลดใหม่: Department
เลือก Department  → Refresh ข้อมูล KPI + Chart + Table เท่านั้น
```

> **หมายเหตุ Dept**: ฟิลด์ `DEPT` มาจาก `HRMS.v_emp01` และกรองด้วย `UPPER(dept) = UPPER(:dept)`  
> **หมายเหตุ Function**: ฟิลด์ `func_name` มาจาก `UPPER(TRIM(ORG_SHORT))` โดย Join ผ่าน COST_CENTER

---

## 🗓️ Month Filter (Snapshot Mode)

ทั้งสองหน้า (Overview / HeadCount) มีปุ่มเลือกเดือน (Jan–Dec) เพื่อดูข้อมูลย้อนหลัง

### กลไกการทำงาน

```
Month = ว่าง (All)  →  Real-time Mode: AND staemp < 9
Month = 01–12       →  Snapshot Mode ณ สิ้นเดือนนั้น
```

**Snapshot SQL Condition:**
```sql
AND NVL(dtereemp, dteempmt) <= TO_DATE('YYYY-MM-DD', 'YYYY-MM-DD')  -- สิ้นเดือน
AND (staemp < 9 OR (staemp = 9 AND dteeffex > สิ้นเดือน))
```

**หลักการ**: นับพนักงานที่ "เข้างานก่อนสิ้นเดือน" และ "ยังไม่ออก ณ สิ้นเดือน"

**ขอบเขตผลกระทบ:**
| Component | ได้รับผลจาก Month Filter |
|---|---|
| KPI Cards | ✅ Snapshot |
| Headcount By Type (Donut) | ✅ Snapshot |
| Headcount By Function (Bar) | ✅ Snapshot |
| Headcount By Plant (Pie) | ✅ Snapshot |
| Headcount By Age (Bar) | ✅ Snapshot |
| Headcount Trend (Line) | ❌ ไม่เปลี่ยน (ใช้ Year Dropdown แยก) |
| Turnover Rate (Bar) | ❌ ไม่เปลี่ยน (ใช้ Year Dropdown แยก) |

---

## 📈 แนวคิดการคำนวณแต่ละ Chart

### หน้า Overview (`index.php` → `api/get_dashboard_data.php`)

#### 1. KPI Cards
- **แนวคิด**: นับจำนวนพนักงาน Active ตาม `emp_category_full` แบ่ง 6 กลุ่ม (PERM, PWC, SUB Thai, SUB Myanmar, SUB Cambodia, OTHER)
- **สูตร Male/Female %**: `ROUND(male_count / headcount_all * 100)` และ `ROUND(female_count / headcount_all * 100)`
- **เงื่อนไข Active**: `staemp < 9` (Real-time) หรือ Snapshot ตาม Month Filter

#### 2. Headcount Trend — `trendChart` (Area Gradient Line)
- **แนวคิด**: แสดงจำนวน Headcount รายเดือน 12 เดือน ของปีที่เลือก
- **ปีปัจจุบัน → Rollback Method**:
  1. นับ Headcount ปัจจุบัน (Active + ผ่าน Filter + Trend Exclusion)
  2. ดึงรายการ เข้า/ออก ของปีนั้น
  3. Rollback ย้อนกลับจากเดือนปัจจุบัน → เดือน 1 (ลบคนเข้า, บวกคนออก)
  4. เดือนหลังเดือนปัจจุบัน = ใช้ยอดปัจจุบัน
- **ปีย้อนหลัง → Snapshot Method**: Query 12 ครั้ง นับ Active ณ สิ้นเดือนแต่ละเดือน
- **สูตร Rollback**: `monthN = monthN+1 - hires[N+1] + resigns[N+1]`
- **Trend Exclusion**: กรองชื่อ "จุฬางกูร" + 25 บริษัท (AAA, AAS, FUJ, JMP, JMX ฯลฯ) เฉพาะ Trend เท่านั้น
- **Chart Style**: Line + Area Gradient fill (blue `#2563eb`), tension 0.4, datalabels แสดงตัวเลข

#### 3. Headcount By Employee Type — `typeChart` (Premium Donut)
- **แนวคิด**: แสดงสัดส่วน % ของแต่ละ Employee Category ต่อ Headcount ทั้งหมด
- **สูตร**: `ROUND(category_count / headcount_all * 100, 1)` → แสดงเป็น `%`
- **ข้อมูล**: จาก KPI Query เดียวกัน (ไม่ต้อง Query ใหม่)
- **Chart Style**: Doughnut, cutout 75%, borderRadius 8, spacing 5
- **Custom Plugins**:
  - `calloutLines` — เส้นชี้ L-shape จาก slice ไปที่ datalabel
  - `centerText` — แสดง Total Headcount ตรงกลาง Donut

#### 4. Headcount By Function — `functionChart` (Red Gradient Bar)
- **แนวคิด**: แสดงสัดส่วน % ของแต่ละ Function (ORG_SHORT) ต่อ Headcount ทั้งหมด
- **สูตร**: `ROUND((func_count / headcount_all) * 100, 1)` → แสดงเป็น `%`
- **SQL**: `GROUP BY func_name ORDER BY qty DESC`
- **Chart Style**: Vertical Bar, Red gradient (`#ef4444` → `#fca5a5`), borderRadius 5

#### 5. Turnover Rate — `turnoverChart` (Blue Gradient Bar)
- **แนวคิด**: แสดง Turnover % รายเดือน = อัตราการลาออกต่อเดือน
- **สูตร**: `(จำนวนลาออกเดือนนั้น / Headcount สิ้นเดือนนั้น) * 100`
- **ข้อมูลลาออก**: นับ `staemp = 9` ที่ `TO_CHAR(dteeffex, 'YYYY') = ปีที่เลือก` GROUP BY เดือน
- **ข้อมูล Headcount**: ใช้ `trendData[i]` (ยอดจาก Trend Chart)
- **Exclusion**: กรองเฉพาะชื่อ "จุฬางกูร" (ไม่กรอง 25 บริษัท)
- **Drill-down**: คลิกแท่งกราฟ → เปิด Modal แสดงรายชื่อคนลาออกเดือนนั้น (`get_turnover_details.php`)
- **Chart Style**: Vertical Bar, Blue gradient (`#2563eb` → `#60a5fa`), borderRadius 5

> **หมายเหตุ**: กราฟ Turnover ในหน้า Overview ยังเป็น Bar Chart เดิม  
> ส่วนหน้า TurnoverRate.php ใช้ Combo Chart (Line + Bar) ที่ละเอียดกว่า — ดูหัวข้อด้านล่าง

---

### หน้า HeadCount Analytics (`HeadCount.php` → `api/get_headcount_data.php`)

#### 1. KPI Cards
- **เหมือน Overview** แต่ API แยก (`get_headcount_data.php`) ใช้ `$baseSql` ที่ refactor แล้ว

#### 2. Employee Growth Trend — `trendChart` (Area Gradient Line)
- **เหมือน Overview** ทั้ง Rollback + Snapshot Method
- **Year Dropdown**: `#filterTrendYear` กรองเฉพาะ Trend (KPI/Pie/Bar ไม่เปลี่ยน)

#### 3. Headcount By Plant — `plantChart` (Pie + Callout Lines)
- **แนวคิด**: แสดงจำนวนพนักงาน (ตัวเลขจริง) แยกตาม Plant Group
- **การรวมกลุ่ม**: SLAB + SLAB-2 + SLAB-3 → "SLAB" / SAM, OTHER แยกออก
- **สูตร**: `COUNT(*)` ต่อกลุ่ม (แสดงเป็น `y = จำนวนคน`)
- **Datalabel**: แสดง `%` (คำนวณ client-side: `value / sum * 100`) ซ่อนถ้า < 3%
- **Chart Style**: Pie chart + Custom Plugin `pieCalloutLines` (เส้นชี้ L-shape)

#### 4. Headcount By Employee Type — `typeChart` (Premium Donut)
- **เหมือน Overview** — แสดง % ต่อ Headcount ทั้งหมด + Callout Lines + Center Text

#### 5. Headcount By Function — `functionChart` (Red Gradient Bar)
- **เหมือน Overview** — แสดง % ต่อ Headcount ทั้งหมด

#### 6. Headcount By Age — `ageChart` (Green Gradient Bar)
- **แนวคิด**: แสดงสัดส่วน % ของแต่ละช่วงอายุ ต่อ Headcount ทั้งหมด
- **การคำนวณอายุ**: `TRUNC(MONTHS_BETWEEN(SYSDATE, dteempdb) / 12)`
- **ช่วงอายุ**: 18-20, 21-25, 26-30, 31-35, 36-40, 41-45, 46-50, 51-55, 55 ปีขึ้นไป
- **สูตร**: `ROUND((age_group_count / headcount_all) * 100, 1)` → แสดงเป็น `%`
- **เรียงลำดับ**: `ORDER BY MIN(age)` (อายุน้อย → มาก)
- **Chart Style**: Vertical Bar, Green gradient (`#10b981` → `#34d399`), borderRadius 5

---

### หน้า Turnover Rate (`TurnoverRate.php` → `api/get_turnover_rate.php`)

#### 1. Turnover Rate & Resignations — `turnoverChart` (Combo Line + Bar)
- **แนวคิด**: แสดงทั้ง **อัตราลาออก (%)** และ **จำนวนคนลาออก** ในกราฟเดียวกัน
- **Line Chart** (สีแดง `#dc2626`, แกน Y ซ้าย): แสดง Turnover Rate %
  - เส้นหนา 3.5px, จุดขนาด 6px พื้นสีเข้มขอบขาว
  - Data Label: ตัวขาวบนพื้นแดง (badge style, borderRadius 4)
- **Bar Chart** (สีน้ำเงินโปร่งใส, แกน Y ขวา): แสดงจำนวนคนลาออก
  - Gradient `rgba(37, 99, 235, 0.35)` → `rgba(96, 165, 250, 0.55)`
  - Data Label: ตัวเลขสีน้ำเงินเข้ม `#1e40af`
- **สูตร Rate**: `(จำนวนลาออกเดือนนั้น / Headcount สิ้นเดือนนั้น) * 100`
- **Interaction**: `mode: 'index'` — hover เดือนไหนแสดงข้อมูลทั้ง 2 dataset
- **Tooltip**: Dark theme (`rgba(15, 23, 42, 0.92)`)

#### 2. Category Breakdown — `subChart`, `permChart`, `pwcChart`, `otherChart` (Combo Line + Bar × 4)
- **แนวคิด**: แยก Turnover Rate ตาม Employee Category (SUB, PERM, PWC, OTHER)
- **รูปแบบเดียวกับกราฟหลัก**: Line = Rate%, Bar = จำนวนคน
- **สี**: ตามสีหมวดหมู่ — SUB (Orange), PERM (Blue), PWC (Purple), OTHER (Gray)
- **การคำนวณ**: คำนวณ Headcount + Resignation แยกรายหมวดหมู่ ใช้ `emp_category_by_prefix` filter
- **ปีปัจจุบัน**: Rollback Method (เหมือน Trend) แยกตาม Category
- **ปีย้อนหลัง**: Snapshot Method 12 เดือน แยกตาม Category

#### 3. Turnover By Reason — `reasonChart` (Horizontal Bar)
- **แนวคิด**: แสดงจำนวนคนลาออกแยกตามเหตุผล (Resignation Reason)
- **ข้อมูล**: จาก `HRMS.TTEXEMPT` JOIN `HRMS.TCODEXEM` → `DESCODT`
- **เรียงลำดับ**: จำนวนมาก → น้อย
- **Chart Style**: Vertical Bar, Blue gradient, borderRadius 6, maxRotation 45°

#### 4. Turnover Details Table — `turnoverDetailsTable` (DataTable)
- **แนวคิด**: แสดงรายชื่อพนักงานที่ลาออกพร้อมรายละเอียด
- **Columns**: Plant, Emp ID, Full Name, Category, Position, Function, Department, Exit Date, Reason
- **Filter**: รับ filter เดียวกับกราฟ + Month filter
- **API**: `get_turnover_details.php` (DataTable format)

#### Color Legend (ตาราง reference สี)
| หมวดหมู่ | สี | Hex |
|---|---|---|
| SUB | 🟠 Orange | `#f59e0b` |
| PERM | 🔵 Blue | `#2563eb` |
| PWC | 🟣 Purple | `#8b5cf6` |
| OTHER | ⚪ Gray | `#94a3b8` |

---

## 📋 KPI Cards (Real-time / Snapshot)

| ID | Label | ข้อมูล |
|---|---|---|
| `kpi-perm` | PERM | จาก Oracle HRMS |
| `kpi-pwc` | PWC | จาก Oracle HRMS |
| `kpi-sub-thai` | SUB Thai | จาก Oracle HRMS |
| `kpi-sub-mm` | SUB Myanmar | จาก Oracle HRMS |
| `kpi-sub-cam` | SUB Cambodia | จาก Oracle HRMS |
| `kpi-other` | OTHER | จาก Oracle HRMS (prefix ที่ไม่ตรงกลุ่มใดเลย) |
| `kpi-male-pct` | Male % | คำนวณจาก codsex ≠ 'F' |
| `kpi-female-pct` | Female % | คำนวณจาก codsex = 'F' |

---

## 📑 Employee DataTable (Real-time)

ดึงข้อมูลจาก `api/get_employee_list.php` → ฐานข้อมูล HRMS จริง

**Columns:** Plant | Employee ID | Full Name | Employee Category | Category(By Nationality) | Function | Department | Section | Cost Center | Band | Grade | Position Group | Years of Service

**Features:** Pagination, Search, Sort, Responsive, Thai language UI

**Filter ที่ส่งไปพร้อม DataTable request:** `plant`, `emp_type`, `emp_category`, `function`, `dept`

---

## 🎨 Design & Responsive

### UI Layout — Sidebar + Top Bar
- **Sidebar** (260px, collapsible → 80px): Navigation links, User profile
- **Top Bar** (sticky): Page title, Desktop toggle, Action buttons
- **Mobile (≤1199px)**: Sidebar เป็น Overlay slide-in + Backdrop blur

### Responsive Breakpoints
| Breakpoint | อุปกรณ์ | การปรับ |
|---|---|---|
| `≥1400px` | XL Desktop | Padding ใหญ่ขึ้น, KPI ตัวหนังสือใหญ่ |
| `≤1440px` | Notebook | Sidebar 240px, KPI/Filter ลดขนาด |
| `≤1199px` | Tablet | Sidebar overlay, margin-left 0 |
| `≤767px` | Mobile | KPI 2 คอลัมน์, Chart padding ลด |
| `≤575px` | Small Mobile | Month button เล็กลง, Chart height ลด |

---

## 🔧 สถานะการพัฒนา

### หน้า Overview (`index.php`)
| ฟีเจอร์ | สถานะ |
|---|---|
| KPI Cards (6 หมวด + Male/Female %) | ✅ เชื่อมต่อจริง |
| Headcount Trend Chart (Line + Area Gradient) | ✅ Rollback + Snapshot |
| Donut Chart (Employee Type) | ✅ เชื่อมต่อจริง |
| Bar Chart (Function) | ✅ เชื่อมต่อจริง |
| Bar Chart (Turnover Rate) | ✅ เชื่อมต่อจริง + Drill-down Modal |
| DataTable รายชื่อพนักงาน | ✅ เชื่อมต่อจริง |
| Month Filter (Snapshot) | ✅ ส่งผลต่อ KPI, Type, Function (ไม่รวม Trend/Turnover) |

### หน้า HeadCount Analytics (`HeadCount.php`)
| ฟีเจอร์ | สถานะ |
|---|---|
| KPI Cards | ✅ เชื่อมต่อจริง |
| Employee Growth Trend (Line + Area) | ✅ Rollback + Snapshot |
| Pie Chart (Plant Groups — 7 กลุ่ม) | ✅ เชื่อมต่อจริง |
| Donut Chart (Employee Type) | ✅ เชื่อมต่อจริง |
| Bar Chart (Function) | ✅ เชื่อมต่อจริง |
| Bar Chart (Age Group) | ✅ เชื่อมต่อจริง |
| Month Filter (Snapshot) | ✅ ส่งผลต่อ KPI, Pie, Donut, Bar (ไม่รวม Trend) |

### หน้า Turnover Rate (`TurnoverRate.php`)
| ฟีเจอร์ | สถานะ |
|---|---|
| Combo Chart หลัก (Line: Rate% + Bar: จำนวนคน) | ✅ เชื่อมต่อจริง |
| Category Breakdown ×4 (SUB, PERM, PWC, OTHER) | ✅ Combo Line+Bar แยกหมวด |
| Turnover By Reason (Bar Chart) | ✅ เชื่อมต่อจริง |
| Turnover Details DataTable | ✅ รายชื่อลาออก + Filter + Pagination |
| Color Legend Reference | ✅ SUB/PERM/PWC/OTHER |

### ระบบกลาง
| ฟีเจอร์ | สถานะ |
|---|---|
| ระบบ Login / Session (15 นาที timeout) | ✅ เชื่อมต่อ SAGDB |
| Filter 5 ชั้น (Cascading/Chained) | ✅ Plant → EmpType → Function → Category → Dept |
| Trend Year Filter Dropdown | ✅ ปีปัจจุบัน + 2 ปีย้อนหลัง |
| Trend Exclusion Logic | ✅ กรองชื่อ + 25 บริษัท (เฉพาะ Trend) |
| Responsive (Tablet + Mobile) | ✅ Sidebar overlay, Collapse toggle, Chart responsive |
| จัดการสิทธิ์ผู้ใช้ | ✅ หน้า manage_auth.php + manage_auth.js |
| Premium Light Theme | ✅ Sidebar + Top Bar + Glassmorphism + Animations |
| Debug SQL View | ✅ ทุกหน้ามีปุ่ม View SQL Query (collapsible) |

---

*บันทึกล่าสุด: 15 พฤษภาคม 2026*
