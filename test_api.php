<?php
// test_api.php — ทดสอบ Trend Data สำหรับ Plant SAB ปี 2026
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once 'config/database.php';
require_once 'includes/functions.php';

$conn = getDbConnection();
if (!$conn) { die('❌ Database connection failed'); }

$plant = $_GET['plant'] ?? 'SAB';
$currentYear = date('Y');

echo "<h2>📊 Headcount Trend Test — Plant: <strong>{$plant}</strong> | Year: {$currentYear}</h2>";

// Plant Mapping — ใช้ SUBSTR(t2.namcent5, 1, 2) จาก tcenter
$plantMap = [
    'SAB'  => "('10','80','91')",
    'SAAB' => "('11')",
    'SLAB' => "('20','22','23')",
    'SRAB' => "('21')",
    'SRDC' => "('30')",
    'SATC' => "('40')",
    'SDC'  => "('50','60')"
];

$plantFilter = " AND t1.namempt NOT LIKE '%จุฬางกูร%' 
                  AND t1.codcomp1 NOT IN ('AAA','AAS','FUJ','JMP','JMX','KTK','HHH','MMM','NJN','SWC','TJS','XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE','EXC','SGV','SON')";

if (!empty($plant) && isset($plantMap[$plant])) {
    $plantFilter .= " AND SUBSTR(t2.namcent5, 1, 2) IN {$plantMap[$plant]}";
}

$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

// ============================
// 0. ยอดปัจจุบัน (Current Active)
// ============================
$curSql = "SELECT COUNT(*) as cnt FROM temploy1 t1, tcenter t2 WHERE t1.codcomp = t2.codcomp AND t1.staemp < 9 {$plantFilter}";
$curRows = fetchAllAssoc($conn, $curSql, []);
$currentHeadcount = !empty($curRows) ? (int)$curRows[0]['CNT'] : 0;
echo "<h3>📍 ยอดพนักงานปัจจุบัน (ยังไม่พ้นสภาพ): <span style='color:blue;font-size:24px;'>$currentHeadcount คน</span></h3>";

// ============================
// 1. TREND: Snapshot per Month (วิธี A)
// ============================
echo "<h3>🔹 1. Headcount Snapshot (วิธี A: สิ้นเดือนแต่ละเดือน)</h3>";
$trendDataA = [];
for ($m = 1; $m <= 12; $m++) {
    $mm = str_pad($m, 2, '0', STR_PAD_LEFT);
    $lastDay = date('t', mktime(0, 0, 0, $m, 1, (int)$currentYear));
    $endOfMonth = "TO_DATE('{$currentYear}-{$mm}-{$lastDay}', 'YYYY-MM-DD')";
    $sql = "SELECT COUNT(*) AS cnt FROM temploy1 t1, tcenter t2 WHERE t1.codcomp = t2.codcomp AND NVL(t1.dtereemp, t1.dteempmt) <= {$endOfMonth} AND (t1.staemp < 9 OR (t1.staemp = 9 AND t1.dteeffex > {$endOfMonth})) {$plantFilter}";
    $res = fetchAllAssoc($conn, $sql, []);
    $trendDataA[$m] = !empty($res) ? (int)$res[0]['CNT'] : 0;
}

// ============================
// 2. คนเข้า / คนออก รายเดือน
// ============================
echo "<h3>🔹 2. ข้อมูลคนเข้า-ออก (Movements) ของปี {$currentYear}</h3>";
$movSql = "
SELECT 
    CASE WHEN TO_CHAR(NVL(t1.dtereemp, t1.dteempmt), 'YYYY', 'nls_calendar=gregorian') = '{$currentYear}' THEN TO_CHAR(NVL(t1.dtereemp, t1.dteempmt), 'MM') END AS hire_mm,
    CASE WHEN t1.staemp = 9 AND TO_CHAR(t1.dteeffex, 'YYYY', 'nls_calendar=gregorian') = '{$currentYear}' THEN TO_CHAR(t1.dteeffex, 'MM') END AS retired_mm,
    t1.staemp
FROM temploy1 t1, tcenter t2 WHERE t1.codcomp = t2.codcomp {$plantFilter}
  AND (
    (TO_CHAR(NVL(t1.dtereemp, t1.dteempmt), 'YYYY', 'nls_calendar=gregorian') = '{$currentYear}')
    OR (t1.staemp = 9 AND TO_CHAR(t1.dteeffex, 'YYYY', 'nls_calendar=gregorian') = '{$currentYear}')
  )
";
$rows = fetchAllAssoc($conn, $movSql, []);
$hires = array_fill(1, 12, 0);
$resigns = array_fill(1, 12, 0);
foreach ($rows as $r) {
    if (!empty($r['HIRE_MM'])) $hires[(int)$r['HIRE_MM']]++;
    if (!empty($r['RETIRED_MM'])) $resigns[(int)$r['RETIRED_MM']]++;
}

// ============================
// 3. วิธี C: Rollback (วิธีใหม่ที่ใช้ในระบบจริง)
// ============================
$trendDataC = array_fill(1, 12, 0);
$thisMonth = (int)date('m');
$tempCount = $currentHeadcount;
for ($i = $thisMonth; $i >= 1; $i--) {
    $trendDataC[$i] = $tempCount;
    $tempCount = $tempCount - $hires[$i] + $resigns[$i];
}
// เดือนหลังจากนี้ให้เป็นยอดปัจจุบัน
for ($i = $thisMonth + 1; $i <= 12; $i++) { $trendDataC[$i] = $currentHeadcount; }

// ============================
// 4. สรุปเปรียบเทียบ
// ============================
echo "<h3>🔹 3. สรุปเปรียบเทียบ: วิธี A (Snapshot) vs วิธี C (Rollback)</h3>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; font-family:monospace; width:100%'>";
echo "<tr style='background:#1e293b;color:#fff;'><th>เดือน</th><th>เข้า (In)</th><th>ออก (Out)</th><th>สุทธิ</th><th>วิธี A (Snapshot)</th><th>วิธี C (Rollback)</th><th>ผลต่าง</th></tr>";

for ($m = 1; $m <= 12; $m++) {
    $net = $hires[$m] - $resigns[$m];
    $diff = $trendDataA[$m] - $trendDataC[$m];
    $isThisMonth = ($m == $thisMonth) ? "background:#fffbeb; font-weight:bold;" : "";
    $bg = ($m % 2 == 0) ? '#f8f9fa' : '#fff';
    echo "<tr style='background:{$bg}; {$isThisMonth}'>
        <td><strong>{$months[$m-1]}</strong> " . ($m == $thisMonth ? "(เดือนนี้)" : "") . "</td>
        <td style='text-align:right;color:green;'>+{$hires[$m]}</td>
        <td style='text-align:right;color:red;'>-{$resigns[$m]}</td>
        <td style='text-align:right;'>" . ($net >= 0 ? "+$net" : "$net") . "</td>
        <td style='text-align:right;font-size:16px;'>{$trendDataA[$m]}</td>
        <td style='text-align:right;font-size:16px;color:#0d6efd;'>{$trendDataC[$m]}</td>
        <td style='text-align:right;color:" . ($diff == 0 ? 'green' : 'red') . ";'>$diff</td>
    </tr>";
}
echo "</table>";

// ============================
// 5. รายละเอียดแยกรายบริษัท (Debug)
// ============================
$testMonth = isset($_GET['month']) ? str_pad($_GET['month'], 2, '0', STR_PAD_LEFT) : date('m');
$testMonthName = $months[(int)$testMonth - 1];

echo "<h3>🔹 4. รายละเอียดแยกรายบริษัท (codcomp1) ของเดือน {$testMonthName}</h3>";
$compSql = "
SELECT 
    codcomp1,
    SUM(CASE WHEN TO_CHAR(NVL(t1.dtereemp, t1.dteempmt), 'MM') = '{$testMonth}' AND TO_CHAR(NVL(t1.dtereemp, t1.dteempmt), 'YYYY') = '{$currentYear}' THEN 1 ELSE 0 END) as in_qty,
    SUM(CASE WHEN t1.staemp = 9 AND TO_CHAR(t1.dteeffex, 'MM') = '{$testMonth}' AND TO_CHAR(t1.dteeffex, 'YYYY') = '{$currentYear}' THEN 1 ELSE 0 END) as out_qty
FROM temploy1 t1, tcenter t2 
WHERE t1.codcomp = t2.codcomp 
  AND t1.namempt NOT LIKE '%จุฬางกูร%'
  AND t1.codcomp1 NOT IN ('AAA','AAS','FUJ','JMP','JMX','KTK','HHH','MMM','NJN','SWC','TJS','XYZ','TUS','TFE','TSM','TEP','TSL','SOG','SWG','ACM','TAI','GRE','EXC','SGV','SON')
GROUP BY codcomp1
ORDER BY in_qty DESC
";

try {
    $compRows = fetchAllAssoc($conn, $compSql, []);
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; font-family:monospace;'>";
    echo "<tr style='background:#475569;color:#fff;'><th>Company (codcomp1)</th><th>{$testMonthName} เข้า (In)</th><th>{$testMonthName} ออก (Out)</th></tr>";
    foreach ($compRows as $cr) {
        $bg = ($cr['IN_QTY'] > 0 || $cr['OUT_QTY'] > 0) ? "background:#dcfce7;" : "";
        echo "<tr style='{$bg}'><td>{$cr['CODCOMP1']}</td><td style='text-align:right;'>{$cr['IN_QTY']}</td><td style='text-align:right;'>{$cr['OUT_QTY']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) { echo "Error: ".$e->getMessage(); }

echo "<div style='margin-top:20px; padding:15px; background:#f1f5f9; border-left:5px solid #0d6efd;'>
    <strong>💡 วิธีคิดแบบ Rollback (วิธี C):</strong><br>
    เราเอายอดปัจจุบันที่ยังไม่พ้นสภาพ (<b>$currentHeadcount</b>) มาเป็นหลักในเดือน <b>{$months[$thisMonth-1]}</b><br>
    จากนั้นคำนวณย้อนกลับไปเดือนก่อนหน้า โดย: <b>[ยอดเดือนถัดไป] - [คนเข้า] + [คนออก]</b><br>
    วิธีนี้จะแม่นยำและรวดเร็วกว่าการทำ Snapshot ทีละเดือนครับ
</div>";

echo "<br><div style='padding:10px; background:#eee;'>
    <strong>📅 เลือกเดือนที่ต้องการเทส:</strong><br>";
    for($i=1; $i<=12; $i++) {
        $mStr = str_pad($i, 2, '0', STR_PAD_LEFT);
        $active = ($mStr == $testMonth) ? "font-weight:bold; color:red;" : "";
        echo "<a href='?plant={$plant}&month={$mStr}' style='margin-right:10px; {$active}'>{$months[$i-1]}</a> ";
    }
echo "</div>";

echo "<br><p>🔗 เลือก Plant อื่น: <a href='?plant=SAB&month={$testMonth}'>SAB</a> | <a href='?plant=SLAB&month={$testMonth}'>SLAB</a> | <a href='?plant=SAAB&month={$testMonth}'>SAAB</a> | <a href='?plant=SRAB&month={$testMonth}'>SRAB</a> | <a href='?plant=SRDC&month={$testMonth}'>SRDC</a> | <a href='?plant=SATC&month={$testMonth}'>SATC</a> | <a href='?plant=SDC&month={$testMonth}'>SDC</a> | <a href='?plant=&month={$testMonth}'>ทั้งหมด</a></p>";
?>
