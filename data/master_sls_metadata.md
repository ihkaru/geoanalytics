| Nama Kolom | Tipe Data (Disarankan) | Deskripsi                                      | Catatan / Relasi                                                                                      |
| ---------- | ---------------------- | ---------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| kdprov     | String                 | Kode Provinsi BPS.                             | 2 digit. (Contoh: 61)                                                                                 |
| nmprov     | String                 | Nama Provinsi.                                 | (Contoh: KALIMANTAN BARAT)                                                                            |
| kdkab      | String                 | Kode Kabupaten BPS.                            | 2 digit. Diawali '0'. (Contoh: 04)                                                                    |
| nmkab      | String                 | Nama Kabupaten.                                | (Contoh: MEMPAWAH)                                                                                    |
| kdkec      | String                 | Kode Kecamatan BPS.                            | 3 digit. (Contoh: 110)                                                                                |
| nmkec      | String                 | Nama Kecamatan.                                | (Contoh: SUNGAI KUNYIT)                                                                               |
| kddesa     | String                 | Kode Desa/Kelurahan BPS.                       | 3 digit. (Contoh: 011)                                                                                |
| nmdesa     | String                 | Nama Desa/Kelurahan.                           | (Contoh: SUNGAI DURI I)                                                                               |
| kdsls      | String                 | Kode Satuan Lingkungan Setempat (SLS).         | 4 digit. Unik di dalam satu desa. (Contoh: 0012)                                                      |
| nmsls      | String                 | Nama Satuan Lingkungan Setempat (SLS).         | Deskripsi tekstual dari SLS, seringkali berupa RT/RW atau Dusun. (Contoh: RT 010 RW 03 DUSUN SUTRA)   |
| idsls      | String                 | Kunci Primer (SLS). ID unik level SLS.         | 14 digit. Ini adalah gabungan dari: [kdprov] + [kdkab] + [kdkec] + [kddesa] + [kdsls].                |
| kdsubsls   | String                 | Kode Sub-SLS.                                  | 2 digit. (Contoh: 00). Seringkali 00 jika SLS tidak dipecah.                                          |
| idsubsls   | String                 | Kunci Primer (Sub-SLS). ID unik level Sub-SLS. | 16 digit. Ini adalah gabungan dari: [idsls] + [kdsubsls]. Ini adalah ID yang paling rinci (granular). |
