import requests
import csv
import time
import os
import json

# --- KONFIGURASI ---
# Ganti dengan API key Anda jika berbeda
API_KEY = "f7899a7f09e8352f04ad230dc7ad19fd"
BASE_URL = "https://webapi.bps.go.id/v1/api/view/model/kbli2020/lang/ind/id/kbli_2020_{}/key/{}/"

# Nama file untuk menyimpan progres
CSV_OUTPUT_FILE = 'kbli_data.csv'
QUEUE_FILE = 'queue.txt'
INVALID_IDS_FILE = 'invalid_ids.txt' # File untuk mencatat ID yang tidak valid

# Header untuk file CSV
CSV_HEADER = ['kbli_id', 'level', 'judul', 'deskripsi']

# Jeda antar request (dalam detik)
REQUEST_DELAY = 1

def load_progress():
    """Memuat progres dari file CSV, file antrian, dan file ID tidak valid."""
    processed_ids = set()
    if os.path.exists(CSV_OUTPUT_FILE):
        try:
            with open(CSV_OUTPUT_FILE, 'r', newline='', encoding='utf-8') as f:
                reader = csv.DictReader(f)
                for row in reader:
                    processed_ids.add(row['kbli_id'])
            print(f"Berhasil memuat {len(processed_ids)} ID yang sudah diproses dari '{CSV_OUTPUT_FILE}'.")
        except (IOError, csv.Error) as e:
            print(f"Peringatan: Gagal membaca file CSV: {e}. Memulai dengan set kosong.")

    queue = []
    if os.path.exists(QUEUE_FILE):
        try:
            with open(QUEUE_FILE, 'r', encoding='utf-8') as f:
                queue = [line.strip() for line in f if line.strip()]
            print(f"Berhasil memuat {len(queue)} ID dalam antrian dari '{QUEUE_FILE}'.")
        except IOError as e:
            print(f"Peringatan: Gagal membaca file antrian: {e}.")
    
    invalid_ids = set()
    if os.path.exists(INVALID_IDS_FILE):
        try:
            with open(INVALID_IDS_FILE, 'r', encoding='utf-8') as f:
                invalid_ids = {line.strip() for line in f if line.strip()}
            print(f"Berhasil memuat {len(invalid_ids)} ID yang sudah ditandai tidak valid.")
        except IOError as e:
            print(f"Peringatan: Gagal membaca file ID tidak valid: {e}.")

    return processed_ids, queue, invalid_ids

def save_queue(queue):
    """Menyimpan daftar ID yang masih dalam antrian ke file."""
    try:
        with open(QUEUE_FILE, 'w', encoding='utf-8') as f:
            for item in queue:
                f.write(f"{item}\n")
    except IOError as e:
        print(f"ERROR: Tidak dapat menyimpan antrian ke '{QUEUE_FILE}': {e}")

def save_invalid_id(kbli_id):
    """Menyimpan ID yang terkonfirmasi tidak valid untuk dilewati di masa depan."""
    try:
        with open(INVALID_IDS_FILE, 'a', encoding='utf-8') as f:
            f.write(f"{kbli_id}\n")
    except IOError as e:
        print(f"ERROR: Tidak dapat menulis ke file ID tidak valid '{INVALID_IDS_FILE}': {e}")

def append_to_csv(data_row):
    """Menambahkan satu baris data ke file CSV menggunakan DictWriter."""
    file_exists = os.path.isfile(CSV_OUTPUT_FILE)
    try:
        with open(CSV_OUTPUT_FILE, 'a', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=CSV_HEADER)
            if not file_exists:
                writer.writeheader()
            writer.writerow(data_row)
    except IOError as e:
        print(f"ERROR: Tidak dapat menulis ke file CSV '{CSV_OUTPUT_FILE}': {e}")

def main():
    """Fungsi utama untuk menjalankan scraper."""
    processed_ids, ids_to_process, invalid_ids = load_progress()

    # Jika antrian kosong dan belum ada progres, inisialisasi dengan kategori A-U.
    if not ids_to_process and not processed_ids:
        print("Memulai dari awal. Menginisialisasi antrian dengan Kategori A-U.")
        # Membuat daftar Kategori dari 'A' sampai 'U'
        ids_to_process = [chr(i) for i in range(ord('A'), ord('U') + 1)]
        save_queue(ids_to_process)

    print("\n--- Memulai Proses Scraping ---")
    
    while ids_to_process:
        current_id = ids_to_process.pop(0)

        if current_id in processed_ids or current_id in invalid_ids:
            print(f"Melewati ID {current_id} (sudah diproses atau tidak valid).")
            continue

        url = BASE_URL.format(current_id, API_KEY)
        print(f"Mencoba mengambil data untuk ID: {current_id}...")
        
        try:
            response = requests.get(url, timeout=30)
            
            # Coba parse JSON bahkan jika status code error, untuk memeriksa pesan error
            try:
                data = response.json()
            except json.JSONDecodeError:
                data = None # Tidak ada body JSON

            is_valid_id = (response.status_code == 200 and data and 
                           data.get("status") == "OK" and 
                           data.get("data-availability") == "available")

            if is_valid_id:
                source_data = data['data'][1][0]['_source']
                
                # Ekstrak data yang dibutuhkan
                row_data = {
                    'kbli_id': current_id,
                    'level': source_data.get('level', ''),
                    'judul': source_data.get('judul', 'Tanpa Judul'),
                    'deskripsi': source_data.get('deskripsi', '')
                }
                
                print(f"  -> SUKSES: [{row_data['level']}] {row_data['judul']}")
                
                # Simpan hasil ke CSV
                append_to_csv(row_data)
                processed_ids.add(current_id)

                # Tambahkan turunan ke antrian
                turunan_list = source_data.get('turunan', [])
                if turunan_list:
                    new_ids_count = 0
                    for turunan in turunan_list:
                        turunan_kode = turunan.get('kode')
                        if turunan_kode and turunan_kode not in processed_ids and turunan_kode not in invalid_ids:
                            ids_to_process.append(turunan_kode)
                            new_ids_count += 1
                    if new_ids_count > 0:
                        print(f"    + Menambahkan {new_ids_count} turunan ke antrian.")
                
            else:
                # Cek apakah ini error spesifik "ID tidak valid" dari API BPS
                is_known_error = data and data.get("status") == "Error"
                if is_known_error:
                    print(f"  -> INVALID: ID {current_id} tidak ada di database. Ditandai untuk dilewati.")
                    invalid_ids.add(current_id)
                    save_invalid_id(current_id)
                else:
                    # Ini adalah error server atau masalah lain
                    print(f"  -> FAILED (SERVER ERROR): HTTP {response.status_code} untuk ID {current_id}.")
                    print(f"     Respons: {response.text[:150]}") # Tampilkan sebagian respons
                    ids_to_process.insert(0, current_id) # Kembalikan ke antrian untuk dicoba lagi
                    print("     ID dikembalikan ke antrian. Menunggu 10 detik...")
                    time.sleep(10)
        
        except requests.exceptions.RequestException as e:
            print(f"  -> FAILED (NETWORK ERROR): {e}")
            ids_to_process.insert(0, current_id)
            print("     ID dikembalikan ke antrian. Menunggu 30 detik...")
            time.sleep(30)
            
        finally:
            # Selalu simpan state antrian terbaru setelah setiap iterasi
            save_queue(ids_to_process)
            time.sleep(REQUEST_DELAY)

    print("\n--- Proses Selesai ---")
    if os.path.exists(QUEUE_FILE) and os.path.getsize(QUEUE_FILE) == 0:
        os.remove(QUEUE_FILE)
        print(f"File antrian '{QUEUE_FILE}' kosong dan telah dihapus.")

if __name__ == "__main__":
    main()