import axios from 'axios';

const apiClient = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

export default {
  getUsahas(params) {
    return apiClient.get('/usaha', { params });
  },
  getKecamatan() {
    return apiClient.get('/wilayah/kecamatan');
  },
  getSubslsGeojson() {
    return apiClient.get('/geojson/subsls');
  },
  getUsahaSuggestions(query) {
    return apiClient.get('/usaha/suggestions', { params: { query } });
  },};
