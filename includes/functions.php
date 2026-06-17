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

/**
 * ฟังก์ชันสร้าง IN clause จาก comma-separated string (สำหรับ multi-select filter)
 * @param string $csvValues - ค่า comma-separated เช่น "SAAB,SAB,SAM"
 * @param string $column - ชื่อ column ใน SQL เช่น "PlantNO"
 * @param string $bindPrefix - prefix สำหรับ bind variable เช่น "plant" 
 * @param array &$binds - array ของ bind variables (pass by reference)
 * @param bool $upperCase - ถ้า true จะ wrap column ด้วย UPPER()
 * @return string - SQL fragment เช่น " AND PlantNO IN (:plant_0, :plant_1, :plant_2)"
 */
function buildMultiFilter($csvValues, $column, $bindPrefix, &$binds, $upperCase = false) {
    if (empty($csvValues)) return '';
    
    $values = array_filter(array_map('trim', explode(',', $csvValues)));
    if (empty($values)) return '';
    
    // ถ้ามีค่าเดียว ใช้ = แทน IN (เร็วกว่า)
    if (count($values) === 1) {
        $bindKey = ":{$bindPrefix}";
        $binds[$bindKey] = $values[0];
        if ($upperCase) {
            return " AND UPPER({$column}) = UPPER({$bindKey})";
        }
        return " AND {$column} = {$bindKey}";
    }
    
    // หลายค่า → สร้าง IN clause
    $placeholders = [];
    foreach ($values as $i => $val) {
        $bindKey = ":{$bindPrefix}_{$i}";
        $binds[$bindKey] = $val;
        $placeholders[] = $bindKey;
    }
    $inList = implode(', ', $placeholders);
    if ($upperCase) {
        return " AND UPPER({$column}) IN (" . implode(', ', array_map(function($p) { return "UPPER($p)"; }, $placeholders)) . ")";
    }
    return " AND {$column} IN ({$inList})";
}
?>
