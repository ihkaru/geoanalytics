<template>
  <f7-page @page:afterin="onPageInit">
    <div class="searchbar-container">
      <f7-searchbar :value="searchQuery" @input="searchQuery = $event.target.value" @searchbar:clear="clearSearch"
        placeholder="Cari Nama Usaha..."></f7-searchbar>
      <f7-list v-if="suggestions.length > 0" class="search-suggestions">
        <f7-list-item v-for="suggestion in suggestions" :key="suggestion.idsbr" :title="suggestion.nama_usaha"
          :subtitle="suggestion.alamat" @click="selectSuggestion(suggestion)"></f7-list-item>
      </f7-list>
    </div>

    <div id="map" style="height: 100%; width: 100%;"></div>

    <UsahaDetailPopup />
  </f7-page>
</template>

<script setup>
console.log('--- MAPLIBREPAGE.VUE SCRIPT EXECUTED ---');
import { f7 } from 'framework7-vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import UsahaDetailPopup from '../components/UsahaDetailPopup.vue';
import { useUsahaStore } from '../stores/usaha';
import { useWilayahStore } from '../stores/wilayah';

const usahaStore = useUsahaStore();
const wilayahStore = useWilayahStore();
const searchQuery = ref('');
const suggestions = computed(() => usahaStore.suggestions);

let map = null;
let highlightPopup = null; // Popup untuk highlight titik yang dipilih
let hoveredSubslsId = ref(null); // ID subsls yang sedang di-hover

let searchTimeout = null;
let updateTimeout = null;

// --- Caching System ---
const pendingRequests = new Map();
let cachedUsahas = [];
let loadedBounds = [];

const getCacheKey = (bounds) => {
  return `${bounds.getNorth().toFixed(3)}_${bounds.getSouth().toFixed(3)}_${bounds.getEast().toFixed(3)}_${bounds.getWest().toFixed(3)}`;
};

const isBoundsLoaded = (targetBounds) => {
  return loadedBounds.some(loaded => loaded.contains(targetBounds.getCenter()));
};

const addToLoadedBounds = (bounds) => {
  loadedBounds.push(bounds);
  if (loadedBounds.length > 20) { // Increased cache size
    loadedBounds.shift();
  }
};

// --- MapLibre Specific Functions ---

const updateHighlightPopup = (usaha) => {
  if (!map) return;

  const lat = parseFloat(usaha.latitude);
  const lng = parseFloat(usaha.longitude);

  if (highlightPopup) {
    highlightPopup.remove();
  }

  highlightPopup = new maplibregl.Popup({ closeButton: false, className: 'highlight-popup' })
    .setLngLat([lng, lat])
    .setHTML(`<strong style="color: #d32f2f;">${usaha.nama_usaha}</strong><br>${usaha.alamat}`)
    .addTo(map);

  // Auto-remove after a delay
  setTimeout(() => {
    if (highlightPopup) {
      highlightPopup.remove();
      highlightPopup = null;
    }
  }, 5000);
};

const updateMarkers = async () => {
  if (!map || !map.isStyleLoaded()) return;

  clearTimeout(updateTimeout);
  updateTimeout = setTimeout(async () => {
    const bounds = map.getBounds();
    const paddedBounds = bounds.extend(bounds.getCenter()); // Pad by 100%

    const params = {
      north: paddedBounds.getNorth(),
      south: paddedBounds.getSouth(),
      east: paddedBounds.getEast(),
      west: paddedBounds.getWest(),
      searchQuery: searchQuery.value,
    };

    // --- DEBUG LOG ---
    console.log('Updating markers for bounds:', {
      north: params.north.toFixed(4),
      south: params.south.toFixed(4),
      east: params.east.toFixed(4),
      west: params.west.toFixed(4),
    });


    if (!searchQuery.value && isBoundsLoaded(paddedBounds)) {
      console.log('Bounds already loaded, skipping fetch.');
      return;
    }

    const cacheKey = getCacheKey(paddedBounds);
    if (pendingRequests.has(cacheKey)) {
      return;
    }

    console.log('Fetching new data...');
    const requestPromise = usahaStore.fetchUsahas(params)
      .then(() => {
        const existingIds = new Set(cachedUsahas.map(u => u.idsbr));
        const newUsahas = usahaStore.usahas.filter(u => !existingIds.has(u.idsbr));

        if (newUsahas.length > 0) {
          cachedUsahas = [...cachedUsahas, ...newUsahas];
        }

        if (cachedUsahas.length > 20000) { // Cache limit
          cachedUsahas = cachedUsahas.slice(-10000);
        }

        if (!searchQuery.value) {
          addToLoadedBounds(paddedBounds);
        }

        // Update source data
        const source = map.getSource('usahas-source');
        if (source) {
          const features = cachedUsahas.map(usaha => ({
            type: 'Feature',
            properties: {
              idsbr: usaha.idsbr,
              nama_usaha: usaha.nama_usaha,
              alamat: usaha.alamat,
            },
            geometry: {
              type: 'Point',
              coordinates: [parseFloat(usaha.longitude), parseFloat(usaha.latitude)]
            }
          }));

          source.setData({
            type: 'FeatureCollection',
            features: features
          });
          console.log(`Updated 'usahas-source' with ${features.length} total features.`);
        }
      })
      .finally(() => {
        pendingRequests.delete(cacheKey);
      });

    pendingRequests.set(cacheKey, requestPromise);
    await requestPromise.catch(error => console.error('Error fetching usahas:', error));
  }, 250); // Debounce
};

const selectSuggestion = (usaha) => {
  if (!map) return;

  console.log('Suggestion selected:', usaha);
  searchQuery.value = usaha.nama_usaha;
  usahaStore.suggestions = [];

  const lat = parseFloat(usaha.latitude);
  const lng = parseFloat(usaha.longitude);

  map.flyTo({
    center: [lng, lat],
    zoom: 16,
    duration: 1500
  });

  // Directly open the detail popup with the correct data after a short delay
  setTimeout(() => {
    updateHighlightPopup(usaha); // Show temporary highlight
    usahaStore.openDetailPopup(usaha); // Show persistent detail view
  }, 500);
};

const clearSearch = () => {
  searchQuery.value = '';
  usahaStore.suggestions = [];
  if (highlightPopup) {
    highlightPopup.remove();
  }
  updateMarkers();
};

const onPageInit = () => {
  map = new maplibregl.Map({
    container: 'map',
    style: {
      version: 8,
      sources: {
        'osm-tiles': {
          type: 'raster',
          tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
          tileSize: 256,
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }
      },
      layers: [{
        id: 'osm-layer',
        type: 'raster',
        source: 'osm-tiles'
      }]
    },
    center: [109.33, 0.02],
    zoom: 10,
    minZoom: 8,
    maxZoom: 19,
  });

  map.on('load', () => {
    console.log('Map loaded, initializing sources and layers.');
    // --- Usaha Source and Layer ---
    map.addSource('usahas-source', {
      type: 'geojson',
      data: { type: 'FeatureCollection', features: [] },
      cluster: true,
      clusterMaxZoom: 14,
      clusterRadius: 50
    });

    map.addLayer({
      id: 'clusters',
      type: 'circle',
      source: 'usahas-source',
      filter: ['has', 'point_count'],
      paint: {
        'circle-color': [
          'step',
          ['get', 'point_count'],
          '#51bbd6', 100, '#f1f075', 750, '#f28cb1'
        ],
        'circle-radius': [
          'step',
          ['get', 'point_count'],
          20, 100, 30, 750, 40
        ]
      }
    });

    map.addLayer({
      id: 'cluster-count',
      type: 'symbol',
      source: 'usahas-source',
      filter: ['has', 'point_count'],
      layout: {
        'text-field': '{point_count_abbreviated}',
        'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
        'text-size': 12
      }
    });

    map.addLayer({
      id: 'unclustered-point',
      type: 'circle',
      source: 'usahas-source',
      filter: ['!', ['has', 'point_count']],
      paint: {
        'circle-color': '#11b4da',
        'circle-radius': 6,
        'circle-stroke-width': 1,
        'circle-stroke-color': '#fff'
      }
    });

    // --- SubSLS Source and Layer ---
    map.addSource('subsls-source', {
      type: 'geojson',
      data: { type: 'FeatureCollection', features: [] },
      generateId: true // Important for feature state
    });

    map.addLayer({
      id: 'subsls-layer',
      type: 'fill',
      source: 'subsls-source',
      layout: {},
      paint: {
        'fill-color': '#ff7800',
        'fill-opacity': [
          'case',
          ['boolean', ['feature-state', 'hover'], false],
          0.4, // Opacity when hovered
          0.15 // Default opacity
        ],
        'fill-outline-color': '#ff7800'
      }
    });

    // --- Event Listeners ---
    map.on('moveend', updateMarkers);
    map.on('zoomend', updateMarkers);

    map.on('click', 'unclustered-point', (e) => {
      const properties = e.features[0].properties;
      // --- DEBUG LOG ---
      console.log('Clicked point properties:', properties);

      const fullData = cachedUsahas.find(u => u.idsbr === properties.idsbr);
      if (fullData) {
        usahaStore.openDetailPopup(fullData);
      } else {
        console.warn('Could not find full data for clicked point:', properties.idsbr);
      }
    });

    map.on('click', 'clusters', (e) => {
      const features = map.queryRenderedFeatures(e.point, { layers: ['clusters'] });
      const clusterId = features[0].properties.cluster_id;
      map.getSource('usahas-source').getClusterExpansionZoom(clusterId, (err, zoom) => {
        if (err) return;
        map.easeTo({
          center: features[0].geometry.coordinates,
          zoom: zoom
        });
      });
    });

    map.on('mouseenter', 'unclustered-point', () => { map.getCanvas().style.cursor = 'pointer'; });
    map.on('mouseleave', 'unclustered-point', () => { map.getCanvas().style.cursor = ''; });
    map.on('mouseenter', 'clusters', () => { map.getCanvas().style.cursor = 'pointer'; });
    map.on('mouseleave', 'clusters', () => { map.getCanvas().style.cursor = ''; });

    // Hover effect for SubSLS
    map.on('mousemove', 'subsls-layer', (e) => {
      if (e.features.length > 0) {
        if (hoveredSubslsId.value !== null) {
          map.setFeatureState({ source: 'subsls-source', id: hoveredSubslsId.value }, { hover: false });
        }
        hoveredSubslsId.value = e.features[0].id;
        map.setFeatureState({ source: 'subsls-source', id: hoveredSubslsId.value }, { hover: true });
      }
    });

    map.on('mouseleave', 'subsls-layer', () => {
      if (hoveredSubslsId.value !== null) {
        map.setFeatureState({ source: 'subsls-source', id: hoveredSubslsId.value }, { hover: false });
      }
      hoveredSubslsId.value = null;
    });


    // Initial data load
    wilayahStore.fetchSubslsGeojson();
    updateMarkers();
  });
};

// --- Watchers ---
watch(() => wilayahStore.subslsGeojson, (newGeojson) => {
  if (map && map.isStyleLoaded() && newGeojson) {
    const source = map.getSource('subsls-source');
    if (source) {
      source.setData(newGeojson);
    }
  }
});

watch(searchQuery, (newQuery) => {
  clearTimeout(searchTimeout);
  if (newQuery.trim()) {
    const query = newQuery.toLowerCase().trim();
    const results = cachedUsahas
      .filter(usaha => usaha.nama_usaha.toLowerCase().includes(query))
      .slice(0, 10);
    usahaStore.suggestions = results;
  } else {
    usahaStore.suggestions = [];
  }
});

onBeforeUnmount(() => {
  clearTimeout(updateTimeout);
  clearTimeout(searchTimeout);
  if (map) {
    map.remove();
    map = null;
  }
  cachedUsahas = [];
  loadedBounds = [];
  pendingRequests.clear();
});
</script>

<style>
/* MapLibre GL requires some global overrides for popups */
.maplibregl-popup-content {
  padding: 10px;
  background-color: rgba(255, 255, 255, 0.9);
  border-radius: 6px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
}

.maplibregl-popup-close-button {
  font-size: 20px;
  color: #555;
}

.highlight-popup .maplibregl-popup-content {
  background-color: #d32f2f;
  color: white;
  border: 2px solid white;
}

.highlight-popup .maplibregl-popup-tip {
  border-top-color: #d32f2f !important;
}

#map {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.searchbar-container {
  position: absolute;
  top: 15px;
  left: 50%;
  transform: translateX(-50%);
  width: calc(100% - 30px);
  max-width: 500px;
  z-index: 1000;
}

.search-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background-color: var(--f7-page-bg-color, #fff);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 999;
  max-height: 50vh;
  overflow-y: auto;
}
</style>
