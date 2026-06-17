<?php
// api/get_headcount_data.php
error_reporting(0);
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
$trend_year   = $_GET['trend_year']   ?? date('Y');

try {
    // --- 1. ตรรกะการแบ่งกลุ่มพนักงาน (Calculated Fields) ---
    $innerCalculatedSql = "
            SELECT 
                t1.codempid, t1.namempt, t1.staemp, t1.dteeffex, t1.dteempmt, t1.dtereemp, t1.codcomp, t1.codcomp1, t1.codsex, t1.dteempdb,
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
                END AS PlantGroup,
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
                    ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = t1.codempid AND ROWNUM = 1), 'OTHER')
                END AS emp_category_by_prefix,
                (SELECT descodt FROM temploy2 e2, tcodnatn cn WHERE e2.codnatnl = cn.codcodec AND e2.codempid = t1.codempid) AS codnatt,
                CASE 
                    WHEN SUBSTR(t1.codpos, 2, 1) >= '4' THEN 'MANAGER'
                    ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = t1.typemp))
                END AS type_name,
                (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(t2.namcent5) AND ROWNUM = 1) AS func_name,
                SUBSTR(t2.namcent4, INSTR(t2.namcent4, '_') + 1) AS dept,
                TRUNC(MONTHS_BETWEEN(SYSDATE, t1.dteempdb) / 12) AS age
            FROM temploy1 t1, tcenter t2
            WHERE t1.codcomp = t2.codcomp
              AND t1.codcomp1 NOT IN (
                  'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                  'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                  'EXC','SGV','SON'
              )
    ";

    $baseSql = "
        SELECT t1.*,
               CASE
                    WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
                    WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
                    WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
                    WHEN emp_category_by_prefix = 'SUB'                                                        THEN 'SUB'
                    ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE emp_category_by_prefix END
               END AS emp_category_full
        FROM ( $innerCalculatedSql ) t1
    ";

    // --- 2. Filter Construction ---
    $where = " WHERE 1=1";
    $binds = [];
    $where .= buildMultiFilter($plant,        'PlantGroup',        'plant',        $binds);
    $where .= buildMultiFilter($emp_type,     "CASE WHEN type_name IN ('ADMIN','DIRECT','INDIRECT','MANAGER') THEN type_name ELSE 'OTHER' END",         'emp_type',     $binds);
    $where .= buildMultiFilter($emp_category, 'emp_category_full', 'emp_category', $binds);
    $where .= buildMultiFilter($function,     'func_name',         'func',         $binds);
    $where .= buildMultiFilter($dept,         'dept',              'dept',         $binds, true);

    $headcountExcl = " AND namempt NOT LIKE '%จุฬางกูร%'";
    $headcountWhere = $where . $headcountExcl;

    // --- Month Snapshot: ถ้าเลือกเดือน → Snapshot ณ สิ้นเดือนนั้น ---
    $activeCondition = " AND staemp < 9"; // default: พนักงาน active ปัจจุบัน
    if (!empty($month) && is_numeric($month)) {
        $snapMM = str_pad((int)$month, 2, '0', STR_PAD_LEFT);
        $snapYY = (int)$trend_year;
        $snapLastDay = date('t', mktime(0, 0, 0, (int)$month, 1, $snapYY));
        $snapEOM = "TO_DATE('{$snapYY}-{$snapMM}-{$snapLastDay}', 'YYYY-MM-DD')";
        $snapTimestamp = mktime(23, 59, 59, (int)$month, $snapLastDay, $snapYY);
        if ($snapTimestamp < time()) {
            $activeCondition = " AND NVL(dtereemp, dteempmt) <= {$snapEOM}
                                AND (staemp < 9 OR (staemp = 9 AND dteeffex > {$snapEOM}))";
        }
    }

    // --- 3. KPI DATA ---
    $kpiSql = "
    SELECT
        SUM(CASE WHEN emp_category_full = 'PERM'         THEN 1 ELSE 0 END) AS perm,
        SUM(CASE WHEN emp_category_full = 'PWC'          THEN 1 ELSE 0 END) AS pwc,
        SUM(CASE WHEN emp_category_full = 'SUB Thai'     THEN 1 ELSE 0 END) AS sub_thai,
        SUM(CASE WHEN emp_category_full = 'SUB Myanmar'  THEN 1 ELSE 0 END) AS sub_myanmar,
        SUM(CASE WHEN emp_category_full = 'SUB Cambodia' THEN 1 ELSE 0 END) AS sub_cambodia,
        SUM(CASE WHEN emp_category_full = 'SUB'          THEN 1 ELSE 0 END) AS sub,
        SUM(CASE WHEN emp_category_full NOT IN ('PERM', 'PWC', 'SUB Thai', 'SUB Myanmar', 'SUB Cambodia', 'SUB') THEN 1 ELSE 0 END) AS other,
        COUNT(*) AS headcount_all,
        SUM(CASE WHEN codsex != 'F' THEN 1 ELSE 0 END) AS male_count,
        SUM(CASE WHEN codsex  = 'F' THEN 1 ELSE 0 END) AS female_count
    FROM ($baseSql) t1 $headcountWhere $activeCondition";

    $kpiRows = fetchAllAssoc($conn, $kpiSql, $binds);
    $row = !empty($kpiRows) ? $kpiRows[0] : [
        'PERM' => 0, 'PWC' => 0, 'SUB_THAI' => 0, 'SUB_MYANMAR' => 0, 'SUB_CAMBODIA' => 0, 
        'OTHER' => 0, 'HEADCOUNT_ALL' => 0, 'MALE_COUNT' => 0, 'FEMALE_COUNT' => 0
    ];
    $headTotal = (int)($row['HEADCOUNT_ALL'] ?? 0);

    $kpi = [
        'perm' => (int)($row['PERM'] ?? 0),
        'pwc' => (int)($row['PWC'] ?? 0),
        'sub_thai' => (int)($row['SUB_THAI'] ?? 0),
        'sub_myanmar' => (int)($row['SUB_MYANMAR'] ?? 0),
        'sub_cambodia' => (int)($row['SUB_CAMBODIA'] ?? 0),
        'sub' => (int)($row['SUB'] ?? 0),
        'other' => (int)($row['OTHER'] ?? 0),
        'headcount_all' => $headTotal,
        'male_pct' => $headTotal > 0 ? round((int)($row['MALE_COUNT'] ?? 0) / $headTotal * 100) : 0,
        'female_pct' => $headTotal > 0 ? round((int)($row['FEMALE_COUNT'] ?? 0) / $headTotal * 100) : 0
    ];

    // --- 4. TREND DATA ---
    $trendYear = (int)$trend_year;
    $isCurrentYear = ($trendYear == (int)date('Y'));
    $trendExcl = " AND namempt NOT LIKE '%จุฬางกูร%'
                   AND codcomp1 NOT IN ('AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS','XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE','EXC','SGV','SON')";
    $trendWhere = $where . $trendExcl;

    if ($isCurrentYear) {
        $tBaseSql = "SELECT COUNT(*) as cnt FROM ($baseSql) t1 $trendWhere AND staemp < 9";
        $tBaseRows = fetchAllAssoc($conn, $tBaseSql, $binds);
        $trendCurrentTotal = (int)($tBaseRows[0]['CNT'] ?? 0);

        $movSql = "
        SELECT 
            TO_CHAR(NVL(dtereemp, dteempmt), 'MM') AS hire_mm,
            TO_CHAR(dteeffex, 'MM') AS retired_mm,
            staemp
        FROM ($baseSql) t1 $trendWhere
          AND (
              (TO_CHAR(NVL(dtereemp, dteempmt), 'YYYY') = '{$trendYear}')
              OR (staemp = 9 AND TO_CHAR(dteeffex, 'YYYY') = '{$trendYear}')
          )
        ";
        $movements = fetchAllAssoc($conn, $movSql, $binds);
        $hires = array_fill(1, 12, 0); $resigns = array_fill(1, 12, 0);
        foreach ($movements as $m) {
            if (!empty($m['HIRE_MM']))   $hires[(int)$m['HIRE_MM']]++;
            if (!empty($m['RETIRED_MM']) && $m['STAEMP'] == '9') $resigns[(int)$m['RETIRED_MM']]++;
        }
        $trendData = array_fill(0, 12, 0);
        $thisMonth = (int)date('m');
        $runningTrend = $trendCurrentTotal;
        for ($i = $thisMonth; $i >= 1; $i--) {
            $trendData[$i-1] = $runningTrend;
            $runningTrend = $runningTrend - $hires[$i] + $resigns[$i];
        }
        for ($i = $thisMonth + 1; $i <= 12; $i++) { $trendData[$i-1] = null; }
    } else {
        $trendData = array_fill(0, 12, 0);
        for ($m = 1; $m <= 12; $m++) {
            $mm = str_pad($m, 2, '0', STR_PAD_LEFT);
            $lastDay = date('t', mktime(0, 0, 0, $m, 1, $trendYear));
            $endOfMonth = "TO_DATE('{$trendYear}-{$mm}-{$lastDay}', 'YYYY-MM-DD')";
            $snapSql = "SELECT COUNT(*) AS cnt FROM ($baseSql) t1 $trendWhere
                          AND NVL(dtereemp, dteempmt) <= {$endOfMonth} 
                          AND (staemp < 9 OR (staemp = 9 AND dteeffex > {$endOfMonth}))";
            $snapRows = fetchAllAssoc($conn, $snapSql, $binds);
            $trendData[$m-1] = (int)($snapRows[0]['CNT'] ?? 0);
        }
    }

    // --- 5. CATEGORICAL DATA ---
    // Pie: Plant Grouping
    $plantSql = "
    SELECT 
        CASE 
            WHEN PlantGroup IN ('SLAB', 'SLAB-2', 'SLAB-3') THEN 'SLAB'
            WHEN PlantGroup IN ('SAB')  THEN 'SAB'
            WHEN PlantGroup IN ('SAAB') THEN 'SAAB'
            WHEN PlantGroup IN ('SRAB') THEN 'SRAB'
            WHEN PlantGroup IN ('SRDC') THEN 'SRDC'
            WHEN PlantGroup IN ('SATC') THEN 'SATC'
            WHEN PlantGroup IN ('SDC')  THEN 'SDC'
            WHEN PlantGroup IN ('SAM')  THEN 'SAM'
            ELSE 'OTHER'
        END AS pgroup,
        COUNT(*) as qty
    FROM ($baseSql) t1 $headcountWhere $activeCondition
    GROUP BY 
        CASE 
            WHEN PlantGroup IN ('SLAB', 'SLAB-2', 'SLAB-3') THEN 'SLAB'
            WHEN PlantGroup IN ('SAB')  THEN 'SAB'
            WHEN PlantGroup IN ('SAAB') THEN 'SAAB'
            WHEN PlantGroup IN ('SRAB') THEN 'SRAB'
            WHEN PlantGroup IN ('SRDC') THEN 'SRDC'
            WHEN PlantGroup IN ('SATC') THEN 'SATC'
            WHEN PlantGroup IN ('SDC')  THEN 'SDC'
            WHEN PlantGroup IN ('SAM')  THEN 'SAM'
            ELSE 'OTHER'
        END
    ORDER BY qty DESC";
    $plantResults = fetchAllAssoc($conn, $plantSql, $binds);
    $plantData = [];
    foreach ($plantResults as $p) {
        if ($p['PGROUP'] !== 'OTHER' || $p['QTY'] > 0) $plantData[] = ['name' => $p['PGROUP'], 'y' => (int)$p['QTY']];
    }

    $typeData = [];
    if ($headTotal > 0) {
        $cats = [
            'PERM' => 'PERM', 'PWC' => 'PWC', 'SUB' => 'SUB', 'SUB Thai' => 'SUB_THAI', 
            'SUB Myanmar' => 'SUB_MYANMAR', 'SUB Cambodia' => 'SUB_CAMBODIA', 'OTHER' => 'OTHER'
        ];
        foreach ($cats as $lbl => $k) {
            $v = (int)($row[$k] ?? 0);
            if ($v > 0) {
                $typeData[] = ['name' => $lbl, 'y' => round($v / $headTotal * 100, 1)];
            }
        }
    }

    // Metric Tiles: Type Count grouped by type_name (ADMIN, DIRECT, INDIRECT, MANAGER + OTHER)
    $typeCountData = [];
    if ($headTotal > 0) {
        $typeSql = "SELECT 
            CASE WHEN type_name IN ('ADMIN','DIRECT','INDIRECT','MANAGER') THEN type_name ELSE 'OTHER' END AS grp_name, 
            COUNT(*) AS qty 
            FROM ($baseSql) t1 $headcountWhere $activeCondition 
            GROUP BY CASE WHEN type_name IN ('ADMIN','DIRECT','INDIRECT','MANAGER') THEN type_name ELSE 'OTHER' END 
            ORDER BY qty DESC";
        $typeResults = fetchAllAssoc($conn, $typeSql, $binds);
        foreach ($typeResults as $tr) {
            $qty = (int)$tr['QTY'];
            $typeCountData[] = [
                'name' => $tr['GRP_NAME'],
                'qty'  => $qty,
                'pct'  => round($qty / $headTotal * 100, 1)
            ];
        }
    }

    // Bar: Function %
    $funcSql = "SELECT func_name, COUNT(*) as qty FROM ($baseSql) t1 $headcountWhere $activeCondition AND func_name IS NOT NULL GROUP BY func_name ORDER BY qty DESC";
    $funcResults = fetchAllAssoc($conn, $funcSql, $binds);
    $functionData = [];
    foreach ($funcResults as $f) {
        $functionData[] = ['name' => $f['FUNC_NAME'], 'y' => round(((int)$f['QTY'] / ($headTotal?:1)) * 100, 1)];
    }

    // Bar: Age %
    $ageSql = "
    SELECT 
        CASE 
            WHEN age <= 20 THEN '18-20 ปี'
            WHEN age BETWEEN 21 AND 25 THEN '21-25 ปี'
            WHEN age BETWEEN 26 AND 30 THEN '26-30 ปี'
            WHEN age BETWEEN 31 AND 35 THEN '31-35 ปี'
            WHEN age BETWEEN 36 AND 40 THEN '36-40 ปี'
            WHEN age BETWEEN 41 AND 45 THEN '41-45 ปี'
            WHEN age BETWEEN 46 AND 50 THEN '46-50 ปี'
            WHEN age BETWEEN 51 AND 55 THEN '51-55 ปี'
            ELSE '55 ปีขึ้นไป'
        END AS age_grp,
        COUNT(*) as qty
    FROM ($baseSql) t1 $headcountWhere $activeCondition
    GROUP BY 
        CASE 
            WHEN age <= 20 THEN '18-20 ปี'
            WHEN age BETWEEN 21 AND 25 THEN '21-25 ปี'
            WHEN age BETWEEN 26 AND 30 THEN '26-30 ปี'
            WHEN age BETWEEN 31 AND 35 THEN '31-35 ปี'
            WHEN age BETWEEN 36 AND 40 THEN '36-40 ปี'
            WHEN age BETWEEN 41 AND 45 THEN '41-45 ปี'
            WHEN age BETWEEN 46 AND 50 THEN '46-50 ปี'
            WHEN age BETWEEN 51 AND 55 THEN '51-55 ปี'
            ELSE '55 ปีขึ้นไป'
        END
    ORDER BY MIN(age)";
    $ageResults = fetchAllAssoc($conn, $ageSql, $binds);
    $ageData = [];
    foreach ($ageResults as $a) {
        $ageData[] = ['name' => $a['AGE_GRP'], 'y' => round(((int)$a['QTY'] / ($headTotal?:1)) * 100, 1)];
    }

    // ส่งค่า debug โดยแปลงตัวที่ไม่ใช่ string ให้เป็น string/json
    echo json_encode([
        'kpi' => $kpi,
        'trendData' => $trendData,
        'plantData' => $plantData,
        'typeData' => $typeData,
        'typeCountData' => $typeCountData,
        'functionData' => $functionData,
        'ageData' => $ageData,
        'debug' => [
            'Filter' => (string)$trendWhere,
            'KPI SQL' => (string)$kpiSql,
            'Trend (Current) SQL' => $isCurrentYear ? (string)$tBaseSql : 'N/A',
            'Trend (Movements) SQL' => $isCurrentYear ? (string)$movSql : 'Snapshot Mode',
            'Plant SQL' => (string)$plantSql,
            'Function SQL' => (string)$funcSql,
            'Age SQL' => (string)$ageSql,
            'Binds Result' => json_encode($binds)
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
