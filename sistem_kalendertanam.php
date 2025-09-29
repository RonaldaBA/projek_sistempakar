<?php
// Fungsi format bahasa Indonesia
function formatIndo(\DateTime $dt): string
{
    $days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $dayname = $days[$dt->format('l')] ?? $dt->format('l');
    $day = $dt->format('d');
    $month = $months[intval($dt->format('n'))] ?? $dt->format('F');
    $year = $dt->format('Y');
    // $time = $dt->format('H:i');
    return sprintf('%s, %s %s %s', $dayname, $day, $month, $year);
}

// Objek DateTime
function nowDateTime(): DateTime
{
    date_default_timezone_set('Asia/Jakarta');
    return new DateTime();
}

// Pengecekan objek datetime
function safeDateFromInput($s = null): DateTime
{
    if ($s && trim($s) !== '') {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $s);
        if ($dt !== false) {
            return $dt;
        }
        try {
            return new DateTime($s);
        } catch (Exception $e) {
            // fallback
        }
    }
    return nowDateTime();
}

$cropParams = [
    // 'padi' => ['plant_offset_start'=>1, 'plant_offset_end'=>7, 'growth_days'=>120],
    // 'jagung' => ['plant_offset_start'=>1, 'plant_offset_end'=>10, 'growth_days'=>90],
    // 'kedelai' => ['plant_offset_start'=>1, 'plant_offset_end'=>14, 'growth_days'=>100],
    // 'cabe' => ['plant_offset_start'=>1, 'plant_offset_end'=>14, 'growth_days'=>110],
    // 'bawang' => ['plant_offset_start'=>1, 'plant_offset_end'=>10, 'growth_days'=>90],
    'padi' => ['plant_offset_start' => 1, 'plant_offset_end' => 2, 'growth_days' => 120],
    'jagung' => ['plant_offset_start' => 1, 'plant_offset_end' => 2, 'growth_days' => 90],
    'kedelai' => ['plant_offset_start' => 1, 'plant_offset_end' => 2, 'growth_days' => 100],
    'cabe' => ['plant_offset_start' => 1, 'plant_offset_end' => 2, 'growth_days' => 110],
    'bawang' => ['plant_offset_start' => 1, 'plant_offset_end' => 2, 'growth_days' => 90],
];

function buildFragWithDays(string $cropKey, \DateTime $base, array $params): string
{
    $sPlant = (clone $base)->modify($params['plant_offset_start'] . ' days');
    $ePlant = (clone $base)->modify($params['plant_offset_end'] . ' days');
    $sHarvest = (clone $sPlant)->modify('+' . $params['growth_days'] . ' days');
    $eHarvest = (clone $ePlant)->modify('+' . $params['growth_days'] . ' days');

    $out = '<div class="rec-fragment">';
    $out .= '<h4>' . htmlspecialchars(ucfirst($cropKey)) . ' (' . intval($params['growth_days']) . ' hari)' . '</h4>';
    $out .= '<p>Rekomendasi tanam:<br> <strong>' . formatIndo($sPlant) . '</strong> sampai <strong>' . formatIndo($ePlant) . '</strong></p>';
    $out .= '<p>Perkiraan panen:<br> <strong>' . formatIndo($sHarvest) . '</strong> sampai <strong>' . formatIndo($eHarvest) . '</strong></p>';
    $out .= '<p class="small"><em>Dasar perhitungan: <code>' . htmlspecialchars($base->format('d-m-Y')) . '</code></em></p>';
    $out .= '</div>';
    return $out;
}

// Tangani AJAX: jika POST crop dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crop'])) {

    $crop = trim($_POST['crop']);
    $hari_post = isset($_POST['hari']) ? trim($_POST['hari']) : '';
    $baseDate = safeDateFromInput($hari_post);
    $cropKey = strtolower($crop);

    if ($cropKey === '' || !isset($cropParams[$cropKey])) {
        echo '<p class="text-danger">Tanaman tidak valid.</p>';
    } else {
        echo buildFragWithDays($cropKey, $baseDate, $cropParams[$cropKey]);
    }
    exit;
}

// Jika bukan AJAX, lanjut rendering halaman biasa
$waktu_dt = nowDateTime();
$hari_dt = clone $waktu_dt;
$hari_str = $hari_dt->format('Y-m-d H:i:s');
$hari_readable = $hari_dt->format('l, d F Y H:i');