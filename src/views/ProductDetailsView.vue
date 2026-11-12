<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import ProductCard from "../components/ProductCard.vue";
import SiteFooter from "../components/SiteFooter.vue";
import SiteHeader from "../components/SiteHeader.vue";
import { useCart } from "../composables/useCart";
import { useProducts } from "../composables/useProducts";
import { swatchStyle } from "../lib/colors";

const route = useRoute();
const router = useRouter();
const { addItem } = useCart();
const { products, isReady, loadProducts, getProductBySlug } = useProducts();

const product = computed(() => getProductBySlug(route.params.slug));

const productColors = computed(() => {
  const colors = product.value?.colors ?? [];
  if (colors.length) return colors;
  return product.value?.color ? [product.value.color] : [];
});

const productSizes = computed(() => product.value?.sizes ?? []);
const hasSizeChoice = computed(() => productSizes.value.length > 1);

const startingColor = () => {
  const wanted = route.query.color;
  if (typeof wanted === "string" && productColors.value.includes(wanted)) return wanted;
  return product.value?.color || productColors.value[0] || "";
};

const selectedColor = ref(startingColor());
const selectedSize = ref(productSizes.value[0] ?? "One size");
const activeImageIndex = ref(0);
const added = ref(false);
let addedTimer = null;

const productImages = computed(() => {
  const p = product.value;
  if (!p) return [];

  // Admin-uploaded products carry a plain list of image URLs.
  if (Array.isArray(p.images) && p.images.length) {
    return p.images.map((image, index) =>
      typeof image === "string"
        ? { src: image, alt: `${p.name}, view ${index + 1}`, label: `View ${index + 1}` }
        : image
    );
  }

  if (p.images?.[selectedColor.value]?.length) return p.images[selectedColor.value];

  return p.image ? [{ src: p.image, alt: p.name, label: "Front" }] : [];
});

const activeImage = computed(
  () => productImages.value[activeImageIndex.value] ?? productImages.value[0] ?? null
);

// When every colour has its own photo, the colour picker shows the photos.
const colorImage = (color) => {
  const images = product.value?.images;
  return images && !Array.isArray(images) ? images[color]?.[0]?.src ?? null : null;
};

const inStock = computed(() => product.value?.in_stock !== false);

const related = computed(() =>
  products.value.filter((p) => p.slug !== product.value?.slug).slice(0, 4)
);

const addToCart = (options = {}) => {
  if (!product.value || !inStock.value) return;

  addItem({
    slug: product.value.slug,
    name: product.value.name,
    price: product.value.price,
    image: activeImage.value?.src ?? product.value.image,
    color: selectedColor.value,
    size: selectedSize.value,
    openDrawer: options.openDrawer !== false,
  });

  if (options.openDrawer !== false) {
    added.value = true;
    clearTimeout(addedTimer);
    addedTimer = setTimeout(() => (added.value = false), 2500);
  }
};

const buyNow = () => {
  addToCart({ openDrawer: false });
  router.push({ name: "checkout" });
};

// Desktop: follow the pointer to zoom into the photo.
const zoomStyle = ref({});
const canZoom = ref(false);

const handleZoomMove = (event) => {
  if (!canZoom.value) return;
  const rect = event.currentTarget.getBoundingClientRect();
  const x = ((event.clientX - rect.left) / rect.width) * 100;
  const y = ((event.clientY - rect.top) / rect.height) * 100;
  zoomStyle.value = { transformOrigin: `${x}% ${y}%`, transform: "scale(1.8)" };
};

const resetZoom = () => {
  zoomStyle.value = {};
};

const resetSelection = () => {
  selectedColor.value = startingColor();
  selectedSize.value = productSizes.value[0] ?? "One size";
  activeImageIndex.value = 0;
  added.value = false;
  resetZoom();
};

onMounted(async () => {
  canZoom.value = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
  await loadProducts();
  if (!selectedColor.value) resetSelection();
});

onUnmounted(() => clearTimeout(addedTimer));

watch(() => route.params.slug, resetSelection);

watch(selectedColor, () => {
  activeImageIndex.value = 0;
  resetZoom();
});

watch(
  product,
  (p) => {
    document.title = p ? `${p.name} | Fortyfive` : "Fortyfive | Eyewear";
  },
  { immediate: true }
);

onUnmounted(() => {
  document.title = "Fortyfive | Eyewear";
});
</script>

<template>
  <div class="ff-page">
    <SiteHeader />

    <main v-if="product" class="pdp">
      <nav class="pdp__crumbs" aria-label="Breadcrumb">
        <router-link :to="{ name: 'home', hash: '#shop' }" class="ff-link">
          Shop
        </router-link>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ product.name }}</span>
      </nav>

      <div class="pdp__layout">
        <section class="pdp__gallery" aria-label="Product images">
          <div
            class="pdp__frame"
            :class="{ 'pdp__frame--zoom': canZoom }"
            @mousemove="handleZoomMove"
            @mouseleave="resetZoom"
          >
            <img
              v-if="activeImage"
              :src="activeImage.src"
              :alt="activeImage.alt ?? product.name"
              :style="zoomStyle"
            />
          </div>

          <div v-if="productImages.length > 1" class="pdp__thumbs">
            <button
              v-for="(image, index) in productImages"
              :key="`${image.src}-${index}`"
              type="button"
              class="pdp__thumb"
              :class="{ 'pdp__thumb--on': activeImageIndex === index }"
              :aria-label="`Show ${image.label ?? `view ${index + 1}`}`"
              :aria-pressed="activeImageIndex === index"
              @click="activeImageIndex = index"
            >
              <img :src="image.src" alt="" />
            </button>
          </div>
        </section>

        <section class="pdp__buy">
          <h1 class="pdp__name">{{ product.name }}</h1>
          <p class="pdp__price">{{ product.price }}</p>

          <p v-if="product.description" class="pdp__desc">{{ product.description }}</p>

          <fieldset v-if="productColors.length" class="pdp__option">
            <legend>
              Colour <span>{{ selectedColor }}</span>
            </legend>
            <div class="pdp__colors">
              <label
                v-for="color in productColors"
                :key="color"
                class="pdp__color"
                :class="{ 'pdp__color--on': selectedColor === color }"
                :title="color"
              >
                <input v-model="selectedColor" type="radio" name="colour" :value="color" :aria-label="color" />
                <img v-if="colorImage(color)" :src="colorImage(color)" alt="" />
                <span v-else class="pdp__dot" :style="swatchStyle(product, color)"></span>
              </label>
            </div>
          </fieldset>

          <fieldset v-if="hasSizeChoice" class="pdp__option">
            <legend>
              Size <span>{{ selectedSize }}</span>
            </legend>
            <div class="pdp__sizes">
              <label
                v-for="size in productSizes"
                :key="size"
                class="pdp__size"
                :class="{ 'pdp__size--on': selectedSize === size }"
              >
                <input v-model="selectedSize" type="radio" name="size" :value="size" />
                {{ size }}
              </label>
            </div>
          </fieldset>

          <div class="pdp__actions">
            <button type="button" class="ff-btn ff-btn--block" :disabled="!inStock" @click="addToCart()">
              <template v-if="!inStock">Sold out</template>
              <template v-else-if="added">Added to cart</template>
              <template v-else>Add to cart</template>
            </button>
            <button v-if="inStock" type="button" class="ff-btn ff-btn--ghost ff-btn--block" @click="buyNow">
              Buy now
            </button>
          </div>

          <ul class="pdp__assurances">
            <li>Pay in cash when your order arrives</li>
            <li>Delivery to your door or a pickup point</li>
          </ul>

          <dl v-if="product.specs?.length" class="pdp__specs">
            <div v-for="spec in product.specs" :key="spec.label">
              <dt>{{ spec.label }}</dt>
              <dd>{{ spec.value }}</dd>
            </div>
          </dl>
        </section>
      </div>

      <section v-if="related.length" class="pdp__more">
        <h2 class="pdp__more-title">You may also like</h2>
        <div class="pdp__more-grid">
          <ProductCard v-for="item in related" :key="item.id" :product="item" />
        </div>
      </section>
    </main>

    <main v-else-if="isReady" class="ff-page__main">
      <h1 class="ff-page-title">We can’t find that product</h1>
      <p class="ff-page-intro">It may have sold out or the link may be wrong.</p>
      <router-link :to="{ name: 'home', hash: '#shop' }" class="ff-btn pdp__back">
        Back to the shop
      </router-link>
    </main>

    <main v-else class="ff-page__main" aria-busy="true"></main>

    <SiteFooter />
  </div>
</template>

<style scoped>
.pdp {
  flex: 1;
  width: 100%;
  max-width: var(--page);
  margin: 0 auto;
  padding: 1.25rem var(--gutter) clamp(3rem, 7vw, 6rem);
}

.pdp__crumbs {
  display: flex;
  gap: 0.6rem;
  margin-bottom: 1.25rem;
  color: var(--ink-soft);
  font-size: 0.875rem;
}

.pdp__crumbs .ff-link {
  font-weight: 400;
  color: var(--ink-soft);
}

.pdp__layout {
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(0, 1fr);
  gap: 2rem clamp(2rem, 5vw, 5rem);
  align-items: start;
}

.pdp__frame {
  aspect-ratio: 5 / 4;
  border-radius: var(--radius);
  background: var(--mist);
  overflow: hidden;
}

.pdp__frame--zoom {
  cursor: zoom-in;
}

.pdp__frame img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  scale: 1.25;
  transition: transform 0.25s ease-out;
}

.pdp__thumbs {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.pdp__thumb {
  width: 5rem;
  aspect-ratio: 1;
  padding: 0;
  border-radius: var(--radius);
  border: 1px solid transparent;
  background: var(--mist);
  overflow: hidden;
}

.pdp__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.pdp__thumb--on {
  border-color: var(--ink);
}

.pdp__buy {
  position: sticky;
  top: calc(var(--header-h) + 1.5rem);
  max-width: 30rem;
}

.pdp__name {
  font-weight: 600;
  font-size: clamp(1.75rem, 2.6vw, 2.25rem);
  letter-spacing: -0.035em;
  line-height: 1.05;
}

.pdp__price {
  margin-top: 0.4rem;
  font-size: 1.0625rem;
  font-variant-numeric: tabular-nums;
}

.pdp__desc {
  margin-top: 1.25rem;
  color: var(--ink-soft);
  line-height: 1.55;
}

.pdp__option {
  margin: 1.75rem 0 0;
  padding: 0;
  border: 0;
  min-width: 0;
}

.pdp__option legend {
  padding: 0;
  margin-bottom: 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.pdp__option legend span {
  margin-left: 0.4rem;
  color: var(--ink-soft);
  font-weight: 400;
}

.pdp__option input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.pdp__colors,
.pdp__sizes {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.pdp__color {
  position: relative;
  display: grid;
  place-items: center;
  width: 4.25rem;
  aspect-ratio: 1;
  border-radius: var(--radius);
  background: var(--mist);
  overflow: hidden;
  cursor: pointer;
  box-shadow: inset 0 0 0 1px transparent;
  transition: box-shadow 0.15s ease;
}

.pdp__color img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  scale: 1.2;
}

.pdp__dot {
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
  box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.15);
}

.pdp__color:hover {
  box-shadow: inset 0 0 0 1px var(--line-strong);
}

.pdp__color--on,
.pdp__color--on:hover {
  box-shadow: inset 0 0 0 1.5px var(--ink);
}

.pdp__size {
  position: relative;
  display: grid;
  place-items: center;
  min-width: 3.25rem;
  min-height: 2.75rem;
  padding: 0 0.875rem;
  border: 1px solid var(--line-strong);
  border-radius: var(--radius);
  cursor: pointer;
}

.pdp__size:hover {
  border-color: var(--ink);
}

.pdp__size--on {
  border-color: var(--ink);
  box-shadow: inset 0 0 0 1px var(--ink);
}

.pdp__color:focus-within,
.pdp__size:focus-within {
  outline: 2px solid var(--ink);
  outline-offset: 3px;
}

.pdp__actions {
  display: grid;
  gap: 0.6rem;
  margin-top: 2rem;
}

.pdp__assurances {
  list-style: none;
  margin: 1.25rem 0 0;
  padding: 0;
  display: grid;
  gap: 0.35rem;
  color: var(--ink-soft);
  font-size: 0.9375rem;
}

.pdp__assurances li {
  position: relative;
  padding-left: 1.5rem;
}

.pdp__assurances li::before {
  content: "";
  position: absolute;
  left: 0.15rem;
  top: 0.45em;
  width: 0.7rem;
  height: 0.35rem;
  border-left: 1.5px solid var(--ink);
  border-bottom: 1.5px solid var(--ink);
  transform: rotate(-45deg);
}

.pdp__specs {
  margin: 2.25rem 0 0;
  border-top: 1px solid var(--line-strong);
}

.pdp__specs div {
  display: grid;
  grid-template-columns: 8rem 1fr;
  gap: 1rem;
  padding: 0.8rem 0;
  border-bottom: 1px solid var(--line);
  font-size: 0.9375rem;
}

.pdp__specs dt {
  color: var(--ink-soft);
}

.pdp__specs dd {
  margin: 0;
}

.pdp__more {
  margin-top: clamp(4rem, 8vw, 7rem);
}

.pdp__more-title {
  margin-bottom: 1.5rem;
  font-weight: 600;
  font-size: 1.25rem;
  letter-spacing: -0.02em;
}

.pdp__more-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 2rem clamp(0.75rem, 1.5vw, 1.5rem);
}

.pdp__back {
  margin-top: 2rem;
}

@media (max-width: 900px) {
  .pdp__layout {
    grid-template-columns: 1fr;
  }

  .pdp__buy {
    position: static;
    max-width: none;
  }

  .pdp__more-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .pdp {
    padding-top: 1rem;
  }

  .pdp__frame {
    aspect-ratio: 1;
  }
}
</style>
