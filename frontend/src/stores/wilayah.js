import { defineStore } from 'pinia';
import api from '../js/api';

export const useWilayahStore = defineStore('wilayah', {
  state: () => ({
    kecamatans: [],
    subslsGeojson: null,
    loading: false,
    error: null,
  }),
  getters: {
    allKecamatans: (state) => state.kecamatans,
    getSubslsGeojson: (state) => state.subslsGeojson,
  },
  actions: {
    async fetchKecamatans() {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.getKecamatan();
        this.kecamatans = response.data;
      } catch (error) {
        this.error = error;
      } finally {
        this.loading = false;
      }
    },
    async fetchSubslsGeojson() {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.getSubslsGeojson();
        this.subslsGeojson = response.data;
      } catch (error) {
        this.error = error;
      } finally {
        this.loading = false;
      }
    },
  },
});
