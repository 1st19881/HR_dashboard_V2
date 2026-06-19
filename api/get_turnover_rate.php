<?php
// api/get_turnover_rate.php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';
require_once '../includes/functions.php';

$conn = getDbConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {

// รับค่า parameter
$year         = $_GET['year']         ?? date('Y');
$plant        = $_GET['plant']        ?? '';
$emp_type     = $_GET['emp_type']     ?? '';
$emp_category = $_GET['emp_category'] ?? '';
$function     = $_GET['function']     ?? '';
$dept         = $_GET['dept']         ?? '';

$trendYear = (int)$year;
$isCurrentYear = ($trendYear == (int)date('Y'));

// --- Logic การดึงตัวส่วน (Headcountรายเดือนสำหรับตัวหาร) ---
// เงื่อนไขยกเว้นสำหรับ Trend/Turnover
$trendExcl = " AND namempt NOT LIKE '%จุฬางกูร%'";

// Reason Codes ที่ไม่นำมาคำนวณ Turnover Rate (CODCODEC จาก TCODEXEM)
$turnoverExclReasons = ['11', '12', '15', '16', '17'];
$reasonPlaceholders = implode(',', array_map(function($v) { return "'" . $v . "'"; }, $turnoverExclReasons));
$turnoverReasonExclSql = " AND NOT EXISTS (
    SELECT 1 FROM HRMS.TTEXEMPT tex
    WHERE tex.CODEMPID = t1.codempid
      AND tex.CODEXEMP IN ($reasonPlaceholders)
      AND tex.rowid = (SELECT MAX(rowid) FROM HRMS.TTEXEMPT WHERE CODEMPID = t1.codempid)
)";

// Build Conditions
$where = "";
$binds = [];
$where .= buildMultiFilter($plant,        'PlantNO',           'plant',        $binds);
$where .= buildMultiFilter($emp_type,     "CASE WHEN type_name IN ('ADMIN','DIRECT','INDIRECT','MANAGER') THEN type_name ELSE 'OTHER' END",         'emp_type',     $binds);
$where .= buildMultiFilter($emp_category, 'emp_category_full', 'emp_category', $binds);
$where .= buildMultiFilter($function,     'func_name',         'func',         $binds);
$where .= buildMultiFilter($dept,         'dept',              'dept',         $binds, true);

$trendWhere = $where . $trendExcl;

// คำนวณ Headcount รายเดือน (Snapshots)
$monthlyHC = array_fill(0, 12, 0);

if ($isCurrentYear) {
    // วิธี Rollback สำหรับปีปัจจุบัน
    $tBaseSql = "SELECT COUNT(*) as cnt FROM (
        SELECT sub2.* FROM (
            SELECT sub1.*,
                   CASE
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
                       WHEN emp_category_by_prefix = 'SUB'                                                        THEN 'SUB'
                       ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE emp_category_by_prefix END
                   END AS emp_category_full
            FROM (
                SELECT 
                    CASE
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '10' THEN 'SAB'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '11' THEN 'SAAB'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '20' THEN 'SLAB'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '21' THEN 'SRAB'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '30' THEN 'SRDC'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '40' THEN 'SATC'
                        WHEN SUBSTR(tc.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                        WHEN SUBSTR(tc.namcent5, 1, 3) = '800' THEN 'SAB'
                        WHEN SUBSTR(tc.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '90' THEN 'SC'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '91' THEN 'SAB'
                        ELSE te.codcomp1
                    END AS PlantNO,
                    CASE SUBSTR(te.codempid, 1, 2)
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
                        ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = te.codempid AND ROWNUM = 1), 'OTHER')
                    END AS emp_category_by_prefix,
                    (SELECT descodt FROM temploy2 te2, tcodnatn cn
                     WHERE te2.codnatnl = cn.codcodec AND te2.codempid = te.codempid) AS codnatt,
                    CASE 
                        WHEN SUBSTR(te.codpos, 2, 1) >= '4' THEN 'MANAGER'
                        ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = te.typemp))
                    END AS type_name,
                    (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(tc.namcent5) AND ROWNUM = 1) AS func_name,
                    SUBSTR(tc.namcent4, INSTR(tc.namcent4, '_') + 1) AS dept,
                    te.namempt, te.codcomp1, te.staemp, te.dteempmt, te.dtereemp, te.dteeffex
                FROM temploy1 te, tcenter tc
                WHERE te.codcomp = tc.codcomp
                  AND te.codcomp1 NOT IN (
                      'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                      'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                      'EXC','SGV','SON','JMX','SC1'
                  )
                  AND te.typemp != 'IU'
            ) sub1
        ) sub2 WHERE 1=1 $trendWhere AND staemp < 9
    ) t1";
    
    $tBaseRows = fetchAllAssoc($conn, $tBaseSql, $binds);
    $trendCurrentTotal = (int)($tBaseRows[0]['CNT'] ?? 0);

    $movSql = "SELECT hire_mm, retired_mm, staemp FROM (
        SELECT sub2.* FROM (
            SELECT sub1.*,
                   CASE
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
                       WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
                       WHEN emp_category_by_prefix = 'SUB'                                                        THEN 'SUB'
                       ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE emp_category_by_prefix END
                   END AS emp_category_full
            FROM (
                SELECT 
                    TO_CHAR(NVL(dteeffex, dteeffex), 'MM') as dummy_mm, -- just to have something
                    TO_CHAR(NVL(dtereemp, dteempmt), 'MM') AS hire_mm,
                    TO_CHAR(dteeffex, 'MM') AS retired_mm,
                    staemp,
                    (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(tc.namcent5) AND ROWNUM = 1) AS func_name,
                    SUBSTR(tc.namcent4, INSTR(tc.namcent4, '_') + 1) AS dept,
                    CASE
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '10' THEN 'SAB'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '11' THEN 'SAAB'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '20' THEN 'SLAB'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '21' THEN 'SRAB'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '30' THEN 'SRDC'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '40' THEN 'SATC'
                        WHEN SUBSTR(tc.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                        WHEN SUBSTR(tc.namcent5, 1, 3) = '800' THEN 'SAB'
                        WHEN SUBSTR(tc.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '90' THEN 'SC'
                        WHEN SUBSTR(tc.namcent5, 1, 2) = '91' THEN 'SAB'
                        ELSE te.codcomp1
                    END AS PlantNO,
                    CASE SUBSTR(te.codempid, 1, 2)
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
                        ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = te.codempid AND ROWNUM = 1), 'OTHER')
                    END AS emp_category_by_prefix,
                    (SELECT descodt FROM temploy2 te2, tcodnatn cn
                     WHERE te2.codnatnl = cn.codcodec AND te2.codempid = te.codempid) AS codnatt,
                    CASE 
                        WHEN SUBSTR(te.codpos, 2, 1) >= '4' THEN 'MANAGER'
                        ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = te.typemp))
                    END AS type_name,
                    te.namempt, te.codcomp1, te.dteempmt, te.dtereemp, te.dteeffex
                FROM temploy1 te, tcenter tc
                WHERE te.codcomp = tc.codcomp
                  AND te.codcomp1 NOT IN (
                      'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                      'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                      'EXC','SGV','SON','JMX','SC1'
                  )
                  AND te.typemp != 'IU'
            ) sub1
        ) sub2 WHERE 1=1 $trendWhere
          AND (
              (TO_CHAR(NVL(dtereemp, dteempmt), 'YYYY') = '$trendYear')
              OR (staemp = 9 AND TO_CHAR(dteeffex, 'YYYY') = '$trendYear')
          )
    ) t1";
    
    $movements = fetchAllAssoc($conn, $movSql, $binds);
    $hires = array_fill(1, 12, 0); $resigns = array_fill(1, 12, 0);
    foreach ($movements as $m) {
        if (!empty($m['HIRE_MM']))   $hires[(int)$m['HIRE_MM']]++;
        if (!empty($m['RETIRED_MM']) && $m['STAEMP'] == '9') $resigns[(int)$m['RETIRED_MM']]++;
    }
    $currentMonth = (int)date('m');
    $tempCount = $trendCurrentTotal;
    for ($i = $currentMonth; $i >= 1; $i--) {
        $monthlyHC[$i-1] = $tempCount;
        $tempCount = $tempCount - $hires[$i] + $resigns[$i];
    }
    for ($i = $currentMonth + 1; $i <= 12; $i++) {
        $monthlyHC[$i-1] = $trendCurrentTotal;
    }
} else {
    //Snapshot สำหรับปีเก่า
    for ($m = 1; $m <= 12; $m++) {
        $mm = str_pad($m, 2, '0', STR_PAD_LEFT);
        $lastDay = date('t', mktime(0, 0, 0, $m, 1, $trendYear));
        $eom = "TO_DATE('$trendYear-$mm-$lastDay','YYYY-MM-DD')";
        $sSql = "SELECT COUNT(*) as cnt FROM (
            SELECT sub2.* FROM (
                SELECT sub1.*,
                       CASE
                           WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
                           WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
                           WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
                           ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE 'OTHER' END
                       END AS emp_category_full
                FROM (
                    SELECT
                        CASE
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '10' THEN 'SAB'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '11' THEN 'SAAB'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '20' THEN 'SLAB'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '21' THEN 'SRAB'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '30' THEN 'SRDC'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '40' THEN 'SATC'
                            WHEN SUBSTR(tc.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                            WHEN SUBSTR(tc.namcent5, 1, 3) = '800' THEN 'SAB'
                            WHEN SUBSTR(tc.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '90' THEN 'SC'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '91' THEN 'SAB'
                            ELSE te.codcomp1
                        END AS PlantNO,
                        CASE SUBSTR(te.codempid, 1, 2)
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
                            ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = te.codempid AND ROWNUM = 1), 'OTHER')
                        END AS emp_category_by_prefix,
                        (SELECT descodt FROM temploy2 te2, tcodnatn cn
                         WHERE te2.codnatnl = cn.codcodec AND te2.codempid = te.codempid) AS codnatt,
                        CASE 
                            WHEN SUBSTR(te.codpos, 2, 1) >= '4' THEN 'MANAGER'
                            ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = te.typemp))
                        END AS type_name,
                        (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(tc.namcent5) AND ROWNUM = 1) AS func_name,
                        SUBSTR(tc.namcent4, INSTR(tc.namcent4, '_') + 1) AS dept,
                        te.namempt, te.codcomp1, te.staemp, te.dteempmt, te.dtereemp, te.dteeffex
                    FROM temploy1 te, tcenter tc
                    WHERE te.codcomp = tc.codcomp
                      AND te.codcomp1 NOT IN (
                          'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                          'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                          'EXC','SGV','SON','JMX','SC1'
                      )
                      AND te.typemp != 'IU'
                ) sub1
            ) sub2
            WHERE NVL(dtereemp, dteempmt) <= $eom
              AND (staemp < 9 OR (staemp = 9 AND dteeffex > $eom))
        ) t1 WHERE 1=1 $trendWhere";
        $sRes = fetchAllAssoc($conn, $sSql, $binds);
        $monthlyHC[$m-1] = (int)($sRes[0]['CNT'] ?? 0);
    }
}

// --- 2. ดึงยอดลาออกรายเดือน (Resignations) ---
$resSql = "SELECT TO_CHAR(dteeffex, 'MM') as mm, COUNT(*) as qty FROM (
    SELECT sub2.* FROM (
        SELECT sub1.*,
               CASE
                   WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
                   WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
                   WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
                   WHEN emp_category_by_prefix = 'SUB'                                                        THEN 'SUB'
                   ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE emp_category_by_prefix END
               END AS emp_category_full
        FROM (
            SELECT
                CASE
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '10' THEN 'SAB'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '11' THEN 'SAAB'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '20' THEN 'SLAB'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '21' THEN 'SRAB'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '30' THEN 'SRDC'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '40' THEN 'SATC'
                    WHEN SUBSTR(tc.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                    WHEN SUBSTR(tc.namcent5, 1, 3) = '800' THEN 'SAB'
                    WHEN SUBSTR(tc.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '90' THEN 'SC'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '91' THEN 'SAB'
                    ELSE te.codcomp1
                END AS PlantNO,
                CASE SUBSTR(te.codempid, 1, 2)
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
                    ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = te.codempid AND ROWNUM = 1), 'OTHER')
                END AS emp_category_by_prefix,
                (SELECT descodt FROM temploy2 te2, tcodnatn cn
                 WHERE te2.codnatnl = cn.codcodec AND te2.codempid = te.codempid) AS codnatt,
                CASE 
                    WHEN SUBSTR(te.codpos, 2, 1) >= '4' THEN 'MANAGER'
                    ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = te.typemp))
                END AS type_name,
                (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(tc.namcent5) AND ROWNUM = 1) AS func_name,
                SUBSTR(tc.namcent4, INSTR(tc.namcent4, '_') + 1) AS dept,
                te.codempid, te.namempt, te.codcomp1, te.staemp, te.dteeffex
            FROM temploy1 te, tcenter tc
            WHERE te.codcomp = tc.codcomp
              AND te.codcomp1 NOT IN (
                  'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                  'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                  'EXC','SGV','SON','JMX','SC1'
              )
              AND te.typemp != 'IU'
        ) sub1
    ) sub2
    WHERE staemp = 9 AND TO_CHAR(dteeffex, 'YYYY') = '$trendYear' $trendExcl
) t1 WHERE 1=1 $trendWhere $turnoverReasonExclSql GROUP BY TO_CHAR(dteeffex, 'MM') ORDER BY mm";

$resResults = fetchAllAssoc($conn, $resSql, $binds);
$monthlyRes = array_fill(1, 12, 0);
foreach ($resResults as $r) {
    $monthlyRes[(int)$r['MM']] = (int)$r['QTY'];
}

// --- 3. คำนวณ Turnover Rate (%) ---
$turnoverData = [];
$monthsEng = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

for ($i = 0; $i < 12; $i++) {
    $hc = $monthlyHC[$i];
    $res = $monthlyRes[$i+1];
    $rate = ($hc > 0) ? round(($res / $hc) * 100, 2) : 0;
    
    $turnoverData[] = [
        'month' => $monthsEng[$i],
        'resignations' => $res,
        'headcount' => $hc,
        'rate' => $rate
    ];
}
// --- 4. ดึงข้อมูลเหตุผลการลาออก (Resignation Reasons) ---
$reasonSql = "
SELECT resign_reason, COUNT(*) as qty FROM (
    SELECT sub2.* FROM (
        SELECT sub1.*,
               CASE
                   WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
                   WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
                   WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
                   WHEN emp_category_by_prefix = 'SUB'                                                        THEN 'SUB'
                   ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE emp_category_by_prefix END
               END AS emp_category_full,
                (
                    SELECT TCODEXEM.DESCODT
                    FROM HRMS.TTEXEMPT, HRMS.TCODEXEM
                    WHERE TTEXEMPT.CODEXEMP = TCODEXEM.CODCODEC
                      AND TTEXEMPT.CODEMPID = sub1.CODEMPID
                      AND TTEXEMPT.rowid = (
                            SELECT MAX(rowid)
                            FROM HRMS.TTEXEMPT
                            WHERE CODEMPID = sub1.CODEMPID
                      )
                ) as resign_reason
        FROM (
            SELECT
                CASE
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '10' THEN 'SAB'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '11' THEN 'SAAB'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '20' THEN 'SLAB'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '21' THEN 'SRAB'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '30' THEN 'SRDC'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '40' THEN 'SATC'
                    WHEN SUBSTR(tc.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                    WHEN SUBSTR(tc.namcent5, 1, 3) = '800' THEN 'SAB'
                    WHEN SUBSTR(tc.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '90' THEN 'SC'
                    WHEN SUBSTR(tc.namcent5, 1, 2) = '91' THEN 'SAB'
                    ELSE te.codcomp1
                END AS PlantNO,
                CASE SUBSTR(te.codempid, 1, 2)
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
                    ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = te.codempid AND ROWNUM = 1), 'OTHER')
                END AS emp_category_by_prefix,
                (SELECT descodt FROM temploy2 te2, tcodnatn cn
                 WHERE te2.codnatnl = cn.codcodec AND te2.codempid = te.codempid) AS codnatt,
                CASE 
                    WHEN SUBSTR(te.codpos, 2, 1) >= '4' THEN 'MANAGER'
                    ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = te.typemp))
                END AS type_name,
                (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(tc.namcent5) AND ROWNUM = 1) AS func_name,
                SUBSTR(tc.namcent4, INSTR(tc.namcent4, '_') + 1) AS dept,
                te.codempid, te.namempt, te.codcomp1, te.staemp, te.dteeffex
            FROM temploy1 te, tcenter tc
            WHERE te.codcomp = tc.codcomp
              AND te.codcomp1 NOT IN (
                  'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                  'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                  'EXC','SGV','SON','JMX','SC1'
              )
              AND te.typemp != 'IU'
        ) sub1
    ) sub2
    WHERE staemp = 9 AND TO_CHAR(dteeffex, 'YYYY') = '$trendYear' $trendExcl
) t1 WHERE 1=1 $trendWhere $turnoverReasonExclSql
GROUP BY resign_reason 
ORDER BY qty DESC";

$reasonResults = fetchAllAssoc($conn, $reasonSql, $binds);
$reasonData = [];
foreach ($reasonResults as $r) {
    $reasonData[] = [
        'name' => $r['RESIGN_REASON'] ?: 'ไม่ระบุ',
        'y' => (int)$r['QTY']
    ];
}


    // --- 5. Category-Specific Turnover (SUB, PERM, PWC, OTHER) ---
    $categories = ['SUB', 'PERM', 'PWC', 'OTHER'];
    $categoryTurnover = [];

    // Base filters for categories
    $baseWhere = "";
    $baseBinds = [];
    $baseWhere .= buildMultiFilter($plant,        'PlantNO',           'p_plant',    $baseBinds);
    $baseWhere .= buildMultiFilter($emp_type,     "CASE WHEN type_name IN ('ADMIN','DIRECT','INDIRECT','MANAGER') THEN type_name ELSE 'OTHER' END",         'p_type',     $baseBinds);
    $baseWhere .= buildMultiFilter($function,     'func_name',         'p_func',     $baseBinds);
    $baseWhere .= buildMultiFilter($dept,         'dept',              'p_dept',     $baseBinds, true);
    $baseWhere .= $trendExcl;

    // Company Exclusion for all queries
    $compExcl = " AND te.codcomp1 NOT IN ('AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS','XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE','EXC','SGV','SON','JMX','SC1') AND te.typemp != 'IU'";

    foreach ($categories as $cat) {
        $catWhere = $baseWhere . " AND emp_category_by_prefix = :cat";
        $catBinds = array_merge($baseBinds, [':cat' => $cat]);
        
        $catMonthlyHC = array_fill(0, 12, 0);
        $catMonthlyRes = array_fill(1, 12, 0);

        if ($isCurrentYear) {
            // Current Total for Category
            $cSql = "SELECT COUNT(*) as cnt FROM (
                SELECT sub2.* FROM (
                    SELECT sub1.* FROM (
                        SELECT 
                            CASE
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '10' THEN 'SAB'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '11' THEN 'SAAB'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '20' THEN 'SLAB'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '21' THEN 'SRAB'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '30' THEN 'SRDC'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '40' THEN 'SATC'
                                WHEN SUBSTR(tc.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                                WHEN SUBSTR(tc.namcent5, 1, 3) = '800' THEN 'SAB'
                                WHEN SUBSTR(tc.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '90' THEN 'SC'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '91' THEN 'SAB'
                                ELSE te.codcomp1
                            END AS PlantNO,
                            CASE SUBSTR(te.codempid, 1, 2)
                                WHEN '11' THEN 'SUB' WHEN '12' THEN 'SUB' WHEN '55' THEN 'SUB' WHEN '56' THEN 'SUB'
                                WHEN 'A1' THEN 'PERM' WHEN 'A2' THEN 'PERM' WHEN 'A7' THEN 'PWC'
                                WHEN 'B1' THEN 'PERM' WHEN 'B2' THEN 'PERM' WHEN 'B7' THEN 'PWC'
                                WHEN 'DB' THEN 'PERM' WHEN 'FK' THEN 'SUB' WHEN 'H1' THEN 'PERM' WHEN 'H3' THEN 'PERM' WHEN 'H4' THEN 'PERM'
                                WHEN 'JM' THEN 'PERM' WHEN 'K2' THEN 'SUB' WHEN 'K5' THEN 'SUB' WHEN 'KA' THEN 'SUB'
                                WHEN 'L1' THEN 'PERM' WHEN 'L3' THEN 'PERM' WHEN 'L4' THEN 'PERM' WHEN 'L7' THEN 'PWC'
                                WHEN 'MA' THEN 'SUB' WHEN 'PB' THEN 'SUB' WHEN 'PW' THEN 'SUB'
                                WHEN 'R1' THEN 'PERM' WHEN 'R4' THEN 'PERM' WHEN 'R7' THEN 'PWC'
                                WHEN 'RA' THEN 'PERM' WHEN 'RD' THEN 'PERM' WHEN 'RE' THEN 'PWC'
                                WHEN 'SA' THEN 'PERM' WHEN 'SM' THEN 'SUB' WHEN 'SP' THEN 'SUB' WHEN 'ST' THEN 'SUB' WHEN 'SW' THEN 'SUB'
                                WHEN 'T1' THEN 'PERM' WHEN 'T7' THEN 'PWC' WHEN 'TH' THEN 'SUB' WHEN 'TR' THEN 'SUB' WHEN 'TS' THEN 'SUB'
                                WHEN 'Y5' THEN 'SUB' WHEN 'Y6' THEN 'SUB'
                                ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = te.codempid AND ROWNUM = 1), 'OTHER')
                            END AS emp_category_by_prefix,
                            CASE WHEN SUBSTR(te.codpos, 2, 1) >= '4' THEN 'MANAGER' ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = te.typemp)) END AS type_name,
                            (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(tc.namcent5) AND ROWNUM = 1) AS func_name,
                            SUBSTR(tc.namcent4, INSTR(tc.namcent4, '_') + 1) AS dept,
                            te.namempt, te.staemp, te.codcomp1
                        FROM temploy1 te, tcenter tc
                        WHERE te.codcomp = tc.codcomp $compExcl
                    ) sub1
                ) sub2 WHERE 1=1 $catWhere AND staemp < 9
            ) t1";
            $cRows = fetchAllAssoc($conn, $cSql, $catBinds);
            $cTotal = (int)($cRows[0]['CNT'] ?? 0);

            // Movements for Category
            $mSql = "SELECT hire_mm, retired_mm, staemp FROM (
                SELECT sub2.* FROM (
                    SELECT sub1.* FROM (
                        SELECT 
                            TO_CHAR(NVL(dtereemp, dteempmt), 'MM') AS hire_mm,
                            TO_CHAR(dteeffex, 'MM') AS retired_mm,
                            staemp,
                            CASE
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '10' THEN 'SAB'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '11' THEN 'SAAB'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '20' THEN 'SLAB'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '21' THEN 'SRAB'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '30' THEN 'SRDC'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '40' THEN 'SATC'
                                WHEN SUBSTR(tc.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                                WHEN SUBSTR(tc.namcent5, 1, 3) = '800' THEN 'SAB'
                                WHEN SUBSTR(tc.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '90' THEN 'SC'
                                WHEN SUBSTR(tc.namcent5, 1, 2) = '91' THEN 'SAB'
                                ELSE te.codcomp1
                            END AS PlantNO,
                            CASE SUBSTR(te.codempid, 1, 2)
                                WHEN '11' THEN 'SUB' WHEN '12' THEN 'SUB' WHEN '55' THEN 'SUB' WHEN '56' THEN 'SUB'
                                WHEN 'A1' THEN 'PERM' WHEN 'A2' THEN 'PERM' WHEN 'A7' THEN 'PWC'
                                WHEN 'B1' THEN 'PERM' WHEN 'B2' THEN 'PERM' WHEN 'B7' THEN 'PWC'
                                WHEN 'DB' THEN 'PERM' WHEN 'FK' THEN 'SUB' WHEN 'H1' THEN 'PERM' WHEN 'H3' THEN 'PERM' WHEN 'H4' THEN 'PERM'
                                WHEN 'JM' THEN 'PERM' WHEN 'K2' THEN 'SUB' WHEN 'K5' THEN 'SUB' WHEN 'KA' THEN 'SUB'
                                WHEN 'L1' THEN 'PERM' WHEN 'L3' THEN 'PERM' WHEN 'L4' THEN 'PERM' WHEN 'L7' THEN 'PWC'
                                WHEN 'MA' THEN 'SUB' WHEN 'PB' THEN 'SUB' WHEN 'PW' THEN 'SUB'
                                WHEN 'R1' THEN 'PERM' WHEN 'R4' THEN 'PERM' WHEN 'R7' THEN 'PWC'
                                WHEN 'RA' THEN 'PERM' WHEN 'RD' THEN 'PERM' WHEN 'RE' THEN 'PWC'
                                WHEN 'SA' THEN 'PERM' WHEN 'SM' THEN 'SUB' WHEN 'SP' THEN 'SUB' WHEN 'ST' THEN 'SUB' WHEN 'SW' THEN 'SUB'
                                WHEN 'T1' THEN 'PERM' WHEN 'T7' THEN 'PWC' WHEN 'TH' THEN 'SUB' WHEN 'TR' THEN 'SUB' WHEN 'TS' THEN 'SUB'
                                WHEN 'Y5' THEN 'SUB' WHEN 'Y6' THEN 'SUB'
                                ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = te.codempid AND ROWNUM = 1), 'OTHER')
                            END AS emp_category_by_prefix,
                            CASE WHEN SUBSTR(te.codpos, 2, 1) >= '4' THEN 'MANAGER' ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = te.typemp)) END AS type_name,
                            (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(tc.namcent5) AND ROWNUM = 1) AS func_name,
                            SUBSTR(tc.namcent4, INSTR(tc.namcent4, '_') + 1) AS dept,
                            te.namempt, te.dteempmt, te.dtereemp, te.dteeffex, te.codcomp1
                        FROM temploy1 te, tcenter tc
                        WHERE te.codcomp = tc.codcomp $compExcl
                    ) sub1
                ) sub2 WHERE 1=1 $catWhere
                  AND ((TO_CHAR(NVL(dtereemp, dteempmt), 'YYYY') = '$trendYear') OR (staemp = 9 AND TO_CHAR(dteeffex, 'YYYY') = '$trendYear'))
            ) t1";
            $mRows = fetchAllAssoc($conn, $mSql, $catBinds);
            $cHires = array_fill(1, 12, 0); $cResignsCount = array_fill(1, 12, 0);
            foreach ($mRows as $m) {
                if (!empty($m['HIRE_MM']))   $cHires[(int)$m['HIRE_MM']]++;
                if (!empty($m['RETIRED_MM']) && $m['STAEMP'] == '9') $cResignsCount[(int)$m['RETIRED_MM']]++;
            }
            $tCount = $cTotal;
            for ($i = $currentMonth; $i >= 1; $i--) {
                $catMonthlyHC[$i-1] = $tCount;
                $tCount = $tCount - $cHires[$i] + $cResignsCount[$i];
            }
            for ($i = $currentMonth + 1; $i <= 12; $i++) $catMonthlyHC[$i-1] = $cTotal;
        } else {
            // Snapshot for Old Year for Category
            for ($m = 1; $m <= 12; $m++) {
                $mm = str_pad($m, 2, '0', STR_PAD_LEFT);
                $lastDay = date('t', mktime(0, 0, 0, $m, 1, $trendYear));
                $eom = "TO_DATE('$trendYear-$mm-$lastDay','YYYY-MM-DD')";
                $sSql = "SELECT COUNT(*) as cnt FROM (
                    SELECT sub2.* FROM (
                        SELECT sub1.* FROM (
                            SELECT 
                                NVL(te.dtereemp, te.dteempmt) as hire_date,
                                te.dteeffex, te.staemp, te.codcomp1,
                                CASE
                                    WHEN SUBSTR(tc.namcent5, 1, 2) = '10' THEN 'SAB'
                                    WHEN SUBSTR(tc.namcent5, 1, 2) = '11' THEN 'SAAB'
                                    WHEN SUBSTR(tc.namcent5, 1, 2) = '20' THEN 'SLAB'
                                    WHEN SUBSTR(tc.namcent5, 1, 2) = '21' THEN 'SRAB'
                                    WHEN SUBSTR(tc.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                                    WHEN SUBSTR(tc.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                                    WHEN SUBSTR(tc.namcent5, 1, 2) = '30' THEN 'SRDC'
                                    WHEN SUBSTR(tc.namcent5, 1, 2) = '40' THEN 'SATC'
                                    WHEN SUBSTR(tc.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                                    WHEN SUBSTR(tc.namcent5, 1, 3) = '800' THEN 'SAB'
                                    WHEN SUBSTR(tc.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                                    WHEN SUBSTR(tc.namcent5, 1, 2) = '90' THEN 'SC'
                                    WHEN SUBSTR(tc.namcent5, 1, 2) = '91' THEN 'SAB'
                                    ELSE te.codcomp1
                                END AS PlantNO,
                                CASE SUBSTR(te.codempid, 1, 2)
                                    WHEN '11' THEN 'SUB' WHEN '12' THEN 'SUB' WHEN '55' THEN 'SUB' WHEN '56' THEN 'SUB'
                                    WHEN 'A1' THEN 'PERM' WHEN 'A2' THEN 'PERM' WHEN 'A7' THEN 'PWC'
                                    WHEN 'B1' THEN 'PERM' WHEN 'B2' THEN 'PERM' WHEN 'B7' THEN 'PWC'
                                    WHEN 'DB' THEN 'PERM' WHEN 'FK' THEN 'SUB' WHEN 'H1' THEN 'PERM' WHEN 'H3' THEN 'PERM' WHEN 'H4' THEN 'PERM'
                                    WHEN 'JM' THEN 'PERM' WHEN 'K2' THEN 'SUB' WHEN 'K5' THEN 'SUB' WHEN 'KA' THEN 'SUB'
                                    WHEN 'L1' THEN 'PERM' WHEN 'L3' THEN 'PERM' WHEN 'L4' THEN 'PERM' WHEN 'L7' THEN 'PWC'
                                    WHEN 'MA' THEN 'SUB' WHEN 'PB' THEN 'SUB' WHEN 'PW' THEN 'SUB'
                                    WHEN 'R1' THEN 'PERM' WHEN 'R4' THEN 'PERM' WHEN 'R7' THEN 'PWC'
                                    WHEN 'RA' THEN 'PERM' WHEN 'RD' THEN 'PERM' WHEN 'RE' THEN 'PWC'
                                    WHEN 'SA' THEN 'PERM' WHEN 'SM' THEN 'SUB' WHEN 'SP' THEN 'SUB' WHEN 'ST' THEN 'SUB' WHEN 'SW' THEN 'SUB'
                                    WHEN 'T1' THEN 'PERM' WHEN 'T7' THEN 'PWC' WHEN 'TH' THEN 'SUB' WHEN 'TR' THEN 'SUB' WHEN 'TS' THEN 'SUB'
                                    WHEN 'Y5' THEN 'SUB' WHEN 'Y6' THEN 'SUB'
                                    ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = te.codempid AND ROWNUM = 1), 'OTHER')
                                END AS emp_category_by_prefix,
                                CASE WHEN SUBSTR(te.codpos, 2, 1) >= '4' THEN 'MANAGER' ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = te.typemp)) END AS type_name,
                                (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(tc.namcent5) AND ROWNUM = 1) AS func_name,
                                SUBSTR(tc.namcent4, INSTR(tc.namcent4, '_') + 1) AS dept,
                                te.namempt
                            FROM temploy1 te, tcenter tc
                            WHERE te.codcomp = tc.codcomp $compExcl
                        ) sub1
                    ) sub2
                    WHERE hire_date <= $eom AND (staemp < 9 OR (staemp = 9 AND dteeffex > $eom))
                ) t1 WHERE 1=1 $catWhere";
                $sRes = fetchAllAssoc($conn, $sSql, $catBinds);
                $catMonthlyHC[$m-1] = (int)($sRes[0]['CNT'] ?? 0);
            }
        }

        // Resignations for Category
        $rSql = "SELECT TO_CHAR(dteeffex, 'MM') as mm, COUNT(*) as qty FROM (
            SELECT sub2.* FROM (
                SELECT sub1.* FROM (
                    SELECT 
                        te.codempid, dteeffex, staemp, namempt, te.codcomp1,
                        CASE
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '10' THEN 'SAB'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '11' THEN 'SAAB'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '20' THEN 'SLAB'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '21' THEN 'SRAB'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '30' THEN 'SRDC'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '40' THEN 'SATC'
                            WHEN SUBSTR(tc.namcent5, 1, 2) IN ('50','60') THEN 'SDC'
                            WHEN SUBSTR(tc.namcent5, 1, 3) = '800' THEN 'SAB'
                            WHEN SUBSTR(tc.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '90' THEN 'SC'
                            WHEN SUBSTR(tc.namcent5, 1, 2) = '91' THEN 'SAB'
                            ELSE te.codcomp1
                        END AS PlantNO,
                        CASE SUBSTR(te.codempid, 1, 2)
                            WHEN '11' THEN 'SUB' WHEN '12' THEN 'SUB' WHEN '55' THEN 'SUB' WHEN '56' THEN 'SUB'
                            WHEN 'A1' THEN 'PERM' WHEN 'A2' THEN 'PERM' WHEN 'A7' THEN 'PWC'
                            WHEN 'B1' THEN 'PERM' WHEN 'B2' THEN 'PERM' WHEN 'B7' THEN 'PWC'
                            WHEN 'DB' THEN 'PERM' WHEN 'FK' THEN 'SUB' WHEN 'H1' THEN 'PERM' WHEN 'H3' THEN 'PERM' WHEN 'H4' THEN 'PERM'
                            WHEN 'JM' THEN 'PERM' WHEN 'K2' THEN 'SUB' WHEN 'K5' THEN 'SUB' WHEN 'KA' THEN 'SUB'
                            WHEN 'L1' THEN 'PERM' WHEN 'L3' THEN 'PERM' WHEN 'L4' THEN 'PERM' WHEN 'L7' THEN 'PWC'
                            WHEN 'MA' THEN 'SUB' WHEN 'PB' THEN 'SUB' WHEN 'PW' THEN 'SUB'
                            WHEN 'R1' THEN 'PERM' WHEN 'R4' THEN 'PERM' WHEN 'R7' THEN 'PWC'
                            WHEN 'RA' THEN 'PERM' WHEN 'RD' THEN 'PERM' WHEN 'RE' THEN 'PWC'
                            WHEN 'SA' THEN 'PERM' WHEN 'SM' THEN 'SUB' WHEN 'SP' THEN 'SUB' WHEN 'ST' THEN 'SUB' WHEN 'SW' THEN 'SUB'
                            WHEN 'T1' THEN 'PERM' WHEN 'T7' THEN 'PWC' WHEN 'TH' THEN 'SUB' WHEN 'TR' THEN 'SUB' WHEN 'TS' THEN 'SUB'
                            WHEN 'Y5' THEN 'SUB' WHEN 'Y6' THEN 'SUB'
                            ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = te.codempid AND ROWNUM = 1), 'OTHER')
                        END AS emp_category_by_prefix,
                        CASE WHEN SUBSTR(te.codpos, 2, 1) >= '4' THEN 'MANAGER' ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = te.typemp)) END AS type_name,
                        (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(tc.namcent5) AND ROWNUM = 1) AS func_name,
                        SUBSTR(tc.namcent4, INSTR(tc.namcent4, '_') + 1) AS dept
                    FROM temploy1 te, tcenter tc
                    WHERE te.codcomp = tc.codcomp $compExcl
                ) sub1
            ) sub2
            WHERE staemp = 9 AND TO_CHAR(dteeffex, 'YYYY') = '$trendYear'
        ) t1 WHERE 1=1 $catWhere $turnoverReasonExclSql GROUP BY TO_CHAR(dteeffex, 'MM')";
        $rRes = fetchAllAssoc($conn, $rSql, $catBinds);
        foreach ($rRes as $r) $catMonthlyRes[(int)$r['MM']] = (int)$r['QTY'];

        // Calculate Category Rate
        $catData = [];
        for ($i = 0; $i < 12; $i++) {
            $hc = $catMonthlyHC[$i];
            $res = $catMonthlyRes[$i+1];
            $rate = ($hc > 0) ? round(($res / $hc) * 100, 2) : 0;
            $catData[] = ['month' => $monthsEng[$i], 'rate' => $rate, 'resignations' => $res];
        }
        $categoryTurnover[$cat] = $catData;
    }

    echo json_encode([
        'year' => $trendYear,
        'turnoverData' => $turnoverData,
        'reasonData' => $reasonData,
        'categoryTurnover' => $categoryTurnover,
        'debug' => [
            'Resignation SQL' => $resSql,
            'Reason SQL' => $reasonSql,
            'Binds' => $binds
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
