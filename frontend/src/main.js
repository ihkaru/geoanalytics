import { createPinia } from 'pinia';
import { createApp } from 'vue';

// Import Framework7 Bundle
import Framework7 from 'framework7/lite-bundle';

// Import Framework7-Vue Plugin
import Framework7Vue, { registerComponents } from 'framework7-vue/bundle';

// === BAGIAN PENTING DI SINI ===
// Import Framework7 Styles
import 'framework7-icons/css/framework7-icons.css'; // CSS untuk Ikon F7
import 'framework7/css/bundle'; // CSS utama Framework7
import 'material-icons/iconfont/material-icons.css'; // CSS untuk Ikon Material

import App from './App.vue';

// Init Framework7-Vue Plugin
Framework7.use(Framework7Vue);

const app = createApp(App);

// Register Framework7 Vue components
registerComponents(app);

app.use(createPinia());

app.mount('#app');
