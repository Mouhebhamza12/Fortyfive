<template>
  <div class="loading-screen">
    <div class="loader-container">
      <div class="spinner"></div>
      <img class="logo" :src="brandMark" alt="45 logo" />
    </div>
  </div>
</template>

<script setup>
import brandMark from "../../assets/images/45svg.png";
</script>

<style scoped>
.loading-screen {
  position: fixed;
  inset: 0;
  background: #7ba3b5;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loader-container {
  position: relative;
  width: 150px;
  height: 150px;
}

.spinner {
  position: absolute;
  inset: 0;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top: 3px solid white;
  border-radius: 50%;
  animation: spin 1.2s linear infinite;
}

.logo {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 60px;
  height: 60px;
  z-index: 2;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
