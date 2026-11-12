<script setup>
import { computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import heroImage from "../../assets/images/atlas/hero-black-rose.png";
import bandImage from "../../assets/images/atlas/hero-silver-smoke.png";
import ProductCard from "../components/ProductCard.vue";
import SiteFooter from "../components/SiteFooter.vue";
import SiteHeader from "../components/SiteHeader.vue";
import { useProducts } from "../composables/useProducts";

const route = useRoute();
const router = useRouter();
const { products, isReady, loadProducts } = useProducts();

const searchTerm = computed(() =>
  typeof route.query.q === "string" ? route.query.q.trim() : ""
);

// The shop lists every colourway as its own tile.
const tiles = computed(() => {
  const query = searchTerm.value.toLowerCase();
  const all = products.value.flatMap((product) => {
    const colors = product.colors?.length ? product.colors : [""];
    return colors.map((color) => ({ key: `${product.slug}-${color}`, product, color }));
  });
  if (!query) return all;
  return all.filter(({ product, color }) =>
    [product.name, product.shape, product.category, color]
      .filter(Boolean)
      .some((text) => String(text).toLowerCase().includes(query))
  );
});

const clearSearch = () => router.push({ name: "home", hash: "#shop" });

onMounted(loadProducts);
</script>

<template>
  <div class="home">
    <SiteHeader />

    <main class="home__main">
      <section id="home" class="hero">
        <h1 class="hero__word" aria-label="Atlas wrap glasses">Atlas</h1>
        <img class="hero__product" :src="heroImage" alt="Atlas wrap glasses in black with rose lenses" />

        <div class="hero__bar">
          <p class="hero__line">The wrap frame, in five colours.</p>
          <div class="hero__actions">
            <router-link :to="{ name: 'product-details', params: { slug: 'atlas' } }" class="ff-btn">
              Shop Atlas
            </router-link>
            <a href="#shop" class="ff-btn ff-btn--ghost">See all colours</a>
          </div>
        </div>
      </section>

      <section id="shop" class="shop">
        <header class="shop__head">
          <h2 class="shop__title">
            <template v-if="searchTerm">Results for “{{ searchTerm }}”</template>
            <template v-else>Atlas</template>
          </h2>
          <button v-if="searchTerm" type="button" class="ff-link shop__clear" @click="clearSearch">
            Clear search
          </button>
          <p v-else-if="isReady" class="shop__meta">{{ tiles.length }} colours</p>
        </header>

        <div v-if="tiles.length" class="shop__grid">
          <ProductCard v-for="tile in tiles" :key="tile.key" :product="tile.product" :color="tile.color" />
        </div>
        <p v-else-if="isReady" class="shop__empty">No results for “{{ searchTerm }}”.</p>
      </section>

      <section class="band">
        <img class="band__product" :src="bandImage" alt="Atlas wrap glasses in silver with smoke lenses" />
        <div class="band__copy">
          <h2 class="band__title">Out all day.</h2>
          <p>Trail, road or town. Atlas sits low and close, so it stays where you put it.</p>
          <router-link
            :to="{ name: 'product-details', params: { slug: 'atlas' }, query: { color: 'Silver / Smoke' } }"
            class="ff-btn"
          >
            Shop Silver / Smoke
          </router-link>
        </div>
      </section>

      <section id="delivery" class="service" aria-label="Delivery and payment">
        <div class="service__inner">
          <article class="service__item">
            <span class="service__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <path d="M3 7.5h18v11H3zM3 10.5h18M7 15.5h3" />
                <path d="M16.5 4.5v3M14.5 6h4" />
              </svg>
            </span>
            <h2>Cash on delivery</h2>
            <p>Nothing to pay online. Pay the courier when your order arrives.</p>
          </article>
          <article class="service__item">
            <span class="service__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <path d="M3 6.5h11v11H3zM14 10h4l3 3.5v4h-7z" />
                <circle cx="7.5" cy="18.5" r="1.5" />
                <circle cx="17.5" cy="18.5" r="1.5" />
                <path d="M3 9.5h8M3 12.5h6" />
              </svg>
            </span>
            <h2>Home or pickup point</h2>
            <p>Delivered to your door or a pickup point in your wilaya. The fee shows at checkout.</p>
          </article>
          <article class="service__item">
            <span class="service__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <path d="M4 5.5h16v12H9l-5 3v-15Z" />
                <path d="M8 10h8M8 13.5h5" />
              </svg>
            </span>
            <h2>Need help?</h2>
            <p>
              Questions about your order? <router-link :to="{ name: 'feedback' }" class="ff-link">Send us a message</router-link>
            </p>
          </article>
        </div>
      </section>
    </main>

    <SiteFooter />
  </div>
</template>

<style scoped>
.home {
  background: var(--paper);
}

/* Slide the page up so the hero runs behind the floating header. */
.home__main {
  margin-top: calc((var(--header-h) + 0.75rem) * -1);
}

/* ── Hero ── */
.hero {
  position: relative;
  display: grid;
  grid-template-rows: 1fr auto;
  min-height: min(calc(100svh - 2.2rem), 58rem);
  overflow: hidden;
  background: radial-gradient(120% 90% at 50% 38%, #efefed 0%, #dededb 55%, #c9c9c5 100%);
}

.hero__word {
  position: absolute;
  top: 50%;
  left: 50%;
  margin: 0;
  transform: translate(-50%, -58%);
  color: #fff;
  font-size: clamp(7rem, 31vw, 34rem);
  font-weight: 600;
  letter-spacing: -0.07em;
  line-height: 0.8;
  white-space: nowrap;
  user-select: none;
}

.hero__product {
  position: relative;
  align-self: center;
  justify-self: center;
  width: min(74vw, 66rem);
  margin-top: clamp(4rem, 8vh, 7rem);
  animation: product-in 1s var(--ease) both;
}

@keyframes product-in {
  from {
    opacity: 0;
    transform: translateY(1.5rem) scale(0.97);
  }
}

.hero__bar {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem 2rem;
  padding: 0 var(--gutter) clamp(1.5rem, 3vw, 2.5rem);
}

.hero__line {
  font-size: clamp(1.125rem, 1.6vw, 1.5rem);
  font-weight: 500;
  letter-spacing: -0.025em;
}

.hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

/* ── Shop ── */
.shop {
  padding: clamp(2.5rem, 5vw, 4.5rem) var(--gutter);
}

.shop__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.shop__title {
  font-size: clamp(1.25rem, 1.8vw, 1.625rem);
  font-weight: 600;
  letter-spacing: -0.03em;
}

.shop__meta {
  color: var(--ink-soft);
  font-size: 0.875rem;
}

.shop__clear {
  border: 0;
  background: none;
  padding: 0;
  font-size: 0.875rem;
}

.shop__grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 2rem 0.75rem;
}

.shop__empty {
  padding: 2rem 0;
  color: var(--ink-soft);
}

/* ── Feature band ── */
.band {
  display: grid;
  grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
  align-items: center;
  gap: 2rem;
  margin: 0 var(--gutter);
  padding: clamp(1.5rem, 4vw, 4rem);
  border-radius: var(--radius);
  background: linear-gradient(160deg, #e9ecee 0%, #d3d8dc 100%);
  overflow: hidden;
}

.band__product {
  width: 100%;
  transform: scale(1.12);
}

.band__title {
  font-size: clamp(2rem, 4vw, 3.5rem);
  font-weight: 600;
  letter-spacing: -0.045em;
  line-height: 1;
}

.band__copy p {
  margin: 1rem 0 1.75rem;
  max-width: 22rem;
  color: #3d3d3d;
}

/* ── Delivery and payment ── */
.service {
  background: #f5f5f3;
  padding: clamp(2.5rem, 5vw, 4.5rem) var(--gutter);
}

.service__inner {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
  max-width: var(--page);
  margin: 0 auto;
}

.service__item {
  min-height: 13rem;
  padding: clamp(1.35rem, 2.5vw, 2rem);
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: var(--radius);
  background: var(--paper);
}

.service__icon {
  display: grid;
  width: 2.75rem;
  height: 2.75rem;
  place-items: center;
  border-radius: 50%;
  background: #f1f1ef;
  color: #242424;
}

.service__icon svg {
  width: 1.35rem;
  height: 1.35rem;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 1.5;
}

.service h2 {
  margin-top: 1.25rem;
  font-size: 0.9375rem;
  font-weight: 600;
}

.service p {
  margin-top: 0.35rem;
  max-width: 24rem;
  color: var(--ink-soft);
  font-size: 0.9375rem;
}

@media (max-width: 1100px) {
  .shop__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .home__main {
    margin-top: calc((var(--header-h) + 0.5rem) * -1);
  }

  .hero {
    min-height: min(calc(100svh - 2.2rem), 40rem);
  }

  .hero__word {
    top: 36%;
    font-size: 36vw;
  }

  .hero__product {
    width: 104vw;
    max-width: none;
    margin-top: 5rem;
  }

  .hero__bar {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
  }

  .hero__actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .hero__actions .ff-btn {
    padding: 0 0.75rem;
  }

  .shop__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.5rem 0.5rem;
  }

  .band {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .service {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
  }

  .service__inner {
    grid-template-columns: 1fr;
  }

  .service__item {
    min-height: auto;
  }
}
</style>
