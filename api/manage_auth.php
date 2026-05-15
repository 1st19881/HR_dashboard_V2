<?php
// api/manage_auth.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    $conn = getDbConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $action = $_GET['action'] ?? 'list';

    // ตรวจสอบว่ามี Table หรือไม่ (ลองทั้งแบบมี Prefix และไม่มี)
    $tableName = "HRMSIT.HRMS_AUTH";
    
    switch ($action) {
        case 'list':
            // ลองดึงแบบมี Prefix ก่อน ถ้าพังค่อยลองแบบไม่มี
            $sql = "SELECT AUT_ID, CODEMPID, AUT_NAME, AUT_LEVEL, AUT_ACTIVE, AUT_PRIVILEGE, AUT_TYPE, 
                           TO_CHAR(CREATE_DATE, 'DD/MM/YYYY HH24:MI') as CREATE_DATE_FMT
                    FROM $tableName 
                    WHERE AUT_PRIVILEGE = 'hr_dashboard'
                    ORDER BY CREATE_DATE DESC";
            
            $stid = @oci_parse($conn, $sql);
            if (!$stid || !@oci_execute($stid)) {
                // ถ้าพัง ลองแบบไม่มี Prefix
                $tableName = "HRMS_AUTH";
                $sql = "SELECT AUT_ID, CODEMPID, AUT_NAME, AUT_LEVEL, AUT_ACTIVE, AUT_PRIVILEGE, AUT_TYPE, 
                               TO_CHAR(CREATE_DATE, 'DD/MM/YYYY HH24:MI') as CREATE_DATE_FMT
                        FROM $tableName 
                        WHERE AUT_PRIVILEGE = 'hr_dashboard'
                        ORDER BY CREATE_DATE DESC";
                $stid = oci_parse($conn, $sql);
                if (!oci_execute($stid)) {
                    $e = oci_error($stid);
                    throw new Exception("ไม่พบตาราง HRMS_AUTH ในระบบ (Error: " . $e['message'] . ")");
                }
            }

            $data = [];
            while ($row = oci_fetch_array($stid, 1 + 4)) {
                $data[] = $row;
            }
            oci_free_statement($stid);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'get_emp':
            $codempid = strtoupper($_GET['codempid'] ?? '');
            if (empty($codempid)) throw new Exception('Missing Employee ID');
            
            $sql = "SELECT namempt FROM temploy1 WHERE UPPER(codempid) = :codempid AND ROWNUM = 1";
            $data = fetchAllAssoc($conn, $sql, [':codempid' => $codempid]);
            if (!empty($data)) {
                echo json_encode(['status' => 'success', 'name' => $data[0]['NAMEMPT']]);
            } else {
                throw new Exception('ไม่พบรายชื่อพนักงานรหัส: ' . $codempid);
            }
            break;

        case 'save':
            $aut_id        = $_POST['aut_id'] ?? '';
            $codempid      = strtoupper($_POST['codempid'] ?? '');
            $aut_name      = $_POST['aut_name'] ?? '';
            $aut_level     = (int)($_POST['aut_level'] ?? 9);
            $aut_active    = $_POST['aut_active'] ?? 'N';
            $aut_privilege = $_POST['aut_privilege'] ?? 'hr_dashboard';
            $aut_type      = $_POST['aut_type'] ?? 'User';

            if (empty($codempid)) throw new Exception('กรุณากรอกรหัสพนักงาน');

            // ตรวจสอบชื่อตารางที่จะใช้
            $checkSql = "SELECT count(*) as CNT FROM ALL_TABLES WHERE OWNER = 'HRMSIT' AND TABLE_NAME = 'HRMS_AUTH'";
            $checkRes = fetchAllAssoc($conn, $checkSql);
            $finalTable = ($checkRes[0]['CNT'] > 0) ? "HRMSIT.HRMS_AUTH" : "HRMS_AUTH";

            if (empty($aut_id)) {
                $sql = "INSERT INTO $finalTable (AUT_ID, CODEMPID, AUT_NAME, AUT_LEVEL, AUT_ACTIVE, AUT_PRIVILEGE, AUT_TYPE, CREATE_DATE)
                        VALUES (SYS_GUID(), :codempid, :aut_name, :aut_level, :aut_active, :aut_privilege, :aut_type, SYSDATE)";
            } else {
                $sql = "UPDATE $finalTable 
                        SET CODEMPID = :codempid, AUT_NAME = :aut_name, AUT_LEVEL = :aut_level, 
                            AUT_ACTIVE = :aut_active, AUT_PRIVILEGE = :aut_privilege, AUT_TYPE = :aut_type
                        WHERE AUT_ID = :aut_id";
            }

            $stid = oci_parse($conn, $sql);
            oci_bind_by_name($stid, ':codempid', $codempid);
            oci_bind_by_name($stid, ':aut_name', $aut_name);
            oci_bind_by_name($stid, ':aut_level', $aut_level);
            oci_bind_by_name($stid, ':aut_active', $aut_active);
            oci_bind_by_name($stid, ':aut_privilege', $aut_privilege);
            oci_bind_by_name($stid, ':aut_type', $aut_type);
            if (!empty($aut_id)) oci_bind_by_name($stid, ':aut_id', $aut_id);

            if (!oci_execute($stid)) {
                $e = oci_error($stid);
                throw new Exception($e['message']);
            }
            echo json_encode(['status' => 'success', 'message' => 'บันทึกสำเร็จ']);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

if (isset($conn) && $conn) oci_close($conn);
?>
