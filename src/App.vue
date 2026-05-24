<script setup>
import "./assets/main.css";
import { ref, onMounted } from 'vue';
import AppToast from "./components/AppToast.vue";
import LoadingScreen from "./components/LoadingScreen.vue";

const showLoading = ref(true);

onMounted(() => {
  // keep loading visible briefly to simulate initial load; adjust duration as needed
  setTimeout(() => {
    showLoading.value = false;
  }, 800);
});
</script>

<template>
  <LoadingScreen v-if="showLoading" />
  <div class="app-wrapper" :aria-hidden="showLoading">
    <router-view />
    <AppToast />
  </div>
</template>

<style>
.app-wrapper {
  min-height: 100vh;
  background: #0d0d0d;
}
</style>
