<?php
$dbhost = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "cuacatani_db";

$connection = mysqli_connect($dbhost, $dbusername, $dbpassword,  $dbname);

# Navigasi Lokasi
$resethalaman = "/projek_sistempakar";
$beranda = "#beranda";
$prediksicuaca = "#prediksi-cuaca";
$kalendertanam = "#kalender-tanam";
$tentangkami = "#tentang-kami";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CuacaTani - Sistem Informasi Iklim Lokal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="data:;base64,iVBORw0KGgo=">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container header-container" style="position: relative; z-index:2;">
            <div class="logo">
                <i class="fas fa-cloud-sun-rain"></i>
                <div class="logo-text">
                    <h1>CuacaTani</h1>
                    <p>Sistem Informasi Iklim Lokal</p>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="<?= htmlspecialchars($beranda) ?>">Beranda</a></li>
                    <li><a href="<?= htmlspecialchars($prediksicuaca) ?>">Prediksi Cuaca</a></li>
                    <li><a href="<?= htmlspecialchars($kalendertanam) ?>">Kalender Tanam</a></li>
                    <li><a href="<?= htmlspecialchars($tentangkami) ?>">Tentang Kami</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="beranda" class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Mitigasi Gagal Panen dengan Informasi Iklim Lokal</h2>
                <p>Platform berbasis web yang menyajikan data cuaca setiap 3 jam dari hari ini hingga 2 hari ke depan berdasarkan lokasi petani. Web ini juga dilengkapi rekomendasi jadwal tanam untuk memperkirakan waktu panen.</p>
                <div class="location-container">
                <div class="dropdown">
                <?php
                $lokasi = $_GET['lokasi'] ?? '';
                $lokasi_esc = mysqli_real_escape_string($connection, $lokasi);
                $currentLabel = '';
                $data = null;
                $result = null;

                if ($lokasi_esc !== '') {
                    $sqlSingle = "SELECT nama FROM lokasi WHERE kode = '{$lokasi_esc}' LIMIT 1";
                    $resSingle = mysqli_query($connection, $sqlSingle);
                    if ($resSingle && mysqli_num_rows($resSingle) > 0) {
                        $currentLabel = mysqli_fetch_assoc($resSingle)['nama'];
                    }
                }

                if (strlen($lokasi) == 0) {
                    $sqlList = "SELECT kode, nama FROM lokasi WHERE CHAR_LENGTH(kode) = 2 LIMIT 100";
                    $currentSelect = "Provinsi";
                } elseif (strlen($lokasi) == 2) {
                    $sqlList = "SELECT kode, nama FROM lokasi WHERE CHAR_LENGTH(kode) = 5 AND kode LIKE '{$lokasi_esc}%' LIMIT 100";
                    $currentSelect = "Kabupaten";
                } elseif (strlen($lokasi) == 5) {
                    $sqlList = "SELECT kode, nama FROM lokasi WHERE CHAR_LENGTH(kode) = 8 AND kode LIKE '{$lokasi_esc}%' LIMIT 100";
                    $currentSelect = "Kecamatan";
                } elseif (strlen($lokasi) == 8) {
                    $sqlList = "SELECT kode, nama FROM lokasi WHERE CHAR_LENGTH(kode) = 13 AND kode LIKE '{$lokasi_esc}%' LIMIT 100";
                    $currentSelect = "Desa / Kelurahan";
                } else { // strlen($lokasi) == 13
                    $sqlList = "SELECT kode, nama FROM lokasi WHERE CHAR_LENGTH(kode) = 13 AND kode LIKE '" . substr($lokasi_esc, 0, 8) . "%' LIMIT 100";
                    $currentSelect = $currentLabel;
                    $api_url = "https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={$lokasi}";
                    $response_body = @file_get_contents($api_url);

                    if ($response_body !== false) {
                        $data = json_decode($response_body, true);
                        if ($data === null) {
                            echo "<div class='alert alert-danger'>PERINGATAN: Gagal memproses data JSON dari BMKG.</div>";
                        }
                    } else {
                        echo "<div class='alert alert-warning'>PERINGATAN: Data tidak tersedia atau server sibuk.</div>";
                    }
                }

                if (isset($sqlList)) {
                    $result = mysqli_query($connection, $sqlList);
                    if (!$result) {
                        die("Query lokasi gagal: " . mysqli_error($connection));
                    }
                }

                if ($data) {
                    $get_params = $_GET;
                    $hari_lama = $get_params['hari'] ?? '0';
                    $index_hari = in_array($hari_lama, ['0','1','2']) ? (int)$hari_lama : 0;
                    $prakiraan_harian = $data["data"][0]["cuaca"][$index_hari] ?? null;
                }
                ?>

                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                    data-bs-toggle="dropdown" aria-expanded="false"
                    style="color: green; background-color: white; font-weight: bold;">
                    <?= ($currentSelect == $currentLabel && !empty($currentLabel)) ? htmlspecialchars($currentSelect) : "Pilih " . htmlspecialchars($currentSelect) ?>
                </button>

                <form method="get" style="display: flex; margin-bottom: 16px; justify-content: center; gap:20px;">

                <style>
                .dropdown-menu .dropdown-item.active { background-color: #49a04fff; color: #fff; }
                .dropdown-menu .dropdown-item:active:not(.active) { background-color: #49a04fff; color: #fff; }
                .dropdown-menu.grid-4-cols { padding: 1vw; width: 50vw; position: absolute; z-index: 1; }
                .dropdown-menu.grid-4-cols .dropdown-row { display: flex; flex-wrap: wrap; }
                .dropdown-menu.grid-4-cols .dropdown-col { width: 15vw; box-sizing: border-box; gap: 0.1vw; }
                .dropdown-menu.grid-4-cols .dropdown-col .dropdown-item { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; text-align: left; }
                </style>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <ul class="dropdown-menu grid-4-cols" aria-labelledby="dropdownMenuButton">
                    <li>
                        <div class="px-2 py-1">
                             <input type="text" class="form-control" id="searchInput" placeholder="Cari lokasi..." onclick="event.stopPropagation();">
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <div class="dropdown-row">
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="dropdown-col">
                            <button class="dropdown-item" type="submit" name="lokasi" value="<?= htmlspecialchars($row['kode']); ?>">
                                <?= htmlspecialchars($row['nama']); ?>
                            </button>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </ul>
                <?php endif; ?>
                </form>
                
                <?php if (!empty($lokasi)): ?>
                    <button class="btn-location" id="reset-location">
                        <i class="fas fa-sync-alt"></i> <?= (strlen($lokasi) == 13) ? 'Cari Lokasi Baru' : 'Reset Pilihan' ?>
                    </button>
                    <script>
                        document.getElementById('reset-location').addEventListener('click', function() {
                            window.location.href = '<?= htmlspecialchars($resethalaman) ?>';
                        });
                    </script>
                <?php endif; ?>

            </div>
            </div>
        </div>
    </section>
    
    <?php if (isset($data) && $data): ?>
        <section class="dashboard">
            <div class="container">
                <div class="dashboard-header">
                    <h2 class="section-title">Dashboard Informasi Iklim</h2>
                </div>
                
                <div class="api-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Detail Informasi Wilayah<br></strong>
                    Sistem ini menyajikan prakiraan cuaca secara langsung untuk dua hari ke depan. Data cuaca diambil langsung dari layanan resmi BMKG dan disajikan berdasarkan wilayah yang ditentukan.<br><br>
                    <?php
                    if (isset($data["lokasi"]["desa"]) && isset($data["lokasi"]["kecamatan"])) {
                        echo "<hr>";
                        echo "<h2>Desa/Kelurahan: " . htmlspecialchars($data["lokasi"]["desa"]) . "</h2>";
                        echo "<p>";
                        echo "Kecamatan: " . htmlspecialchars($data["lokasi"]["kecamatan"] ?? "N/A") . "<br>";
                        echo "Kota/Kabupaten: " . htmlspecialchars($data["lokasi"]["kotkab"] ?? "N/A") . "<br>";
                        echo "Provinsi: " . htmlspecialchars($data["lokasi"]["provinsi"] ?? "N/A") . "<br>";
                        echo "Koordinat: Latitude: " . htmlspecialchars($data["lokasi"]["lat"] ?? "N/A") . " | Longitude: " . htmlspecialchars($data["lokasi"]["lon"] ?? "N/A") . "<br>";
                        echo "Zona Waktu: " . htmlspecialchars($data["lokasi"]["timezone"] ?? "N/A") . "<br><br><hr>";
                        echo "</p>";
                    } else {
                        echo "<h2>Lokasi Tidak Ditemukan</h2>";
                    } ?>
                </div>
                    <div id="prediksi-cuaca" class="dashboard-grid">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-cloud"></i>
                                <h3>Prediksi Cuaca</h3>
                            </div>
                            <div class="card-body">
                                <h4 class="data-source">Hari ke-<?= ($index_hari + 1) ?></h4>
                                <div class="forecast-container" id="forecast-container">
                                    <?php if (is_array($prakiraan_harian)): ?>
                                    <?php foreach ($prakiraan_harian as $prakiraan): ?>
                                        <?php
                                            $waktu_lokal = !empty($prakiraan["local_datetime"]) ? htmlspecialchars((new DateTime($prakiraan["local_datetime"]))->format('d-m-Y H:i:s')) : "N/A";
                                            $deskripsi   = htmlspecialchars($prakiraan["weather_desc"] ?? "N/A");
                                            $alt_text    = htmlspecialchars($prakiraan["weather_desc"] ?? "Ikon Cuaca", ENT_QUOTES, "UTF-8");
                                            $suhu        = htmlspecialchars($prakiraan["t"] ?? "N/A");
                                            $kelembapan  = htmlspecialchars($prakiraan["hu"] ?? "N/A");
                                            $kec_angin   = htmlspecialchars($prakiraan["ws"] ?? "N/A");
                                            $arah_angin  = htmlspecialchars($prakiraan["wd"] ?? "N/A");
                                            $jarak_pandang = htmlspecialchars($prakiraan["vs_text"] ?? "N/A");
                                            $raw_img_url   = $prakiraan["image"] ?? "";
                                            $img_url_processed = !empty($raw_img_url) ? str_replace(" ", "%20", $raw_img_url) : "";
                                        ?>
                                        <div class="forecast-day loading">
                                            <div class="slot-text">
                                                <div><strong>Waktu:</strong> <?= $waktu_lokal ?></div>
                                                <div><strong>Cuaca:</strong> <?= $deskripsi ?></div>
                                                <?php if ($img_url_processed && filter_var($img_url_processed, FILTER_VALIDATE_URL)): ?>
                                                    <div><img src="<?= $img_url_processed ?>" alt="<?= $alt_text ?>" title="<?= $alt_text ?>"></div>
                                                <?php endif; ?>
                                                <div><strong>Suhu:</strong> <?= $suhu ?>°C</div>
                                                <div><strong>Kelembapan:</strong> <?= $kelembapan ?>%</div>
                                                <div><strong>Kec. Angin:</strong> <?= $kec_angin ?> km/j</div>
                                                <div><strong>Arah Angin:</strong> dari <?= $arah_angin ?></div>
                                                <div><strong>Jarak Pandang:</strong> <?= $jarak_pandang ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>Hari ke-<?= ($index_hari + 1) ?> tidak tersedia dalam data.</p>
                                    <?php endif; ?>
                                </div>
                                
                                <form id="form-hari" method="get" style="display: flex; margin-bottom: 16px; justify-content: center; gap:20px;">
                                <?php foreach ($get_params as $key => $value): ?>
                                    <?php
                                    if ($key === 'hari') continue;
                                    $key_s = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                                    $value_s = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <input type="hidden" name="<?= $key_s ?>" value="<?= $value_s ?>">
                                <?php endforeach; ?>
                                    <button type="submit" name="hari" value="0" class="weather-btn <?= ($hari_lama === "0" ? "active" : "") ?>">Hari ke-1</button>
                                    <button type="submit" name="hari" value="1" class="weather-btn <?= ($hari_lama === "1" ? "active" : "") ?>">Hari ke-2</button>
                                    <button type="submit" name="hari" value="2" class="weather-btn <?= ($hari_lama === "2" ? "active" : "") ?>">Hari ke-3</button>
                                </form>
                                
                                <div class="data-source">
                                    <i class="fas fa-database"></i> Sumber Data: BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)
                                </div>
                            </div>
                        </div>
                    </div>
                <br>
                <div class="dashboard-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
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
                                <div id="weather-icon-container">
                                    <i class="fas fa-cloud-sun weather-icon" id="weather-icon"></i>
                                </div>
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
                                    <i class="fas fa-compass"></i>
                                    <div>Arah Angin: <span id="wind-direction">--</span></div>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-eye"></i>
                                    <div>Jarak Pandang: <span id="visibility">--</span> km</div>
                                </div>
                            </div>
                            
                            <div class="temp-chart">
                                <canvas id="temp-chart"></canvas>
                            </div>
                            
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

                        </div>
                    </div>

                    <?php @include "sistem_kalendertanam.php"; ?>

                    <div id="kalender-tanam" class="card">
                        <div class="card-header">
                            <i class="fas fa-calendar"></i>
                            <h3>Kalender Tanam</h3>
                        </div>
                        <div class="card-body">
                            <p><strong>Rekomendasi Tanam dan Prakiraan Panen:</strong></p>
                            <div class="planting-recommendation">
                                <div id="recommendation">Pilih komoditas untuk rekomendasi.</div>
                            </div>
                            <div class="crop-buttons">
                                <button class="crop-btn" data-crop="padi">Padi</button>
                                <button class="crop-btn" data-crop="jagung">Jagung</button>
                                <button class="crop-btn" data-crop="kedelai">Kedelai</button>
                                <button class="crop-btn" data-crop="cabe">Cabe</button>
                                <button class="crop-btn" data-crop="bawang">Bawang</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php @include "sistem_rekomendasitanam.php"; ?>
            </div>
        </section>
    <?php endif; ?>

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
                    <p>Rekomendasi waktu tanam & panen dan jenis komoditas untuk hasil optimal.</p>
                </div>
            </div>
        </div>
    </section>

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
                        <li><a href="<?= htmlspecialchars($beranda) ?>"><i class="fas fa-home"></i> Beranda</a></li>
                        <li><a href="<?= htmlspecialchars($prediksicuaca) ?>"><i class="fas fa-cloud"></i> Prediksi Cuaca</a></li>
                        <li><a href="<?= htmlspecialchars($kalendertanam) ?>"><i class="fas fa-calendar"></i> Kalender Tanam</a></li>
                        <li><a href="<?= htmlspecialchars($tentangkami) ?>"><i class="fas fa-bell"></i> Tentang Kami</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Kontak Kami</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Ngaliyan, Kota Semarang, Semarang, Jawa Tengah</li>
                        <li><i class="fas fa-phone"></i> (029) 1234-5678</li>
                        <li><i class="fas fa-envelope"></i> info@cuacatani.id</li>
                        <li><i class="fas fa-users"></i> Kelompok 5 Sistem Pakar</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Mitra</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-cloud-sun"></i> BMKG Stasiun Semarang</li>
                        <li><i class="fas fa-graduation-cap"></i> Universitas Islam Negeri Walisongo Semarang</li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; 2025 CuacaTani - Sistem Informasi Iklim Lokal</p>
                <p>Data cuaca disediakan oleh BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)</p>
            </div>
        </div>
    </footer>
    
    <div class="notification" id="notification">
        <i class="fas fa-info-circle"></i>
        <span id="notification-message">Pesan notifikasi</span>
    </div>

    <?php if (isset($data) && $data): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const prakiraanHariIni = <?= json_encode($data["data"][0]["cuaca"][0] ?? null); ?>;
        const timezone = '<?= $data["lokasi"]["timezone"] ?? 'Asia/Jakarta'; ?>';
        let cuacaSaatIni = null;

        if (prakiraanHariIni) {
            const now = new Date(new Date().toLocaleString('en-US', { timeZone: timezone }));
            
            for (const prakiraan of prakiraanHariIni) {
                const prakiraanTime = new Date(prakiraan.local_datetime);
                if (prakiraanTime >= now) {
                    cuacaSaatIni = prakiraan;
                    break;
                }
                cuacaSaatIni = prakiraan;
            }

            if (cuacaSaatIni) {
                document.getElementById('current-temp').textContent = `${cuacaSaatIni.t}°C`;
                document.getElementById('weather-desc').textContent = cuacaSaatIni.weather_desc;
                document.getElementById('wind-speed').textContent = cuacaSaatIni.ws;
                document.getElementById('humidity').textContent = cuacaSaatIni.hu;
                document.getElementById('visibility').textContent = cuacaSaatIni.vs;
                
                document.getElementById('wind-direction').textContent = `dari ${cuacaSaatIni.wd}`;
                
                const iconContainer = document.getElementById('weather-icon-container');
                iconContainer.innerHTML = `<img src="${cuacaSaatIni.image.replace(/ /g, '%20')}" style="width:64px; height:64px;" alt="${cuacaSaatIni.weather_desc}" title="${cuacaSaatIni.weather_desc}">`;
            }

            const chartLabels = prakiraanHariIni.map(p => new Date(p.local_datetime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }));
            const chartData = prakiraanHariIni.map(p => p.t);

            const ctxDynamic = document.getElementById('temp-chart').getContext('2d');
            if (window.myTempChart) {
                window.myTempChart.destroy();
            }
            window.myTempChart = new Chart(ctxDynamic, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Suhu (°C)',
                        data: chartData,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }
    });
    </script>
    <?php endif; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Cari elemen input pencarian
        const searchInput = document.getElementById('searchInput');

        // Pastikan elemen input ada sebelum menambahkan event listener
        if (searchInput) {
            // Dapatkan semua item yang akan difilter
            const itemsToFilter = document.querySelectorAll('.dropdown-menu .dropdown-col');

            // Tambahkan event listener 'keyup' yang akan aktif setiap kali pengguna mengetik
            searchInput.addEventListener('keyup', function() {
                const filter = searchInput.value.toLowerCase();

                // Loop melalui setiap item
                itemsToFilter.forEach(function(item) {
                    const button = item.querySelector('.dropdown-item');
                    if (button) {
                        const text = button.textContent.toLowerCase();
                        
                        // Periksa apakah teks item mengandung teks filter
                        if (text.includes(filter)) {
                            item.style.display = ''; // Tampilkan item jika cocok
                        } else {
                            item.style.display = 'none'; // Sembunyikan item jika tidak cocok
                        }
                    }
                });
            });
        }
    });
    </script>
    </body>
</html>