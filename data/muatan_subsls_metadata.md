# **Metadata: Pemutakhiran Wilkerstat BPS Kabupaten Mempawah**

Dokumen ini berisi metadata untuk dataset hasil kegiatan Pemutakhiran Wilayah Kerja Statistik (Wilkerstat) yang dilaksanakan oleh BPS Kabupaten Mempawah.

- **Pembaruan Terakhir:** Semester 1, 2025
- **Penerbit:** BPS Kabupaten Mempawah

## **1. Informasi Umum**

| Atribut                        | Deskripsi                                                                                                                                                         |
| :----------------------------- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Tujuan Data**                | Untuk mendapatkan data muatan (jumlah keluarga, bangunan, dan usaha) terbaru pada level wilayah kerja statistik terkecil (Sub-SLS) di seluruh Kabupaten Mempawah. |
| **Sumber Data**                | Akumulasi data dari berbagai kegiatan survei dan sensus yang dilaksanakan oleh BPS Kabupaten Mempawah.                                                            |
| **Cakupan Wilayah**            | Seluruh wilayah administratif Kabupaten Mempawah.                                                                                                                 |
| **Frekuensi Pemutakhiran**     | Diperbarui secara berkala setiap semester, seiring dengan pelaksanaan kegiatan survei dan sensus BPS.                                                             |
| **Pengguna Utama**             | Internal BPS, sebagai kerangka induk (master frame) untuk penentuan sampel dan alokasi petugas pada kegiatan survei dan sensus di masa mendatang.                 |
| **Kunci Primer (Primary Key)** | `idsubsls`. Kolom ini berisi ID unik untuk setiap baris data yang merepresentasikan satu unit Sub-SLS.                                                            |

---

## **2. Kamus Data (Data Dictionary)**

Berikut adalah penjelasan rinci untuk setiap kolom dalam dataset.

| Nama Kolom      | Tipe Data | Deskripsi                                                                                                                  | Contoh / Catatan                               |
| :-------------- | :-------- | :------------------------------------------------------------------------------------------------------------------------- | :--------------------------------------------- |
| `id`            | Integer   | ID unik internal untuk setiap baris dalam file data.                                                                       | `20662799`                                     |
| `semester`      | String    | Periode waktu pemutakhiran data. Format: `YYYY_S`.                                                                         | `2025_1` (Semester 1, Tahun 2025)              |
| `idsls`         | String    | ID unik level Satuan Lingkungan Setempat (SLS). Terdiri dari 14 digit gabungan: `kdprov`+`kdkab`+`kdkec`+`kddesa`+`kdsls`. | `61040800010001`                               |
| `nmsls`         | String    | Nama SLS, umumnya setingkat RT, RW, atau dusun.                                                                            | `RT 001 RW 01 DUSUN MAWAR`                     |
| `nama_ketua`    | String    | Nama ketua SLS (misalnya, Ketua RT).                                                                                       | `MARTOK`                                       |
| `jenis`         | String    | Jenis wilayah kerja statistik.                                                                                             | Nilai: `SLS` atau `Non-SLS`                    |
| `kdprov`        | String    | Kode Provinsi (Kalimantan Barat). Sesuai standar BPS.                                                                      | `61`                                           |
| `kdkab`         | String    | Kode Kabupaten (Mempawah). Sesuai standar BPS.                                                                             | `04`                                           |
| `kdkec`         | String    | Kode Kecamatan. Sesuai standar BPS.                                                                                        | `080`                                          |
| `kddesa`        | String    | Kode Desa/Kelurahan. Sesuai standar BPS.                                                                                   | `001`                                          |
| `kdsls`         | String    | Kode Satuan Lingkungan Setempat (SLS) sebanyak 4 digit, unik dalam satu desa.                                              | `0001`                                         |
| `kdsubsls`      | String    | Kode Sub-SLS sebanyak 2 digit. Bernilai `00` jika SLS tidak dipecah.                                                       | `00`                                           |
| `klas`          | Integer   | Klasifikasi urban/rural untuk desa/kelurahan.                                                                              | `1`: Perkotaan (Urban), `2`: Perdesaan (Rural) |
| `nmprov`        | String    | Nama Provinsi.                                                                                                             | `KALIMANTAN BARAT`                             |
| `nmkab`         | String    | Nama Kabupaten.                                                                                                            | `MEMPAWAH`                                     |
| `nmkec`         | String    | Nama Kecamatan.                                                                                                            | `JONGKAT`                                      |
| `nmdesa`        | String    | Nama Desa/Kelurahan.                                                                                                       | `SUNGAI NIPAH`                                 |
| `kk`            | Integer   | Jumlah total Kepala Keluarga (KK) di dalam Sub-SLS.                                                                        | `76`                                           |
| `btt`           | Integer   | Jumlah total Bangunan Tempat Tinggal (BTT).                                                                                | `53`                                           |
| `bttk`          | Integer   | Jumlah Bangunan Tempat Tinggal Kosong (BTTK).                                                                              | `0`                                            |
| `bku`           | Integer   | Jumlah Bangunan Khusus (contoh: rumah sakit, sekolah, asrama).                                                             | `1`                                            |
| `bbtt_nonusaha` | Integer   | Jumlah Bangunan Bukan Tempat Tinggal yang tidak digunakan untuk usaha.                                                     | `3`                                            |
| `usaha`         | Integer   | Jumlah unit usaha. Satu usaha dihitung jika memiliki satu pembukuan tersendiri.                                            | `4`                                            |
| `muatan`        | Integer   | **Perkiraan total muatan** dalam Sub-SLS, dihitung dengan formula: `Max(btt, kk) + bttk + bbtt_nonusaha + usaha`.          | `83`                                           |
| `dominan`       | Integer   | Kode yang merepresentasikan karakteristik dominan dari muatan di dalam Sub-SLS. (Lihat tabel kode di bawah).               | `1`                                            |
| `berubah_batas` | Integer   | Indikator apakah terjadi perubahan batas pada SLS sejak pemutakhiran terakhir.                                             | `1`: Ya, berubah, `0`: Tidak berubah           |
| `idsubsls`      | String    | **KUNCI PRIMER**. ID unik level Sub-SLS. Terdiri dari 16 digit gabungan: `idsls` + `kdsubsls`.                             | `6104080001000100`                             |

---

## **3. Detail Kode Tambahan**

### 3.1 Kode Karakteristik Dominan (`dominan`)

| Kode | Kategori Muatan Dominan                              |
| :--- | :--------------------------------------------------- |
| 1    | Permukiman Biasa                                     |
| 2    | Permukiman Mewah / Elite / Real estat                |
| 3    | Permukiman Kumuh                                     |
| 4    | Apartemen / Kondominium / Flat                       |
| 5    | Kos-kosan / Kontrakan                                |
| 6    | Pesantren / Barak / Asrama / Seminari                |
| 8    | Pusat Perbelanjaan Modern / Mall / Pertokoan / Pasar |
| 9    | Kawasan Industri / Sentra Industri                   |
| 10   | Hotel / Tempat Rekreasi                              |
| 11   | Wilayah Tidak Berpenghuni (hutan, kebun, dll.)       |
| 12   | Perkantoran                                          |
| 13   | Pelabuhan / Bandara / Terminal Bus / Stasiun         |
