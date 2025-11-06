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
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import UsahaDetailPopup from '../components/UsahaDetailPopup.vue';
import { useUsahaStore } from '../stores/usaha';
import { useWilayahStore } from '../stores/wilayah';

// Import glify after Leaflet
let glifyLoaded = ref(false);
const useGlify = ref(false); // Flag to control glify usage

onMounted(async () => {
  try {
    // Try dynamic import
    await import('leaflet.glify');
    glifyLoaded.value = true;
    useGlify.value = false; // Set to true to use glify, false to always use canvas
    console.log('Leaflet.glify loaded successfully. Using:', useGlify.value ? 'Glify' : 'Canvas (forced)');
  } catch (error) {
    console.warn('Failed to load leaflet.glify, using fallback markers:', error);
    glifyLoaded.value = false;
    useGlify.value = false;
  }
});

const usahaStore = useUsahaStore();
const wilayahStore = useWilayahStore();
const searchQuery = ref('');
const suggestions = computed(() => usahaStore.suggestions);

let map = null;
const geojsonLayer = L.layerGroup();
let glifyPoints = null;
let canvasRenderer = null; // Reuse canvas renderer
let highlightMarker = null; // Marker untuk highlight titik yang dipilih
let selectedUsahaId = ref(null); // ID usaha yang dipilih

let searchTimeout = null;
let updateTimeout = null;

// Advanced caching system
const dataCache = new Map(); // Cache per tile/region
const pendingRequests = new Map(); // Track ongoing requests
let cachedUsahas = []; // All loaded usahas
let loadedBounds = []; // Array of loaded bound rectangles

// Generate cache key from bounds
const getCacheKey = (bounds) => {
  return `${bounds.north.toFixed(3)}_${bounds.south.toFixed(3)}_${bounds.east.toFixed(3)}_${bounds.west.toFixed(3)}`;
};

// Check if bounds are already loaded
const isBoundsLoaded = (bounds) => {
  const targetBounds = L.latLngBounds(
    [bounds.south, bounds.west],
    [bounds.north, bounds.east]
  );

  return loadedBounds.some(loaded => loaded.contains(targetBounds));
};

// Add bounds to loaded cache
const addToLoadedBounds = (bounds) => {
  const newBounds = L.latLngBounds(
    [bounds.south, bounds.west],
    [bounds.north, bounds.east]
  );
  loadedBounds.push(newBounds);

  // Limit cache size - keep only last 10 regions
  if (loadedBounds.length > 10) {
    loadedBounds.shift();
  }
};

// Function to create or update highlight marker
const updateHighlightMarker = (usaha) => {
  if (!map) return;

  const lat = parseFloat(usaha.latitude);
  const lng = parseFloat(usaha.longitude);

  // Remove old highlight marker
  if (highlightMarker) {
    map.removeLayer(highlightMarker);
  }

  // Create pulsing highlight marker
  const pulsingIcon = L.divIcon({
    className: 'pulsing-marker',
    html: `
      <div class="pulse-container">
        <div class="pulse-ring"></div>
        <div class="pulse-dot"></div>
      </div>
    `,
    iconSize: [40, 40],
    iconAnchor: [20, 20]
  });

  highlightMarker = L.marker([lat, lng], {
    icon: pulsingIcon,
    zIndexOffset: 1000
  }).addTo(map);

  // Open popup
  highlightMarker.bindPopup(
    `<strong style="color: #ff0000;">${usaha.nama_usaha}</strong><br>${usaha.alamat}`,
    { closeButton: true, maxWidth: 300 }
  ).openPopup();

  // Remove highlight after 5 seconds
  setTimeout(() => {
    if (highlightMarker) {
      map.removeLayer(highlightMarker);
      highlightMarker = null;
      selectedUsahaId.value = null;
    }
  }, 5000);
};

// Non-blocking update with proper caching
const updateMarkers = async () => {
  if (!map) return;

  clearTimeout(updateTimeout);
  updateTimeout = setTimeout(async () => {
    const bounds = map.getBounds();
    const paddedBounds = bounds.pad(0.5);

    const params = {
      north: paddedBounds.getNorth(),
      south: paddedBounds.getSouth(),
      east: paddedBounds.getEast(),
      west: paddedBounds.getWest(),
      searchQuery: searchQuery.value,
    };

    // ALWAYS show cached data first (prevent flicker)
    const visibleCached = cachedUsahas.filter(usaha => {
      const lat = parseFloat(usaha.latitude);
      const lng = parseFloat(usaha.longitude);
      return lat >= params.south && lat <= params.north &&
        lng >= params.west && lng <= params.east;
    });

    // Show cached data immediately - this prevents flicker
    if (visibleCached.length > 0) {
      console.log('Displaying', visibleCached.length, 'cached markers');
      usahaStore.usahas = visibleCached;
    }

    // Check if bounds are covered by loaded areas
    if (!searchQuery.value && isBoundsLoaded(params)) {
      console.log('Bounds already fully loaded from cache');
      return;
    }

    const cacheKey = getCacheKey(params);

    // Check if same request is already pending
    if (pendingRequests.has(cacheKey)) {
      console.log('Request already pending:', cacheKey);
      try {
        await pendingRequests.get(cacheKey);
      } catch (e) {
        console.warn('Pending request failed:', e);
      }
      return;
    }

    // Make new request only if needed (non-blocking, in background)
    console.log('Fetching new data for:', cacheKey);

    const requestPromise = usahaStore.fetchUsahas(params)
      .then((data) => {
        // Add to cache
        dataCache.set(cacheKey, usahaStore.usahas);

        // Merge with existing cached data (remove duplicates)
        const existingIds = new Set(cachedUsahas.map(u => u.idsbr));
        const newUsahas = usahaStore.usahas.filter(u => !existingIds.has(u.idsbr));

        if (newUsahas.length > 0) {
          cachedUsahas = [...cachedUsahas, ...newUsahas];
          console.log(`Added ${newUsahas.length} new usahas. Total cached: ${cachedUsahas.length}`);

          // Update display with new data
          const updatedVisible = cachedUsahas.filter(usaha => {
            const lat = parseFloat(usaha.latitude);
            const lng = parseFloat(usaha.longitude);
            return lat >= params.south && lat <= params.north &&
              lng >= params.west && lng <= params.east;
          });
          usahaStore.usahas = updatedVisible;
        }

        // Limit total cache size
        if (cachedUsahas.length > 10000) {
          cachedUsahas = cachedUsahas.slice(-5000);
        }

        // Add bounds to loaded areas
        if (!searchQuery.value) {
          addToLoadedBounds(params);
        }

        return data;
      })
      .finally(() => {
        pendingRequests.delete(cacheKey);
      });

    pendingRequests.set(cacheKey, requestPromise);

    try {
      await requestPromise;
    } catch (error) {
      console.error('Error fetching usahas:', error);
    }
  }, 100);
};

const selectSuggestion = (usaha) => {
  if (!map) return;

  // Set search query first
  searchQuery.value = usaha.nama_usaha;

  // Clear suggestions immediately for instant UI feedback
  usahaStore.suggestions = [];

  // Set selected usaha ID
  selectedUsahaId.value = usaha.idsbr;

  // Show the selected marker immediately from cache
  const lat = parseFloat(usaha.latitude);
  const lng = parseFloat(usaha.longitude);

  // Create bounds around the selected point to ensure it's in view
  const targetBounds = L.latLngBounds(
    [lat - 0.01, lng - 0.01],
    [lat + 0.01, lng + 0.01]
  );

  // Filter markers around this area from cache for immediate display
  const nearbyUsahas = cachedUsahas.filter(u => {
    const uLat = parseFloat(u.latitude);
    const uLng = parseFloat(u.longitude);
    return Math.abs(uLat - lat) < 0.05 && Math.abs(uLng - lng) < 0.05;
  });

  // Show nearby markers immediately
  if (nearbyUsahas.length > 0) {
    usahaStore.usahas = nearbyUsahas;
  }

  // Fly to location with callback
  map.flyTo([lat, lng], 16, {
    duration: 1.5
  });

  // Create highlight marker after a short delay (after map animation starts)
  setTimeout(() => {
    updateHighlightMarker(usaha);
  }, 500);
};

const clearSearch = () => {
  searchQuery.value = '';
  usahaStore.suggestions = [];
  selectedUsahaId.value = null;

  // Remove highlight marker
  if (highlightMarker) {
    map.removeLayer(highlightMarker);
    highlightMarker = null;
  }

  // Don't clear cache, just reload from cache
  updateMarkers();
};

const onPageInit = () => {
  map = L.map('map', {
    center: [0.02, 109.33],
    zoom: 10,
    zoomControl: false,
    preferCanvas: false,
    zoomAnimation: true,
    fadeAnimation: true,
    markerZoomAnimation: true,
    inertia: true,
    inertiaDeceleration: 3000,
    inertiaMaxSpeed: 1500,
    worldCopyJump: false,
    maxBoundsViscosity: 1.0,
  });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19,
    minZoom: 8,
    keepBuffer: 2,
    updateWhenIdle: false,
    updateWhenZooming: false,
  }).addTo(map);

  // Initialize canvas renderer once
  canvasRenderer = L.canvas({ padding: 0.5, tolerance: 5 });

  map.on('moveend', updateMarkers);
  map.on('zoomend', updateMarkers);

  wilayahStore.fetchSubslsGeojson();
  updateMarkers();
};

// Watch for GeoJSON data
watch(() => wilayahStore.subslsGeojson, (newGeojson) => {
  if (map && newGeojson) {
    geojsonLayer.clearLayers();

    const currentZoom = map.getZoom();
    if (currentZoom < 12) {
      return;
    }

    const layer = L.geoJSON(newGeojson, {
      style: {
        fillColor: '#ff7800',
        weight: 1,
        opacity: 0.6,
        color: 'white',
        dashArray: '3',
        fillOpacity: 0.15
      },
      onEachFeature: (feature, layer) => {
        if (feature.properties && feature.properties.nama_sls) {
          layer.bindPopup(feature.properties.nama_sls);
        }
        layer.on({
          mouseover: (e) => {
            e.target.setStyle({ weight: 3, color: '#666', dashArray: '', fillOpacity: 0.3 });
          },
          mouseout: (e) => layer.resetStyle(e.target),
          click: (e) => {
            e.originalEvent.stopPropagation();
            map.fitBounds(e.target.getBounds());
          },
        });
      }
    });
    geojsonLayer.addLayer(layer);
    map.addLayer(geojsonLayer);
  }
});

// Optimized glify rendering with progressive loading
let renderTimeout = null;
watch(() => usahaStore.usahas, async (newUsahas) => {
  if (!map) {
    console.log('Map not ready');
    return;
  }

  // Clear previous render timeout
  clearTimeout(renderTimeout);

  // Delay render slightly to allow smooth panning
  renderTimeout = setTimeout(async () => {
    console.log('Rendering', newUsahas?.length || 0, 'usahas');

    // Remove old layer
    if (glifyPoints) {
      try {
        glifyPoints.remove();
      } catch (e) {
        console.warn('Error removing glify:', e);
      }
      glifyPoints = null;
    }

    // Also clear geojsonLayer to prevent overlap (but keep highlight marker)
    geojsonLayer.clearLayers();

    if (!newUsahas || newUsahas.length === 0) {
      console.log('No usahas to render');
      return;
    }

    const validUsahas = newUsahas.filter(usaha => {
      const lat = parseFloat(usaha.latitude);
      const lng = parseFloat(usaha.longitude);
      return !isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0;
    });

    console.log('Valid usahas:', validUsahas.length);

    if (validUsahas.length === 0) {
      console.warn('No valid coordinates');
      return;
    }

    const currentZoom = map.getZoom();
    let pointSize = 8;
    if (currentZoom >= 14) pointSize = 12;
    else if (currentZoom >= 12) pointSize = 10;
    else if (currentZoom < 10) pointSize = 6;

    const points = validUsahas.map(usaha => ({
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

    await nextTick();

    try {
      if (!L.glify || !glifyLoaded.value || !useGlify.value) {
        console.log('Glify not available or disabled, using fallback');
        throw new Error('Glify not loaded');
      }

      console.log('Creating glify layer with', points.length, 'points');

      glifyPoints = L.glify.points({
        map: map,
        data: {
          type: 'FeatureCollection',
          features: points
        },
        click: (e, pointOrGeoJsonFeature, xy) => {
          const idsbr = pointOrGeoJsonFeature.properties.idsbr;
          const fullData = cachedUsahas.find(u => u.idsbr === idsbr) ||
            usahaStore.usahas.find(u => u.idsbr === idsbr);
          if (fullData) {
            usahaStore.openDetailPopup(fullData);
          }
        },
        color: { r: 0, g: 100, b: 255 },
        size: pointSize,
        opacity: 0.85,
        className: 'glify-points',
        sensitivity: 4,
        sensitivityHover: 2,
        pane: 'overlayPane',
      });

      console.log('Glify layer created successfully');

      requestAnimationFrame(() => {
        if (map) {
          map.invalidateSize({ animate: false });
        }
      });

    } catch (error) {
      // Silent fallback to canvas markers
      console.log('Using canvas markers fallback for', validUsahas.length, 'points');

      // Clear old markers efficiently
      geojsonLayer.clearLayers();

      // Batch add markers for better performance
      const markers = validUsahas.map(usaha => {
        const isSelected = selectedUsahaId.value === usaha.idsbr;

        const marker = L.circleMarker(
          [parseFloat(usaha.latitude), parseFloat(usaha.longitude)],
          {
            renderer: canvasRenderer,
            radius: isSelected ? pointSize : pointSize / 2,
            fillColor: isSelected ? '#ff0000' : '#0066ff',
            color: '#ffffff',
            weight: isSelected ? 2 : 1,
            opacity: isSelected ? 1 : 0.8,
            fillOpacity: isSelected ? 0.9 : 0.7
          }
        )
          .bindPopup(`<strong${isSelected ? ' style="color: #ff0000;"' : ''}>${usaha.nama_usaha}</strong><br>${usaha.alamat}`, {
            closeButton: true,
            maxWidth: 300
          })
          .on('click', () => {
            const fullData = cachedUsahas.find(u => u.idsbr === usaha.idsbr) || usaha;
            usahaStore.openDetailPopup(fullData);
          });
        return marker;
      });

      console.log('Adding', markers.length, 'canvas markers');

      // Add all markers at once
      markers.forEach(marker => geojsonLayer.addLayer(marker));

      if (!map.hasLayer(geojsonLayer)) {
        map.addLayer(geojsonLayer);
      }

      console.log('Canvas markers added to map');
    }
  }, 50); // Small delay for smooth UX
}, { flush: 'post' });

// Optimized search with local filtering only
watch(searchQuery, (newQuery, oldQuery) => {
  if (newQuery === oldQuery) return;

  // Clear suggestions immediately when query changes
  clearTimeout(searchTimeout);

  if (newQuery.trim()) {
    const query = newQuery.toLowerCase().trim();

    // Search from all cached data for instant results
    const allData = [...new Set([...cachedUsahas, ...usahaStore.usahas])];

    const results = allData
      .filter(usaha => usaha.nama_usaha.toLowerCase().includes(query))
      .slice(0, 10);

    usahaStore.suggestions = results;

    // Debounced backend search (optional, for fresh data)
    searchTimeout = setTimeout(() => {
      if (newQuery.trim()) {
        // This is optional - only if you want to fetch from backend
        // For better performance, comment this out and rely only on cache
        // usahaStore.fetchSuggestions(newQuery);
      }
    }, 500);
  } else {
    usahaStore.suggestions = [];
  }
});

onBeforeUnmount(() => {
  clearTimeout(updateTimeout);
  clearTimeout(searchTimeout);
  clearTimeout(renderTimeout);

  // Clear all caches
  dataCache.clear();
  pendingRequests.clear();
  cachedUsahas = [];
  loadedBounds = [];

  // Remove highlight marker
  if (highlightMarker) {
    map.removeLayer(highlightMarker);
    highlightMarker = null;
  }

  if (glifyPoints) {
    try {
      glifyPoints.remove();
    } catch (e) {
      console.warn('Error removing glify on unmount:', e);
    }
  }
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
  z-index: 1;
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

/* Pulsing marker styles */
:deep(.pulsing-marker) {
  background: transparent;
  border: none;
}

.pulse-container {
  position: relative;
  width: 40px;
  height: 40px;
}

.pulse-ring {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 30px;
  height: 30px;
  border: 3px solid #ff0000;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  animation: pulse-ring 1.5s ease-out infinite;
}

.pulse-dot {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 16px;
  height: 16px;
  background-color: #ff0000;
  border: 3px solid #ffffff;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
  animation: pulse-dot 1.5s ease-in-out infinite;
  z-index: 1001;
}

@keyframes pulse-ring {
  0% {
    transform: translate(-50%, -50%) scale(0.5);
    opacity: 1;
  }

  100% {
    transform: translate(-50%, -50%) scale(1.5);
    opacity: 0;
  }
}

@keyframes pulse-dot {

  0%,
  100% {
    transform: translate(-50%, -50%) scale(1);
  }

  50% {
    transform: translate(-50%, -50%) scale(1.2);
  }
}

:deep(.glify-points) {
  z-index: 400 !important;
  pointer-events: auto !important;
  image-rendering: -webkit-optimize-contrast;
  image-rendering: crisp-edges;
}

:deep(.leaflet-tile-container) {
  image-rendering: -webkit-optimize-contrast;
}

:deep(.leaflet-zoom-animated) {
  will-change: transform;
}
</style>
