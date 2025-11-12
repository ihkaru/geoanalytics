# Spesifikasi Teknis: Peta Peluang Ekonomi (Economic Opportunity Map) Kabupaten Mempawah

Dokumen ini menguraikan kebutuhan data dan spesifikasi teknis untuk membangun platform web-mapping interaktif guna menganalisis potensi ekonomi di Kabupaten Mempawah.

**Tujuan Utama:** Membangun aplikasi berbasis GIS yang membandingkan Penawaran (Supply) usaha yang ada dengan Permintaan (Demand) penduduk, untuk mengidentifikasi Kesenjangan Layanan (Service Gaps). Hasil akhir adalah peta interaktif "Zona Peluang" (Merah, Kuning, Jenuh, Hijau).

## 1. Arsitektur Teknologi

- **Backend:** Laravel
- **Database:** PostgreSQL dengan ekstensi PostGIS
- **Frontend:** Framework7 Vue
- **Map Rendering:** MapLibre GL JS
- **Format Data Peta:** Vector Tiles (MVT)

## 2. Gambaran Besar Alur Data (Data Flow Overview)

Sistem ini dirancang dengan arsitektur Pre-calculation (Pra-Perhitungan) untuk menghindari kueri GIS yang lambat (on-the-fly). Analisis berat dilakukan di backend secara terjadwal, dan frontend hanya mengonsumsi data yang sudah matang.

Berikut adalah gambaran alur datanya dari hulu ke hilir:

### INPUT (Data Mentah di Database):

- **usaha (Data Supply):** 14.000+ titik koordinat lokasi usaha + KBLI.
- **demografi_sls (Data Demand):** Jumlah penduduk di setiap SLS.
- **peta_sls (Data Spasial):** 1.300+ poligon batas SLS.
- **referensi_kbli (Data Kamus):** Daftar KBLI untuk UI.

### PROCESS (Backend - Laravel Scheduler ->daily()):

- Sebuah job (`Perintah Artisan analisis:run-zona`) berjalan setiap malam.
- Job ini mengeksekusi kueri PostGIS yang "mahal" (`Buffer`, `Union`, `Difference`, `Spatial Join`) untuk setiap KBLI prioritas (Apotek, Warung Kopi, dll.) dan setiap radius (500m, 1000m).
- **Logika Inti:** Untuk setiap SLS, kueri ini menghitung `skor_potensi` (misal: jumlah penduduk yang tidak terlayani) dan menentukan `zona` (Merah/Jenuh).

### OUTPUT (Data Matang - Cache):

- Hasil dari job di atas disimpan dalam satu tabel cache yang cepat diakses.
- **Tabel:** `analisis_zona_cache`
- **Isinya:** (`idsubsls`, `kbli`, `radius`, `skor_potensi`, `zona`, `jumlah_penduduk`, `jumlah_usaha_sekitar`)

### SERVING (Backend - API Server):

- Saat frontend meminta peta, API `GET /api/peta-tiles/{z}/{x}/{y}` dipanggil.
- API ini **TIDAK MENGHITUNG ULANG**.
- Ia hanya men-`JOIN` `peta_sls` (untuk geometri) dengan `analisis_zona_cache` (untuk data skor) dan menyajikannya sebagai Vector Tiles (MVT).

### CONSUMPTION (Frontend - F7 Vue + MapLibre):

- MapLibre menerima Vector Tiles yang ringan.
- Pengguna memilih KBLI dan Radius dari dropdown.
- MapLibre menggunakan `expression` untuk mewarnai poligon secara instan di sisi klien (GPU) berdasarkan data `zona` yang sudah ada di dalam tiles.
- Pengguna mengklik sebuah poligon untuk melihat detail analisisnya.

Alur ini memastikan user experience di frontend tetap instan (< 1 detik), meskipun analisis di backend sangat kompleks dan memakan waktu (dijalankan semalam).

## 3. Kebutuhan Data (Data Requirements)

Analisis ini membutuhkan 4 (empat) set data utama yang harus tersedia di database PostGIS.

### 3.1. Data SUPPLY (Penawaran) - Perlu Tindakan

Data ini mengidentifikasi semua usaha yang terdaftar dan lokasinya.

**Tabel:** `usaha`

**Data Sampel (dari User):**

```json
{
  "idsbr": "29492",
  "nama_usaha": "EQUATOR MANUNGGAL POWER, PT",
  "alamat": "",
  "kdkab": "04",
  "kdkec": "",
  "kddesa": "",
  "latitude": null,
  "longitude": null,
  "idsubsls": "",
  "kbli": "35111"
}
```

**Kolom Kritis yang Dibutuhkan:**

- `idsbr` (Primary Key)
- `kbli` (Kunci analisis, WAJIB terisi 5 digit)
- `latitude` (WAJIB terisi, tipe float atau decimal)
- `longitude` (WAJIB terisi, tipe float atau decimal)
- `idsubsls` (Kunci fallback jika lat/long NULL, untuk di-JOIN ke Peta SLS)
- `geom` (Kolom `geometry(Point)` PostGIS, dibuat dari `latitude` & `longitude`)

**Status & Tindak Lanjut (KRUSIAL):**

- **Masalah:** Sampel data menunjukkan `latitude`, `longitude`, `kddesa`, dan `idsubsls` NULL / Kosong.
- **Risiko:** Analisis spasial (Buffer, Proximity) TIDAK DAPAT DIJALANKAN tanpa data lokasi. Peta heatmap atau cluster tidak dapat dibuat.
- **Rekomendasi #1 (Geocoding):** Lakukan proses Geocoding untuk mengisi `latitude`/`longitude` berdasarkan kolom `alamat` (jika alamat terisi).
- **Rekomendasi #2 (Data Cleaning):** Lakukan proses data matching/cleaning untuk mengisi `idsubsls` atau `kddesa` berdasarkan data administratif lain. Ini adalah fallback jika geocoding gagal.

### 3.2. Data DEMAND (Permintaan)

Data ini mengidentifikasi pasar potensial (penduduk) di setiap unit analisis terkecil.

**Tabel:** `demografi_sls`

**Kolom Kritis yang Dibutuhkan:**

- `idsubsls` (Primary Key, contoh: 6104010001001S)
- `kode_desa` (Kunci agregasi)
- `nama_desa`
- `jumlah_penduduk` (WAJIB terisi)
- `jumlah_kk` (Kepala Keluarga)

**Sumber:** BPS (Hasil Sensus Penduduk / Proyeksi Penduduk).

### 3.3. Data SPASIAL (Peta Poligon)

Data ini adalah "wadah" poligon yang akan diwarnai di peta.

**Tabel:** `peta_sls` (1.300+ poligon)

**Kolom Kritis yang Dibutuhkan:**

- `idsubsls` (Primary Key, untuk di-JOIN ke `demografi_sls`)
- `geom` (WAJIB ada, tipe `geometry(Polygon)` atau `MultiPolygon`)

**Sumber:** BPS (Peta Wilayah Kerja Statistik / Wilkerstat).

### 3.4. Data REFERENSI (Kamus)

Data ini digunakan untuk mengisi UI dropdown di frontend.

**Tabel:** `referensi_kbli`

**Kolom Kritis yang Dibutuhkan:**

- `kbli_5_digit` (Primary Key)
- `judul_kbli` (Contoh: "Apotek")
- `deskripsi_kbli`

## 4. Spesifikasi Fungsional

### 4.1. Backend (Laravel + PostGIS)

Backend akan berfokus pada Pre-calculation (karena analisis on-the-fly terlalu lambat) dan penyajian Vector Tiles.

#### A. Job Terjadwal (Pre-calculation)

- **Trigger:** Scheduler Laravel (`php artisan schedule:run`), berjalan setiap malam (`->daily()`).
- **Perintah:** `php artisan analisis:run-zona`
- **Logika:**
  - Membuat loop untuk setiap **KBLI Prioritas** (misal: '47721', '56303', '45407', ...).
  - Membuat loop untuk setiap **Radius Analisis** (misal: 500m, 1000m, 1500m).
  - Menjalankan kueri PostGIS yang "mahal" (`Buffer`, `Union`, `Difference`, `Spatial Join`) untuk menghitung `skor_potensi` dan `zona` untuk setiap SLS, KBLI, dan Radius.
  - Menyimpan hasilnya ke tabel cache.
- **Tabel Output (Cache):** `analisis_zona_cache`
  - `idsubsls` (Kunci)
  - `kbli_5_digit` (Kunci)
  - `radius_meter` (Kunci)
  - `skor_potensi` (Hasil skor, misal: jumlah penduduk tidak terlayani)
  - `zona` (Hasil klasifikasi, misal: 'Merah', 'Kuning', 'Jenuh', 'Hijau')
  - `jumlah_penduduk` (Data pendukung untuk popup)
  - `jumlah_usaha_sekitar` (Data pendukung untuk popup)
  - `rasio_penduduk_per_usaha` (Data pendukung untuk popup)

#### B. API (Penyaji Data ke Frontend)

- **API Tile Server (Utama):**
  - **Endpoint:** `GET /api/peta-tiles/{z}/{x}/{y}`
  - **Fungsi:** Menyajikan Vector Tiles (MVT).
  - **Logika:**
    - Kueri PostGIS `ST_AsMVT`.
    - `JOIN` antara `peta_sls` (untuk `geom`) dan `analisis_zona_cache` (untuk properti `skor_potensi` dan `zona`).
    - Frontend (MapLibre) akan menerima tiles yang sudah berisi poligon SLS dan semua data skornya.
- **API Referensi (Dropdown):**
  - **Endpoint:** `GET /api/referensi-kbli`
  - **Fungsi:** Mengambil data dari tabel `referensi_kbli` untuk mengisi dropdown di UI.
- **API Detail Zona (Untuk Popup):**
  - **Endpoint:** `GET /api/zona-detail/{idsubsls}`
  - **Fungsi:** Mengambil detail data analisis untuk satu zona SLS spesifik.
  - **Logika:** `SELECT * FROM analisis_zona_cache WHERE idsubsls = ?`. Mengembalikan semua data pendukung untuk ditampilkan di popup.

### 4.2. Frontend (Framework7 Vue + MapLibre)

Frontend tidak melakukan analisis. Frontend hanya bertugas mengambil tiles dan mewarnainya berdasarkan input pengguna.

#### A. Tampilan Peta (Map View)

- Menggunakan komponen `maplibregl.Map`.
- Saat `map.on('load')`:
  - Menambahkan **Source** (Sumber Data) yang mengarah ke API Tile Server: `map.addSource('sumber-sls', { type: 'vector', tiles: ['/api/peta-tiles/{z}/{x}/{y}'] })`.
  - Menambahkan **Layer** (Lapisan Peta): `map.addLayer({ id: 'layer-sls', type: 'fill', source: 'sumber-sls', ... })`.

#### B. Kontrol UI (User Input)

- **Dropdown KBLI:**
  - **Komponen:** `f7-list-input` (tipe `select`).
  - **Label:** "Pilih Jenis Usaha".
  - **Data:** Diisi dari API `GET /api/referensi-kbli`.
  - **Variabel Vue:** `selectedKBLI` (misal: '47721').
- **Input Radius:**
  - **Komponen:** `f7-list-input` (tipe `select`) atau `f7-range` (Slider).
  - **Label:** "Pilih Radius Layanan".
  - **Pilihan:** 500m, 1000m, 1500m.
  - **Variabel Vue:** `selectedRadius` (misal: 1000).

#### C. Alur Interaksi (Sangat Cepat)

- Pengguna mengubah `selectedKBLI` atau `selectedRadius` di UI.
- Watcher Vue mendeteksi perubahan ini.
- Aplikasi **TIDAK MEMANGGIL API BARU**.
- Aplikasi hanya menjalankan fungsi MapLibre: `map.setPaintProperty(...)`.
- **Logika `setPaintProperty`:**
  - Menggunakan **MapLibre Expression** untuk mewarnai layer `layer-sls`.
  - Logika pseudocode-nya: `"Warnai poligon dengan fill-color berdasarkan properties.zona HANYA JIKA properties.kbli_5_digit == selectedKBLI DAN properties.radius_meter == selectedRadius."`
- **Hasilnya:** Peta akan berubah warna (Merah/Jenuh/Hijau) secara instan (< 1 detik) karena pewarnaan terjadi di GPU client-side.

#### D. Interaktivitas Peta: Popup Detail Zona

- **Trigger:** `map.on('click', 'layer-sls', ...)`
- **Logika:**
  1. Pengguna mengklik sebuah poligon pada layer `layer-sls`.
  2. Ambil `idsubsls` dari properti fitur yang diklik.
  3. Panggil API `GET /api/zona-detail/{idsubsls}`.
  4. Setelah data diterima, tampilkan `maplibregl.Popup` di lokasi klik.
  5. **Isi Popup:** Tampilkan detail analisis dari API, seperti:
     - ID Wilayah (IDSUBSLS)
     - Zona Potensi
     - Skor Potensi
     - Jumlah Penduduk
     - Jumlah Usaha Sejenis
     - Rasio Penduduk per Usaha
- **Tampilan:** Dapat menggunakan komponen Framework7 di dalam popup jika memungkinkan, atau HTML standar yang diformat dengan baik.