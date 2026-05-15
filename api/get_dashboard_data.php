<?php
// api/get_dashboard_data.php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';
require_once '../includes/functions.php';

$conn = getDbConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// รับค่า parameter
$month        = $_GET['month']        ?? '';
$plant        = $_GET['plant']        ?? '';
$emp_type     = $_GET['emp_type']     ?? '';
$emp_category = $_GET['emp_category'] ?? '';
$function     = $_GET['function']     ?? '';
$dept         = $_GET['dept']         ?? '';

$employeeExclSql = "AND t1.namempt NOT LIKE '%จุฬางกูร%'";

// ======================================================
// Month Snapshot: ถ้าเลือกเดือน → ใช้ Snapshot ณ สิ้นเดือนนั้น
// ======================================================
$trend_year = $_GET['year'] ?? date('Y');
$activeFilter = "AND t1.staemp < 9"; // default: พนักงาน active ปัจจุบัน

if (!empty($month) && is_numeric($month)) {
    $snapMM = str_pad((int)$month, 2, '0', STR_PAD_LEFT);
    $snapYY = (int)$trend_year;
    $snapLastDay = date('t', mktime(0, 0, 0, (int)$month, 1, $snapYY));
    $snapEOM = "TO_DATE('{$snapYY}-{$snapMM}-{$snapLastDay}', 'YYYY-MM-DD')";
    // ใช้ Snapshot เฉพาะเมื่อวันที่ไม่ใช่อนาคต
    $snapTimestamp = mktime(23, 59, 59, (int)$month, $snapLastDay, $snapYY);
    if ($snapTimestamp < time()) {
        $activeFilter = "AND NVL(t1.dtereemp, t1.dteempmt) <= {$snapEOM}
                         AND (t1.staemp < 9 OR (t1.staemp = 9 AND t1.dteeffex > {$snapEOM}))";
    }
}

// ======================================================
// 1. Query นับจำนวน KPI หลัก
// ======================================================
$kpiSql = "
SELECT
    SUM(CASE WHEN emp_category_full = 'PERM'         THEN 1 ELSE 0 END) AS perm,
    SUM(CASE WHEN emp_category_full = 'PWC'          THEN 1 ELSE 0 END) AS pwc,
    SUM(CASE WHEN emp_category_full = 'SUB Thai'     THEN 1 ELSE 0 END) AS sub_thai,
    SUM(CASE WHEN emp_category_full = 'SUB Myanmar'  THEN 1 ELSE 0 END) AS sub_myanmar,
    SUM(CASE WHEN emp_category_full = 'SUB Cambodia' THEN 1 ELSE 0 END) AS sub_cambodia,
    SUM(CASE WHEN emp_category_full = 'OTHER' THEN 1 ELSE 0 END) AS other,
    COUNT(*) AS headcount_all,
    SUM(CASE WHEN codsex != 'F' THEN 1 ELSE 0 END) AS male_count,
    SUM(CASE WHEN codsex  = 'F' THEN 1 ELSE 0 END) AS female_count
FROM (
    SELECT t1.*,
           CASE
               WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
               WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
               WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
               ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE 'OTHER' END
           END AS emp_category_full
    FROM (
        SELECT
            CASE
                WHEN SUBSTR(t2.namcent5, 1, 2) = '10' THEN 'SAB'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '11' THEN 'SAAB'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '20' THEN 'SLAB'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '21' THEN 'SRAB'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '30' THEN 'SRDC'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '40' THEN 'SATC'
                WHEN SUBSTR(t2.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                WHEN SUBSTR(t2.namcent5, 1, 3) = '800' THEN 'SAB'
                WHEN SUBSTR(t2.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '90' THEN 'SC'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '91' THEN 'SAB'
                ELSE t1.codcomp1
            END AS PlantNO,
            CASE SUBSTR(t1.codempid, 1, 2)
                WHEN '11' THEN 'SUB'  WHEN '12' THEN 'SUB'
                WHEN '55' THEN 'SUB'  WHEN '56' THEN 'SUB'
                WHEN 'A1' THEN 'PERM' WHEN 'A2' THEN 'PERM' WHEN 'A7' THEN 'PWC'
                WHEN 'B1' THEN 'PERM' WHEN 'B2' THEN 'PERM' WHEN 'B7' THEN 'PWC'
                WHEN 'DB' THEN 'PERM' WHEN 'FK' THEN 'SUB'
                WHEN 'H1' THEN 'PERM' WHEN 'H3' THEN 'PERM' WHEN 'H4' THEN 'PERM'
                WHEN 'JM' THEN 'PERM'
                WHEN 'K2' THEN 'SUB'  WHEN 'K5' THEN 'SUB'  WHEN 'KA' THEN 'SUB'
                WHEN 'L1' THEN 'PERM' WHEN 'L3' THEN 'PERM' WHEN 'L4' THEN 'PERM' WHEN 'L7' THEN 'PWC'
                WHEN 'MA' THEN 'SUB'  WHEN 'PB' THEN 'SUB'  WHEN 'PW' THEN 'SUB'
                WHEN 'R1' THEN 'PERM' WHEN 'R4' THEN 'PERM' WHEN 'R7' THEN 'PWC'
                WHEN 'RA' THEN 'PERM' WHEN 'RD' THEN 'PERM' WHEN 'RE' THEN 'PWC'
                WHEN 'SA' THEN 'PERM'
                WHEN 'SM' THEN 'SUB'  WHEN 'SP' THEN 'SUB'  WHEN 'ST' THEN 'SUB'  WHEN 'SW' THEN 'SUB'
                WHEN 'T1' THEN 'PERM' WHEN 'T7' THEN 'PWC'
                WHEN 'TH' THEN 'SUB'  WHEN 'TR' THEN 'SUB'  WHEN 'TS' THEN 'SUB'
                WHEN 'Y5' THEN 'SUB'  WHEN 'Y6' THEN 'SUB'
                ELSE 'OTHER'
            END AS emp_category_by_prefix,
            (SELECT descodt FROM temploy2 e2, tcodnatn cn
             WHERE e2.codnatnl = cn.codcodec AND e2.codempid = t1.codempid) AS codnatt,
            t1.codsex,
            CASE 
                WHEN SUBSTR(t1.codpos, 2, 1) >= '4' THEN 'MANAGER'
                ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = t1.typemp))
            END AS type_name,
            (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(t2.namcent5) AND ROWNUM = 1) AS func_name,
            SUBSTR(t2.namcent4, INSTR(t2.namcent4, '_') + 1) AS dept
        FROM temploy1 t1, tcenter t2
        WHERE t1.codcomp = t2.codcomp
          AND t1.codcomp1 NOT IN (
              'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
              'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
              'EXC','SGV','SON'
          )
          $activeFilter
          $employeeExclSql
    ) t1
) t1
WHERE 1=1";

$where = "";
$binds = [];
if (!empty($plant))        { $where .= " AND PlantNO = :plant";              $binds[':plant'] = $plant; }
if (!empty($emp_type))     { $where .= " AND type_name = :emp_type";          $binds[':emp_type'] = $emp_type; }
if (!empty($emp_category)) { $where .= " AND emp_category_full = :emp_category";  $binds[':emp_category'] = $emp_category; }
if (!empty($function))     { $where .= " AND func_name = :function";          $binds[':function'] = $function; }
if (!empty($dept))         { $where .= " AND UPPER(dept) = UPPER(:dept)";       $binds[':dept'] = $dept; }

$kpiSql .= $where;

try {
    // 1. Execute KPI query
    $kpiRows = fetchAllAssoc($conn, $kpiSql, $binds);
    $row = $kpiRows[0];
    $headcount = (int)$row['HEADCOUNT_ALL'];

    $kpi = [
        'perm' => (int)$row['PERM'],
        'pwc' => (int)$row['PWC'],
        'sub_thai' => (int)$row['SUB_THAI'],
        'sub_myanmar' => (int)$row['SUB_MYANMAR'],
        'sub_cambodia' => (int)$row['SUB_CAMBODIA'],
        'other' => (int)$row['OTHER'],
        'headcount_all' => $headcount,
        'male_pct' => $headcount > 0 ? round((int)$row['MALE_COUNT'] / $headcount * 100) : 0,
        'female_pct' => $headcount > 0 ? round((int)$row['FEMALE_COUNT'] / $headcount * 100) : 0
    ];

    // 2. คำนวณ typeData สำหรับ Donut Chart
    $typeData = [];
    if ($headcount > 0) {
        $categories = [
            'PERM' => 'PERM', 'PWC' => 'PWC', 'SUB Thai' => 'SUB_THAI', 
            'SUB Myanmar' => 'SUB_MYANMAR', 'SUB Cambodia' => 'SUB_CAMBODIA', 'OTHER' => 'OTHER'
        ];
        foreach ($categories as $label => $key) {
            $val = (int)$row[$key];
            if ($val > 0) {
                $typeData[] = ['name' => $label, 'y' => round($val / $headcount * 100, 1)];
            }
        }
    }

    // 3. Query สำหรับ Headcount By Function (ของจริง)
    $sqlFunc = "
    SELECT func_name, COUNT(*) as qty
    FROM (
        SELECT
            PlantNO, type_name, func_name, dept,
            CASE
                WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
                WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
                WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
                ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE 'OTHER' END
            END AS emp_category_full
        FROM (
            SELECT
                CASE
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '10' THEN 'SAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '11' THEN 'SAAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '20' THEN 'SLAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '21' THEN 'SRAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '30' THEN 'SRDC'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '40' THEN 'SATC'
                    WHEN SUBSTR(t2.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                    WHEN SUBSTR(t2.namcent5, 1, 3) = '800' THEN 'SAB'
                    WHEN SUBSTR(t2.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '90' THEN 'SC'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '91' THEN 'SAB'
                    ELSE t1.codcomp1
                END AS PlantNO,
                CASE SUBSTR(t1.codempid, 1, 2)
                    WHEN '11' THEN 'SUB'  WHEN '12' THEN 'SUB'
                    WHEN '55' THEN 'SUB'  WHEN '56' THEN 'SUB'
                    WHEN 'A1' THEN 'PERM' WHEN 'A2' THEN 'PERM' WHEN 'A7' THEN 'PWC'
                    WHEN 'B1' THEN 'PERM' WHEN 'B2' THEN 'PERM' WHEN 'B7' THEN 'PWC'
                    WHEN 'DB' THEN 'PERM' WHEN 'FK' THEN 'SUB'
                    WHEN 'H1' THEN 'PERM' WHEN 'H3' THEN 'PERM' WHEN 'H4' THEN 'PERM'
                    WHEN 'JM' THEN 'PERM'
                    WHEN 'K2' THEN 'SUB'  WHEN 'K5' THEN 'SUB'  WHEN 'KA' THEN 'SUB'
                    WHEN 'L1' THEN 'PERM' WHEN 'L3' THEN 'PERM' WHEN 'L4' THEN 'PERM' WHEN 'L7' THEN 'PWC'
                    WHEN 'MA' THEN 'SUB'  WHEN 'PB' THEN 'SUB'  WHEN 'PW' THEN 'SUB'
                    WHEN 'R1' THEN 'PERM' WHEN 'R4' THEN 'PERM' WHEN 'R7' THEN 'PWC'
                    WHEN 'RA' THEN 'PERM' WHEN 'RD' THEN 'PERM' WHEN 'RE' THEN 'PWC'
                    WHEN 'SA' THEN 'PERM'
                    WHEN 'SM' THEN 'SUB'  WHEN 'SP' THEN 'SUB'  WHEN 'ST' THEN 'SUB'  WHEN 'SW' THEN 'SUB'
                    WHEN 'T1' THEN 'PERM' WHEN 'T7' THEN 'PWC'
                    WHEN 'TH' THEN 'SUB'  WHEN 'TR' THEN 'SUB'  WHEN 'TS' THEN 'SUB'
                    WHEN 'Y5' THEN 'SUB'  WHEN 'Y6' THEN 'SUB'
                    ELSE 'OTHER'
                END AS emp_category_by_prefix,
                (SELECT descodt FROM temploy2 e2, tcodnatn cn
                 WHERE e2.codnatnl = cn.codcodec AND e2.codempid = t1.codempid) AS codnatt,
                CASE 
                    WHEN SUBSTR(t1.codpos, 2, 1) >= '4' THEN 'MANAGER'
                    ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = t1.typemp))
                END AS type_name,
                (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(t2.namcent5) AND ROWNUM = 1) AS func_name,
                SUBSTR(t2.namcent4, INSTR(t2.namcent4, '_') + 1) AS dept
            FROM temploy1 t1, tcenter t2
            WHERE t1.codcomp = t2.codcomp
              AND t1.codcomp1 NOT IN (
                  'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                  'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                  'EXC','SGV','SON'
              )
              $activeFilter
              $employeeExclSql
        ) t1
    ) t1
    WHERE func_name IS NOT NULL";

    // ติดตัวกรองเดิมสำหรับ Query Function
    if (!empty($plant))        { $sqlFunc .= " AND PlantNO = :plant"; }
    if (!empty($emp_type))     { $sqlFunc .= " AND type_name = :emp_type"; }
    if (!empty($emp_category)) { $sqlFunc .= " AND emp_category_full = :emp_category"; }
    if (!empty($function))     { $sqlFunc .= " AND func_name = :function"; }
    if (!empty($dept))         { $sqlFunc .= " AND UPPER(dept) = UPPER(:dept)"; }

    $sqlFunc .= " GROUP BY func_name ORDER BY qty DESC";
    $funcResults = fetchAllAssoc($conn, $sqlFunc, $binds);

    $functionData = [];
    $denom = $headcount > 0 ? $headcount : 1;
    foreach ($funcResults as $fRow) {
        $functionData[] = [
            'name' => $fRow['FUNC_NAME'],
            'y' => round(($fRow['QTY'] / $denom) * 100, 1)
        ];
    }

    // --- 4. TREND DATA (ของจริง) ---
    $trend_year = $_GET['year'] ?? date('Y');
    $trendYear = (int)$trend_year;
    $isCurrentYear = ($trendYear == (int)date('Y'));
    
    // เงื่อนไขยกเว้นสำหรับ Trend (ยังคงต้องกัน 25 บริษัทตามความต้องการ)
    $headcountTrendExcl = " AND namempt NOT LIKE '%จุฬางกูร%' 
                   AND codcomp1 NOT IN ('AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS','XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE','EXC','SGV','SON')";
    $trendTrendWhere = $where . $headcountTrendExcl;

    // เงื่อนไขยกเว้นสำหรับ Turnover (เอาออกตามความต้องการ)
    $trendExcl = " AND namempt NOT LIKE '%จุฬางกูร%'";
    $trendWhere = $where . $trendExcl;

    if ($isCurrentYear) {
        // วิธี Rollback
        $tBaseSql = "SELECT COUNT(*) as cnt FROM (
            SELECT t1.*,
                   CASE
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
                       ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE 'OTHER' END
                   END AS emp_category_full
            FROM (
            SELECT 
                CASE
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '10' THEN 'SAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '11' THEN 'SAAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '20' THEN 'SLAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '21' THEN 'SRAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '30' THEN 'SRDC'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '40' THEN 'SATC'
                    WHEN SUBSTR(t2.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                    WHEN SUBSTR(t2.namcent5, 1, 3) = '800' THEN 'SAB'
                    WHEN SUBSTR(t2.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '90' THEN 'SC'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '91' THEN 'SAB'
                    ELSE t1.codcomp1
                END AS PlantNO,
                CASE SUBSTR(t1.codempid, 1, 2)
                    WHEN '11' THEN 'SUB'  WHEN '12' THEN 'SUB'
                    WHEN '55' THEN 'SUB'  WHEN '56' THEN 'SUB'
                    WHEN 'A1' THEN 'PERM' WHEN 'A2' THEN 'PERM' WHEN 'A7' THEN 'PWC'
                    WHEN 'B1' THEN 'PERM' WHEN 'B2' THEN 'PERM' WHEN 'B7' THEN 'PWC'
                    WHEN 'DB' THEN 'PERM' WHEN 'FK' THEN 'SUB'
                    WHEN 'H1' THEN 'PERM' WHEN 'H3' THEN 'PERM' WHEN 'H4' THEN 'PERM'
                    WHEN 'JM' THEN 'PERM'
                    WHEN 'K2' THEN 'SUB'  WHEN 'K5' THEN 'SUB'  WHEN 'KA' THEN 'SUB'
                    WHEN 'L1' THEN 'PERM' WHEN 'L3' THEN 'PERM' WHEN 'L4' THEN 'PERM' WHEN 'L7' THEN 'PWC'
                    WHEN 'MA' THEN 'SUB'  WHEN 'PB' THEN 'SUB'  WHEN 'PW' THEN 'SUB'
                    WHEN 'R1' THEN 'PERM' WHEN 'R4' THEN 'PERM' WHEN 'R7' THEN 'PWC'
                    WHEN 'RA' THEN 'PERM' WHEN 'RD' THEN 'PERM' WHEN 'RE' THEN 'PWC'
                    WHEN 'SA' THEN 'PERM'
                    WHEN 'SM' THEN 'SUB'  WHEN 'SP' THEN 'SUB'  WHEN 'ST' THEN 'SUB'  WHEN 'SW' THEN 'SUB'
                    WHEN 'T1' THEN 'PERM' WHEN 'T7' THEN 'PWC'
                    WHEN 'TH' THEN 'SUB'  WHEN 'TR' THEN 'SUB'  WHEN 'TS' THEN 'SUB'
                    WHEN 'Y5' THEN 'SUB'  WHEN 'Y6' THEN 'SUB'
                    ELSE 'OTHER'
                END AS emp_category_by_prefix,
                (SELECT descodt FROM temploy2 e2, tcodnatn cn
                 WHERE e2.codnatnl = cn.codcodec AND e2.codempid = t1.codempid) AS codnatt,
                CASE 
                    WHEN SUBSTR(t1.codpos, 2, 1) >= '4' THEN 'MANAGER'
                    ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = t1.typemp))
                END AS type_name,
                (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(t2.namcent5) AND ROWNUM = 1) AS func_name,
                SUBSTR(t2.namcent4, INSTR(t2.namcent4, '_') + 1) AS dept,
                t1.codsex, t1.namempt, t1.codcomp1, t1.staemp, t1.dteempmt, t1.dtereemp, t1.dteeffex
            FROM temploy1 t1, tcenter t2
            WHERE t1.codcomp = t2.codcomp
              AND t1.codcomp1 NOT IN (
                  'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                  'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                  'EXC','SGV','SON'
              )
            ) t1
        ) t1 WHERE 1=1 $trendTrendWhere AND staemp < 9";
        
        $tBaseRows = fetchAllAssoc($conn, $tBaseSql, $binds);
        $trendCurrentTotal = (int)($tBaseRows[0]['CNT'] ?? 0);

        $movSql = "SELECT hire_mm, retired_mm, staemp FROM (
            SELECT t1.*,
                   CASE
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
                       ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE 'OTHER' END
                   END AS emp_category_full
            FROM (
            SELECT 
                TO_CHAR(NVL(dtereemp, dteempmt), 'MM') AS hire_mm,
                TO_CHAR(dteeffex, 'MM') AS retired_mm,
                staemp,
                CASE
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '10' THEN 'SAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '11' THEN 'SAAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '20' THEN 'SLAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '21' THEN 'SRAB'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '30' THEN 'SRDC'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '40' THEN 'SATC'
                    WHEN SUBSTR(t2.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                    WHEN SUBSTR(t2.namcent5, 1, 3) = '800' THEN 'SAB'
                    WHEN SUBSTR(t2.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '90' THEN 'SC'
                    WHEN SUBSTR(t2.namcent5, 1, 2) = '91' THEN 'SAB'
                    ELSE t1.codcomp1
                END AS PlantNO,
                CASE SUBSTR(t1.codempid, 1, 2)
                    WHEN '11' THEN 'SUB'  WHEN '12' THEN 'SUB'
                    WHEN '55' THEN 'SUB'  WHEN '56' THEN 'SUB'
                    WHEN 'A1' THEN 'PERM' WHEN 'A2' THEN 'PERM' WHEN 'A7' THEN 'PWC'
                    WHEN 'B1' THEN 'PERM' WHEN 'B2' THEN 'PERM' WHEN 'B7' THEN 'PWC'
                    WHEN 'DB' THEN 'PERM' WHEN 'FK' THEN 'SUB'
                    WHEN 'H1' THEN 'PERM' WHEN 'H3' THEN 'PERM' WHEN 'H4' THEN 'PERM'
                    WHEN 'JM' THEN 'PERM'
                    WHEN 'K2' THEN 'SUB'  WHEN 'K5' THEN 'SUB'  WHEN 'KA' THEN 'SUB'
                    WHEN 'L1' THEN 'PERM' WHEN 'L3' THEN 'PERM' WHEN 'L4' THEN 'PERM' WHEN 'L7' THEN 'PWC'
                    WHEN 'MA' THEN 'SUB'  WHEN 'PB' THEN 'SUB'  WHEN 'PW' THEN 'SUB'
                    WHEN 'R1' THEN 'PERM' WHEN 'R4' THEN 'PERM' WHEN 'R7' THEN 'PWC'
                    WHEN 'RA' THEN 'PERM' WHEN 'RD' THEN 'PERM' WHEN 'RE' THEN 'PWC'
                    WHEN 'SA' THEN 'PERM'
                    WHEN 'SM' THEN 'SUB'  WHEN 'SP' THEN 'SUB'  WHEN 'ST' THEN 'SUB'  WHEN 'SW' THEN 'SUB'
                    WHEN 'T1' THEN 'PERM' WHEN 'T7' THEN 'PWC'
                    WHEN 'TH' THEN 'SUB'  WHEN 'TR' THEN 'SUB'  WHEN 'TS' THEN 'SUB'
                    WHEN 'Y5' THEN 'SUB'  WHEN 'Y6' THEN 'SUB'
                    ELSE 'OTHER'
                END AS emp_category_by_prefix,
                (SELECT descodt FROM temploy2 e2, tcodnatn cn
                 WHERE e2.codnatnl = cn.codcodec AND e2.codempid = t1.codempid) AS codnatt,
                CASE 
                    WHEN SUBSTR(t1.codpos, 2, 1) >= '4' THEN 'MANAGER'
                    ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = t1.typemp))
                END AS type_name,
                (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(t2.namcent5) AND ROWNUM = 1) AS func_name,
                SUBSTR(t2.namcent4, INSTR(t2.namcent4, '_') + 1) AS dept,
                t1.namempt, t1.codcomp1, t1.dteempmt, t1.dtereemp, t1.dteeffex
            FROM temploy1 t1, tcenter t2
            WHERE t1.codcomp = t2.codcomp
              AND t1.codcomp1 NOT IN (
                  'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                  'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                  'EXC','SGV','SON'
              )
            ) t1
        ) t1 WHERE 1=1 $trendTrendWhere
          AND (
              (TO_CHAR(NVL(dtereemp, dteempmt), 'YYYY') = '{$trendYear}')
              OR (staemp = 9 AND TO_CHAR(dteeffex, 'YYYY') = '{$trendYear}')
          )";
        
        $movements = fetchAllAssoc($conn, $movSql, $binds);
        $hires = array_fill(1, 12, 0); $resigns = array_fill(1, 12, 0);
        foreach ($movements as $m) {
            if (!empty($m['HIRE_MM']))   $hires[(int)$m['HIRE_MM']]++;
            if (!empty($m['RETIRED_MM']) && $m['STAEMP'] == '9') $resigns[(int)$m['RETIRED_MM']]++;
        }
        $trendData = array_fill(0, 12, 0);
        $currentMonth = (int)date('m');
        $tempCount = $trendCurrentTotal;
        for ($i = $currentMonth; $i >= 1; $i--) {
            $trendData[$i-1] = $tempCount;
            $tempCount = $tempCount - $hires[$i] + $resigns[$i];
        }
        // เดือนที่เหลือ (อนาคต) ให้เป็น null เพื่อไม่แสดงเส้นในกราฟ
        for ($i = $currentMonth + 1; $i <= 12; $i++) {
            $trendData[$i-1] = null;
        }
    } else {
        // วิธี Snapshot สำหรับปีเก่า
        $trendData = [];
        for ($m = 1; $m <= 12; $m++) {
            $mm = str_pad($m, 2, '0', STR_PAD_LEFT);
            $lastDay = date('t', mktime(0, 0, 0, $m, 1, $trendYear));
            $eom = "TO_DATE('{$trendYear}-{$mm}-{$lastDay}','YYYY-MM-DD')";
            $sSql = "SELECT COUNT(*) as cnt FROM (
                SELECT 
                    PlantNO, type_name, emp_category_full, func_name, dept, namempt, codcomp1, staemp, dteempmt, dtereemp, dteeffex
                FROM (
                    SELECT t1.*,
                           CASE
                               WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
                               WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
                               WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
                               ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE 'OTHER' END
                           END AS emp_category_full
                    FROM (
                        SELECT
                            CASE
                                WHEN SUBSTR(t2.namcent5, 1, 2) = '10' THEN 'SAB'
                                WHEN SUBSTR(t2.namcent5, 1, 2) = '11' THEN 'SAAB'
                                WHEN SUBSTR(t2.namcent5, 1, 2) = '20' THEN 'SLAB'
                                WHEN SUBSTR(t2.namcent5, 1, 2) = '21' THEN 'SRAB'
                                WHEN SUBSTR(t2.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                                WHEN SUBSTR(t2.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                                WHEN SUBSTR(t2.namcent5, 1, 2) = '30' THEN 'SRDC'
                                WHEN SUBSTR(t2.namcent5, 1, 2) = '40' THEN 'SATC'
                                WHEN SUBSTR(t2.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                                WHEN SUBSTR(t2.namcent5, 1, 3) = '800' THEN 'SAB'
                                WHEN SUBSTR(t2.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                                WHEN SUBSTR(t2.namcent5, 1, 2) = '90' THEN 'SC'
                                WHEN SUBSTR(t2.namcent5, 1, 2) = '91' THEN 'SAB'
                                ELSE t1.codcomp1
                            END AS PlantNO,
                            CASE SUBSTR(t1.codempid, 1, 2)
                                WHEN '11' THEN 'SUB'  WHEN '12' THEN 'SUB'
                                WHEN '55' THEN 'SUB'  WHEN '56' THEN 'SUB'
                                WHEN 'A1' THEN 'PERM' WHEN 'A2' THEN 'PERM' WHEN 'A7' THEN 'PWC'
                                WHEN 'B1' THEN 'PERM' WHEN 'B2' THEN 'PERM' WHEN 'B7' THEN 'PWC'
                                WHEN 'DB' THEN 'PERM' WHEN 'FK' THEN 'SUB'
                                WHEN 'H1' THEN 'PERM' WHEN 'H3' THEN 'PERM' WHEN 'H4' THEN 'PERM'
                                WHEN 'JM' THEN 'PERM'
                                WHEN 'K2' THEN 'SUB'  WHEN 'K5' THEN 'SUB'  WHEN 'KA' THEN 'SUB'
                                WHEN 'L1' THEN 'PERM' WHEN 'L3' THEN 'PERM' WHEN 'L4' THEN 'PERM' WHEN 'L7' THEN 'PWC'
                                WHEN 'MA' THEN 'SUB'  WHEN 'PB' THEN 'SUB'  WHEN 'PW' THEN 'SUB'
                                WHEN 'R1' THEN 'PERM' WHEN 'R4' THEN 'PERM' WHEN 'R7' THEN 'PWC'
                                WHEN 'RA' THEN 'PERM' WHEN 'RD' THEN 'PERM' WHEN 'RE' THEN 'PWC'
                                WHEN 'SA' THEN 'PERM'
                                WHEN 'SM' THEN 'SUB'  WHEN 'SP' THEN 'SUB'  WHEN 'ST' THEN 'SUB'  WHEN 'SW' THEN 'SUB'
                                WHEN 'T1' THEN 'PERM' WHEN 'T7' THEN 'PWC'
                                WHEN 'TH' THEN 'SUB'  WHEN 'TR' THEN 'SUB'  WHEN 'TS' THEN 'SUB'
                                WHEN 'Y5' THEN 'SUB'  WHEN 'Y6' THEN 'SUB'
                                ELSE 'OTHER'
                            END AS emp_category_by_prefix,
                            (SELECT descodt FROM temploy2 e2, tcodnatn cn
                             WHERE e2.codnatnl = cn.codcodec AND e2.codempid = t1.codempid) AS codnatt,
                            CASE 
                                WHEN SUBSTR(t1.codpos, 2, 1) >= '4' THEN 'MANAGER'
                                ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = t1.typemp))
                            END AS type_name,
                            (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(t2.namcent5) AND ROWNUM = 1) AS func_name,
                            SUBSTR(t2.namcent4, INSTR(t2.namcent4, '_') + 1) AS dept,
                            t1.namempt, t1.codcomp1, t1.staemp, t1.dteempmt, t1.dtereemp, t1.dteeffex
                        FROM temploy1 t1, tcenter t2
                        WHERE t1.codcomp = t2.codcomp
                          AND t1.codcomp1 NOT IN (
                              'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                              'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                              'EXC','SGV','SON'
                          )
                    ) t1
                ) t1
                WHERE NVL(dtereemp, dteempmt) <= $eom
                  AND (staemp < 9 OR (staemp = 9 AND dteeffex > $eom))
            ) t1 WHERE 1=1 $trendTrendWhere";
            $sRes = fetchAllAssoc($conn, $sSql, $binds);
            $trendData[] = (int)($sRes[0]['CNT'] ?? 0);
        }
    }

    // --- 5. TURNOVER RATE DATA (ของจริง) ---
    $turnoverData = [];
    $monthsEng = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    // ดึงยอดลาออกรายเดือน
    $resSql = "SELECT TO_CHAR(dteeffex, 'MM') as mm, COUNT(*) as qty FROM (
        SELECT 
            PlantNO, type_name, emp_category_full, func_name, dept, namempt, codcomp1, staemp, dteeffex
        FROM (
            SELECT t1.*,
                   CASE
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
                       ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE 'OTHER' END
                   END AS emp_category_full
            FROM (
                SELECT
                    CASE
                        WHEN SUBSTR(t2.namcent5, 1, 2) = '10' THEN 'SAB'
                        WHEN SUBSTR(t2.namcent5, 1, 2) = '11' THEN 'SAAB'
                        WHEN SUBSTR(t2.namcent5, 1, 2) = '20' THEN 'SLAB'
                        WHEN SUBSTR(t2.namcent5, 1, 2) = '21' THEN 'SRAB'
                        WHEN SUBSTR(t2.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                        WHEN SUBSTR(t2.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                        WHEN SUBSTR(t2.namcent5, 1, 2) = '30' THEN 'SRDC'
                        WHEN SUBSTR(t2.namcent5, 1, 2) = '40' THEN 'SATC'
                        WHEN SUBSTR(t2.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                        WHEN SUBSTR(t2.namcent5, 1, 3) = '800' THEN 'SAB'
                        WHEN SUBSTR(t2.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                        WHEN SUBSTR(t2.namcent5, 1, 2) = '90' THEN 'SC'
                        WHEN SUBSTR(t2.namcent5, 1, 2) = '91' THEN 'SAB'
                        ELSE t1.codcomp1
                    END AS PlantNO,
                    CASE SUBSTR(t1.codempid, 1, 2)
                        WHEN '11' THEN 'SUB'  WHEN '12' THEN 'SUB'
                        WHEN '55' THEN 'SUB'  WHEN '56' THEN 'SUB'
                        WHEN 'A1' THEN 'PERM' WHEN 'A2' THEN 'PERM' WHEN 'A7' THEN 'PWC'
                        WHEN 'B1' THEN 'PERM' WHEN 'B2' THEN 'PERM' WHEN 'B7' THEN 'PWC'
                        WHEN 'DB' THEN 'PERM' WHEN 'FK' THEN 'SUB'
                        WHEN 'H1' THEN 'PERM' WHEN 'H3' THEN 'PERM' WHEN 'H4' THEN 'PERM'
                        WHEN 'JM' THEN 'PERM'
                        WHEN 'K2' THEN 'SUB'  WHEN 'K5' THEN 'SUB'  WHEN 'KA' THEN 'SUB'
                        WHEN 'L1' THEN 'PERM' WHEN 'L3' THEN 'PERM' WHEN 'L4' THEN 'PERM' WHEN 'L7' THEN 'PWC'
                        WHEN 'MA' THEN 'SUB'  WHEN 'PB' THEN 'SUB'  WHEN 'PW' THEN 'SUB'
                        WHEN 'R1' THEN 'PERM' WHEN 'R4' THEN 'PERM' WHEN 'R7' THEN 'PWC'
                        WHEN 'RA' THEN 'PERM' WHEN 'RD' THEN 'PERM' WHEN 'RE' THEN 'PWC'
                        WHEN 'SA' THEN 'PERM'
                        WHEN 'SM' THEN 'SUB'  WHEN 'SP' THEN 'SUB'  WHEN 'ST' THEN 'SUB'  WHEN 'SW' THEN 'SUB'
                        WHEN 'T1' THEN 'PERM' WHEN 'T7' THEN 'PWC'
                        WHEN 'TH' THEN 'SUB'  WHEN 'TR' THEN 'SUB'  WHEN 'TS' THEN 'SUB'
                        WHEN 'Y5' THEN 'SUB'  WHEN 'Y6' THEN 'SUB'
                        ELSE 'OTHER'
                    END AS emp_category_by_prefix,
                    (SELECT descodt FROM temploy2 e2, tcodnatn cn
                     WHERE e2.codnatnl = cn.codcodec AND e2.codempid = t1.codempid) AS codnatt,
                    CASE 
                        WHEN SUBSTR(t1.codpos, 2, 1) >= '4' THEN 'MANAGER'
                        ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = t1.typemp))
                    END AS type_name,
                    (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(t2.namcent5) AND ROWNUM = 1) AS func_name,
                    SUBSTR(t2.namcent4, INSTR(t2.namcent4, '_') + 1) AS dept,
                    t1.namempt, t1.codcomp1, t1.staemp, t1.dteeffex
                FROM temploy1 t1, tcenter t2
                WHERE t1.codcomp = t2.codcomp
                  AND t1.codcomp1 NOT IN (
                      'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                      'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                      'EXC','SGV','SON'
                  )
            ) t1
        ) t1
        WHERE staemp = 9 AND TO_CHAR(dteeffex, 'YYYY') = '{$trendYear}' $trendExcl
    ) t1 WHERE 1=1 $trendWhere GROUP BY TO_CHAR(dteeffex, 'MM') ORDER BY mm";
    
    $resCounts = fetchAllAssoc($conn, $resSql, $binds);
    $monthlyRes = array_fill(1, 12, 0);
    foreach ($resCounts as $r) {
        $monthlyRes[(int)$r['MM']] = (int)$r['QTY'];
    }

    // คำนวณ Turnover % รายเดือน (ใช้ Headcount จาก trendData)
    // Formula: (Resigned / Headcount End of Month) * 100
    for ($i = 0; $i < 12; $i++) {
        $hc = $trendData[$i];
        $res = $monthlyRes[$i+1];
        if ($hc === null) {
            $rate = null;
        } else {
            $rate = ($hc > 0) ? round(($res / $hc) * 100, 2) : 0;
        }
        $turnoverData[] = [$monthsEng[$i], $rate];
    }

    $response = [
        'kpi' => $kpi,
        'typeData' => $typeData,
        'functionData' => $functionData,
        'trendData' => $trendData,
        'turnoverData' => $turnoverData,
        'debug' => [
            'Month Param' => $month,
            'Year Param' => $trend_year,
            'Snapshot Mode' => ($activeFilter !== "AND t1.staemp < 9") ? 'YES - Snapshot Active' : 'NO - Realtime',
            'Active Filter' => $activeFilter,
            'KPI SQL' => $kpiSql,
            'Trend Year' => $trendYear,
            'Trend SQL Sample' => ($isCurrentYear ? $movSql : $sSql),
            'Function SQL' => $sqlFunc,
            'Binds' => json_encode($binds)
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
