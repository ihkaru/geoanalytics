import { defineStore } from 'pinia';
import api from '../js/api';

export const useOpportunityMapStore = defineStore('opportunityMap', {
  state: () => ({
    // List of KBLI options for the dropdown
    kbliOptions: [],
    // User's current selection
    selectedKbli: null,
    selectedRadius: null, // Ubah dari 1000 ke null agar user harus pilih

    loading: false,
    error: null,

    // State for the details popup
    popupOpened: false,
    selectedZoneId: null,
    selectedZoneDetails: null,
    loadingDetails: false,
    errorDetails: null,
  }),

  actions: {
    async fetchKbliOptions() {
      console.log('[Store] fetchKbliOptions called...');
      if (this.kbliOptions.length > 0) return; // Fetch only once

      this.loading = true;
      this.error = null;

      try {
        const response = await api.getKbli();
        console.log('[Store] Raw API response for KBLI:', response.data);

        this.kbliOptions = response.data.map(kbli => ({
          value: kbli.kbli_id,
          text: `[${kbli.kbli_id}] ${kbli.judul}`
        }));

        console.log(`[Store] Successfully processed ${this.kbliOptions.length} KBLI options.`);
      } catch (error) {
        this.error = error;
        console.error("[Store] Error fetching KBLI options:", error);
      } finally {
        this.loading = false;
      }
    },

    async fetchZoneDetails(idsubsls, kbli, radius) {
      console.log(`[Store] fetchZoneDetails called with:`, { idsubsls, kbli, radius });

      this.loadingDetails = true;
      this.errorDetails = null;
      this.selectedZoneId = idsubsls;
      this.selectedZoneDetails = null;
      this.popupOpened = true;

      try {
        const response = await api.getZonaDetail(idsubsls);
        console.log('[Store] Raw API response for details:', response.data);

        // Find matching analysis
        const details = response.data.find(d =>
          d.kbli_5_digit === kbli &&
          d.radius_meter === parseInt(radius, 10)
        );

        console.log('[Store] Found matching details:', details);

        if (details) {
          this.selectedZoneDetails = details;
        } else {
          this.errorDetails = `Tidak ada data analisis untuk KBLI ${kbli} dan Radius ${radius}m di zona ini.`;
        }
      } catch (error) {
        this.errorDetails = error.response?.data?.message || error.message || 'Gagal memuat detail zona';
        console.error("[Store] Error fetching zone details:", error);
      } finally {
        this.loadingDetails = false;
      }
    },

    closePopup() {
      this.popupOpened = false;
      this.selectedZoneId = null;
      this.selectedZoneDetails = null;
      this.errorDetails = null;
    },

    setSelectedKbli(kbli) {
      this.selectedKbli = kbli;
    },

    setSelectedRadius(radius) {
      this.selectedRadius = parseInt(radius, 10);
    }
  },
});
