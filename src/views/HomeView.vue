<script setup>
import heroImage from "../../assets/images/bg (2).png";
import SiteHeader from "../components/SiteHeader.vue";
import ProductCard from "../components/ProductCard.vue";
import { onMounted, ref, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useProducts } from "../composables/useProducts";

const route = useRoute();
const router = useRouter();
const { products, loadProducts } = useProducts();

const heroReady = ref(false);
const navReady = ref(false);
const productsReady = ref(false);

const navLinks = [
  { label: "Home", target: "home" },
  { label: "Shop", target: "shop" },
];

const filteredProducts = computed(() => {
  const catalog = products.value;
  const q = route.query.q;
  if (!q) return catalog;

  const query = q.toLowerCase().trim();
  return catalog.filter(
    (p) =>
      p.name.toLowerCase().includes(query) ||
      (p.color && p.color.toLowerCase().includes(query)) ||
      (p.colors && p.colors.some((c) => c.toLowerCase().includes(query))) ||
      (p.description && p.description.toLowerCase().includes(query))
  );
});

const clearSearchFilter = () => {
  router.push({ name: "home", hash: "#shop" });
};

onMounted(async () => {
  await loadProducts();
  setTimeout(() => (navReady.value = true), 100);
  setTimeout(() => (heroReady.value = true), 300);
  setTimeout(() => (productsReady.value = true), 600);

  if (route.query.q) {
    setTimeout(() => {
      document
        .getElementById("shop")
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
    }, 800);
  }
});
</script>

<template>
  <main class="site-shell">
    <SiteHeader :nav-links="navLinks" :visible="navReady" />

    <section id="home" class="drop-hero">
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
        <p class="hero-sub">
          Built for everyday wear. Clean, direct, and easy to live in.
        </p>
        <a href="#shop" class="cta-link">
          <span class="cta-text">GO SHOP</span>
        </a>
      </div>
    </section>

    <section id="shop" class="body-content">
      <div class="content-container">
        <div
          class="section-header"
          :class="{ 'section-header--visible': productsReady }"
        >
          <h2>OUR LATEST DROP</h2>

          <div v-if="route.query.q" class="search-filter-badge">
            <span>Showing results for "{{ route.query.q }}"</span>
            <button @click="clearSearchFilter" class="clear-search-btn">
              <svg
                viewBox="0 0 24 24"
                aria-hidden="true"
                width="12"
                height="12"
                style="display: inline-block; margin-right: 2px"
              >
                <path
                  d="M6 6 18 18"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
                <path
                  d="M18 6 6 18"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
              </svg>
              Clear
            </button>
          </div>
        </div>

        <div
          class="products-grid"
          :class="{ 'products-grid--visible': productsReady }"
        >
          <template v-if="filteredProducts.length">
            <div
              v-for="(product, i) in filteredProducts"
              :key="product.id"
              class="product-wrap"
              :style="{ '--delay': `${i * 120}ms` }"
            >
              <ProductCard :product="product" />
            </div>
          </template>
          <div v-else class="no-search-results">
            <p>No products match your search query.</p>
            <button @click="clearSearchFilter" class="cta-link inline-cta">
              View All Products
            </button>
          </div>
        </div>
      </div>
    </section>

    <footer class="site-footer">
      <div class="footer-inner">
        <div class="footer-brand">
          <img
            src="../../assets/images/45svg.png"
            alt="45 logo"
            class="footer-logo"
            width="44"
            height="44"
          />
        </div>

        <div class="footer-links">
          <div class="footer-col">
            <span class="footer-col-title">Shop</span>
            <a href="#shop" class="footer-link">All Products</a>
            <router-link to="/feedback" class="footer-link">Feedback</router-link>
          </div>
          <div class="footer-col">
            <span class="footer-col-title">Info</span>
            <a href="#" class="footer-link">About</a>
            <a href="#" class="footer-link">Shipping &amp; Returns</a>
            <a href="#" class="footer-link">Size Guide</a>
          </div>
          <div class="footer-col">
            <span class="footer-col-title">Contact</span>
            <a href="mailto:contact@fortyfive.com" class="footer-link"
              >contact@fortyfive.com</a
            >
            <a href="#" class="footer-link">Instagram</a>
            <a href="#" class="footer-link">Twitter / X</a>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <p class="footer-copy">
          &copy; {{ new Date().getFullYear() }} YE. All rights reserved.
        </p>
        <p class="footer-made">
          Made by <span class="footer-author">Mouheb</span>
        </p>
        <div class="footer-legal">
          <a href="#" class="footer-legal-link">Privacy Policy</a>
          <span class="footer-dot">&middot;</span>
          <a href="#" class="footer-legal-link">Terms of Use</a>
        </div>
      </div>
    </footer>
  </main>
</template>

<style scoped>
/* ── Shell ── */
.site-shell {
  background: #050505;
  color: #fff;
  min-height: 100vh;
  overflow-x: hidden;
  display: flex;
  flex-direction: column;
  font-family: "Cormorant Garamond", Georgia, serif;
}

/* ── Hero ── */
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
  text-align: left;
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

.hero-sub {
  font-family: "Sora", sans-serif;
  margin: 1rem 0 0;
  max-width: 20rem;
  color: rgba(244, 234, 214, 0.78);
  font-size: 0.86rem;
  line-height: 1.6;
}

/* ── CTA Button ── */
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
  border-radius: 999px;
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

/* ── Body / Products Section ── */
.body-content {
  flex: 1;
  padding: clamp(3rem, 6vw, 6rem) clamp(1.25rem, 7vw, 16rem);
  background:
    radial-gradient(circle at top, rgba(123, 163, 181, 0.1), transparent 28%),
    linear-gradient(180deg, #f3ede6 0%, #ece4da 100%);
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.content-container {
  max-width: 1280px;
  margin: 0 auto;
  text-align: center;
}

.section-header {
  position: relative;
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

.content-container h2 {
  font-family: "Sora", sans-serif;
  font-size: clamp(2rem, 4.8vw, 4.2rem);
  font-weight: 800;
  letter-spacing: -0.05em;
  color: #1a2429;
  margin: 0 0 0.9rem;
  line-height: 0.92;
  text-transform: uppercase;
}

.section-sub {
  font-family: "Sora", sans-serif;
  font-size: 0.8rem;
  font-weight: 400;
  letter-spacing: 0.18em;
  color: rgba(56, 70, 77, 0.72);
  font-style: italic;
  text-transform: uppercase;
  margin: 0;
}

/* ── Search Filter Badge ── */
.search-filter-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  margin-top: 1.2rem;
  padding: 0.5rem 1rem;
  background: rgba(123, 163, 181, 0.15);
  border: 1px solid rgba(123, 163, 181, 0.3);
  border-radius: 999px;
  font-family: "Sora", sans-serif;
  font-size: 0.75rem;
  color: #1a2429;
}

.clear-search-btn {
  cursor: pointer;
  background: none;
  border: none;
  color: #7ba3b5;
  font-family: "Sora", sans-serif;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  display: inline-flex;
  align-items: center;
  padding: 0;
  transition: color 0.2s ease;
}

.clear-search-btn:hover {
  color: #102f36;
}

.no-search-results {
  text-align: center;
  padding: 3rem 1rem;
  font-family: "Sora", sans-serif;
  color: rgba(56, 70, 77, 0.72);
}

.no-search-results p {
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

.inline-cta {
  margin-top: 0;
}

/* ── Products Grid ── */
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 320px));
  justify-content: center;
  gap: clamp(1.25rem, 2vw, 2rem);
}

@media (min-width: 768px) {
  .products-grid {
    grid-template-columns: repeat(auto-fit, minmax(300px, 320px));
    max-width: 1120px;
    margin-left: auto;
    margin-right: auto;
  }
}

.product-wrap {
  width: min(100%, 320px);
  min-height: 0;
  opacity: 0;
  transform: translateY(30px);
  transition:
    opacity 0.7s ease var(--delay, 0ms),
    transform 0.7s cubic-bezier(0.22, 1, 0.36, 1) var(--delay, 0ms);
  background: transparent;
  border-radius: 0;
  overflow: visible;
  border: 0;
  box-shadow: none;
  cursor: default;
  position: relative;
}

.product-wrap::after {
  content: none;
}

.products-grid--visible .product-wrap {
  opacity: 1;
  transform: translateY(0);
}

.products-grid--visible .product-wrap:hover {
  transform: translateY(-4px);
  transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

/* ── Footer ── */
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
  font-weight: bold;
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

/* ── Mobile ── */
@media (max-width: 760px) {
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
    left: auto;
    opacity: 1;
    position: absolute;
    right: 1rem;
    text-align: right;
    width: 11rem;
    transform: none;
  }

  h1 {
    font-size: clamp(1.8rem, 8vw, 2.4rem);
    line-height: 0.95;
    margin-bottom: 0.55rem;
  }

  .hero-sub {
    font-size: 0.62rem;
    line-height: 1.45;
    margin-top: 0;
    margin-left: auto;
    max-width: 10rem;
  }

  .cta-link {
    display: flex;
    font-size: 0.62rem;
    justify-content: center;
    letter-spacing: 0.16em;
    margin-top: 0.8rem;
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
    font-size: clamp(1.5rem, 8vw, 2.2rem);
    line-height: 1;
    margin-left: auto;
    margin-right: auto;
    max-width: 18rem;
    overflow-wrap: anywhere;
  }

  .section-sub {
    font-size: 0.66rem;
    line-height: 1.5;
  }

  .products-grid {
    grid-template-columns: 1fr;
    justify-items: center;
  }

  .product-wrap {
    width: min(100%, 320px);
    min-height: 0;
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
