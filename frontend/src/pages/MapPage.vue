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
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import UsahaDetailPopup from '../components/UsahaDetailPopup.vue';
import { useUsahaStore } from '../stores/usaha';
import { useWilayahStore } from '../stores/wilayah';

// --- SVG Icon Creation ---
const createSVGIcon = (color = '#007aff') => {
  const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28">
      <path fill="${color}" d="M12 0C7.31 0 3.5 3.81 3.5 8.5c0 5.25 8.5 15.5 8.5 15.5s8.5-10.25 8.5-15.5C20.5 3.81 16.69 0 12 0zm0 12a3.5 3.5 0 110-7 3.5 3.5 0 010 7z"/>
      <circle fill="white" cx="12" cy="8.5" r="1.5"/>
    </svg>
  `;
  return L.divIcon({
    html: svg,
    className: 'svg-icon', // for base styling
    iconSize: [28, 28],
    iconAnchor: [14, 28],
    popupAnchor: [0, -28]
  });
};

const defaultIcon = createSVGIcon('#007aff'); // Blue
const highlightIcon = createSVGIcon('#34c759'); // Green

const usahaStore = useUsahaStore();
const wilayahStore = useWilayahStore();
const searchQuery = ref('');
const suggestions = computed(() => usahaStore.suggestions);
let map = null;
const geojsonLayer = L.layerGroup();

let searchTimeout = null;
const highlightedUsahaId = ref(null);

let bufferedBounds = null;

const updateMarkers = () => {
  if (!map) return;

  const bounds = map.getBounds();

  // Only fetch if the current view is outside the buffered area
  if (bufferedBounds && bufferedBounds.contains(bounds)) {
    return; // We already have data for this view
  }

  // Extend the bounds to create a buffer (fetch a larger area)
  const paddedBounds = bounds.pad(0.5); // 50% padding

  // Update the buffered area
  bufferedBounds = paddedBounds;

  const params = {
    north: paddedBounds.getNorth(),
    south: paddedBounds.getSouth(),
    east: paddedBounds.getEast(),
    west: paddedBounds.getWest(),
    searchQuery: searchQuery.value,
  };
  usahaStore.fetchUsahas(params);
};

const selectSuggestion = (usaha) => {
  if (!map) return;
  highlightedUsahaId.value = usaha.idsbr;
  map.flyTo([usaha.latitude, usaha.longitude], 16);
  searchQuery.value = usaha.nama_usaha;
  usahaStore.suggestions = []; // Clear suggestions
};

const clearSearch = () => {
  searchQuery.value = '';
  usahaStore.suggestions = [];
  bufferedBounds = null; // Reset buffer
  updateMarkers();
};

const onPageInit = () => {
  map = L.map('map', {
    center: [0.02, 109.33], // Pontianak
    zoom: 10,
    zoomControl: false,
  });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  map.addLayer(geojsonLayer);

  map.on('moveend', updateMarkers);

  // Initial data fetch
  wilayahStore.fetchSubslsGeojson();
  updateMarkers();
};

// Watch for GeoJSON data
watch(() => wilayahStore.subslsGeojson, (newGeojson) => {
  if (map && newGeojson) {
    geojsonLayer.clearLayers();
    const layer = L.geoJSON(newGeojson, {
      style: { fillColor: '#ff7800', weight: 2, opacity: 1, color: 'white', dashArray: '3', fillOpacity: 0.5 },
      onEachFeature: (feature, layer) => {
        if (feature.properties && feature.properties.nama_sls) {
          layer.bindPopup(feature.properties.nama_sls);
        }
        layer.on({
          mouseover: (e) => {
            e.target.setStyle({ weight: 5, color: '#666', dashArray: '', fillOpacity: 0.7 });
            e.target.bringToFront();
          },
          mouseout: (e) => layer.resetStyle(e.target),
          click: (e) => map.fitBounds(e.target.getBounds()),
        });
      }
    });
    geojsonLayer.addLayer(layer);
  }
});

// Watch for Usaha data and add to map directly
watch(() => usahaStore.usahas, (newUsahas) => {
  // Clear existing markers from map
  map.eachLayer(function (layer) {
    if (layer instanceof L.Marker) {
      map.removeLayer(layer);
    }
  });

  newUsahas.forEach(usaha => {
    if (usaha.latitude && usaha.longitude) {
      let icon = defaultIcon;
      if (usaha.idsbr === highlightedUsahaId.value) {
        icon = highlightIcon;
        // Set a timeout to revert the icon
        setTimeout(() => {
          // Find the marker again to change its icon back
          map.eachLayer(function (layer) {
            if (layer instanceof L.Marker && layer.options.usahaId === usaha.idsbr) {
              layer.setIcon(defaultIcon);
            }
          });
        }, 3000);
        highlightedUsahaId.value = null; // Reset
      }

      const marker = L.marker([usaha.latitude, usaha.longitude], { icon: icon, usahaId: usaha.idsbr });
      marker.on('click', () => {
        usahaStore.openDetailPopup(usaha);
      });
      map.addLayer(marker);
    }
  });
});

// Watch for search query changes with debounce
watch(searchQuery, (newQuery, oldQuery) => {
  if (newQuery === oldQuery) return;

  // --- Instant Local Search for Suggestions ---
  if (newQuery) {
    const localResults = usahaStore.usahas.filter(usaha =>
      usaha.nama_usaha.toLowerCase().includes(newQuery.toLowerCase())
    ).slice(0, 10);
    usahaStore.suggestions = localResults;
  } else {
    usahaStore.suggestions = [];
  }

  // --- Debounced Server Search (for both suggestions and map markers) ---
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    // For suggestions
    if (newQuery) {
      usahaStore.fetchSuggestions(newQuery);
    }
    // For map markers
    bufferedBounds = null; // Reset buffer to force re-fetch
    updateMarkers();
  }, 300);
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});

</script>

<style scoped>
#map {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.searchbar-container {
  position: absolute;
  top: 15px;
  left: 50%;
  transform: translateX(-50%);
  width: calc(100% - 30px);
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

.search-suggestions .item-content {
  transition: background-color 0.2s ease-in-out, transform 0.1s ease-out;
}

.search-suggestions .item-content:hover {
  background-color: var(--f7-list-item-bg-color-active, #f0f0f0);
  transform: translateX(5px);
  cursor: pointer;
}

/* Base style for SVG icons to remove default leaflet backgrounds */
:global(.svg-icon) {
  background: none;
  border: none;
}
</style>