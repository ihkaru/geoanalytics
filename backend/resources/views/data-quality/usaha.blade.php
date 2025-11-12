<pre>
--- Data Quality Summary for LLM ---

Overall Completeness:
- Total Records: {{ number_format($stats['total_records']) }}
- Latitude Null: {{ number_format($stats['latitude_null']) }} ({{ number_format(($stats['latitude_null'] / $stats['total_records']) * 100, 2) }}%)
- Longitude Null: {{ number_format($stats['longitude_null']) }} ({{ number_format(($stats['longitude_null'] / $stats['total_records']) * 100, 2) }}%)
- Idsubsls Null: {{ number_format($stats['idsubsls_null']) }} ({{ number_format(($stats['idsubsls_null'] / $stats['total_records']) * 100, 2) }}%)
- Kbli Null: {{ number_format($stats['kbli_null']) }} ({{ number_format(($stats['kbli_null'] / $stats['total_records']) * 100, 2) }}%)
- Kbli Invalid Length: {{ number_format($stats['kbli_invalid_length']) }} ({{ number_format(($stats['kbli_invalid_length'] / $stats['total_records']) * 100, 2) }}%)

Deeper Analysis:
- Lat and Idsubsls Null: {{ number_format($stats['lat_and_idsubsls_null']) }} ({{ number_format(($stats['lat_and_idsubsls_null'] / $stats['total_records']) * 100, 2) }}%)
- Geocoding Potential: {{ number_format($stats['geocoding_potential']) }} (of {{ number_format($stats['latitude_null']) }} records with null latitude, {{ number_format(($stats['geocoding_potential'] / $stats['latitude_null']) * 100, 2) }}% have an address)
- Rescuable by IDSUB SLS: {{ number_format($stats['rescuable_by_idsubsls']) }} (of {{ number_format($stats['latitude_null']) }} records with null latitude, {{ number_format(($stats['rescuable_by_idsubsls'] / $stats['latitude_null']) * 100, 2) }}% can be located via `muatan_subsls` data)
- Enrichable by Wilayah: {{ number_format($stats['enrichable_by_wilayah']) }} (of {{ number_format($stats['idsubsls_null']) }} records with null idsubsls, {{ number_format(($stats['enrichable_by_wilayah'] / $stats['idsubsls_null']) * 100, 2) }}% can be enriched)
- Average Idsubsls Per Wilayah For Enrichable: {{ number_format($stats['average_idsubsls_per_wilayah_for_enrichable'], 2) }} (average number of `idsubsls` per matching wilayah)

Invalid KBLI Samples (Top 10):
@if($invalidKbliSamples->isEmpty())
  No invalid KBLI samples found.
@else
@foreach($invalidKbliSamples as $item)
- '{{ $item->kbli }}' ({{ $item->total }})
@endforeach
@endif

Address Samples for Geocoding Potential:
@if($geocodingAddressSamples->isEmpty())
  No address samples available for geocoding.
@else
@foreach($geocodingAddressSamples as $item)
- {{ $item->alamat }}
@endforeach
@endif

Enrichable Records Samples (by Wilayah):
@if($enrichableSamples->isEmpty())
    No enrichable samples found.
@else
@foreach($enrichableSamples as $item)
- IDSBR: {{ $item->idsbr }}, Nama: {{ $item->nama_usaha }}, Alamat: {{ $item->alamat }}, Potential IDSUB SLS: {{ $item->potential_idsubsls }}
@endforeach
@endif


Top 15 KBLI Distribution:
@if($topKbli->isEmpty())
  No KBLI data available.
@else
@foreach($topKbli as $item)
- {{ $item->kbli }}: {{ number_format($item->total) }} ({{ number_format(($item->total / $stats['total_records']) * 100, 2) }}%)
@endforeach
@endif

Status Usaha Distribution:
@if($statusUsaha->isEmpty())
  No Status Usaha data available.
@else
@foreach($statusUsaha as $item)
- {{ $item->status_usaha ?? 'NULL' }}: {{ number_format($item->total) }} ({{ number_format(($item->total / $stats['total_records']) * 100, 2) }}%)
@endforeach
@endif

---

## Tabel: demografi_sls (Data Demand)
- Total Records: {{ number_format($demografiSlsData['total']) }}
- Attributes: {{ implode(', ', $demografiSlsData['attributes']) }}
@if($demografiSlsData['total'] > 0)
### Snippet Data:
| idsubsls | kode_desa | nama_desa | jumlah_penduduk | jumlah_kk | tahun |
|---|---|---|---|---|---|
@foreach($demografiSlsData['snippet'] as $row)
| {{ $row['idsubsls'] }} | {{ $row['kode_desa'] }} | {{ $row['nama_desa'] }} | {{ $row['jumlah_penduduk'] }} | {{ $row['jumlah_kk'] }} | {{ $row['tahun'] }} |
@endforeach
@else
- Status: **Data Kosong**
@endif

---

## Tabel: peta_sls (Data Spasial)
- Total Records: {{ number_format($petaSlsData['total']) }}
- Attributes: {{ implode(', ', $petaSlsData['attributes']) }}
@if($petaSlsData['total'] > 0)
### Snippet Data:
| idsubsls |
|---|
@foreach($petaSlsData['snippet'] as $row)
| {{ $row['idsubsls'] }} |
@endforeach
@else
- Status: **Data Kosong**
@endif

---

## Tabel: referensi_kbli (Data Kamus)
- Total Records: {{ number_format($referensiKbliData['total']) }}
- Attributes: {{ implode(', ', $referensiKbliData['attributes']) }}
@if($referensiKbliData['total'] > 0)
### Snippet Data:
| kbli_id | judul | deskripsi_kbli |
|---|---|---|
@foreach($referensiKbliData['snippet'] as $row)
| {{ $row['kbli_id'] }} | {{ Str::limit($row['judul'], 25) }} | {{ Str::limit($row['deskripsi'], 30) }} |
@endforeach
@else
- Status: **Data Kosong**
@endif

---

## Tabel: regsosek (Data Individu)
- Total Records: {{ number_format($regsosekData['total']) }}
- Attributes: {{ implode(', ', $regsosekData['attributes']) }}
@if($regsosekData['total'] > 0)
### Snippet Data:
| kode_prov | kode_kab | id_rt | r401 | r407 |
|---|---|---|---|---|
@foreach($regsosekData['snippet'] as $row)
| {{ $row['kode_prov'] }} | {{ $row['kode_kab'] }} | {{ $row['id_rt'] }} | {{ $row['r401'] }} | {{ $row['r407'] }} |
@endforeach
@else
- Status: **Data Kosong**
@endif

--- End of Summary ---
</pre>
