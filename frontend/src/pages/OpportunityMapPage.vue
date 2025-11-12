<template>
  <f7-page @page:afterin="onPageInit" @page:beforeout="onPageExit" class="opportunity-map-page">
    <!-- Modern Navbar -->
    <f7-navbar class="navbar-modern" large transparent>
      <f7-nav-right>
        <f7-link icon-only href="#" @click="toggleDebug">
          <f7-icon ios="f7:bug_fill" md="material:bug_report"></f7-icon>
        </f7-link>
        <f7-link icon-only href="#" @click="refreshData">
          <f7-icon ios="f7:arrow_clockwise" md="material:refresh"></f7-icon>
        </f7-link>
      </f7-nav-right>
    </f7-navbar>

    <!-- Modern Map Controls Card -->
    <div class="top-ui-container">
      <f7-card class="map-controls-card elevation-4">
        <f7-card-header>
          <div class="card-header-content">
            <f7-icon color="primary" ios="f7:funnel_fill" md="material:filter_alt"></f7-icon>
            <span>Filter Analisis</span>
          </div>
        </f7-card-header>
        <f7-card-content>
          <!-- KBLI Smart Select -->
          <f7-list no-hairlines>
            <f7-list-item title="Kategori KBLI" smart-select
              :smart-select-params="{ openIn: 'popup', searchbar: true, searchbarPlaceholder: 'Cari KBLI...', pageTitle: 'Pilih KBLI' }">
              <select v-model="store.selectedKbli" @change="onKbliChange">
                <option value="">-- Pilih KBLI --</option>
                <option v-for="kbli in store.kbliOptions" :key="kbli.value" :value="kbli.value">
                  {{ kbli.text }}
                </option>
              </select>
            </f7-list-item>
          </f7-list>
          <br />

          <!-- Radius Segmented Control -->
          <div class="radius-selector" v-if="store.selectedKbli">
            <div class="selector-label">Radius Analisis</div>
            <f7-segmented strong>
              <f7-button v-for="option in radiusOptions" :key="option.value"
                :active="store.selectedRadius === option.value" @click="store.selectedRadius = option.value" round
                outline>
                {{ option.label }}
              </f7-button>
            </f7-segmented>
          </div>

          <!-- Active Filters Chips -->
          <div class="active-filters" v-if="store.selectedKbli || store.selectedRadius">
            <f7-chip v-if="store.selectedKbli" :text="getKbliLabel()" color="primary" deleteable
              @delete="clearKbli"></f7-chip>
            <f7-chip v-if="store.selectedRadius" :text="`${store.selectedRadius}m`" color="orange" deleteable
              @delete="clearRadius"></f7-chip>
          </div>
        </f7-card-content>
      </f7-card>

      <!-- Stats Summary Card -->
      <f7-card class="stats-card elevation-3" v-if="store.selectedKbli && visibleFeaturesCount > 0">
        <f7-card-content :padding="false">
          <div class="stats-grid">
            <div class="stat-item">
              <div class="stat-value">{{ visibleFeaturesCount }}</div>
              <div class="stat-label">Zona Terdeteksi</div>
            </div>
            <div class="stat-item">
              <div class="stat-value" :class="dominantZoneClass">{{ dominantZone }}</div>
              <div class="stat-label">Dominan</div>
            </div>
          </div>
        </f7-card-content>
      </f7-card>
    </div>


    <!-- Map Container -->
    <div class="map-wrapper">
      <div id="opportunity-map" class="map-container" ref="mapContainerRef"></div>

      <!-- Map Loading Overlay -->
      <div class="map-loading" v-if="mapLoading">
        <f7-preloader size="42" color="white"></f7-preloader>
        <div class="loading-text">Memuat peta...</div>
      </div>

      <!-- Map Error State -->
      <div class="map-error" v-if="mapError">
        <f7-icon color="red" ios="f7:wifi_exclamationmark" md="material:signal_wifi_off" size="48"></f7-icon>
        <div class="error-text">Gagal memuat data peta</div>
        <f7-button small fill round @click="refreshData">Coba Lagi</f7-button>
      </div>
    </div>

    <f7-fab position="left-bottom" @click="openLegendSheet">
      <f7-icon ios="f7:info" md="material:info_outline"></f7-icon>
    </f7-fab>
    <f7-fab position="right-bottom" @click="resetMapView">
      <f7-icon ios="f7:location_fill" md="material:my_location"></f7-icon>
    </f7-fab>
    <f7-sheet swipe-to-close class="legend-sheet" style="height: auto; --f7-sheet-bg-color: #fff;"
      :opened="legendSheetOpened" @sheet:closed="legendSheetOpened = false" backdrop>
      <f7-toolbar>
        <div class="left">
          <div class="legend-sheet-title">
            <f7-icon ios="f7:info_circle_fill" md="material:info"></f7-icon>
            <span>Legenda Zona Peluang</span>
          </div>
        </div>
        <div class="right">
          <f7-link sheet-close>Tutup</f7-link>
        </div>
      </f7-toolbar>

      <!-- Konten Legenda (sama seperti sebelumnya, hanya dipindah ke sini) -->
      <f7-page-content>
        <f7-list no-hairlines class="legend-list">
          <f7-list-item v-for="item in legendItems" :key="item.zone">
            <div class="legend-item">
              <div class="legend-color" :style="{ backgroundColor: item.color }"></div>
              <div class="legend-text">
                <div class="legend-zone">{{ item.zone }}</div>
                <div class="legend-desc">{{ item.description }}</div>
              </div>
            </div>
          </f7-list-item>
        </f7-list>
      </f7-page-content>
    </f7-sheet>
    <!-- Modern Bottom Sheet -->
    <f7-sheet swipe-to-close class="zone-detail-sheet" style="height: auto; --f7-sheet-bg-color: #f5f7fa;"
      :opened="store.popupOpened" @sheet:closed="store.closePopup()" backdrop>
      <f7-block-title>Detail Zona Peluang</f7-block-title>

      <!-- Sheet Content -->
      <f7-page-content>
        <!-- Loading State with Skeleton -->
        <div v-if="store.loadingDetails" class="sheet-loading">
          <f7-skeleton-block style="width: 60%; height: 24px; margin: 20px auto;"></f7-skeleton-block>
          <div class="skeleton-list">
            <f7-skeleton-block style="width: 100%; height: 60px; margin-bottom: 10px;"></f7-skeleton-block>
            <f7-skeleton-block style="width: 100%; height: 60px; margin-bottom: 10px;"></f7-skeleton-block>
            <f7-skeleton-block style="width: 100%; height: 60px;"></f7-skeleton-block>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="store.errorDetails" class="sheet-error">
          <f7-icon color="red" ios="f7:exclamationmark_triangle_fill" md="material:warning" size="64"></f7-icon>
          <div class="error-title">Terjadi Kesalahan</div>
          <div class="error-message">{{ store.errorDetails }}</div>
          <f7-button fill round large @click="retryFetch" class="retry-button">Muat Ulang</f7-button>
        </div>

        <!-- Success State -->
        <f7-block v-else-if="store.selectedZoneDetails" class="sheet-content-block">
          <!-- Zone Header -->
          <div class="zone-header" :class="zoneHeaderClass">
            <div class="zone-icon">
              <f7-icon :ios="zoneIconIos" :md="zoneIconMd" size="48" color="white"></f7-icon>
            </div>
            <div class="zone-title">{{ store.selectedZoneDetails.zona || 'Zona' }}</div>
            <div class="zone-subtitle">
              {{ store.selectedZoneDetails.nama_desa || 'Desa' }}
              <span v-if="store.selectedZoneDetails.nama_kecamatan">, {{ store.selectedZoneDetails.nama_kecamatan
              }}</span>
            </div>
          </div>

          <!-- Detail Cards -->
          <f7-card class="detail-card">
            <f7-card-header class="card-section-title">
              <f7-icon color="blue" ios="f7:info_circle" md="material:info_outline"></f7-icon>
              <span>Informasi Zona</span>
            </f7-card-header>
            <f7-card-content :padding="false">
              <f7-list dividers>
                <f7-list-item>
                  <div class="list-item-label">ID Sub SLS</div>
                  <div class="list-item-value">{{ store.selectedZoneDetails.idsubsls || '-' }}</div>
                </f7-list-item>
                <f7-list-item>
                  <div class="list-item-label">Nama SLS</div>
                  <div class="list-item-value">{{ store.selectedZoneDetails.nama_sls || '-' }}</div>
                </f7-list-item>
                <f7-list-item>
                  <div class="list-item-label">Desa</div>
                  <div class="list-item-value">{{ store.selectedZoneDetails.nama_desa || '-' }}</div>
                </f7-list-item>
                <f7-list-item>
                  <div class="list-item-label">Kecamatan</div>
                  <div class="list-item-value">{{ store.selectedZoneDetails.nama_kecamatan || '-' }}</div>
                </f7-list-item>
                <f7-list-item>
                  <div class="list-item-label">Kabupaten</div>
                  <div class="list-item-value">{{ store.selectedZoneDetails.nama_kabupaten || '-' }}</div>
                </f7-list-item>
                <f7-list-item>
                  <div class="list-item-label">KBLI</div>
                  <div class="list-item-value">{{ store.selectedZoneDetails.kbli_5_digit || '-' }}</div>
                </f7-list-item>
                <f7-list-item>
                  <div class="list-item-label">Radius</div>
                  <div class="list-item-value">{{ store.selectedZoneDetails.radius_meter || 0 }} meter</div>
                </f7-list-item>
              </f7-list>
            </f7-card-content>
          </f7-card>

          <!-- Analysis Results -->
          <f7-card class="detail-card">
            <f7-card-header class="card-section-title">
              <f7-icon color="green" ios="f7:chart_bar" md="material:analytics"></f7-icon>
              <span>Hasil Analisis</span>
            </f7-card-header>
            <f7-card-content>
              <div class="stats-row">
                <div class="stat-box">
                  <div class="stat-box-value">{{ store.selectedZoneDetails.jumlah_penduduk_total || 0 }}</div>
                  <div class="stat-box-label">Total Penduduk</div>
                </div>
                <div class="stat-box">
                  <div class="stat-box-value">{{ store.selectedZoneDetails.jumlah_usaha_sejenis || 0 }}</div>
                  <div class="stat-box-label">Usaha Sejenis</div>
                </div>
                <div class="stat-box">
                  <div class="stat-box-value">{{ formatRatio }}</div>
                  <div class="stat-box-label">Rasio</div>
                </div>
              </div>

              <!-- Potential Score -->
              <div class="score-container">
                <div class="score-label">Skor Potensi</div>
                <div class="score-value" :class="scoreClass">
                  {{ formatScore }}
                </div>
                <div class="score-bar">
                  <div class="score-bar-fill" :style="{ width: scorePercentage + '%' }"></div>
                </div>
              </div>
            </f7-card-content>
          </f7-card>
        </f7-block>
      </f7-page-content>
    </f7-sheet>
  </f7-page>
</template>

<script setup>
import { f7 } from 'framework7-vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useOpportunityMapStore } from '../stores/opportunityMap';

const store = useOpportunityMapStore();
let map = null;
const mapContainerRef = ref(null);
const showDebugOverlay = ref(false);
const mapLoading = ref(false);
const mapError = ref(false);
const visibleFeaturesCount = ref(0);
const dominantZone = ref('Tidak ada');

const radiusOptions = [
  { value: '500', label: '500m' },
  { value: '1000', label: '1km' },
  { value: '1500', label: '1.5km' }
];

const legendItems = [
  { zone: 'Merah', color: '#d73027', description: 'Peluang Tinggi - Ekspansi optimal' },
  { zone: 'Kuning', color: '#fee08b', description: 'Peluang Sedang - Perlu analisis lebih lanjut' },
  { zone: 'Jenuh', color: '#1a9850', description: 'Saturated - Hindari pembukaan usaha baru' }
];

// Computed Properties
const zoneHeaderClass = computed(() => {
  const zone = store.selectedZoneDetails?.zona;
  if (!zone) return '';
  return zone.toLowerCase().replace(/\s+/g, '-');
});

const zoneIconIos = computed(() => {
  const zone = store.selectedZoneDetails?.zona;
  const icons = {
    'Merah': 'f7:flame_fill',
    'Kuning': 'f7:question_circle_fill',
    'Jenuh': 'f7:exclamationmark_octagon_fill'
  };
  return icons[zone] || 'f7:question';
});

const zoneIconMd = computed(() => {
  const zone = store.selectedZoneDetails?.zona;
  const icons = {
    'Merah': 'material:trending_up',
    'Kuning': 'material:insights',
    'Jenuh': 'material:block'
  };
  return icons[zone] || 'material:help';
});

const formatScore = computed(() => {
  const score = store.selectedZoneDetails?.skor_potensi;
  return score ? Number(score).toFixed(2) : '-';
});

const scoreClass = computed(() => {
  const score = parseFloat(store.selectedZoneDetails?.skor_potensi || 0);
  if (score >= 80) return 'score-high';
  if (score >= 50) return 'score-medium';
  return 'score-low';
});

const scorePercentage = computed(() => {
  const score = parseFloat(store.selectedZoneDetails?.skor_potensi || 0);
  return Math.min(score, 100);
});

const formatRatio = computed(() => {
  const ratio = store.selectedZoneDetails?.rasio_penduduk_per_usaha;
  return ratio ? Number(ratio).toFixed(1) : '-';
});

const dominantZoneClass = computed(() => {
  return dominantZone.value.toLowerCase().replace(/\s+/g, '-');
});

// Methods
const log = (context, message, data = null) => {
  const timestamp = new Date().toLocaleTimeString();
  console.log(`[${timestamp}] [${context}] ${message}`, data || '');
};

const onKbliChange = () => {
  if (!store.selectedKbli) {
    store.selectedRadius = '';
  }
  updateMapStats();
};

const clearKbli = () => {
  store.selectedKbli = '';
  f7.toast.create({
    text: 'Filter KBLI dihapus',
    position: 'top',
    closeTimeout: 2000
  }).open();
};

const clearRadius = () => {
  store.selectedRadius = '';
  f7.toast.create({
    text: 'Filter radius dihapus',
    position: 'top',
    closeTimeout: 2000
  }).open();
};

const getKbliLabel = () => {
  const kbli = store.kbliOptions.find(k => k.value === store.selectedKbli);
  return kbli ? kbli.value : '';
};

const updateMapStats = () => {
  if (!map || !map.isStyleLoaded() || !store.selectedKbli || !store.selectedRadius) {
    visibleFeaturesCount.value = 0;
    dominantZone.value = 'Tidak ada';
    return;
  }

  try {
    const features = map.queryRenderedFeatures({ layers: ['zona-peluang-layer'] });
    visibleFeaturesCount.value = features.length;

    if (features.length > 0) {
      const zoneCount = {};
      features.forEach(f => {
        const zone = f.properties.zona || 'Unknown';
        zoneCount[zone] = (zoneCount[zone] || 0) + 1;
      });
      dominantZone.value = Object.entries(zoneCount).sort((a, b) => b[1] - a[1])[0][0];
    } else {
      dominantZone.value = 'Tidak ada';
    }
  } catch (e) {
    log('STATS', 'Error updating stats', e);
  }
};

const toggleDebug = () => {
  showDebugOverlay.value = !showDebugOverlay.value;
  f7.toast.create({
    text: `Debug mode ${showDebugOverlay.value ? 'aktif' : 'nonaktif'}`,
    position: 'top',
    closeTimeout: 1500
  }).open();
};

const refreshData = () => {
  mapLoading.value = true;
  f7.toast.create({
    text: 'Memuat ulang data peta...',
    position: 'center',
    closeTimeout: 1000,
    cssClass: 'toast-loading'
  }).open();

  setTimeout(() => {
    mapLoading.value = false;
    updateMapFilter();
    updateMapStats();
    f7.toast.create({
      text: 'Data peta segar kembali!',
      position: 'top',
      closeTimeout: 2000
    }).open();
  }, 1500);
};

const resetMapView = () => {
  if (map) {
    map.flyTo({ center: [108.9539299, 0.3647826], zoom: 10, duration: 1000 });
    f7.toast.create({
      text: 'Tampilan peta direset',
      position: 'top',
      closeTimeout: 1500
    }).open();
  }
};

const legendSheetOpened = ref(false);
const openLegendSheet = () => {
  legendSheetOpened.value = true;
  console.log('Legenda dibuka');
};

const retryFetch = () => {
  store.closeSheet();
  f7.toast.create({
    text: 'Mencoba mengambil data lagi...',
    position: 'center',
    closeTimeout: 1000
  }).open();
  setTimeout(() => {
    store.popupOpened = true;
  }, 1000);
};

// Core Functions
const handleMapClick = (e) => {
  log('CLICK', 'Processing map click');

  if (!map) {
    log('CLICK', 'ERROR: Map instance is NULL!');
    return;
  }

  const features = map.queryRenderedFeatures(e.point, { layers: ['zona-peluang-layer'] });
  log('CLICK', `Found ${features.length} features`);

  if (!features.length) {
    f7.toast.create({
      text: 'Tidak ada data di lokasi ini',
      position: 'top',
      closeTimeout: 2000
    }).open();
    return;
  }

  const feature = features[0];
  const { idsubsls, kbli_5_digit, radius_meter } = feature.properties;

  if (!idsubsls || !kbli_5_digit || !radius_meter) {
    log('CLICK', 'ERROR: Missing properties', feature.properties);
    f7.toast.create({
      text: 'Data zona tidak lengkap',
      position: 'top',
      closeTimeout: 2000
    }).open();
    return;
  }

  store.fetchZoneDetails(idsubsls, kbli_5_digit, radius_meter);
};

const updateMapFilter = () => {
  log('FILTER', 'Updating map filter');

  if (!map || !map.isStyleLoaded() || !map.getLayer('zona-peluang-layer')) {
    log('FILTER', 'Map not ready');
    return;
  }

  const kbli = store.selectedKbli;
  const radius = store.selectedRadius;

  if (!kbli || !radius) {
    map.setFilter('zona-peluang-layer', ['==', ['get', 'zona'], '###']);
    updateMapStats();
    return;
  }

  try {
    const filterExpr = [
      'all',
      ['==', ['get', 'kbli_5_digit'], kbli],
      ['==', ['get', 'radius_meter'], parseInt(radius, 10)]
    ];

    map.setFilter('zona-peluang-layer', filterExpr);

    const colorExpr = [
      'match',
      ['get', 'zona'],
      'Merah', '#d73027',
      'Kuning', '#fee08b',
      'Jenuh', '#1a9850',
      '#ccc'
    ];

    map.setPaintProperty('zona-peluang-layer', 'fill-color', colorExpr);

    setTimeout(() => updateMapStats(), 500);
  } catch (error) {
    log('FILTER', 'ERROR!', error);
  }
};

const onPageInit = async () => {
  log('PAGE_INIT', 'Initializing page');

  // Show initial loading
  mapLoading.value = true;

  store.fetchKbliOptions();
  await new Promise(resolve => setTimeout(resolve, 100));

  try {
    map = new maplibregl.Map({
      container: 'opportunity-map',
      style: {
        version: 8,
        sources: {
          'osm-tiles': {
            type: 'raster',
            tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
            tileSize: 256,
            attribution: '&copy; OpenStreetMap contributors'
          }
        },
        layers: [{
          id: 'osm-layer',
          type: 'raster',
          source: 'osm-tiles'
        }]
      },
      center: [108.9539299, 0.3647826],
      zoom: 10,
      minZoom: 8,
      maxZoom: 19,
    });

    map.once('load', () => {
      log('MAP', 'Map loaded successfully');

      try {
        map.addSource('zona-peluang-source', {
          type: 'vector',
          tiles: [`${import.meta.env.VITE_API_BASE_URL}/peta-tiles/{z}/{x}/{y}`],
          minzoom: 8,
          maxzoom: 15,
        });

        map.addLayer({
          id: 'zona-peluang-layer',
          type: 'fill',
          source: 'zona-peluang-source',
          'source-layer': 'zona_peluang',
          paint: {
            'fill-color': '#ccc',
            'fill-opacity': 0.7,
            'fill-outline-color': 'rgba(0, 0, 0, 0.3)'
          }
        });

        map.on('click', 'zona-peluang-layer', handleMapClick);
        mapLoading.value = false;
        updateMapFilter();
      } catch (error) {
        log('MAP', 'Error adding layer', error);
        mapError.value = true;
        mapLoading.value = false;
      }
    });

    map.on('error', (e) => {
      log('MAP_ERROR', e.error.message);
      mapError.value = true;
      mapLoading.value = false;
    });
  } catch (error) {
    log('PAGE_INIT', 'ERROR creating map', error);
    mapError.value = true;
    mapLoading.value = false;
  }
};

const onPageExit = () => {
  log('PAGE_EXIT', 'Cleaning up map');
  if (map) {
    map.remove();
    map = null;
  }
};

// Watchers
watch(() => [store.selectedKbli, store.selectedRadius], () => {
  updateMapFilter();
});

// Lifecycle hooks
onMounted(() => {
  // Component mounted
});

onBeforeUnmount(() => {
  onPageExit();
});
</script>
<style>
/*
  KODE CSS LENGKAP - VERSI FINAL
  Mengatasi tumpang tindih di bagian atas (filter) dan bawah (legenda/fab).
*/

:root {
  --primary-gradient: linear-gradient(135deg, #667eea, #764ba2);
  --success-gradient: linear-gradient(135deg, #11998e, #38ef7d);
  --warning-gradient: linear-gradient(135deg, #f093fb, #f5576c);
  --danger-gradient: linear-gradient(135deg, #eb3349, #f45c43);
}

/* Main Layout */
.opportunity-map-page {
  background: #f5f7fa;
}

/* Modern Navbar */
.navbar-modern {
  background: var(--primary-gradient) !important;
  color: white;
}

.nav-title-content {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Wadah UI Atas (Filter & Stats) */
.top-ui-container {
  position: absolute;
  top: calc(var(--f7-navbar-height) + 10px);
  left: 10px;
  right: 10px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  pointer-events: none;
}

.map-controls-card,
.stats-card {
  width: 100%;
  max-width: 380px;
  margin: 0;
  pointer-events: auto;
}

.map-controls-card {
  border-radius: 16px;
  overflow: hidden;
  background: white;
}

.stats-card {
  border-radius: 12px;
}


/* Wadah baru untuk UI pojok kanan-bawah (Legend & FAB) */
/* BARU: Style untuk tombol pemicu legenda */
.fab-legend-trigger {
  background-color: white !important;
  color: var(--f7-theme-color) !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* BARU: Style untuk sheet legenda */
.legend-sheet-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: 16px;
  padding-left: 16px;
}

.legend-list {
  margin: 0 !important;
}

/* Style untuk item legenda (sama seperti sebelumnya, hanya selectornya sedikit berbeda) */
.legend-list .legend-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 8px 16px;
  width: 100%;
}

.legend-list .legend-color {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  flex-shrink: 0;
}

.legend-list .legend-text {
  flex: 1;
}

.legend-list .legend-zone {
  font-weight: 600;
  font-size: 15px;
}

.legend-list .legend-desc {
  font-size: 13px;
  color: #555;
  line-height: 1.4;
}



/* FAB Container - Posisi di atas legend */
.fab-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  pointer-events: auto;
  margin-bottom: 0;
}

/* Perbaiki posisi FAB agar tidak overlap */
.fab-main,
.fab-secondary {
  position: static !important;
  /* Hilangkan position absolute dari FAB */
  margin: 0 !important;
}

/* Override Framework7 FAB default positioning */
.fab-container .fab {
  position: static !important;
  transform: none !important;
}


.card-header-content {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #333;
}

.radius-selector {
  padding: 0 16px 16px;
}

.selector-label {
  font-size: 14px;
  font-weight: 500;
  color: #666;
  margin-bottom: 8px;
}

.radius-segmented {
  width: 100%;
}

.radius-button {
  flex: 1;
  font-size: 13px;
  font-weight: 500;
}

.active-filters {
  padding: 0 16px 16px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  padding: 16px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 4px;
}

.stat-value.merah {
  color: #d73027;
}

.stat-value.kuning {
  color: #fee08b;
}

.stat-value.jenuh {
  color: #1a9850;
}

.stat-value.tidak-ada {
  color: #999;
}

.stat-label {
  font-size: 12px;
  color: #666;
  text-transform: uppercase;
  font-weight: 500;
}


.legend-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
}

.legend-color {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  flex-shrink: 0;
}

.legend-text {
  flex: 1;
}

.legend-zone {
  font-weight: 600;
  font-size: 14px;
}

.legend-desc {
  font-size: 12px;
  color: #666;
  line-height: 1.3;
}

.map-wrapper {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.map-container {
  width: 100%;
  height: 100%;
}

.map-loading,
.map-error {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  background: rgba(255, 255, 255, 0.95);
  padding: 24px;
  border-radius: 16px;
  z-index: 100;
}

.loading-text,
.error-text {
  margin-top: 12px;
  font-size: 14px;
  color: #333;
}

.error-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin: 12px 0 4px;
}

.error-message {
  font-size: 14px;
  color: #666;
  margin-bottom: 16px;
}

.fab-main {
  background: var(--primary-gradient) !important;
}

.fab-secondary {
  background: #fff !important;
  color: #333 !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.zone-detail-sheet {
  height: 70vh !important;
}

.sheet-toolbar {
  background: white;
  border-bottom: 1px solid #eee;
  padding: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sheet-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.sheet-loading {
  padding: 20px;
}

.skeleton-list {
  padding: 16px 0;
}

.sheet-error {
  text-align: center;
  padding: 40px 20px;
}

.retry-button {
  margin-top: 20px;
  max-width: 200px;
  margin-left: auto;
  margin-right: auto;
}

.sheet-content-block {
  padding: 0;
}

.zone-header {
  padding: 24px 20px;
  text-align: center;
  color: white;
  margin-bottom: 20px;
}

.zone-header.merah {
  background: var(--danger-gradient);
}

.zone-header.kuning {
  background: var(--warning-gradient);
}

.zone-header.jenuh {
  background: var(--success-gradient);
}

.zone-icon {
  margin-bottom: 12px;
}

.zone-title {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 4px;
}

.zone-subtitle {
  font-size: 14px;
  opacity: 0.9;
}

.detail-card {
  margin: 0 16px 16px;
  border-radius: 12px;
}

.card-section-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  display: flex;
  align-items: center;
  gap: 8px;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.stat-box {
  text-align: center;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
}

.stat-box-value {
  font-size: 18px;
  font-weight: 700;
  color: #333;
}

.stat-box-label {
  font-size: 11px;
  color: #666;
  text-transform: uppercase;
  margin-top: 4px;
}

.score-container {
  padding-top: 20px;
  border-top: 1px solid #eee;
}

.score-label {
  font-size: 14px;
  color: #666;
  margin-bottom: 8px;
}

.score-value {
  font-size: 32px;
  font-weight: 700;
  margin-bottom: 8px;
}

.score-value.score-high {
  color: #d73027;
}

.score-value.score-medium {
  color: #fee08b;
}

.score-value.score-low {
  color: #1a9850;
}

.score-bar {
  width: 100%;
  height: 8px;
  background: #eee;
  border-radius: 4px;
  overflow: hidden;
}

.score-bar-fill {
  height: 100%;
  background: var(--primary-gradient);
  transition: width 0.5s ease;
}

.list-item-label {
  font-size: 14px;
  color: #666;
  margin-bottom: 4px;
}

.list-item-value {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.rotate-180 {
  transform: rotate(180deg);
  transition: transform 0.3s ease;
}

.elevation-2 {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.elevation-3 {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
}

.elevation-4 {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.toast-loading {
  background: rgba(0, 0, 0, 0.8) !important;
  color: white !important;
}

@media (max-width: 768px) {

  .legend-card {
    width: 100%;
    /* Buat legenda full-width di mobile */
  }

  .top-ui-container {
    left: 10px;
    right: 10px;
  }
}
</style>
