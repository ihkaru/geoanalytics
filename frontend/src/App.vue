<!-- app.vue -->
<template>
  <!-- Main Framework7 App component where we pass Framework7 params -->
  <f7-app v-bind="f7params">
    <!-- initial page is specified in routes.js -->
    <f7-view main url="/"></f7-view>
  </f7-app>
</template>

<script setup>
import { reactive } from 'vue';
import routes from './routes.js';

// Framework7 parameters that we pass to <f7-app> component
const f7params = reactive({
  // Array with app routes
  routes,
  // App Name
  name: 'My App',
  // ...
});
</script>
