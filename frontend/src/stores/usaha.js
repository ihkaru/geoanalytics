import { defineStore } from 'pinia';
import api from '../js/api';

export const useUsahaStore = defineStore('usaha', {
  state: () => ({
    usahas: [],
    selectedUsaha: null,
    isDetailPopupOpen: false,
    suggestions: [],
    loading: false,
    error: null,
  }),
  getters: {
    allUsahas: (state) => state.usahas,
  },
  actions: {
    async fetchUsahas(params) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.getUsahas(params);
        this.usahas = response.data || [];
      } catch (error) {
        this.error = error;
      } finally {
        this.loading = false;
      }
    },
    openDetailPopup(usaha) {
      this.selectedUsaha = usaha;
      this.isDetailPopupOpen = true;
    },
    closeDetailPopup() {
      this.isDetailPopupOpen = false;
    },
    async fetchSuggestions(query) {
      if (!query) {
        this.suggestions = [];
        return;
      }
      try {
        const response = await api.getUsahaSuggestions(query);
        this.suggestions = response.data;
      } catch (error) {
        console.error("Error fetching suggestions:", error);
        this.suggestions = [];
      }
    },
  },
});
