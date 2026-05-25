<template>
  <div class="boot" role="status" aria-live="polite" aria-label="Loading">
    <img class="boot__logo" :src="logo" alt="45" width="88" height="88" />
    <div class="boot__dots" aria-hidden="true">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>
</template>

<script setup>
import logo from "../../assets/images/45svg.png";
</script>

<style scoped>
.boot {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2rem;
  background: #7ba3b5;
}

.boot__logo {
  width: 5.5rem;
  height: 5.5rem;
  object-fit: contain;
  filter: drop-shadow(0 6px 18px rgba(23, 33, 38, 0.22));
}

.boot__dots {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
}

.boot__dots span {
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 50%;
  background: #f4ead6;
  opacity: 0.45;
  animation: boot-dot 0.9s ease-in-out infinite;
}

.boot__dots span:nth-child(2) {
  animation-delay: 0.15s;
}

.boot__dots span:nth-child(3) {
  animation-delay: 0.3s;
}

@keyframes boot-dot {
  0%,
  80%,
  100% {
    opacity: 0.35;
    transform: scale(0.85);
  }
  40% {
    opacity: 1;
    transform: scale(1);
  }
}

@media (prefers-reduced-motion: reduce) {
  .boot__dots span {
    animation: none;
    opacity: 0.85;
  }
}
</style>
