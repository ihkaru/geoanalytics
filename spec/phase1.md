Deskripsi Fase Awal: "Dashboard Pemetaan SBR Interaktif"
Aplikasi ini pada fase awalnya adalah sebuah viewer data spasial yang memungkinkan pengguna (OPD, Bupati) untuk memvisualisasikan dan memfilter direktori usaha (SBR) BPS di atas peta Kabupaten Mempawah.

Fitur Utama di Sisi Pengguna (Frontend F7/Vue):
Visualisasi Peta Dasar:

Menampilkan peta dasar (misalnya OpenStreetMap) yang terpusat di wilayah Kabupaten Mempawah.

Layer Batas Wilayah (Poligon):

Menampilkan layer poligon batas wilayah (Kecamatan dan/atau Desa) yang diambil dari file GeoJSON.

Setiap poligon wilayah bisa diklik untuk menampilkan namanya (misal: "Kecamatan Anjongan").

Layer Sebaran Usaha (Titik):

Menampilkan semua data usaha dari tabel SBR sebagai marker (titik) di atas peta.

Saat marker diklik, akan muncul popup yang menampilkan informasi dasar usaha tersebut (misal: nama_usaha, kegiatan_usaha, kbli, alamat).

Fitur Filter Sederhana (Non-Spasial):

Tersedia dropdown atau button group di UI (luar peta) untuk memfilter marker yang tampil berdasarkan:

Status Usaha (misal: "Hanya tampilkan yang Aktif").

Skala Usaha (misal: "Hanya tampilkan UMKM").

Fitur Filter Spasial (Dasar):

Ini adalah fitur interaktif kuncinya: Saat pengguna mengklik sebuah poligon wilayah (misal: Kecamatan Anjongan)...

...aplikasi akan otomatis memfilter dan hanya menampilkan marker usaha yang berada di dalam wilayah tersebut (berdasarkan kdkec atau kddesa).

Alur Kerja Teknis di Balik Layar (Backend Laravel):
Frontend (F7/Vue + Leaflet): Bertugas merender peta.

Saat dimuat, ia memanggil 2 API dari backend.

API Call 1: Meminta data GeoJSON batas wilayah (misal: /api/wilayah/kecamatan).

API Call 2: Meminta data semua usaha (misal: /api/usaha).

Backend (Laravel): Bertugas sebagai penyedia data murni (API).

Endpoint /api/wilayah/...: Membaca file GeoJSON statis dari storage dan mengirimkannya ke frontend.

Endpoint /api/usaha: Melakukan query ke database PostGIS.

Logika Filter: Jika request berisi parameter (misal: /api/usaha?status=1 atau /api/usaha?kdkec=91), Laravel hanya menambahkan klausa WHERE sederhana pada query Eloquent-nya.

Database (PostGIS):

Pada fase ini, PostGIS utamanya berfungsi sebagai database PostgreSQL yang tangguh untuk menyimpan tabel usaha Anda (termasuk kolom latitude dan longitude).

Kita belum menggunakan fungsi spasial canggih PostGIS (seperti ST_Within atau ST_DWithin). Logika filter spasial masih ditangani di level front-end (klik poligon) dan back-end (query WHERE kdkec = ...).

Singkatnya, Fase Awal ini adalah tentang visualisasi dan filter dasar. Kita memastikan semua data (poligon dan titik) dapat tampil di peta, dapat berinteraksi, dan dapat difilter berdasarkan kode wilayah dan kategori dasarnya.

Ini adalah fondasi yang kokoh sebelum kita masuk ke Fase 2: mengganti dropdown filter manual dengan input bahasa alami (LLM).
