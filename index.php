<?php
$dbhost = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "cuacatani_db";

$connection = mysqli_connect($dbhost, $dbusername, $dbpassword,  $dbname);

$query  = "SELECT kode, nama FROM lokasi WHERE CHAR_LENGTH(kode) = 13  AND kode LIKE '33.74.15%' LIMIT 100";
$result = mysqli_query($connection, $query);

if (!$result) {
    die('Query error: ' . mysqli_error($conn));
}

// Ambil semua parameter GET sekarang
$get_params = $_GET;
$hari_lama = isset($get_params['hari']) ? $get_params['hari'] : null;
$lokasi_lama = isset($get_params['lokasi']) ? $get_params['lokasi'] : null;


// Set header
if (isset($_GET['lokasi']) && $_GET['lokasi'] !== '') {
    // $lokasi diasumsikan berupa string seperti '31.71.03.1001'
    $lokasi = urlencode($_GET['lokasi']);
    $api_url = "https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={$lokasi}";
} else {
    // Default jika parameter tidak tersedia
    $lokasi = '11.01.01.2001';
    $api_url = "https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={$lokasi}";
}

$response_body = @file_get_contents($api_url);

// Check if fail
if ($response_body === false) {
    die("ERROR: Gagal mengambil data.");
}

// Decode String JSON
$data = json_decode($response_body, true);

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    die(
        "ERROR: Data bukan format JSON yang valid. " .
            htmlspecialchars(json_last_error_msg())
    );
}

// Tentukan index hari dari parameter GET (0,1,2). Default: 0
$index_hari = isset($_GET['hari']) && in_array($_GET['hari'], ['0','1','2'], true)
    ? (int)$_GET['hari']
    : 0;

// Akses data harian sesuai index
$prakiraan_harian = $data["data"][0]["cuaca"][$index_hari] ?? null;

// Set header
if (isset($_GET['hari'])) { // Check User's Last Position Before Direct
    $hari = intval($_GET['hari']);
?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
    // Jika ada parameter ?hari tapi belum ada fragment
    const params = new URLSearchParams(window.location.search);
    if (params.has("hari")) {
        const hari = params.get("hari");
        window.location.hash = "#prediksi-cuaca";
    }
    });
</script>

<?php
} else {
    header("Content-Type: text/html; charset=utf-8");
}

include __DIR__ . '/get_recommendation.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CuacaTani - Sistem Informasi Iklim Lokal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <div class="logo">
                <i class="fas fa-cloud-sun-rain"></i>
                <div class="logo-text">
                    <h1>CuacaTani</h1>
                    <p>Sistem Informasi Iklim Lokal</p>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="/web/con2.php">Beranda</a></li>
                    <li><a href="#prediksi-cuaca">Prediksi Cuaca</a></li>
                    <li><a href="#kalender-tanam">Kalender Tanam</a></li>
                    <!--<li><a href="#">Artikel</a></li>-->
                    <!--<li><a href="#">Catatan Tanam</a></li>-->
                    <li><a href="#tentang-kami">Tentang Kami</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="beranda" class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Mitigasi Gagal Panen dengan Informasi Iklim Lokal</h2>
                <p>Platform berbasis web yang menyajikan data cuaca harian dan mingguan berbasis lokasi petani, disertai rekomendasi waktu tanam dan panen untuk adaptasi perubahan iklim.</p>
                <div class="location-container">
                <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <?= $lokasi ?> - <?= $data["lokasi"]["desa"] ?>
                </button>
                <form method="get" style="display: flex; margin-bottom: 16px; justify-content: center; gap:20px;">
                <!-- Input hidden untuk semua GET parameter lama, kecuali 'lokasi' karena akan diganti -->
                <?php //foreach ($get_params as $key => $value): ?>
                    <?php
                    // if ($key === 'lokasi') continue;
                    // $key_s = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                    // $value_s = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    ?>
                    <!-- <input type="hidden" name="<? //$key_s ?>" value="<? //$value_s ?>"> -->
                <?php //endforeach; ?>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <li>
                                <button class="dropdown-item <?= ($lokasi_lama === htmlspecialchars($row['kode']) ? "active" : "") ?>" type="submit" name="lokasi" value="<?= htmlspecialchars($row['kode']); ?>"
                                data-kode="<?= htmlspecialchars($row['kode']); ?>"
                                data-nama="<?= htmlspecialchars($row['nama']); ?>">
                                <?= htmlspecialchars($row['nama']); ?>
                                </button>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </form>
                </div>
                    <!-- <div class="location-tag">
                        <i class="fas fa-map-marker-alt"></i> <span id="current-location">Tajemsari, Tegowanu, Grobogan, Jawa Tengah</span>
                    </div>
                    <button class="btn-location" id="change-location">
                        <i class="fas fa-sync-alt"></i> Ubah Lokasi
                    </button> -->
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Section -->
    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h2 class="section-title">Dashboard Informasi Iklim</h2>
                <!-- <form method="get">
                    <button class="refresh-btn" id="refresh-weather" type="submit" name="hari" value="0">
                        <i class="fas fa-sync-alt"></i> Perbarui Data
                    </button>
                </form> -->
            </div>
            
            <div class="api-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Detail Informasi Wilayah<br></strong>
                Sistem ini menyajikan prakiraan cuaca secara langsung untuk dua hari ke depan. Data cuaca diambil langsung dari layanan resmi BMKG dan disajikan berdasarkan wilayah yang ditentukan.<br><br>
                        <?php
                        if (isset($data["lokasi"]["desa"]) && isset($data["lokasi"]["kecamatan"])) {
                            echo "<hr>";
                            echo "<h2>Desa/Kelurahan: " .
                                htmlspecialchars($data["lokasi"]["desa"]) .
                                "</h2>";
                            echo "<p>";
                            echo "Kecamatan: " .
                                htmlspecialchars($data["lokasi"]["kecamatan"] ?? "N/A") .
                                "<br>";
                            echo "Kota/Kabupaten: " .
                                htmlspecialchars($data["lokasi"]["kotkab"] ?? "N/A") .
                                "<br>";
                            echo "Provinsi: " .
                                htmlspecialchars($data["lokasi"]["provinsi"] ?? "N/A") .
                                "<br>";
                            echo "Koordinat: Latitude: " .
                                htmlspecialchars($data["lokasi"]["lat"] ?? "N/A") .
                                " | Longitude: " .
                                htmlspecialchars($data["lokasi"]["lon"] ?? "N/A") .
                                "<br>";
                            echo "Zona Waktu: " .
                                htmlspecialchars($data["lokasi"]["timezone"] ?? "N/A") .
                                "<br><br><hr>";
                            echo "</p>";
                        } else {
                            echo "<h2>Lokasi Tidak Ditemukan</h2>";
                        } ?>
            </div>
            
            <!-- Grid untuk Cuaca Saat Ini & Kalender Tanam -->
            <div class="dashboard-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

                    <!-- Current Weather Card -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-sun"></i>
                            <h3>Cuaca Saat Ini</h3>
                        </div>
                        <div class="card-body">
                            <div class="weather-display">
                                <div>
                                    <div class="current-temp" id="current-temp">--°C</div>
                                    <div id="weather-desc">--</div>
                                </div>
                                <i class="fas fa-cloud-sun weather-icon" id="weather-icon"></i>
                            </div>
                            
                            <div class="weather-details">
                                <div class="detail-item">
                                    <i class="fas fa-wind"></i>
                                    <div>Kecepatan Angin: <span id="wind-speed">--</span> km/jam</div>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-tint"></i>
                                    <div>Kelembaban: <span id="humidity">--</span>%</div>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-compress-alt"></i>
                                    <div>Tekanan: <span id="pressure">--</span> hPa</div>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-eye"></i>
                                    <div>Jarak Pandang: <span id="visibility">--</span> km</div>
                                </div>
                            </div>
                            
                            <div class="temp-chart">
                                <canvas id="temp-chart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Kalender Tanam -->
                    <div id="kalender-tanam" class="card">
                        <div class="card-header">
                            <i class="fas fa-seedling"></i>
                            <h3>Kalender Tanam</h3>
                        </div>
                        <div class="card-body">
                            <p><strong>Rekomendasi Berdasarkan Musim:</strong></p>
                            <div class="planting-recommendation">
                                <p id="planting-advice">
                                <div id="recommendation"></div>

                                <script>
                                document.querySelectorAll('.crop-btn').forEach(btn => {
                                btn.addEventListener('click', () => {
                                    const crop = btn.getAttribute('data-crop');
                                    // Menggunakan FormData agar PHP bisa baca di $_POST
                                    const formData = new FormData();
                                    formData.append('crop', crop);

                                    fetch('get_recommendation.php', {
                                    method: 'POST',
                                    body: formData
                                    })
                                    .then(res => {
                                    if (!res.ok) throw new Error('Network response was not ok');
                                    return res.text();
                                    })
                                    .then(html => {
                                    document.getElementById('recommendation').innerHTML = html;
                                    })
                                    .catch(err => console.error('Fetch error:', err));
                                });
                                });
                                </script>
                                </p>
                            </div>
                            
                            <div class="crop-buttons">
                            <?php
                            $crops = [
                                'padi'    => 'Padi',
                                'jagung'  => 'Jagung',
                                'kedelai' => 'Kedelai',
                                'cabe'    => 'Cabe',
                                'bawang'  => 'Bawang',
                            ];
                            ?>

                            <!-- Loop tombol -->
                            <?php foreach ($crops as $key => $label): ?>
                            <button class="crop-btn" data-crop="<?= htmlspecialchars($key) ?>">
                                <?= htmlspecialchars($label) ?>
                            </button>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart.js untuk grafik suhu -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    const ctx = document.getElementById('temp-chart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['08:00', '10:00', '12:00', '14:00', '16:00'],
                            datasets: [{
                                label: 'Suhu (°C)',
                                data: [26, 27, 28, 29, 28],
                                borderColor: 'rgba(75, 192, 192, 1)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: false } }
                        }
                    });
                </script>

                <br>

                <!-- Prediksi Cuaca dipindah ke bawah -->
                <div id="prediksi-cuaca" class="dashboard-grid">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-calendar-alt"></i>
                            <h3>Prediksi Cuaca</h3>
                        </div>
                        <div class="card-body">
                            <h4 class="data-source">Hari ke-<?= ($index_hari + 1) ?></h4>
                            <div class="forecast-container" id="forecast-container">
                                <?php if (is_array($prakiraan_harian)): ?>
                                <?php foreach ($prakiraan_harian as $prakiraan): ?>
                                    <?php
                                        $waktu_lokal   = htmlspecialchars($prakiraan["local_datetime"] ?? "N/A");
                                        $deskripsi     = htmlspecialchars($prakiraan["weather_desc"] ?? "N/A");
                                        $alt_text      = htmlspecialchars($prakiraan["weather_desc"] ?? "Ikon Cuaca", ENT_QUOTES, "UTF-8");
                                        $suhu          = htmlspecialchars($prakiraan["t"] ?? "N/A");
                                        $kelembapan    = htmlspecialchars($prakiraan["hu"] ?? "N/A");
                                        $kec_angin     = htmlspecialchars($prakiraan["ws"] ?? "N/A");
                                        $arah_angin    = htmlspecialchars($prakiraan["wd"] ?? "N/A");
                                        $jarak_pandang = htmlspecialchars($prakiraan["vs_text"] ?? "N/A");
                                        $raw_img_url   = $prakiraan["image"] ?? "";
                                        $img_url_processed = !empty($raw_img_url) ? str_replace(" ", "%20", $raw_img_url) : "";
                                    ?>
                                    <div class="forecast-day loading">
                                        <div class="slot-text">
                                            <div><strong>Jam:</strong> <?= $waktu_lokal ?></div>
                                            <div><strong>Cuaca:</strong> <?= $deskripsi ?></div>
                                            <?php if ($img_url_processed && filter_var($img_url_processed, FILTER_VALIDATE_URL)): ?>
                                                <div><img src="<?= $img_url_processed ?>" alt="<?= $alt_text ?>" title="<?= $alt_text ?>"></div>
                                            <?php endif; ?>
                                            <div><strong>Suhu:</strong> <?= $suhu ?>°C</div>
                                            <div><strong>Kelembapan:</strong> <?= $kelembapan ?>%</div>
                                            <div><strong>Kec. Angin:</strong> <?= $kec_angin ?> km/j</div>
                                            <div><strong>Arah Angin:</strong> dari <?= $arah_angin ?></div>
                                            <div><strong>Jarak Pandang:</strong> dari <?= $jarak_pandang ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php else: ?>
                                    <p>Hari ke-<?= ($index_hari + 1) ?> tidak tersedia dalam data.</p>
                                <?php endif; ?>
                            </div>
                            
                            <form method="get" style="display: flex; margin-bottom: 16px; justify-content: center; gap:20px;">
                            <!-- Input hidden agar parameter lama ikut termuat -->
                            <?php foreach ($get_params as $key => $value): ?>
                                <?php
                                if ($key === 'hari') continue; // skip hari lama agar tidak duplikat
                                $key_s = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                                $value_s = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                                ?>
                                <input type="hidden" name="<?= $key_s ?>" value="<?= $value_s ?>">
                            <?php endforeach; ?>
                                <button type="submit" name="hari" value="0" class="crop-btn <?= ($hari_lama === "0" ? "active" : "") ?>">Hari ke-1</button>
                                <button type="submit" name="hari" value="1" class="crop-btn <?= ($hari_lama === "1" ? "active" : "") ?>">Hari ke-2</button>
                                <button type="submit" name="hari" value="2" class="crop-btn <?= ($hari_lama === "2" ? "active" : "") ?>">Hari ke-3</button>
                            </form>
                            
                            <div class="data-source">
                                <i class="fas fa-database"></i> Sumber Data: BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Fitur Utama CuacaTani</h2>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cloud-sun"></i>
                    </div>
                    <h3>Prediksi Cuaca Lokal</h3>
                    <p>Prakiraan cuaca 2 hari ke depan berbasis desa/kecamatan dengan akurasi tinggi untuk perencanaan pertanian.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Kalender Tanam Adaptif</h3>
                    <p>Rekomendasi waktu tanam & panen berdasarkan musim dan jenis komoditas untuk hasil optimal.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3>Laporan & Catatan Tanam</h3>
                    <p>Simpan riwayat tanam-panen dan cocokkan dengan data cuaca untuk analisis pola pertanian.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Analisis Produktivitas</h3>
                    <p>Evaluasi hasil panen berdasarkan data iklim untuk perbaikan strategi pertanian.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="tentang-kami">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>CuacaTani</h3>
                    <p>Sistem Informasi Iklim Lokal untuk membantu petani dalam mengambil keputusan bercocok tanam yang adaptif terhadap perubahan iklim dan bencana alam.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Link Cepat</h3>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-home"></i> Beranda</a></li>
                        <li><a href="#"><i class="fas fa-cloud"></i> Prediksi Cuaca</a></li>
                        <li><a href="#"><i class="fas fa-calendar"></i> Kalender Tanam</a></li>
                        <li><a href="#"><i class="fas fa-bell"></i> Peringatan Dini</a></li>
                        <li><a href="#"><i class="fas fa-book"></i> Artikel Edukasi</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Kontak Kami</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Tajemsari, Tegowanu, Grobogan, Jawa Tengah</li>
                        <li><i class="fas fa-phone"></i> (029) 1234-5678</li>
                        <li><i class="fas fa-envelope"></i> info@cuacatani.id</li>
                        <li><i class="fas fa-users"></i> Kelompok Tani Sumber Makmur</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Mitra</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-building"></i> Dinas Pertanian Kab. Grobogan</li>
                        <li><i class="fas fa-cloud-sun"></i> BMKG Stasiun Semarang</li>
                        <li><i class="fas fa-graduation-cap"></i> Universitas Negeri Semarang</li>
                        <li><i class="fas fa-seedling"></i> Balai Penyuluhan Pertanian</li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; 2025 CuacaTani - Sistem Informasi Iklim Lokal. Prototipe untuk Desa Tajemsari, Kabupaten Grobogan.</p>
                <p>Data cuaca disediakan oleh BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)</p>
            </div>
        </div>
    </footer>
    
    <!-- Notification -->
    <div class="notification" id="notification">
        <i class="fas fa-info-circle"></i>
        <span id="notification-message">Pesan notifikasi</span>
    </div>
    
    <!-- Search Modal -->
    <div class="search-modal" id="search-modal">
        <div class="modal-content">
            <button class="close-modal" id="close-modal">
                <i class="fas fa-times"></i>
            </button>
            <h2>Ubah Lokasi</h2>
            <p>Silakan pilih lokasi baru untuk melihat informasi cuaca:</p>
            
            <div class="location-search">
                <input type="text" id="location-input" placeholder="Masukkan nama desa/kecamatan">
                <button id="search-location"><i class="fas fa-search"></i> Cari Lokasi</button>
            </div>
            
            <div class="location-results" id="location-results" style="margin-top: 20px;">
                <p>Lokasi yang tersedia:</p>
                <ul style="list-style: none; margin-top: 10px; max-height: 200px; overflow-y: auto;">
                    <li><button class="location-option" data-location="Tajemsari, Tegowanu, Grobogan, Jawa Tengah">Tajemsari, Tegowanu, Grobogan, Jawa Tengah</button></li>
                    <li><button class="location-option" data-location="Tegowanu, Grobogan, Jawa Tengah">Tegowanu, Grobogan, Jawa Tengah</button></li>
                    <li><button class="location-option" data-location="Godan, Tegowanu, Grobogan, Jawa Tengah">Godan, Tegowanu, Grobogan, Jawa Tengah</button></li>
                    <li><button class="location-option" data-location="Tambahrejo, Grobogan, Jawa Tengah">Tambahrejo, Grobogan, Jawa Tengah</button></li>
                    <li><button class="location-option" data-location="Grobogan, Jawa Tengah">Grobogan, Jawa Tengah</button></li>
                </ul>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>