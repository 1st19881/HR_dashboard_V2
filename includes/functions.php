<?php
// includes/functions.php
// รวมฟังก์ชันช่วยเหลือต่างๆ (Procedural Pattern)

/**
 * ฟังก์ชันสำหรับ Query ข้อมูลจาก Oracle และคืนค่าเป็น Array
 */
function fetchAllAssoc($conn, $sql, $binds = []) {
    $stid = oci_parse($conn, $sql);
    if (!$stid) {
        $e = oci_error($conn);
        throw new Exception("SQL Parse Error: " . $e['message']);
    }
    
    foreach ($binds as $key => $val) {
        oci_bind_by_name($stid, $key, $binds[$key]);
    }
    
    $r = oci_execute($stid);
    if (!$r) {
        $e = oci_error($stid);
        throw new Exception("SQL Execute Error: " . $e['message'] . " (SQL: $sql)");
    }
    
    $results = [];
    while ($row = oci_fetch_assoc($stid)) {
        $results[] = $row;
    }
    oci_free_statement($stid);
    return $results;
}

/**
 * ฟังก์ชันจัดรูปแบบตัวเลข
 */
function formatNumber($number) {
    return number_format($number, 0, '.', ',');
}

/**
 * ฟังก์ชันคำนวณเปอร์เซ็นต์
 */
function calculatePercentage($part, $total) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, 2);
}
?>
