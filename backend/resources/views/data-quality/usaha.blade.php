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
