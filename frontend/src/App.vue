<!-- app.vue -->
<template>
  <f7-app v-bind="f7params">

    <!--
      Struktur Tabbed Views.
      Toolbar sekarang mengontrol Tabs di bawahnya.
    -->
    <f7-view main :toolbar="true">
      <f7-toolbar bottom labels>
        <!--
          Gunakan properti 'icon-f7' atau 'icon-material'.
          'tab-link-active' menandai tab yang aktif saat pertama kali dimuat.
        -->
        <f7-link tab-link href="/" text="Peta Usaha" icon-f7="building_2_fill" icon-material="store"
          tab-link-active></f7-link>

        <f7-link tab-link href="/opportunity-map" text="Peta Peluang" icon-f7="sparkles"
          icon-material="insights"></f7-link>
      </f7-toolbar>
    </f7-view>

  </f7-app>
</template>

<script setup>
import { reactive } from 'vue';
import routes from './routes.js';

// Framework7 parameters
const f7params = reactive({
  routes,
  name: 'GeoAnalytics',
  id: 'io.framework7.myapp',
  // Menambahkan theme detection otomatis untuk tampilan native iOS/Android
  theme: 'auto',
  // Aktifkan swipe back pada halaman (fitur standar di iOS)
  view: {
    stackPages: true,
    swipeBackPage: true,
  },
});
</script>
