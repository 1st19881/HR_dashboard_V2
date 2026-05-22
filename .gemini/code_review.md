# Agent Skills — Code Review Checklist

> แนวทางการรีวิว Code สำหรับโปรเจค HR Dashboard V1
> อัปเดตล่าสุด: 18 พฤษภาคม 2026

---

## 🔍 Review Process — ขั้นตอนการรีวิว

### ลำดับการตรวจสอบ
1. **Security** → ตรวจช่องโหว่ก่อนเสมอ
2. **Correctness** → Logic ถูกต้องตาม Business Rules
3. **Consistency** → ตรง Convention ของโปรเจค
4. **Performance** → ประสิทธิภาพ Query / Rendering
5. **Maintainability** → อ่านง่าย, แก้ไขง่าย
6. **UX/UI** → Responsive, Accessible

---

## 🛡️ 1. Security Review

### PHP Backend

| ✅ ต้องมี | ❌ ห้ามมี |
|---|---|
| Bind variables (`:paramName`) ทุก user input | String concatenation ใน SQL (`$_GET['x']` ลงใน query ตรงๆ) |
| `error_reporting(0)` ใน production APIs | `display_errors = 1` ใน production |
| Session check via `auth_check.php` ทุกหน้าที่ต้อง login | เข้าหน้าได้โดยไม่ต้อง login |
| `htmlspecialchars()` ก่อนแสดง user input ใน HTML | Echo `$_GET`/`$_POST` ลง HTML ตรงๆ |
| `strtoupper()` / trim ก่อนเทียบค่า | Case-sensitive comparison กับ user input |

### ⚠️ Known Pattern — SQL Injection Risk ที่ยอมรับในโปรเจคนี้

โปรเจคนี้มีบาง API ที่ใช้ **string interpolation** ใน SQL สำหรับค่าที่ไม่ได้มาจาก user input โดยตรง:

```php
// ✅ ยอมรับได้ — ค่าจาก server-side calculation
$eom = "TO_DATE('$trendYear-$mm-$lastDay','YYYY-MM-DD')";

// ❌ ไม่ยอมรับ — ค่าจาก user input ตรงๆ
$sql = "SELECT * FROM t WHERE name = '$_GET[name]'";
```

**กฎ**: ค่าจาก `$_GET` / `$_POST` ต้องผ่าน bind variables เสมอ ยกเว้นค่าที่ผ่าน type cast `(int)` แล้ว

### Login / Auth

- [ ] Password ไม่ถูก log ลง debug output
- [ ] Session timeout ตั้งค่าที่ 900 วินาที (15 นาที)
- [ ] `ob_clean()` ก่อนส่ง JSON response ใน auth_login
- [ ] ตรวจสอบ `AUT_ACTIVE = 'Y'` ก่อนอนุญาตเข้าระบบ
- [ ] DB credentials ไม่ถูก expose ใน API response

---

## 📐 2. Consistency Review — ตาม Convention โปรเจค

### PHP API Files

```
✅ Correct Pattern:
<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
require_once '../includes/functions.php';
$conn = getDbConnection();
if (!$conn) { echo json_encode(['error' => '...']); exit; }
try { ... } catch (Exception $e) { ... }
```

| Item | ตรวจสอบ |
|---|---|
| Header | `Content-Type: application/json; charset=utf-8` |
| Error reporting | `error_reporting(0)` (ไม่ใช่ `E_ALL`) |
| DB Connection | ใช้ `getDbConnection()` จาก `config/database.php` |
| Query | ใช้ `fetchAllAssoc($conn, $sql, $binds)` |
| Bind | OCI8 format `:paramName` (ไม่ใช่ `?`) |
| Response | `json_encode([...], JSON_UNESCAPED_UNICODE)` |
| Debug | มี `debug` key ใน response พร้อม SQL |
| Error | `try/catch` + `http_response_code(500)` |

### JavaScript Files

| Item | ตรวจสอบ |
|---|---|
| Chart registration | `Chart.register(ChartDataLabels)` ที่ต้นไฟล์ |
| Chart instances | เก็บใน `chartInstances = {}` |
| Chart creation | ใช้ `getOrCreateChart()` หรือ destroy ก่อนสร้างใหม่ |
| AJAX | ใช้ `fetch()` API (ไม่ใช่ `$.ajax`) |
| Filter chain | Plant → EmpType → Function → Category → Dept |
| Color | ใช้ `PALETTE` object |
| Chart defaults | ตั้งใน `DOMContentLoaded` |
| DataTables | Thai language UI |

### CSS

| Item | ตรวจสอบ |
|---|---|
| Variables | ใช้ CSS custom properties จาก `:root` |
| Border radius | ใช้ `var(--radius)` หรือ `var(--radius-sm)` |
| Shadow | ใช้ `var(--shadow-card)` หรือ `var(--shadow-premium)` |
| Transition | ใช้ `var(--transition)` |
| Font | Inter จาก Google Fonts |
| No Tailwind | ไม่มี class แบบ `bg-blue-500`, `flex`, `p-4` |

---

## 🧠 3. Business Logic Review

### Employee Category (3-Tier)

เมื่อรีวิว SQL ที่เกี่ยวกับ employee category:

```
Tier 1: emp_category_by_prefix → PERM / PWC / SUB / OTHER
  ↓
Tier 2: emp_category_full → SUB แยกตามสัญชาติ (Thai/Myanmar/Cambodia)
  ↓
Tier 3: WHERE filter → user-selected category
```

- [ ] CASE WHEN ครบทุก prefix ตาม mapping table
- [ ] SUB nationality split ใช้ทั้ง Thai (`ไทย`, `Thai`) และ Myanmar (`พม่า`, `Myanmar`)
- [ ] PERM, PWC ไม่ถูก split ตามสัญชาติ (คงเดิม)
- [ ] OTHER = ทุกอย่างที่ไม่ตรงกลุ่มใดเลย

### Plant Mapping

- [ ] Prefix 2 หลัก vs 3 หลัก ถูกต้อง (`800` = SAB, `801-890` = SAM)
- [ ] SLAB consolidation: SLAB + SLAB-2 + SLAB-3 → "SLAB" (เฉพาะ Pie Chart)
- [ ] SDC: prefix `50` + `60` รวมกัน

### Trend Calculation

- [ ] ปีปัจจุบัน → Rollback Method (ลบ hires + บวก resigns ย้อนกลับ)
- [ ] ปีย้อนหลัง → Snapshot Method (query 12 ครั้ง)
- [ ] สูตร Rollback: `monthN = monthN+1 - hires[N+1] + resigns[N+1]`
- [ ] เดือนหลังเดือนปัจจุบัน = `null` (ไม่ใช่ 0)

### Turnover Rate

- [ ] สูตร: `(จำนวนลาออก / Headcount สิ้นเดือน) * 100`
- [ ] ลาออก = `staemp = 9` + `TO_CHAR(dteeffex, 'YYYY') = year`
- [ ] Division by zero: ตรวจ `$hc > 0` ก่อนหาร

### Exclusion Rules

| ใช้ที่ไหน | กรองอะไร |
|---|---|
| **ทุก Chart** | `namempt NOT LIKE '%จุฬางกูร%'` |
| **Trend เท่านั้น** | `codcomp1 NOT IN ('AAA','AAS','FUJ',...23 บริษัท)` |
| **Turnover (Overview)** | กรองเฉพาะชื่อ (ไม่กรอง 23 บริษัท) |

### Month Snapshot

- [ ] Month = empty → `AND staemp < 9` (real-time)
- [ ] Month = 01-12 → Snapshot condition พร้อม `NVL(dtereemp, dteempmt)`
- [ ] Future months check: `$snapTimestamp < time()` ก่อน apply snapshot

---

## ⚡ 4. Performance Review

### Oracle SQL

| ⚠️ ตรวจสอบ | คำแนะนำ |
|---|---|
| Correlated subqueries ใน SELECT | ถ้า query ช้า ให้ย้ายเป็น JOIN |
| `ROWNUM = 1` | ต้องมีเมื่อ join กับ `HRMS_MAIN_DEPART` |
| Loop queries (12 ครั้ง) | Snapshot method ต้อง query 12 รอบ — ยอมรับได้แต่ต้องระวัง |
| `NOT IN` vs `NOT EXISTS` | ปัจจุบันใช้ `NOT IN` — ถ้าช้าให้เปลี่ยนเป็น `NOT EXISTS` |
| `TRIM()` ใน JOIN | อาจทำให้ index ไม่ทำงาน — flag ถ้าพบ performance issue |

### SQL Duplication (Known Technical Debt)

`get_turnover_rate.php` (740 บรรทัด) มี SQL blocks ซ้ำกันหลายที่ — เป็น known tech debt:
- Inner query (Plant mapping + Category prefix) ถูก copy-paste ~8 ครั้ง
- **ยอมรับ** ในสถานะปัจจุบัน แต่ flag เมื่อ refactor
- ถ้าต้องแก้ logic (เช่น เพิ่ม prefix ใหม่) → ต้องแก้ **ทุกที่** ที่ copy ไว้

### Frontend

| ⚠️ ตรวจสอบ | คำแนะนำ |
|---|---|
| Chart destroy ก่อนสร้างใหม่ | ป้องกัน memory leak |
| `setTimeout` หลัง sidebar toggle | 400ms สำหรับ chart resize |
| DataTables `ajax.reload()` | ไม่ควรเรียกซ้ำหลายครั้งในเวลาเดียวกัน |
| Event listeners ซ้ำ | ตรวจว่าไม่ bind event ซ้ำ |

---

## 🎨 5. UI/UX Review

### Responsive

- [ ] Sidebar collapse ที่ ≤1199px
- [ ] KPI cards 2 คอลัมน์ที่ ≤767px
- [ ] Chart height ลดที่ ≤575px
- [ ] Month buttons scrollable (ไม่ wrap)
- [ ] DataTables responsive mode

### Design Consistency

- [ ] ทุก card ใช้ `var(--radius)` = 14px
- [ ] Hover effects มี `translateY(-4px)` + premium shadow
- [ ] Animation delay staggered (0.05s increments)
- [ ] Font Inter ทุก element
- [ ] Color ตรงกับ `DESIGN_THEME.md`

### Accessibility

- [ ] `aria-label` บน buttons ที่ไม่มี text
- [ ] Form labels มีอยู่ทุก input
- [ ] Color contrast เพียงพอ (WCAG AA)
- [ ] Keyboard navigation ทำงาน (Tab order)

---

## 📝 6. Documentation Review

เมื่อรีวิว code ที่เปลี่ยนแปลง:

- [ ] Comment ใน PHP/JS เขียนเป็น**ภาษาไทย**
- [ ] ถ้าเพิ่ม feature → `README.md` อัปเดตหรือยัง?
- [ ] ถ้าเปลี่ยน design → `DESIGN_THEME.md` อัปเดตหรือยัง?
- [ ] ถ้าเพิ่มหน้าใหม่ → Sidebar link เพิ่มใน `header.php` หรือยัง?
- [ ] Debug SQL output ยังคงอยู่ใน API response

---

## 🐛 7. Common Bugs Checklist

### PHP

| Bug Pattern | วิธีตรวจ |
|---|---|
| OCI bind by reference | `oci_bind_by_name` bind by reference — ค่าใน `$binds` ต้องไม่เปลี่ยนหลัง bind |
| Oracle column name UPPERCASE | `$row['COLUMN_NAME']` ต้องเป็น uppercase เสมอ |
| `fetchAllAssoc` empty result | ตรวจ `!empty($rows)` ก่อนเข้าถึง `$rows[0]` |
| Division by zero | ตรวจ `$total > 0` ก่อน `$part / $total` |
| Date format mismatch | Oracle ใช้ `'YYYY-MM-DD'` format, PHP ใช้ `date('Y-m-d')` |
| Encoding mismatch | HRMS = AL32UTF8, SAGDB = WE8DEC — `iconv()` เมื่อแสดงชื่อ |
| `error_reporting(0)` vs `E_ALL` | API ใช้ `0`, auth_login ใช้ `E_ALL` + `display_errors=0` |

### JavaScript

| Bug Pattern | วิธีตรวจ |
|---|---|
| `chartInstances` not destroyed | ต้อง destroy ก่อน create ใหม่ |
| `null` vs `0` ใน chart data | Trend ใช้ `null` สำหรับเดือนอนาคต (ไม่ใช่ 0) |
| Filter text vs value | Plant filter ใช้ **text** (ไม่ใช่ value) — `plantSelect.options[...].text` |
| `getFilters()` consistency | ตรวจว่าทุกหน้าส่ง filter ครบ 5 ตัว |
| `$.val()` returns `null` | ตรวจ `|| ''` fallback |

### CSS

| Bug Pattern | วิธีตรวจ |
|---|---|
| `!important` overuse | ใช้เมื่อ override Bootstrap เท่านั้น |
| z-index conflict | Sidebar=1040, Overlay=1035, Topbar=1030 |
| `transition` on `all` | อาจทำให้ animation ช้า — ใช้ specific properties ถ้าได้ |

---

## 📋 8. Review Output Template

เมื่อรีวิวเสร็จ ให้สรุปผลในรูปแบบนี้:

```markdown
## Code Review Summary — [ชื่อไฟล์/Feature]

### 🔴 Critical (ต้องแก้)
- [รายการปัญหาร้ายแรง — Security, Data Loss]

### 🟡 Warning (ควรแก้)
- [รายการปัญหาที่อาจเกิด bug — Logic, Performance]

### 🔵 Info (แนะนำ)
- [รายการที่ปรับปรุงได้ — Code style, Readability]

### ✅ Good Practices Found
- [สิ่งที่ทำได้ดี — เพื่อ positive feedback]

### 📊 Metrics
| Metric | Value |
|---|---|
| Security Issues | 0 |
| Logic Issues | 0 |
| Style Issues | 0 |
| Total Lines Reviewed | 0 |
```

---

## 🔄 9. Review Triggers — เมื่อไหร่ต้องรีวิวอะไร

| เปลี่ยนแปลงอะไร | ต้องรีวิวอะไรเพิ่ม |
|---|---|
| เพิ่ม/แก้ API | Security (bind), Business logic, Debug output |
| เพิ่ม/แก้ Chart | Chart lifecycle (destroy), PALETTE colors, Responsive |
| เพิ่ม/แก้ Filter | Chain logic (5 ชั้น), ทุก JS file ที่ใช้ filter |
| เพิ่มหน้าใหม่ | Page template pattern, Sidebar link, README |
| แก้ SQL query | Bind variables, Column names (UPPER), Exclusion rules |
| แก้ CSS | Responsive breakpoints, Design theme consistency |
| แก้ Auth | Session variables, Permission levels, Timeout |
