import { createPinia } from 'pinia';
import { createApp } from 'vue';

// Import Framework7 Bundle
import Framework7 from 'framework7/lite-bundle';

// Import Framework7-Vue Plugin
import Framework7Vue, { registerComponents } from 'framework7-vue/bundle';

// Import Framework7 Styles
import 'framework7/css/bundle';

import App from './App.vue';

// Init Framework7-Vue Plugin
Framework7.use(Framework7Vue);

const app = createApp(App);

// Register Framework7 Vue components
registerComponents(app);

app.use(createPinia());

app.mount('#app');
