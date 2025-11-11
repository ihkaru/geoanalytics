import HomePage from './pages/HomePage.vue';
import MapPage from './pages/MapPage.vue';
import MapLibrePage from './pages/MapLibrePage.vue';

export default [
  {
    path: '/',
    redirect: '/map-libre',
  },
  {
    path: '/map',
    component: MapPage,
  },
  {
    path: '/map-libre',
    component: MapLibrePage,
  },
  {
    path: '/home',
    component: HomePage,
  }
];
