<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const scrolled = ref(false)
const handleScroll = () => scrolled.value = window.scrollY > 50

onMounted(() => window.addEventListener('scroll', handleScroll))
onUnmounted(() => window.removeEventListener('scroll', handleScroll))
</script>

<template>
  <nav class="nav" :class="{ 'nav--scrolled': scrolled }">
    <div class="nav-inner">
      <router-link to="/" class="brand">STUDIO</router-link>
      <div class="links">
        <a href="#">ARCHIVE</a>
        <a href="#">SHOP</a>
      </div>
      <button class="cart">
        CART [0]
      </button>
    </div>
  </nav>
</template>

<style scoped>
.nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  padding: 1.5rem 2vw;
  mix-blend-mode: difference;
  transition: padding 0.4s ease;
}

.nav--scrolled {
  padding: 0.8rem 2vw;
}

.nav-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.brand {
  font-weight: 900;
  font-size: 1.2rem;
  letter-spacing: 0.1em;
}

.links {
  display: flex;
  gap: 2rem;
}

.links a, .cart {
  font-weight: 700;
  font-size: 0.8rem;
  letter-spacing: 0.05em;
}

@media (max-width: 768px) {
  .links { display: none; }
}
</style>
