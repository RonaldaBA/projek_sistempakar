// Konfigurasi API BMKG
const API_URL =
  "https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4=33.15.18.2011";

// Inisialisasi variabel global
let weatherData = null;
let tempChart = null;

// Fungsi untuk menampilkan notifikasi
function showNotification(message, type = "info") {
  const notification = document.getElementById("notification");
  const messageElement = document.getElementById("notification-message");

  notification.className = `notification ${type}`;
  messageElement.textContent = message;
  notification.classList.add("show");

  setTimeout(() => {
    notification.classList.remove("show");
  }, 3000);
}

// Fungsi untuk mendapatkan ikon cuaca berdasarkan kode cuaca BMKG
function getWeatherIcon(code) {
  const iconMap = {
    0: "fas fa-sun", // Cerah
    1: "fas fa-cloud-sun", // Cerah Berawan
    2: "fas fa-cloud-sun", // Cerah Berawan
    3: "fas fa-cloud", // Berawan
    4: "fas fa-cloud", // Berawan Tebal
    5: "fas fa-smog", // Udara Kabur
    10: "fas fa-smog", // Asap
    45: "fas fa-smog", // Kabut
    60: "fas fa-cloud-rain", // Hujan Ringan
    61: "fas fa-cloud-showers-heavy", // Hujan Sedang
    63: "fas fa-cloud-showers-heavy", // Hujan Lebat
    80: "fas fa-cloud-showers-heavy", // Hujan Lokal
    95: "fas fa-bolt", // Hujan Petir
    97: "fas fa-bolt", // Hujan Petir dan Hujan
  };

  return iconMap[code] || "fas fa-cloud";
}

// Fungsi untuk mendapatkan deskripsi cuaca
function getWeatherDesc(code) {
  const descriptions = {
    0: "Cerah",
    1: "Cerah Berawan",
    2: "Cerah Berawan",
    3: "Berawan",
    4: "Berawan Tebal",
    5: "Udara Kabur",
    10: "Asap",
    45: "Kabut",
    60: "Hujan Ringan",
    61: "Hujan Sedang",
    63: "Hujan Lebat",
    80: "Hujan Lokal",
    95: "Hujan Petir",
    97: "Hujan Petir & Hujan",
  };

  return descriptions[code] || "Data tidak tersedia";
}

// Fungsi untuk mengambil data cuaca dari BMKG
async function fetchBMKGWeather() {
  try {
    showNotification("Memuat data cuaca terbaru...", "info");

    // Tampilkan loading state
    document.getElementById("refresh-weather").classList.add("loading");

    // Mengambil data dari API BMKG
    const response = await axios.get(API_URL);
    const data = response.data;

    // Verifikasi respons API
    if (
      data.code !== 200 ||
      !data.data ||
      !data.data.areas ||
      data.data.areas.length === 0
    ) {
      throw new Error("Data cuaca tidak ditemukan");
    }

    // Mengambil data untuk wilayah Tajemsari
    const tajemsariData = data.data.areas[0];

    // Memproses data cuaca
    weatherData = {
      location: tajemsariData.name,
      forecasts: [],
      current: {},
    };

    // Memproses parameter cuaca
    tajemsariData.params.forEach((param) => {
      if (param.id === "weather") {
        // Mengambil data cuaca saat ini (jam terdekat)
        const now = new Date();
        const currentHour = now.getHours();

        // Mencari data cuaca untuk jam saat ini
        const currentWeather = param.times.find((time) => {
          const timeHour = parseInt(time.datetime.substring(8, 10));
          return timeHour === currentHour;
        });

        if (currentWeather) {
          weatherData.current.weatherCode = currentWeather.value;
        }
      } else if (param.id === "t") {
        // Mengambil suhu saat ini
        const now = new Date();
        const currentHour = now.getHours();

        const currentTemp = param.times.find((time) => {
          const timeHour = parseInt(time.datetime.substring(8, 10));
          return timeHour === currentHour;
        });

        if (currentTemp) {
          weatherData.current.temperature = currentTemp.value;
        }
      } else if (param.id === "hu") {
        // Mengambil kelembaban saat ini
        const now = new Date();
        const currentHour = now.getHours();

        const currentHumidity = param.times.find((time) => {
          const timeHour = parseInt(time.datetime.substring(8, 10));
          return timeHour === currentHour;
        });

        if (currentHumidity) {
          weatherData.current.humidity = currentHumidity.value;
        }
      } else if (param.id === "ws") {
        // Mengambil kecepatan angin saat ini
        const now = new Date();
        const currentHour = now.getHours();

        const currentWind = param.times.find((time) => {
          const timeHour = parseInt(time.datetime.substring(8, 10));
          return timeHour === currentHour;
        });

        if (currentWind) {
          weatherData.current.windSpeed = currentWind.value;
        }
      } else if (param.id === "vs") {
        // Mengambil jarak pandang saat ini
        const now = new Date();
        const currentHour = now.getHours();

        const currentVisibility = param.times.find((time) => {
          const timeHour = parseInt(time.datetime.substring(8, 10));
          return timeHour === currentHour;
        });

        if (currentVisibility) {
          weatherData.current.visibility = currentVisibility.value;
        }
      }
    });

    // Mengumpulkan prakiraan cuaca harian
    const dailyForecasts = {};

    tajemsariData.params.forEach((param) => {
      if (param.id === "weather") {
        param.times.forEach((time) => {
          const date = time.datetime.substring(0, 8); // Format: YYYYMMDD
          const hour = parseInt(time.datetime.substring(8, 10));

          // Kita hanya akan mengambil data untuk jam 12 siang sebagai representasi harian
          if (hour === 12) {
            if (!dailyForecasts[date]) {
              dailyForecasts[date] = {};
            }
            dailyForecasts[date].weatherCode = time.value;
          }
        });
      } else if (param.id === "t") {
        param.times.forEach((time) => {
          const date = time.datetime.substring(0, 8);
          const hour = parseInt(time.datetime.substring(8, 10));

          if (hour === 12) {
            if (!dailyForecasts[date]) {
              dailyForecasts[date] = {};
            }
            dailyForecasts[date].temperature = time.value;
          }
        });
      }
    });

    // Mengonversi dailyForecasts menjadi array
    for (const date in dailyForecasts) {
      if (
        dailyForecasts[date].weatherCode &&
        dailyForecasts[date].temperature
      ) {
        weatherData.forecasts.push({
          date: date,
          weatherCode: dailyForecasts[date].weatherCode,
          temperature: dailyForecasts[date].temperature,
        });
      }
    }

    // Mengurutkan berdasarkan tanggal
    weatherData.forecasts.sort((a, b) => a.date.localeCompare(b.date));

    // Mengambil maksimal 7 hari
    weatherData.forecasts = weatherData.forecasts.slice(0, 7);

    // Update UI dengan data cuaca
    updateWeatherUI(weatherData);
    showNotification("Data cuaca berhasil diperbarui", "success");
  } catch (error) {
    console.error("Error fetching weather data:", error);
    // Fallback to static data if API fails
    useStaticWeatherData();
    showNotification(
      "Gagal memuat data cuaca. Menampilkan data simulasi.",
      "error"
    );
  } finally {
    // Sembunyikan loading state
    document.getElementById("refresh-weather").classList.remove("loading");
  }
}

// Fungsi untuk memperbarui UI dengan data cuaca
function updateWeatherUI(data) {
  // Update current weather
  if (data.current.temperature) {
    document.getElementById(
      "current-temp"
    ).textContent = `${data.current.temperature}°C`;
  }

  if (data.current.weatherCode) {
    document.getElementById("weather-desc").textContent = getWeatherDesc(
      data.current.weatherCode
    );
    document.getElementById("weather-icon").className = `${getWeatherIcon(
      data.current.weatherCode
    )} weather-icon`;
  }

  if (data.current.humidity) {
    document.getElementById("humidity").textContent = data.current.humidity;
  }

  if (data.current.windSpeed) {
    document.getElementById("wind-speed").textContent = data.current.windSpeed;
  }

  if (data.current.visibility) {
    document.getElementById("visibility").textContent = data.current.visibility;
  }

  // Update forecast
  const forecastContainer = document.getElementById("forecast-container");
  forecastContainer.innerHTML = "";

  const tempData = [];
  const labels = [];

  data.forecasts.forEach((forecast) => {
    // Format tanggal: YYYYMMDD -> Date object
    const year = parseInt(forecast.date.substring(0, 4));
    const month = parseInt(forecast.date.substring(4, 6)) - 1;
    const day = parseInt(forecast.date.substring(6, 8));

    const forecastDate = new Date(year, month, day);
    const dayNames = ["Ming", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
    const dayName = dayNames[forecastDate.getDay()];

    const formattedDate = `${day}/${month + 1}`;

    const forecastDay = document.createElement("div");
    forecastDay.className = "forecast-day";
    forecastDay.innerHTML = `
                    <div>${dayName}</div>
                    <div>${formattedDate}</div>
                    <i class="${getWeatherIcon(forecast.weatherCode)}"></i>
                    <div class="temp">${forecast.temperature}°C</div>
                    <div>${getWeatherDesc(forecast.weatherCode)}</div>
                `;

    forecastContainer.appendChild(forecastDay);

    // Kumpulkan data untuk chart
    tempData.push(parseInt(forecast.temperature));
    labels.push(`${dayName} ${formattedDate}`);
  });

  // Update chart
  updateTempChart(labels, tempData);

  // Update warning system
  updateWarningSystem(data);
}

// Fungsi untuk memperbarui chart suhu
function updateTempChart(labels, data) {
  const ctx = document.getElementById("temp-chart").getContext("2d");

  // Hapus chart sebelumnya jika ada
  if (tempChart) {
    tempChart.destroy();
  }

  tempChart = new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Suhu (°C)",
          data: data,
          backgroundColor: "rgba(46, 125, 50, 0.2)",
          borderColor: "rgba(46, 125, 50, 1)",
          borderWidth: 2,
          pointBackgroundColor: "rgba(46, 125, 50, 1)",
          pointRadius: 4,
          tension: 0.3,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          backgroundColor: "rgba(0, 0, 0, 0.7)",
          titleFont: {
            size: 14,
          },
          bodyFont: {
            size: 13,
          },
          padding: 10,
        },
      },
      scales: {
        y: {
          beginAtZero: false,
          grid: {
            color: "rgba(0, 0, 0, 0.05)",
          },
          ticks: {
            font: {
              size: 11,
            },
          },
        },
        x: {
          grid: {
            display: false,
          },
          ticks: {
            font: {
              size: 11,
            },
          },
        },
      },
    },
  });
}

// Fungsi untuk memperbarui sistem peringatan dini
function updateWarningSystem(data) {
  const warningList = document.getElementById("warning-list");
  warningList.innerHTML = "";

  // Check for heavy rain forecast (code >= 60)
  const heavyRainDays = data.forecasts.filter((forecast) => {
    const code = parseInt(forecast.weatherCode);
    return code >= 60;
  });

  if (heavyRainDays.length > 0) {
    document.getElementById("warning-status").textContent =
      "Potensi hujan diprediksi terjadi pada:";

    heavyRainDays.forEach((forecast) => {
      // Format tanggal
      const year = parseInt(forecast.date.substring(0, 4));
      const month = parseInt(forecast.date.substring(4, 6)) - 1;
      const day = parseInt(forecast.date.substring(6, 8));
      const forecastDate = new Date(year, month, day);

      const dayNames = [
        "Minggu",
        "Senin",
        "Selasa",
        "Rabu",
        "Kamis",
        "Jumat",
        "Sabtu",
      ];
      const dayName = dayNames[forecastDate.getDay()];

      const li = document.createElement("li");
      li.textContent = `${dayName}, ${day}/${month + 1} (${getWeatherDesc(
        forecast.weatherCode
      )})`;
      warningList.appendChild(li);
    });

    document.getElementById("warning-recommendation").innerHTML = `
                    <strong>Rekomendasi:</strong> 
                    Perhatikan sistem drainase lahan, tunda aktivitas pengeringan hasil panen, 
                    dan pastikan tanaman terlindung dari potensi genangan air.
                `;
  } else {
    document.getElementById("warning-status").textContent =
      "Tidak ada potensi cuaca ekstrem dalam 7 hari ke depan";
    warningList.innerHTML = "<li>Kondisi cuaca relatif stabil</li>";
    document.getElementById("warning-recommendation").innerHTML = `
                    <strong>Rekomendasi:</strong> 
                    Kondisi cuaca mendukung aktivitas pertanian. Manfaatkan periode ini untuk 
                    persiapan lahan dan penanaman sesuai kalender tanam.
                `;
  }
}

// // Fungsi untuk rekomendasi tanam berdasarkan komoditas
// function getPlantingRecommendation(crop) {
//   const recommendations = {
//     padi: "Tanam: 1-15 Agustus | Panen: 15-30 November<br>Rekomendasi: Pastikan sistem irigasi berfungsi dengan baik selama fase vegetatif.",
//     jagung:
//       "Tanam: 1-20 Juli | Panen: 20-30 September<br>Rekomendasi: Perhatikan drainase tanah untuk menghindari genangan air.",
//     kedelai:
//       "Tanam: 10-25 Agustus | Panen: 25 Oktober-10 Desember<br>Rekomendasi: Berikan pupuk fosfor pada awal pertumbuhan.",
//     cabe: "Tanam: 15-30 Juli | Panen: 15-30 Oktober<br>Rekomendasi: Gunakan mulsa plastik untuk menjaga kelembaban tanah.",
//     bawang:
//       "Tanam: 5-20 Agustus | Panen: 5-20 November<br>Rekomendasi: Kontrol gulma secara rutin untuk mencegah persaingan nutrisi.",
//   };

//   return (
//     recommendations[crop] || "Pilih komoditas untuk melihat rekomendasi tanam"
//   );
// }

// Fallback function if API fails
function useStaticWeatherData() {
  document.getElementById("current-temp").textContent = "28°C";
  document.getElementById("weather-desc").textContent = "Cerah Berawan";
  document.getElementById("weather-icon").className =
    "fas fa-cloud-sun weather-icon";
  document.getElementById("humidity").textContent = "65";
  document.getElementById("wind-speed").textContent = "12";
  document.getElementById("visibility").textContent = "10";

  const warningList = document.getElementById("warning-list");
  warningList.innerHTML =
    "<li>Rabu, 24 Juli 2025 (Hujan Lebat)</li><li>Kamis, 25 Juli 2025 (Hujan Sedang)</li>";

  // Update chart with static data
  updateTempChart(
    [
      "Sen 21/7",
      "Sel 22/7",
      "Rab 23/7",
      "Kam 24/7",
      "Jum 25/7",
      "Sab 26/7",
      "Min 27/7",
    ],
    [28, 29, 27, 26, 27, 28, 29]
  );

  // Show error message
  const sourceInfo = document.querySelector(".data-source");
  sourceInfo.innerHTML =
    '<i class="fas fa-exclamation-triangle"></i> Gagal mengambil data real-time. Menampilkan data simulasi.';
  sourceInfo.style.color = "var(--warning)";
}

// Fungsi untuk memperbarui lokasi
function updateLocation(newLocation) {
  document.getElementById("current-location").textContent = newLocation;
  showNotification(`Lokasi diperbarui: ${newLocation}`, "success");
}

// Event listeners
document.addEventListener("DOMContentLoaded", function () {
  // Crop buttons
  const cropButtons = document.querySelectorAll(".crop-btn");
  const plantingAdvice = document.getElementById("planting-advice");

  cropButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const crop = this.getAttribute("data-crop");
      plantingAdvice.innerHTML = getPlantingRecommendation(crop);

      // Update active button
      cropButtons.forEach((btn) => btn.classList.remove("active"));
      this.classList.add("active");
    });
  });

  // Refresh button
  document
    .getElementById("refresh-weather")
    .addEventListener("click", function () {
      fetchBMKGWeather();
    });

  // Change location button
  document
    .getElementById("change-location")
    .addEventListener("click", function () {
      document.getElementById("search-modal").classList.add("active");
    });

  // Close modal button
  document.getElementById("close-modal").addEventListener("click", function () {
    document.getElementById("search-modal").classList.remove("active");
  });

  // Location options
  const locationOptions = document.querySelectorAll(".location-option");
  locationOptions.forEach((option) => {
    option.addEventListener("click", function () {
      const location = this.getAttribute("data-location");
      updateLocation(location);
      document.getElementById("search-modal").classList.remove("active");
    });
  });

  // Panggil fungsi untuk mengambil data cuaca saat halaman dimuat
  fetchBMKGWeather();
});
