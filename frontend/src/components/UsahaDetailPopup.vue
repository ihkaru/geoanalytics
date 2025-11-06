<template>
  <f7-popup
    class="usaha-detail-popup"
    :opened="isPopupOpen"
    @popup:closed="closePopup"
    swipe-to-step
    swipe-to-close="to-bottom"
    :breakpoints="[0.4, 0.8, 1]"
    swipe-handler=".swipe-handler"
  >
    <div class="swipe-handler"></div>
    <f7-page-content>
      <f7-block-title large>{{ usaha?.nama_usaha }}</f7-block-title>
      <f7-list strong-ios dividers-ios inset-ios>
        <f7-list-item header="Alamat" :title="usaha?.alamat"></f7-list-item>
        <f7-list-item header="Nama Komersial" :title="usaha?.nama_komersial_usaha || '-'"></f7-list-item>
        <f7-list-item header="Skala Usaha" :title="usaha?.skala_usaha"></f7-list-item>
        <f7-list-item header="Kategori" :title="usaha?.kategori_usaha"></f7-list-item>
        <f7-list-item header="KBLI" :title="usaha?.kbli"></f7-list-item>
        <f7-list-item header="Kegiatan Usaha" :title="usaha?.kegiatan_usaha || '-'"></f7-list-item>
        <f7-list-item header="Nomor WhatsApp" :title="usaha?.nomor_whatsapp || '-'"></f7-list-item>
      </f7-list>
    </f7-page-content>
  </f7-popup>
</template>

<script setup>
import { computed } from 'vue';
import { useUsahaStore } from '../stores/usaha';

const usahaStore = useUsahaStore();

const isPopupOpen = computed(() => usahaStore.isDetailPopupOpen);
const usaha = computed(() => usahaStore.selectedUsaha);

const closePopup = () => {
  usahaStore.closeDetailPopup();
};
</script>

<style scoped>
.usaha-detail-popup {
  --f7-popup-starting-breakpoint: 0.4;
  height: 90%;
  top: auto;
  bottom: 0;
}
.swipe-handler {
  height: 20px;
  background: #f7f7f7;
  cursor: grab;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}
.swipe-handler::after {
  content: '';
  display: block;
  width: 40px;
  height: 4px;
  background: #ccc;
  border-radius: 2px;
}
</style>
