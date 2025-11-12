### **Snippet Data**

kbli_id,level,judul,deskripsi
A,kategori,"[A] Pertanian, Kehutanan dan Perikanan","Kategori ini mencakup semua kegiatan ekonomi/lapangan usaha, yang meliputi pertanian tanaman pangan, perkebunan, hortikultura, peternakan, pemanenan hasil hutan serta penangkapan dan budidaya ikan/biota air. Kategori ini juga mencakup jasa penunjang masing-masing kegiatan ekonomi tersebut."
B,kategori,[B] Pertambangan dan Penggalian,"Kategori ini mencakup kegiatan ekonomi/lapangan usaha pengambilan mineral dalam bentuk alami, yaitu padat (batu bara dan bijih logam), cair (minyak bumi) atau gas (gas alam). Kegiatan ini dapat dilakukan dengan metode yang berbeda seperti pertambangan dan penggalian di permukaan tanah atau dibawah tanah, pengoperasian sumur pertambangan, penambangan di dasar laut dan lain-lain. Kategori ini juga mencakup kegiatan tambahan untuk penyiapan barang tambang dan galian mentah untuk dipasarkan seperti pemecahan, pengasahan, pembersihan, pengeringan, sortasi bijih logam, pencairan gas alam dan aglomerasi bahan bakar padat."
01,golongan pokok,"[01] Pertanian Tanaman, Peternakan, Perburuan dan Kegiatan YBDI","Golongan pokok ini mencakup pertanian tanaman pangan, perkebunan dan hortikultura; usaha pemeliharaan hewan ternak dan unggas; perburuan dan penangkapan hewan dengan perangkap serta kegiatan penunjang ybdi yang ditujukan untuk dijual. Termasuk budidaya tanaman dan hewan ternak secara organik dan genetik. Kegiatan pertanian tidak mencakup kegiatan pengolahan dari komoditas pertanian, termasuk dalam Kategori C (Industri Pengolahan). Kegiatan konstruksi lahan seperti pembuatan petak-petak sawah, irigasi saluran pembuangan air, serta pembersihan dan perbaikan lahan untuk pertanian tidak termasuk di sini, tetapi tercakup pada kategori konstruksi (F)."
02,golongan pokok,[02] Pengelolaan Kehutanan dan Penebangan,"Golongan pokok ini mencakup produksi kayu bulat untuk industri manufaktur berbasis hutan (Golongan Pokok 16 dan 17) serta ekstraksi dan pengumpulan/pemungutan produk hutan non-kayu yang tumbuh liar. Selain produksi kayu, kegiatan kehutanan menghasilkan produk yang hanya diproses sedikit, seperti kayu bakar, arang, serpihan kayu dan kayu bulat yang digunakan dalam bentuk yang tidak diproses (mis. Pit-props, pulpwood, dll.). Kegiatan ini dapat dilakukan di hutan alam atau hutan tanaman."
011,golongan,[011] Pertanian Tanaman Semusim,"Golongan ini mencakup penanaman tanaman yang tidak berlangsung lebih dari dua musim panen. Termasuk penanaman tanaman dalam berbagai media dan budidaya tanaman secara genetik, dan juga penanaman untuk tujuan pembibitan dan pembenihan."
0111,subgolongan,"[0111] Pertanian serealia (bukan padi), aneka kacang dan biji-bijian penghasil minyak","Subgolongan ini mencakup pertanian semua serealia, aneka kacang dan biji-bijian penghasil minyak di lahan terbuka, termasuk pertanian tanaman organik dan pertanian tanaman yang telah dimodifikasi. Pertanian tanaman ini sering dikombinasikan dalam unit pertanian. Subgolongan ini mencakup : - Pertanian serealia seperti gandum, jagung, sorgum, gandum untuk membuat bir (barley), gandum hitam (rye), oats, millet dan serealia lainnya - Pertanian aneka kacang palawija, mencakup kacang kedelai, kacang tanah dan kacang hijau - Pertanian aneka kacang hortikultura, mencakup buncis, buncis besar, kacang panjang, cow peas, miju-miju, lupin, kacang polong, pigeon peas dan tanaman aneka kacang lainnya - Pertanian biji-bijian penghasil minyak, seperti biji kapas, biji castor, biji rami, biji mustard, niger seeds, rapeseed/canola, biji wijen, safflower seeds, biji bunga matahari dan tanaman penghasil minyak lainnya Subgolongan ini tidak mencakup : - Pertanian jagung (maize) untuk makanan ternak, lihat 0119"
01111,kelompok,[01111] Pertanian Jagung,"Kelompok ini mencakup usaha pertanian komoditas jagung mulai dari kegiatan pengolahan lahan, penanaman, pemeliharaan, dan juga pemanenan dan pasca panen jika menjadi satu kesatuan kegiatan tanaman jagung. Termasuk kegiatan pembibitan dan pembenihan tanaman jagung."

### **Analisis Kualitas Data**

[+] Kolom: 'kbli_id' - Panjang Karakter Maksimum: 5 - Jumlah Nilai Kosong/NULL : 0 dari 2710 baris - Bisa Berisi NULL (Nullable): Tidak - Semua Nilai Unik : Ya (Cocok untuk Primary Key) - Tipe Data DB Disarankan: VARCHAR(50)

[+] Kolom: 'level' - Panjang Karakter Maksimum: 14 - Jumlah Nilai Kosong/NULL : 0 dari 2710 baris - Bisa Berisi NULL (Nullable): Tidak - Tipe Data DB Disarankan: VARCHAR(50)

[+] Kolom: 'judul' - Panjang Karakter Maksimum: 178 - Jumlah Nilai Kosong/NULL : 0 dari 2710 baris - Bisa Berisi NULL (Nullable): Tidak - Tipe Data DB Disarankan: VARCHAR(255)

[+] Kolom: 'deskripsi' - Panjang Karakter Maksimum: 3191 - Jumlah Nilai Kosong/NULL : 0 dari 2710 baris - Bisa Berisi NULL (Nullable): Tidak - Tipe Data DB Disarankan: TEXT

# **Metadata: Dataset Klasifikasi Baku Lapangan Usaha Indonesia (KBLI) 2020**

## **1. Deskripsi Dataset**

Dokumen ini menyediakan metadata untuk dataset `kbli_data.csv`. Dataset ini berisi struktur lengkap dari **Klasifikasi Baku Lapangan Usaha Indonesia (KBLI) tahun 2020**, sebagaimana diperoleh melalui Web API resmi Badan Pusat Statistik (BPS).

KBLI adalah kerangka kerja standar yang digunakan untuk mengklasifikasikan aktivitas ekonomi di Indonesia dalam suatu hierarki yang terstruktur. Dataset ini mencakup semua level klasifikasi, mulai dari level tertinggi (Kategori) hingga level paling detail (Kelompok).

## **2. Detail File**

- **Nama File**: `kbli_data.csv`
- **Format**: CSV (Comma-Separated Values)
- **Encoding**: UTF-8
- **Total Baris Data**: 2710

## **3. Definisi Kolom (Skema Data)**

Tabel berikut merinci setiap kolom yang ada di dalam dataset.

| Nama Kolom      | Tipe Data (SQL) | Deskripsi                                                                                                                                                                             | Batasan (Constraints)     | Contoh Nilai                                                                  |
| :-------------- | :-------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | :------------------------ | :---------------------------------------------------------------------------- |
| **`kbli_id`**   | `VARCHAR(50)`   | **Kode Unik KBLI**. Merupakan _Primary Key_ dari dataset ini. Kode ini merepresentasikan posisi entitas dalam hierarki klasifikasi. Panjang kode bervariasi dari 1 hingga 5 karakter. | `PRIMARY KEY`, `NOT NULL` | `'A'`, `'01'`, `'011'`, `'0111'`, `'01111'`                                   |
| **`level`**     | `VARCHAR(50)`   | **Tingkat Hierarki** dari kode KBLI. Menjelaskan jenis klasifikasi dari kode tersebut. Nilai pada kolom ini bersifat kategorikal.                                                     | `NOT NULL`                | `'kategori'`, `'golongan pokok'`, `'golongan'`, `'subgolongan'`, `'kelompok'` |
| **`judul`**     | `VARCHAR(255)`  | **Judul Resmi** dari klasifikasi KBLI. Memberikan nama yang deskriptif dan mudah dibaca untuk setiap `kbli_id`. Umumnya diawali dengan kode KBLI di dalam kurung siku `[]`.           | `NOT NULL`                | `'[A] Pertanian, Kehutanan dan Perikanan'`, `'[01111] Pertanian Jagung'`      |
| **`deskripsi`** | `TEXT`          | **Penjelasan Rinci** mengenai cakupan aktivitas ekonomi yang termasuk dalam kode KBLI tersebut. Kolom ini berisi teks yang panjang dan mendetail.                                     | `NOT NULL`                | `"Kategori ini mencakup semua kegiatan ekonomi/lapangan usaha..."`            |

## **4. Struktur dan Hubungan Hierarki**

Data dalam dataset ini memiliki struktur hierarki yang melekat, yang dapat dilihat dari pola `kbli_id`. Level yang lebih detail merupakan turunan dari level yang lebih umum.

Contoh alur hierarki dari data sampel:

- **`A`** (Kategori)
  - **`01`** (Golongan Pokok)
    - **`011`** (Golongan)
      - **`0111`** (Subgolongan)
        - **`01111`** (Kelompok)

Setiap entri adalah node dalam pohon klasifikasi ini, dan `kbli_id` berfungsi sebagai jalurnya.
