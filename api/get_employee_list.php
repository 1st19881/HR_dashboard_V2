<?php
// api/get_employee_list.php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';
require_once '../includes/functions.php';

$conn = getDbConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed', 'data' => []]);
    exit;
}

// รับค่า parameter สำหรับ Filter
$plant        = $_GET['plant']        ?? '';
$emp_type     = $_GET['emp_type']     ?? '';
$emp_category = $_GET['emp_category'] ?? '';
$function     = $_GET['function']     ?? '';
$dept         = $_GET['dept']         ?? '';

// ============================================================
// SQL 3 ชั้น:
//  ชั้น 3 (นอกสุด) — WHERE กรอง emp_category_full, PlantNO
//  ชั้น 2 — คำนวณ emp_category_full (SUB Thai / SUB Myanmar)
//  ชั้น 1 (ใน)      — ดึงข้อมูลพนักงาน + CASE WHEN prefix
// ============================================================
$sql = "SELECT *
FROM (
    SELECT t1.*,
           CASE
               WHEN emp_category_by_prefix = 'SUB' AND (codnatt = 'ไทย'   OR codnatt = 'Thai')               THEN 'SUB Thai'
               WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%พม่า%'    OR codnatt LIKE '%Myanmar%') THEN 'SUB Myanmar'
               WHEN emp_category_by_prefix = 'SUB' AND (codnatt LIKE '%กัมพูชา%' OR codnatt LIKE '%Cambodia%') THEN 'SUB Cambodia'
               WHEN emp_category_by_prefix = 'SUB' THEN 'SUB'
               ELSE CASE WHEN emp_category_by_prefix IN ('PERM', 'PWC') THEN emp_category_by_prefix ELSE emp_category_by_prefix END
           END AS emp_category_full
    FROM (
        SELECT
            CASE SUBSTR(t2.namcent5, 1, 2)
                WHEN '10' THEN 'SAB'    WHEN '11' THEN 'SAAB'
                WHEN '20' THEN 'SLAB'   WHEN '21' THEN 'SRAB'
                WHEN '22' THEN 'SLAB-2' WHEN '23' THEN 'SLAB-3'
                WHEN '30' THEN 'SRDC'   WHEN '40' THEN 'SATC'
                WHEN '60' THEN 'SDC'    WHEN '80' THEN 'SAM'
                WHEN '81' THEN 'SAM'    WHEN '91' THEN 'SAB'
                ELSE t1.codcomp1
            END AS plantNO1,
            CASE
                WHEN SUBSTR(t2.namcent5, 1, 2) = '10' THEN 'SAB'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '11' THEN 'SAAB'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '20' THEN 'SLAB'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '21' THEN 'SRAB'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '22' THEN 'SLAB-2'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '23' THEN 'SLAB-3'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '30' THEN 'SRDC'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '40' THEN 'SATC'
                WHEN SUBSTR(t2.namcent5, 1, 2) IN ('50', '60') THEN 'SDC'
                WHEN SUBSTR(t2.namcent5, 1, 3) = '800' THEN 'SAB'
                WHEN SUBSTR(t2.namcent5, 1, 3) BETWEEN '801' AND '890' THEN 'SAM'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '90' THEN 'SC'
                WHEN SUBSTR(t2.namcent5, 1, 2) = '91' THEN 'SAB'
                ELSE t2.codcom1
            END AS PlantNO,
            t1.codcomp1 AS company,
            (SELECT namcomt FROM tcompny WHERE codcompy = t1.codcomp1) AS company_name,
            (SELECT UPPER(TRIM(ORG_SHORT)) FROM HRMS_MAIN_DEPART WHERE TRIM(COST_CENTER) = TRIM(t2.namcent5) AND ROWNUM = 1) AS func_name,
            (SELECT namcenttha FROM tcenter WHERE codcomp = SUBSTR(t1.codcomp,1,12) || '000000000')       AS dept_name,
            (SELECT namcenttha FROM tcenter WHERE codcomp = SUBSTR(t1.codcomp,1,15) || '000000')          AS sec_name,
            t2.namcenttha AS cost_name,
            (SELECT numpasid FROM temploy2 WHERE codempid = t1.codempid) AS numpasid,
            (SELECT numprmid FROM temploy2 WHERE codempid = t1.codempid) AS numprmid,
            t1.codcomp,
            t1.codcomp1||'-'||t1.codcomp2||'-'||t1.codcomp3||'-'||
            t1.codcomp4||'-'||t1.codcomp5||'-'||t1.codcomp6||'-'||t1.codcomp7 AS code_comp,
            SUBSTR(t2.namcent4, INSTR(t2.namcent4, '_') + 1) AS dept,
            t2.namcent5 AS costct,
            t1.codempid,
            SUBSTR(t1.codempid, 1, 2) AS emp_prefix,
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
                WHEN 'SM' THEN 'SUB'  WHEN 'SP' THEN 'SUB'  WHEN 'ST' THEN 'SUB' WHEN 'SW' THEN 'SUB'
                WHEN 'T1' THEN 'PERM' WHEN 'T7' THEN 'PWC'
                WHEN 'TH' THEN 'SUB'  WHEN 'TR' THEN 'SUB'  WHEN 'TS' THEN 'SUB'
                WHEN 'Y5' THEN 'SUB'  WHEN 'Y6' THEN 'SUB'
                ELSE NVL((SELECT ex.ASSIGNED_TYPE FROM HRMS_TYPE_EXCEPTIONS ex WHERE ex.EMP_ID = t1.codempid AND ROWNUM = 1), 'OTHER')
            END AS emp_category_by_prefix,
            t1.namempt,
            t1.namempe,
            t1.codpos,
            SUBSTR(t1.codpos, 2, 1) AS band,
            t1.numlvl AS grade,
            (SELECT nampost FROM tpostn WHERE codpos = t1.codpos) AS pos_name,
            (SELECT nampose FROM tpostn WHERE codpos = t1.codpos) AS pos_namee,
            (SELECT descodt FROM temploy2 e2, tcodnatn cn
             WHERE e2.codnatnl = cn.codcodec AND e2.codempid = t1.codempid) AS codnatt,
            (SELECT descodt FROM hrms.tcodempl WHERE codcodec = t1.codempmt) AS descodt,
            t1.typpayroll,
            (SELECT descodt FROM hrms.tcodtypy WHERE codcodec = t1.typpayroll) AS py_type,
            t1.typemp,
            CASE 
                WHEN SUBSTR(t1.codpos, 2, 1) >= '4' THEN 'MANAGER'
                ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = t1.typemp))
            END AS type_name,
            t1.codjob,
            (SELECT namjobt FROM hrms.tjobcode WHERE codjob = t1.codjob) AS job_name,
            t1.dteempmt AS hire_date,
            hrms.GET_AGE(t1.dteempmt, SYSDATE) AS working_year,
            t1.dteempdb AS birth_date,
            hrms.GET_AGE(t1.dteempdb, SYSDATE) AS age,
            DECODE(t1.codsex, 'F', 'หญิง', 'ชาย') AS codsex,
            t3.descode, t3.cod_dgee, t3.minor, t3.major, t3.instt
        FROM
            temploy1 t1
            JOIN tcenter t2 ON t1.codcomp = t2.codcomp
            LEFT JOIN (SELECT * FROM v_emp_education WHERE rn = max_rn) t3
                ON t1.codempid = t3.codempid
        WHERE t1.staemp   < 9
          AND t1.codpos   < '0800'
          AND t1.namempt NOT LIKE '%จุฬางกูร%'
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

// กรองตาม Plant (multi-select)
$sql .= buildMultiFilter($plant, 'PlantNO', 'plant', $binds);

// กรองตาม Employee Type (multi-select)
$sql .= buildMultiFilter($emp_type, "CASE WHEN type_name IN ('ADMIN','DIRECT','INDIRECT','MANAGER') THEN type_name ELSE 'OTHER' END", 'emp_type', $binds);

// กรองตาม Employee Category (multi-select)
$sql .= buildMultiFilter($emp_category, 'emp_category_full', 'emp_category', $binds);

// กรองตาม Function (multi-select)
$sql .= buildMultiFilter($function, 'func_name', 'func', $binds);

// กรองตาม Department (multi-select)
$sql .= buildMultiFilter($dept, 'dept', 'dept', $binds, true);

$sql .= " ORDER BY band DESC";

try {
    $results = fetchAllAssoc($conn, $sql, $binds);
    echo json_encode([
        'data' => $results, 
        'debug_sql' => $sql,
        'binds' => json_encode($binds)
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'data' => [], 'debug_sql' => $sql], JSON_UNESCAPED_UNICODE);
}
