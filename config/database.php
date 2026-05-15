<?php
// config/database.php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล Oracle

// --- ชุดที่ 1: HRMS Database ---
$HrmsUser = "hrmsit";
$HrmsPWD  = "ithrms";
$HrmsDB   = "HRMS";
$HrmsLang = "AL32UTF8";

// --- ชุดที่ 2: SAG Database ---
$SagUser = "web";
$SagPWD  = "web123";
$SagDB   = "SAGDB";
$SagLang = "WE8DEC";

putenv("NLS_LANG=AMERICAN_AMERICA.AL32UTF8");

// ฟังก์ชันเชื่อมต่อฐานข้อมูล HRMS
function getDbConnection() {
    global $HrmsUser, $HrmsPWD, $HrmsDB, $HrmsLang;
    $conn = @oci_connect($HrmsUser, $HrmsPWD, $HrmsDB, $HrmsLang);
    if (!$conn) {
        $e = oci_error();
        return false;
    }
    return $conn;
}

// ฟังก์ชันเชื่อมต่อฐานข้อมูล SAG
function getSagDbConnection() {
    global $SagUser, $SagPWD, $SagDB, $SagLang;
    $conn = @oci_connect($SagUser, $SagPWD, $SagDB, $SagLang);
    if (!$conn) {
        $e = oci_error();
        return false;
    }
    return $conn;
}
?>
