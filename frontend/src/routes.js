import HomePage from './pages/HomePage.vue';
import MapPage from './pages/MapPage.vue';

export default [
  {
    path: '/',
    redirect: '/map',
  },
  {
    path: '/map',
    component: MapPage,
  },
  {
    path: '/home',
    component: HomePage,
  }
];
