import pandas as pd
import json
import os
import math

# --- KONFIGURASI ---
CSV_INPUT_FILE = 'kbli_data.csv'
METADATA_OUTPUT_FILE = 'metadata.json'

def analyze_csv():
    """
    Menganalisis file CSV yang dihasilkan oleh scraper untuk membuat metadata
    yang berguna untuk definisi skema database.
    """
    if not os.path.exists(CSV_INPUT_FILE):
        print(f"Error: File '{CSV_INPUT_FILE}' tidak ditemukan.")
        print("Pastikan Anda sudah menjalankan script scraper (scrape_kbli_robust.py) terlebih dahulu.")
        return

    print(f"Membaca dan menganalisis file '{CSV_INPUT_FILE}'...")
    
    try:
        df = pd.read_csv(CSV_INPUT_FILE)
    except Exception as e:
        print(f"Gagal membaca file CSV. Error: {e}")
        return
        
    if df.empty:
        print("File CSV kosong. Tidak ada data untuk dianalisis.")
        return

    metadata = {
        'table_name': 'kbli_2020',
        'file_source': CSV_INPUT_FILE,
        'total_rows': int(df.shape[0]),
        'columns': {}
    }

    print("\n--- Hasil Analisis Metadata ---")
    
    for column in df.columns:
        col_series = df[column]
        col_series_str = col_series.fillna('').astype(str)
        max_len = col_series_str.str.len().max()
        
        null_count = pd.concat([col_series.isnull(), col_series_str == ''], axis=1).any(axis=1).sum()
        is_nullable = null_count > 0
        
        suggested_type = "VARCHAR"
        if column == 'deskripsi' or max_len > 255:
            suggested_type = "TEXT"
        
        varchar_len = None
        if suggested_type == "VARCHAR":
            safe_len = math.ceil(max_len * 1.25)
            if safe_len <= 50: varchar_len = 50
            elif safe_len <= 100: varchar_len = 100
            elif safe_len <= 255: varchar_len = 255
            else: suggested_type = "TEXT"
            
        # === PERBAIKAN DIMULAI DI SINI ===

        # Buat dictionary dasar untuk metadata kolom
        column_meta = {
            # Konversi tipe data NumPy/Pandas ke Python standar
            'max_length': int(max_len),
            'null_count': int(null_count),
            'is_nullable': bool(is_nullable), # <-- FIX: Konversi numpy.bool_ ke bool
            'suggested_db_type': f"{suggested_type}({varchar_len})" if varchar_len else suggested_type
        }
        
        # Cek keunikan, khusus untuk kolom 'kbli_id'
        if column == 'kbli_id':
            is_unique = col_series.is_unique
            column_meta['is_unique'] = bool(is_unique) # <-- FIX: Konversi numpy.bool_ ke bool

        # Masukkan metadata kolom yang sudah bersih ke dictionary utama
        metadata['columns'][column] = column_meta

        # === AKHIR DARI PERBAIKAN ===


        # Tampilkan hasil di konsol
        print(f"\n[+] Kolom: '{column}'")
        print(f"    - Panjang Karakter Maksimum: {column_meta['max_length']}")
        print(f"    - Jumlah Nilai Kosong/NULL : {column_meta['null_count']} dari {metadata['total_rows']} baris")
        print(f"    - Bisa Berisi NULL (Nullable): {'Ya' if column_meta['is_nullable'] else 'Tidak'}")
        if 'is_unique' in column_meta:
            print(f"    - Semua Nilai Unik       : {'Ya (Cocok untuk Primary Key)' if column_meta['is_unique'] else 'Tidak'}")
        print(f"    - Tipe Data DB Disarankan: {column_meta['suggested_db_type']}")

    try:
        with open(METADATA_OUTPUT_FILE, 'w', encoding='utf-8') as f:
            json.dump(metadata, f, indent=4)
        print(f"\n\n[INFO] Metadata lengkap telah disimpan ke file '{METADATA_OUTPUT_FILE}'")
    except IOError as e:
        print(f"\n[ERROR] Gagal menyimpan file metadata: {e}")

    print("\n--- Contoh Perintah SQL CREATE TABLE (PostgreSQL/MySQL) ---")
    sql_statement = f"CREATE TABLE {metadata['table_name']} (\n"
    sql_columns = []
    for col_name, col_meta in metadata['columns'].items():
        col_def = f"    {col_name} {col_meta['suggested_db_type']}"
        if not col_meta['is_nullable']:
            col_def += " NOT NULL"
        if col_name == 'kbli_id' and col_meta.get('is_unique'):
            col_def += " PRIMARY KEY"
        sql_columns.append(col_def)
    
    sql_statement += ",\n".join(sql_columns)
    sql_statement += "\n);"
    print(sql_statement)

if __name__ == "__main__":
    try:
        import pandas
    except ImportError:
        print("Library 'pandas' tidak ditemukan. Menginstal secara otomatis...")
        os.system('pip install pandas')
    
    analyze_csv()