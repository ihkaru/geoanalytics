import MapLibrePage from './pages/MapLibrePage.vue';
import OpportunityMapPage from './pages/OpportunityMapPage.vue';
// Hapus import untuk HomePage dan MapPage jika tidak dipakai

export default [
  // Rute ini akan menjadi rute utama yang menampilkan Tab Bar
  {
    path: '/',
    component: MapLibrePage, // Halaman yang aktif pertama kali
  },
  {
    path: '/opportunity-map',
    component: OpportunityMapPage,
  },
  // Hapus rute yang tidak terpakai seperti /home dan /map
];
