<script setup>
import "./assets/main.css";
import { onMounted, ref } from "vue";
import AppToast from "./components/AppToast.vue";
import LoadingScreen from "./components/LoadingScreen.vue";

const showLoading = ref(true);

onMounted(() => {
  let hidden = false;
  const startedAt = performance.now();
  const minMs = 900;

  const hide = () => {
    if (hidden) return;
    const wait = Math.max(0, minMs - (performance.now() - startedAt));
    window.setTimeout(() => {
      hidden = true;
      showLoading.value = false;
    }, wait);
  };

  Promise.all([
    document.fonts?.ready ?? Promise.resolve(),
    new Promise((resolve) => {
      if (document.readyState === "complete") {
        resolve();
        return;
      }
      window.addEventListener("load", resolve, { once: true });
    }),
  ]).then(hide);

  window.setTimeout(hide, 3200);
});
</script>

<template>
  <Transition name="boot-exit">
    <LoadingScreen v-if="showLoading" />
  </Transition>
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

.boot-exit-leave-active {
  transition: opacity 0.4s ease;
}

.boot-exit-leave-to {
  opacity: 0;
}
</style>
