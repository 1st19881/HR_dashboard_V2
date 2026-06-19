<?php
// api/get_employee_categories.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
require_once '../includes/functions.php';

$conn = getDbConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$plant    = $_GET['plant']    ?? '';
$emp_type = $_GET['emp_type'] ?? '';
$function = $_GET['function'] ?? '';

$sql = "
SELECT DISTINCT emp_category_full
FROM (
    SELECT
        CASE
            WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย' OR codnatt = 'Thai')              THEN 'SUB Thai'
            WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%' OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
            WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
            WHEN emp_category_by_prefix = 'SUB'                                                        THEN 'SUB'
            ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE emp_category_by_prefix END
        END AS emp_category_full,
        PlantNO,
        type_name,
        func_name
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
            (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(t2.namcent5) AND ROWNUM = 1) AS func_name
        FROM temploy1 t1, tcenter t2
        WHERE t1.codcomp = t2.codcomp
          AND t1.staemp < 9
          AND t1.codcomp1 NOT IN (
              'AAA','AAS','FUJ','KTK','HHH','MMM','NJN','SWC','TJS',
              'XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE',
              'EXC','SGV','SON','JMX','SC1'
          )
          AND t1.typemp != 'IU'
    ) t1
) t1
WHERE 1=1";

$binds = [];
$sql .= buildMultiFilter($plant,    'PlantNO',   'plant',    $binds);
$sql .= buildMultiFilter($emp_type, "CASE WHEN type_name IN ('ADMIN','DIRECT','INDIRECT','MANAGER') THEN type_name ELSE 'OTHER' END", 'emp_type', $binds);
$sql .= buildMultiFilter($function, 'func_name', 'func',     $binds);

$sql .= " ORDER BY emp_category_full ASC";

try {
    $results = fetchAllAssoc($conn, $sql, $binds);
    $cleaned = [];
    $seen = [];
    foreach ($results as $r) {
        $val = isset($r['EMP_CATEGORY_FULL']) ? trim($r['EMP_CATEGORY_FULL']) : '';
        if ($val !== '' && !isset($seen[$val])) {
            $seen[$val] = true;
            $cleaned[] = ['EMP_CATEGORY_FULL' => $val];
        }
    }
    echo json_encode($cleaned, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
