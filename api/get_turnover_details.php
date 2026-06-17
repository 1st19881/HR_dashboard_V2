<?php
// api/get_turnover_details.php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';
require_once '../includes/functions.php';

$conn = getDbConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// รับค่า parameter
$month_name   = $_GET['month']        ?? ''; 
$year         = $_GET['year']         ?? date('Y');
$plant        = $_GET['plant']        ?? '';
$emp_type     = $_GET['emp_type']     ?? '';
$emp_category = $_GET['emp_category'] ?? '';
$function     = $_GET['function']     ?? '';
$dept         = $_GET['dept']         ?? '';

// เงื่อนไขยกเว้นสำหรับ Trend/Turnover
$trendExcl = " AND t1.namempt NOT LIKE '%จุฬางกูร%'";

// Reason Codes ที่ไม่นำมาคำนวณ Turnover Rate (CODCODEC จาก TCODEXEM)
$turnoverExclReasons = ['11', '12', '15', '16', '17'];
$reasonPlaceholders = implode(',', array_map(function($v) { return "'" . $v . "'"; }, $turnoverExclReasons));
$turnoverReasonExclSql = " AND NOT EXISTS (
    SELECT 1 FROM HRMS.TTEXEMPT tex
    WHERE tex.CODEMPID = t1.codempid
      AND tex.CODEXEMP IN ($reasonPlaceholders)
      AND tex.rowid = (SELECT MAX(rowid) FROM HRMS.TTEXEMPT WHERE CODEMPID = t1.codempid)
)";

// Build Where Clause
$where = " WHERE 1=1";
$binds = [];

$where .= buildMultiFilter($plant,        't1.PlantNO',           'plant',        $binds);
$where .= buildMultiFilter($emp_type,     "CASE WHEN t1.type_name IN ('ADMIN','DIRECT','INDIRECT','MANAGER') THEN t1.type_name ELSE 'OTHER' END",         'emp_type',     $binds);
$where .= buildMultiFilter($emp_category, 't1.emp_category_full', 'emp_category', $binds);
$where .= buildMultiFilter($function,     't1.func_name',         'func',         $binds);
$where .= buildMultiFilter($dept,         't1.dept',              'dept',         $binds, true);

// แปลงชื่อเดือนเป็นตัวเลข (ถ้ามี)
if (!empty($month_name) && $month_name !== 'All') {
    $monthsMap = [
        'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
        'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
        'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'
    ];
    $mm = $monthsMap[$month_name] ?? '';
    if ($mm) {
        $year_mm = $year . '-' . $mm;
        $where .= " AND TO_CHAR(t1.dteeffex, 'YYYY-MM') = :year_mm";
        $binds[':year_mm'] = $year_mm;
    } else {
        $where .= " AND TO_CHAR(t1.dteeffex, 'YYYY') = :year";
        $binds[':year'] = $year;
    }
} else {
    $where .= " AND TO_CHAR(t1.dteeffex, 'YYYY') = :year";
    $binds[':year'] = $year;
}

$finalWhere = $where . " AND t1.staemp = 9 " . $trendExcl . $turnoverReasonExclSql;

$sql = "
SELECT * FROM (
    SELECT 
        m.PlantNO, m.codempid, m.namempt, m.emp_category_full, m.type_name, m.func_name, m.dept, 
        m.dteeffex, m.staemp, m.codcomp1,
        TO_CHAR(m.dteeffex, 'DD/MM/YYYY') as exit_date,
        (SELECT nampost FROM hrms.tpostn WHERE codpos = m.codpos) as pos_name,
        (
            SELECT TCODEXEM.DESCODT
            FROM HRMS.TTEXEMPT, HRMS.TCODEXEM
            WHERE TTEXEMPT.CODEXEMP = TCODEXEM.CODCODEC
              AND TTEXEMPT.CODEMPID = m.CODEMPID
              AND TTEXEMPT.rowid = (
                    SELECT MAX(rowid)
                    FROM HRMS.TTEXEMPT
                    WHERE CODEMPID = m.CODEMPID
              )
        ) as resign_reason
    FROM (
        SELECT t1.*,
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
                    ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = t1.codempid AND ROWNUM = 1), 'OTHER')
                END AS emp_category_by_prefix,
                (SELECT descodt FROM temploy2 e2, tcodnatn cn
                 WHERE e2.codnatnl = cn.codcodec AND e2.codempid = t1.codempid) AS codnatt,
                CASE 
                    WHEN SUBSTR(t1.codpos, 2, 1) >= '4' THEN 'MANAGER'
                    ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = t1.typemp))
                END AS type_name,
                (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(t2.namcent5) AND ROWNUM = 1) AS func_name,
                SUBSTR(t2.namcent4, INSTR(t2.namcent4, '_') + 1) AS dept,
                t1.namempt, t1.codcomp1, t1.staemp, t1.dteeffex, t1.codpos, t1.codempid
            FROM temploy1 t1, tcenter t2
            WHERE t1.codcomp = t2.codcomp
              AND t1.codcomp1 NOT IN (
                  'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
                  'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
                  'EXC','SGV','SON'
              )
        ) t1
    ) m
) t1 
$finalWhere 
ORDER BY t1.dteeffex DESC";

try {
    $results = fetchAllAssoc($conn, $sql, $binds);
    echo json_encode([
        'data' => $results ?: [],
        'debug_sql' => $sql,
        'binds' => $binds
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'debug_sql' => $sql,
        'binds' => $binds
    ], JSON_UNESCAPED_UNICODE);
}
?>
