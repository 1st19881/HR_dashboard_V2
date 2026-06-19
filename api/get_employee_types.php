<?php
// api/get_employee_types.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
require_once '../includes/functions.php';

$conn = getDbConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$plant = $_GET['plant'] ?? '';

$sql = "
SELECT DISTINCT CASE WHEN type_name IN ('ADMIN','DIRECT','INDIRECT','MANAGER') THEN type_name ELSE 'OTHER' END AS type_name
FROM (
    SELECT
        CASE 
            WHEN SUBSTR(t1.codpos, 2, 1) >= '4' THEN 'MANAGER'
            ELSE UPPER((SELECT descodt FROM hrms.tcodcatg WHERE codcodec = t1.typemp))
        END AS type_name,
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
        END AS PlantNO
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
WHERE 1=1";

$binds = [];
$sql .= buildMultiFilter($plant, 'PlantNO', 'plant', $binds);

$sql .= " ORDER BY type_name ASC";

try {
    $results = fetchAllAssoc($conn, $sql, $binds);
    echo json_encode($results, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
