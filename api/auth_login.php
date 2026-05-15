<?php
// api/auth_login.php
header('Content-Type: application/json; charset=utf-8');

// ปิดการพ่น HTML error ออกไปตรงๆ ให้เก็บไว้ใน Buffer แทน
ob_start();
session_start();

// ตั้งค่าให้แสดง error แต่จะดักเอามาตอบเป็น JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // ปิดไม่ให้พ่น HTML ออกไป

try {
    if (!function_exists('oci_connect')) {
        throw new Exception('PHP OCI8 extension is not enabled on this server');
    }

    $timeout = 900;
    ini_set("session.gc_maxlifetime", $timeout);

    $SagUser = "web";
    $SagPWD = "web123";
    $SagDB = "SAGDB"; 
    $SagLang = "WE8DEC";

    $username = strtoupper($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        throw new Exception('กรุณากรอก Username และ Password');
    }

    // เชื่อมต่อ Oracle
    $conn = @oci_connect($SagUser, $SagPWD, $SagDB, $SagLang);

    if (!$conn) {
        $e = oci_error();
        throw new Exception('Database Connection Error: ' . ($e['message'] ?? 'Unknown Error'));
    }

    $sql = "SELECT * FROM intra.users WHERE upper(USERS_USERNAME) = :u_name AND USERS_STATUS = '1'";
    $rs = oci_parse($conn, $sql);
    oci_bind_by_name($rs, ":u_name", $username);
    
    if (!@oci_execute($rs)) {
        $e = oci_error($rs);
        throw new Exception('Query Execution Error: ' . $e['message']);
    }

    // ใช้เลข 1 + 4 แทน OCI_ASSOC + OCI_RETURN_NULLS เผื่อ Constant ไม่ถูกโหลด
    if (($Row = oci_fetch_array($rs, 1 + 4)) != false) {
        if ($Row['USERS_PASSWORD'] == $password) {
            $userCode = $Row['USERS_EMPCODE'];

            // --- ตรวจสอบสิทธิ์เพิ่มเติมในตาราง HRMSIT.HRMS_AUTH ---
            require_once '../config/database.php';
            $h_conn = getDbConnection();
            if (!$h_conn) {
                throw new Exception('ไม่สามารถเชื่อมต่อฐานข้อมูล HRMS เพื่อตรวจสอบสิทธิ์ได้');
            }

            $authSql = "SELECT AUT_LEVEL, AUT_TYPE, AUT_NAME 
                        FROM HRMSIT.HRMS_AUTH 
                        WHERE CODEMPID = :codempid 
                        AND AUT_ACTIVE = 'Y' 
                        AND (UPPER(AUT_PRIVILEGE) = 'HR_DASHBOARD' OR UPPER(AUT_PRIVILEGE) = 'HR_DASHBORAD')";
            $h_rs = oci_parse($h_conn, $authSql);
            oci_bind_by_name($h_rs, ":codempid", $userCode);
            oci_execute($h_rs);
            
            $authData = oci_fetch_array($h_rs, 1);
            if (!$authData) {
                throw new Exception('คุณยังไม่มีสิทธิ์เข้าใช้งานระบบ HR Dashboard กรุณาติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์');
            }
            
            // เก็บชื่อและระดับสิทธิ์ลง Session
            $_SESSION['aut_level'] = (int)$authData['AUT_LEVEL'];
            $_SESSION['aut_type']  = $authData['AUT_TYPE'];
            $_SESSION['aut_name']  = $authData['AUT_NAME'];

            $perm = array();
            $perm['SSID'] = session_id();
            $perm['VSID'] = md5(time() . rand(0, 999));
            $perm['UserID'] = $Row['USERS_ID'];
            $perm['User_Code'] = $Row['USERS_EMPCODE'];
            
            $fNameTh = $Row['USERS_FNAMETH'] ?? '';
            $lNameTh = $Row['USERS_LNAMETH'] ?? '';
            $fNameEn = $Row['USERS_FNAME'] ?? '';
            $lNameEn = $Row['USERS_LNAME'] ?? '';
            
            $fullnameTh = trim($fNameTh . ' ' . $lNameTh);
            $fullnameEn = trim($fNameEn . ' ' . $lNameEn);
            $perm['Fullname'] = !empty($fullnameTh) ? $fullnameTh : $fullnameEn;
            
            $perm['Status'] = $Row['USERS_GROUP'];
            $perm['Usersite'] = $Row['USERS_SITEID'];
            $perm['CodComp'] = $Row['USERS_CODECOMP'];
            $perm['Department'] = $Row['USERS_DEPARTMENT'];
            $perm['CostCenter'] = $Row['USERS_COSTCENTER'];
            $perm['Position'] = substr("0000" . $Row['USERS_POSITION'], -4);

            $_SESSION['user_id'] = $Row['USERS_ID'];
            $_SESSION['user_code'] = $Row["USERS_EMPCODE"];
            $_SESSION['user_name'] = $fNameTh;
            $_SESSION['codcomp'] = $Row["USERS_CODECOMP"];
            $_SESSION['department_code'] = $Row["USERS_DEPARTMENT"];
            $_SESSION['position'] = str_pad($Row["USERS_POSITION"], 4, "0", STR_PAD_LEFT);
            $_SESSION['cost_center'] = $Row["USERS_COSTCENTER"];
            $_SESSION['dept_code'] = $Row["USERS_DEPARTMENT"];

            $ArrPlantNo = array(
                "10" => "1100", "11" => "1101",
                "20" => "1200", "21" => "1201", "22" => "1202", "23" => "1203", 
                "30" => "1300", "40" => "1400"
            );
            $S_inx = substr($Row["USERS_COSTCENTER"], 0, 2);
            $S_Plant_No = isset($ArrPlantNo[$S_inx]) ? $ArrPlantNo[$S_inx] : "1100";
            
            $_SESSION['plant_no'] = $S_Plant_No;
            $perm['plant_no'] = $S_Plant_No;

            if (isset($Row['USERS_MENUPER']) && $Row['USERS_MENUPER'] != 'null' && !empty($Row['USERS_MENUPER'])) {
                $perm['UserMenuPerm'] = json_decode($Row['USERS_MENUPER'], true);
            } else {
                $perm['UserMenuPerm'] = array();
            }

            $_SESSION['Sesession_User'] = $perm;

            ob_clean();
            echo json_encode(['status' => 'success']);
        } else {
            throw new Exception('Password ไม่ถูกต้อง');
        }
    } else {
        throw new Exception('ไม่พบรายชื่อผู้ใช้งานหรือสถานะไม่ปกติ');
    }

    @oci_close($conn);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}
?>
