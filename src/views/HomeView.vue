<script setup>
import heroImage from "../../assets/images/bg (2).png";
import studioMark from "../../assets/images/45bw.png";
import tshirtImage from "../../assets/images/kanye-college-dropout-beige-tee.png";
import ProductCard from "../components/ProductCard.vue";
import { ref, onMounted } from "vue";

const products = ref([
  { id: 1, name: "College Dropout Tee", price: 45, image: tshirtImage },
  { id: 2, name: "College Dropout Tee", price: 45, image: tshirtImage },
  { id: 3, name: "College Dropout Tee", price: 45, image: tshirtImage },
]);

const heroReady = ref(false);
const navReady = ref(false);
const productsReady = ref(false);
const mobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = () => {
  mobileMenuOpen.value = false;
};

onMounted(() => {
  setTimeout(() => (navReady.value = true), 100);
  setTimeout(() => (heroReady.value = true), 300);
  setTimeout(() => (productsReady.value = true), 600);
});
</script>

<template>
  <main class="site-shell">
    <header
      class="topbar"
      :class="{
        'topbar--visible': navReady,
        'topbar--menu-open': mobileMenuOpen,
      }"
    >
      <a href="#" class="brand" aria-label="Studio home">
        <img :src="studioMark" alt="" width="44" height="44" />
      </a>

      <button
        class="mobile-menu-toggle"
        type="button"
        :aria-expanded="mobileMenuOpen"
        aria-controls="mobile-menu"
        aria-label="Toggle navigation"
        @click="toggleMobileMenu"
      >
        <span></span>
        <span></span>
        <span></span>
      </button>

      <nav class="primary-nav" aria-label="Primary navigation">
        <a href="#" class="nav-link">Home</a>
        <a href="#" class="nav-link">Shop</a>
      </nav>

      <div class="nav-tools" aria-label="Account and cart actions">
        <button class="icon-btn" aria-label="Account">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
          </svg>
        </button>
        <button class="icon-btn" aria-label="Search">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="10.5" cy="10.5" r="6.5" />
            <path d="m16 16 5 5" />
          </svg>
        </button>
        <button class="icon-btn cart-btn" aria-label="Cart">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 8h12l-1 13H7L6 8Z" />
            <path d="M9 8a3 3 0 0 1 6 0" />
          </svg>
          <span class="cart-dot"></span>
        </button>
      </div>

      <div
        id="mobile-menu"
        class="mobile-menu"
        :class="{ 'mobile-menu--open': mobileMenuOpen }"
      >
        <nav class="mobile-menu-links" aria-label="Mobile navigation">
          <a href="#" @click="closeMobileMenu">Home</a>
          <a href="#" @click="closeMobileMenu">Shop</a>
        </nav>
        <div class="mobile-menu-actions" aria-label="Mobile account and cart actions">
          <button type="button" @click="closeMobileMenu">Account</button>
          <button type="button" @click="closeMobileMenu">Search</button>
          <button type="button" @click="closeMobileMenu">Cart [0]</button>
        </div>
      </div>
    </header>

    <section class="drop-hero">
      <img
        :src="heroImage"
        alt=""
        class="hero-photo"
        :class="{ 'hero-photo--visible': heroReady }"
      />
      <div class="hero-overlay"></div>
      <div class="hero-grain"></div>

      <div class="drop-copy" :class="{ 'drop-copy--visible': heroReady }">
        <h1>
          YE DROP<br />
          IS OUT
        </h1>
        <a href="#" class="cta-link">
          <span class="cta-text">GO SHOP</span>
        </a>
      </div>
    </section>

    <section class="body-content">
      <div class="content-container">
        <div
          class="section-header"
          :class="{ 'section-header--visible': productsReady }"
        >
          <span class="section-tag">Latest</span>
          <h2>OUR LATEST DROP</h2>
          <p class="section-sub">Limited quantities. No restocks.</p>
        </div>

        <div
          class="products-grid"
          :class="{ 'products-grid--visible': productsReady }"
        >
          <div
            v-for="(product, i) in products"
            :key="product.id"
            class="product-wrap"
            :style="{ '--delay': `${i * 120}ms` }"
          >
            <ProductCard :product="product" />
          </div>
        </div>
      </div>
    </section>

    <footer class="site-footer">
      <div class="footer-inner">
        <div class="footer-brand">
          <img
            :src="studioMark"
            alt="Studio logo"
            class="footer-logo"
            width="44"
            height="44"
          />
        </div>

        <div class="footer-links">
          <div class="footer-col">
            <span class="footer-col-title">Shop</span>
            <a href="#" class="footer-link">New Arrivals</a>
            <a href="#" class="footer-link">All Products</a>
            <a href="#" class="footer-link">Archive</a>
          </div>
          <div class="footer-col">
            <span class="footer-col-title">Info</span>
            <a href="#" class="footer-link">About</a>
            <a href="#" class="footer-link">Shipping & Returns</a>
            <a href="#" class="footer-link">Size Guide</a>
          </div>
          <div class="footer-col">
            <span class="footer-col-title">Contact</span>
            <a href="mailto:contact@yestudio.com" class="footer-link"
              >contact@yestudio.com</a
            >
            <a href="#" class="footer-link">Instagram</a>
            <a href="#" class="footer-link">Twitter / X</a>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <p class="footer-copy">
          Â© {{ new Date().getFullYear() }} YE Studio. All rights reserved.
        </p>
        <p class="footer-made">
          Made by <span class="footer-author">Mouheb</span>
        </p>
        <div class="footer-legal">
          <a href="#" class="footer-legal-link">Privacy Policy</a>
          <span class="footer-dot">Â·</span>
          <a href="#" class="footer-legal-link">Terms of Use</a>
        </div>
      </div>
    </footer>
  </main>
</template>

<style scoped>
.site-shell {
  background: #050505;
  color: #fff;
  min-height: 100vh;
  overflow-x: hidden;
  display: flex;
  flex-direction: column;
  font-family: "Cormorant Garamond", Georgia, serif;
}

.topbar {
  align-items: center;
  background: #7ba3b5;
  display: flex;
  justify-content: center;
  height: clamp(5rem, 6.6vw, 7.9rem);
  padding: 0 clamp(1.25rem, 7vw, 16rem);
  position: sticky;
  top: 0;
  z-index: 100;
  gap: clamp(2rem, 8vw, 5rem);
  opacity: 0;
  transform: translateY(-100%);
  transition:
    opacity 0.6s ease,
    transform 0.6s cubic-bezier(0.22, 1, 0.36, 1);
}

.topbar--visible {
  opacity: 1;
  transform: translateY(0);
}

.topbar::after {
  content: "";
  position: absolute;
  inset: 0;
  backdrop-filter: blur(0px);
  transition: backdrop-filter 0.3s ease;
  pointer-events: none;
}

.brand {
  display: inline-flex;
  width: fit-content;
}

.brand img {
  display: block;
  filter: invert(1) brightness(1.1);
  height: clamp(2.2rem, 3.2vw, 3.4rem);
  mix-blend-mode: screen;
  object-fit: contain;
  width: clamp(2.2rem, 3.2vw, 3.4rem);
}

.primary-nav {
  align-items: center;
  display: flex;
  gap: clamp(0.4rem, 1vw, 0.8rem);
  justify-content: center;
  flex: 1;
  min-width: 0;
}

.nav-link {
  align-items: center;
  color: #e8dcc8;
  display: inline-flex;
  font-family: "Helvetica Neue", sans-serif;
  font-size: clamp(0.72rem, 0.85vw, 0.9rem);
  font-weight: 800;
  letter-spacing: 0.18em;
  line-height: 1;
  text-decoration: none;
  text-transform: uppercase;
  white-space: nowrap;
  padding: 0.45em 1.1em;
  border-radius: 999px;
  border: 1px solid transparent;
  transition:
    background 0.28s cubic-bezier(0.22, 1, 0.36, 1),
    border-color 0.28s cubic-bezier(0.22, 1, 0.36, 1),
    color 0.22s ease,
    transform 0.22s ease;
}

.nav-link:hover {
  background: rgba(244, 234, 214, 0.14);
  border-color: rgba(244, 234, 214, 0.35);
  color: #fff;
  transform: translateY(-1px);
}

.nav-link:active {
  transform: translateY(0);
  background: rgba(244, 234, 214, 0.22);
}

.nav-tools {
  align-items: center;
  border-left: 1px solid rgba(232, 220, 200, 0.4);
  display: flex;
  gap: clamp(1rem, 1.5vw, 1.7rem);
  margin-left: auto;
  padding-left: clamp(1.2rem, 2.1vw, 2rem);
}

.mobile-menu-toggle {
  display: none;
}

.mobile-menu {
  display: none;
}

.icon-btn {
  background: transparent;
  border: 0;
  color: #e8dcc8;
  cursor: pointer;
  display: inline-grid;
  height: 1.75rem;
  padding: 0;
  place-items: center;
  position: relative;
  width: 1.75rem;
  transition:
    transform 0.2s ease,
    opacity 0.2s ease;
}

.icon-btn:hover {
  opacity: 0.7;
  transform: scale(1.1);
}

.icon-btn svg {
  fill: none;
  height: 1.55rem;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 2;
  width: 1.55rem;
}

.cart-dot {
  position: absolute;
  top: 0;
  right: 0;
  width: 7px;
  height: 7px;
  background: #f4ead6;
  border-radius: 50%;
  animation: pulse-dot 2.5s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%,
  100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.4);
    opacity: 0.6;
  }
}

.drop-hero {
  position: relative;
  min-height: 0;
  height: clamp(18rem, 56vw, calc(100vh - clamp(5rem, 6.6vw, 7.9rem)));
  display: flex;
  align-items: center;
  justify-content: flex-end;
  overflow: hidden;
  padding: 0 clamp(1.5rem, 7vw, 8rem);
}

.hero-photo {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: 24% center;
  opacity: 0;
  transform: scale(1.06);
  transition:
    opacity 1.2s ease,
    transform 1.8s cubic-bezier(0.22, 1, 0.36, 1);
}

.hero-photo--visible {
  opacity: 1;
  transform: scale(1);
  animation: hero-drift 18s ease-in-out infinite;
}

@keyframes hero-drift {
  0% {
    transform: scale(1) translateY(0);
  }
  50% {
    transform: scale(1.03) translateY(-1%);
  }
  100% {
    transform: scale(1) translateY(0);
  }
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    rgba(0, 0, 0, 0.02) 0%,
    rgba(0, 0, 0, 0.08) 35%,
    rgba(0, 0, 0, 0.58) 72%,
    rgba(0, 0, 0, 0.82) 100%
  );
}

.hero-grain {
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  background-size: 180px 180px;
  opacity: 0.4;
  pointer-events: none;
}

.drop-copy {
  position: relative;
  z-index: 2;
  width: min(34rem, 40vw);
  text-align: right;
  color: #f4ead6;
  opacity: 0;
  transform: translateX(40px);
  transition:
    opacity 1s ease 0.4s,
    transform 1s cubic-bezier(0.22, 1, 0.36, 1) 0.4s;
}

.drop-copy--visible {
  opacity: 1;
  transform: translateX(0);
}

h1 {
  margin: 0;
  font-family: "Helvetica Neue", sans-serif;
  font-size: clamp(4.6rem, 8vw, 8.4rem);
  font-weight: 900;
  line-height: 0.88;
  letter-spacing: -0.02em;
  text-transform: uppercase;
  color: #fff;
}

.cta-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.65rem;
  margin-top: 2rem;
  color: #102f36;
  text-decoration: none;
  font-family: "Helvetica Neue", sans-serif;
  font-size: 0.72rem;
  font-weight: 900;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  padding: 0.9rem 1.35rem 0.9rem 1.55rem;
  background: #f4ead6;
  border: 1px solid rgba(244, 234, 214, 0.85);
  border-radius: 0;
  box-shadow: 0 0.45rem 0 rgba(123, 163, 181, 0.72);
  position: relative;
  min-width: 11.25rem;
  transition:
    background 0.24s ease,
    box-shadow 0.24s ease,
    color 0.24s ease,
    transform 0.24s ease;
}

.cta-link:hover {
  background: #7ba3b5;
  border-color: #7ba3b5;
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 0.6rem 0 rgba(244, 234, 214, 0.72);
}

.cta-link:active {
  transform: translateY(2px);
  box-shadow: 0 0.18rem 0 rgba(123, 163, 181, 0.72);
}

.cta-text,
.cta-arrow {
  position: relative;
  z-index: 1;
}

.body-content {
  flex: 1;
  padding: clamp(3rem, 6vw, 6rem) clamp(1.25rem, 7vw, 16rem);
  background: #f5f1ed;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.content-container {
  max-width: 1200px;
  margin: 0 auto;
  text-align: center;
}

.section-header {
  margin-bottom: clamp(2.5rem, 5vw, 4.5rem);
  opacity: 0;
  transform: translateY(24px);
  transition:
    opacity 0.8s ease,
    transform 0.8s cubic-bezier(0.22, 1, 0.36, 1);
}

.section-header--visible {
  opacity: 1;
  transform: translateY(0);
}

.section-tag {
  display: inline-block;
  font-family: "Helvetica Neue", sans-serif;
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: rgba(26, 26, 26, 0.4);
  margin-bottom: 0.6rem;
}

.content-container h2 {
  font-family: "Cormorant Garamond", serif;
  font-size: clamp(2.4rem, 5vw, 4rem);
  font-weight: 700;
  letter-spacing: 0.03em;
  color: #1a1a1a;
  margin: 0 0 0.5rem;
  line-height: 1;
}

.section-sub {
  font-family: "Helvetica Neue", sans-serif;
  font-size: 0.78rem;
  letter-spacing: 0.1em;
  color: rgba(26, 26, 26, 0.45);
  text-transform: uppercase;
  margin: 0;
}

.products-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: clamp(1.5rem, 3vw, 2.5rem);
}

@media (min-width: 768px) {
  .products-grid {
    grid-template-columns: repeat(3, 1fr);
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
  }
}

.product-wrap {
  opacity: 0;
  transform: translateY(30px);
  transition:
    opacity 0.7s ease var(--delay, 0ms),
    transform 0.7s cubic-bezier(0.22, 1, 0.36, 1) var(--delay, 0ms);
  background: #fff;
  border-radius: 2px;
  overflow: hidden;
  border: 1px solid rgba(26, 26, 26, 0.08);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
  cursor: pointer;
  position: relative;
}

.product-wrap::after {
  content: "";
  position: absolute;
  inset: 0;
  border: 1px solid transparent;
  border-radius: 2px;
  transition: border-color 0.3s ease;
  pointer-events: none;
}

.products-grid--visible .product-wrap {
  opacity: 1;
  transform: translateY(0);
}

.products-grid--visible .product-wrap:hover {
  transform: translateY(-8px);
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12);
  transition:
    transform 0.35s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.35s ease;
}

.products-grid--visible .product-wrap:hover::after {
  border-color: rgba(123, 163, 181, 0.5);
}

.site-footer {
  background: #7ba3b5;
  color: #e8dcc8;
  padding: clamp(3rem, 5vw, 5rem) clamp(1.25rem, 7vw, 8rem) 0;
  font-family: "Helvetica Neue", sans-serif;
}

.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-wrap: wrap;
  gap: clamp(2.5rem, 5vw, 5rem);
  padding-bottom: clamp(2.5rem, 4vw, 4rem);
  border-bottom: 1px solid rgba(232, 220, 200, 0.2);
}

.footer-brand {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-width: 200px;
}

.footer-logo {
  display: block;
  height: clamp(2rem, 3vw, 2.8rem);
  width: clamp(2rem, 3vw, 2.8rem);
  object-fit: contain;
  filter: invert(1) brightness(1.1);
  mix-blend-mode: screen;
}

.footer-links {
  flex: 1;
  display: flex;
  flex-wrap: wrap;
  gap: clamp(1.5rem, 4vw, 4rem);
  justify-content: flex-end;
}

.footer-col {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  min-width: 120px;
}

.footer-col-title {
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.26em;
  text-transform: uppercase;
  color: rgba(232, 220, 200, 0.4);
  margin-bottom: 0.3rem;
}

.footer-link {
  color: #e8dcc8;
  text-decoration: none;
  font-size: 0.82rem;
  letter-spacing: 0.06em;
  opacity: 0.8;
  transition:
    opacity 0.2s ease,
    letter-spacing 0.25s ease;
}

.footer-link:hover {
  opacity: 1;
  letter-spacing: 0.1em;
}

.footer-bottom {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1.4rem 0 1.6rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 2rem;
}

.footer-copy {
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  color: rgba(232, 220, 200, 0.5);
  margin: 0;
}

.footer-made {
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  color: rgba(232, 220, 200, 0.5);
  margin: 0;
}

.footer-author {
  color: #e8dcc8;
  font-weight: 700;
  letter-spacing: 0.1em;
}

.footer-legal {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.footer-dot {
  color: rgba(232, 220, 200, 0.3);
  font-size: 0.75rem;
}

.footer-legal-link {
  font-size: 0.7rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(232, 220, 200, 0.5);
  text-decoration: none;
  transition: color 0.2s ease;
}

.footer-legal-link:hover {
  color: #e8dcc8;
}

@media (max-width: 1120px) {
  .topbar {
    padding: 0 1.2rem;
    gap: 1rem;
  }

  .primary-nav {
    gap: 0.4rem;
  }
}

@media (max-width: 760px) {
  .topbar {
    gap: 1rem;
    padding: 0 1rem;
    justify-content: flex-start;
    position: relative;
  }

  .brand {
    flex-shrink: 0;
  }

  .brand img {
    height: 2.2rem;
  }

  .primary-nav {
    display: none;
  }

  .nav-tools {
    border-left: 0;
    gap: 0.9rem;
    margin-left: auto;
    padding-left: 0;
  }

  .mobile-menu-toggle {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.2rem;
    width: 1.6rem;
    height: 1.6rem;
    padding: 0;
    border: 0;
    background: transparent;
    color: #e8dcc8;
    cursor: pointer;
  }

  .mobile-menu-toggle span {
    display: block;
    width: 1rem;
    height: 1.5px;
    background: currentColor;
  }

  .icon-btn {
    height: 1.5rem;
    width: 1.5rem;
  }

  .icon-btn svg {
    height: 1.3rem;
    width: 1.3rem;
  }

  .mobile-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    display: none;
    background: #7ba3b5;
    padding: 0 1rem 1rem;
    border-top: 1px solid rgba(232, 220, 200, 0.16);
    box-shadow: 0 14px 24px rgba(0, 0, 0, 0.08);
  }

  .mobile-menu--open {
    display: block;
  }

  .mobile-menu-links,
  .mobile-menu-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .mobile-menu-links {
    margin-bottom: 0.85rem;
    padding-top: 0.85rem;
  }

  .mobile-menu-links a,
  .mobile-menu-actions button {
    color: #e8dcc8;
    font-family: "Helvetica Neue", sans-serif;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: none;
    background: transparent;
    border: 0;
    padding: 0;
    text-align: left;
  }

  .drop-hero {
    justify-content: flex-end;
    align-items: flex-end;
    height: clamp(12rem, 48vw, 20rem);
    padding: 0 1.1rem 1.5rem;
  }

  .hero-photo {
    object-position: 18% center;
  }

  .hero-overlay {
    background: linear-gradient(
      180deg,
      rgba(0, 0, 0, 0.02) 0%,
      rgba(0, 0, 0, 0.55) 100%
    );
  }

  .drop-copy {
    bottom: 1rem;
    display: block;
    left: 1rem;
    opacity: 1;
    position: absolute;
    right: auto;
    text-align: left;
    width: 7.75rem;
    transform: none;
  }

  h1 {
    display: none;
  }

  .cta-link {
    display: flex;
    font-size: 0.62rem;
    justify-content: center;
    letter-spacing: 0.16em;
    margin-top: 0;
    min-width: 0;
    padding: 0.7rem 1rem;
    width: 100%;
  }

  .body-content {
    padding: 3rem 1rem;
  }

  .section-header {
    margin-bottom: 2.4rem;
  }

  .content-container h2 {
    font-size: clamp(1.2rem, 5.2vw, 1.45rem);
    line-height: 1.04;
    margin-left: auto;
    margin-right: auto;
    max-width: 18rem;
    overflow-wrap: anywhere;
  }

  .section-sub {
    font-size: 0.68rem;
    line-height: 1.5;
  }

  .footer-links {
    justify-content: flex-start;
  }

  .footer-legal {
    margin-left: 0;
    width: 100%;
  }

  .footer-bottom {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.4rem;
  }
}
</style>
