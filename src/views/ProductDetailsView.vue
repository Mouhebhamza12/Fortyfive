<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import SiteHeader from "../components/SiteHeader.vue";
import { useCart } from "../composables/useCart";
import { getProductBySlug } from "../data/products";

const route = useRoute();
const router = useRouter();
const { addItem } = useCart();

const product = computed(() => getProductBySlug(route.params.slug));
const selectedSize = ref(product.value?.sizes?.[1] ?? product.value?.sizes?.[0] ?? "");
const imageViewerOpen = ref(false);
const activeImageIndex = ref(0);
const selectedColor = ref(product.value?.color ?? "White");
const canHover = ref(false);
const addToCartFeedback = ref(false);
let addToCartFeedbackTimer = null;
const zoomActive = ref(false);
const zoomStyle = ref({
  transformOrigin: "50% 50%",
  transform: "scale(1)",
});

const productImages = computed(() => {
  if (!product.value) {
    return [];
  }

  if (Array.isArray(product.value.images) && product.value.images.length) {
    return product.value.images;
  }

  if (product.value.images?.[selectedColor.value]?.length) {
    return product.value.images[selectedColor.value];
  }

  return [{ src: product.value.image, alt: product.value.name, label: "Image" }];
});

const productColors = computed(() => product.value?.colors ?? [product.value?.color ?? "White"]);

const activeImage = computed(
  () => productImages.value[activeImageIndex.value] ?? productImages.value[0] ?? null
);

const navLinks = [
  { label: "Home", route: { name: "home" } },
  { label: "Shop", route: { name: "home", hash: "#shop" } },
];

const goHome = () => {
  router.push({ name: "home" });
};

const showAddToCartFeedback = () => {
  addToCartFeedback.value = true;
  if (addToCartFeedbackTimer) {
    clearTimeout(addToCartFeedbackTimer);
  }
  addToCartFeedbackTimer = setTimeout(() => {
    addToCartFeedback.value = false;
    addToCartFeedbackTimer = null;
  }, 2500);
};

const addCurrentProductToCart = (options = {}) => {
  if (!product.value || !activeImage.value) {
    return;
  }

  addItem({
    slug: product.value.slug,
    name: product.value.name,
    price: product.value.price,
    image: activeImage.value.src,
    color: selectedColor.value,
    size: selectedSize.value,
    openDrawer: options.openDrawer !== false,
  });

  if (options.openDrawer !== false) {
    showAddToCartFeedback();
  }
};

const goToCheckout = () => {
  addCurrentProductToCart({ openDrawer: false });
  router.push({ name: "checkout" });
};

const openImageViewer = () => {
  if (canHover.value) {
    return;
  }

  imageViewerOpen.value = true;
};

const closeImageViewer = () => {
  imageViewerOpen.value = false;
};

const handleZoomMove = (event) => {
  if (!canHover.value) {
    return;
  }

  const frame = event.currentTarget;
  const rect = frame.getBoundingClientRect();
  const x = ((event.clientX - rect.left) / rect.width) * 100;
  const y = ((event.clientY - rect.top) / rect.height) * 100;

  zoomActive.value = true;
  zoomStyle.value = {
    transformOrigin: `${x}% ${y}%`,
    transform: "scale(1.9)",
  };
};

const resetZoom = () => {
  zoomActive.value = false;
  zoomStyle.value = {
    transformOrigin: "50% 50%",
    transform: "scale(1)",
  };
};

let hoverQuery;
const syncInteractionMode = () => {
  canHover.value = Boolean(
    window.matchMedia("(hover: hover) and (pointer: fine)").matches
  );

  if (!canHover.value) {
    resetZoom();
  }
};

onMounted(() => {
  hoverQuery = window.matchMedia("(hover: hover) and (pointer: fine)");
  syncInteractionMode();
  hoverQuery.addEventListener?.("change", syncInteractionMode);
});

onUnmounted(() => {
  hoverQuery?.removeEventListener?.("change", syncInteractionMode);
  if (addToCartFeedbackTimer) {
    clearTimeout(addToCartFeedbackTimer);
  }
});

watch(
  () => [route.params.slug, selectedSize.value, selectedColor.value],
  () => {
    addToCartFeedback.value = false;
    if (addToCartFeedbackTimer) {
      clearTimeout(addToCartFeedbackTimer);
      addToCartFeedbackTimer = null;
    }
  }
);

watch(
  () => route.params.slug,
  () => {
    activeImageIndex.value = 0;
    selectedColor.value = product.value?.color ?? "White";
    selectedSize.value = product.value?.sizes?.[1] ?? product.value?.sizes?.[0] ?? "";
    closeImageViewer();
    resetZoom();
  }
);

watch(selectedColor, () => {
  activeImageIndex.value = 0;
  closeImageViewer();
  resetZoom();
});

watch(imageViewerOpen, (isOpen) => {
  document.body.style.overflow = isOpen ? "hidden" : "";
});
</script>

<template>
  <main v-if="product" class="details-shell">
    <SiteHeader :nav-links="navLinks" :visible="true" />

    <section class="details-layout">
      <div class="details-gallery">
        <button
          type="button"
          class="details-image-frame"
          aria-label="Open product image"
          :class="{ 'details-image-frame--touch': !canHover }"
          @click="openImageViewer"
          @mousemove="handleZoomMove"
          @mouseleave="resetZoom"
        >
          <img
            :src="activeImage?.src"
            :alt="activeImage?.alt ?? product.name"
            class="details-image"
            :class="{ 'details-image--zoomed': zoomActive }"
            :style="zoomStyle"
          />
        </button>

        <div v-if="productImages.length > 1" class="details-image-switcher">
          <button
            v-for="(image, index) in productImages"
            :key="`${image.label}-${index}`"
            type="button"
            class="details-image-tab"
            :class="{ 'details-image-tab--active': activeImageIndex === index }"
            @click="activeImageIndex = index"
          >
            {{ image.label }}
          </button>
        </div>
      </div>

      <div class="details-copy">
        <p class="details-eyebrow">Available now</p>
        <h1>{{ product.name }}</h1>
        <div class="details-meta-row">
          <p class="details-price">{{ product.price }}</p>
          <p class="details-fit">{{ product.fit }}</p>
        </div>

        <p class="details-description">
          {{ product.description }}
        </p>

        <dl class="details-facts">
          <div class="details-fact">
            <dt>Color</dt>
            <dd class="details-color-options">
              <button
                v-for="color in productColors"
                :key="color"
                type="button"
                class="details-color-btn"
                :class="{ 'details-color-btn--active': selectedColor === color }"
                @click="selectedColor = color"
              >
                {{ color }}
              </button>
            </dd>
          </div>
          <div class="details-fact">
            <dt>Fabric</dt>
            <dd>{{ product.material }}</dd>
          </div>
        </dl>

        <div class="details-sizes">
          <span class="details-sizes-label">Select size</span>
          <div class="details-sizes-grid">
            <button
              v-for="size in product.sizes"
              :key="size"
              type="button"
              class="details-size-btn"
              :class="{ 'details-size-btn--active': selectedSize === size }"
              @click="selectedSize = size"
            >
              {{ size }}
            </button>
          </div>
        </div>

        <div class="details-actions">
          <button
            type="button"
            class="details-action details-action--primary"
            :class="{ 'details-action--added': addToCartFeedback }"
            @click="addCurrentProductToCart"
          >
            <template v-if="addToCartFeedback">
              <svg class="details-action-check" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M5 12.5 9.5 17 19 7" />
              </svg>
              Added to cart
            </template>
            <template v-else>Add to Cart</template>
          </button>
          <button type="button" class="details-action details-action--secondary" @click="goToCheckout">
            Checkout
          </button>
        </div>

        <p class="details-checkout-note">
          No payment on the website. You pay when the order arrives.
        </p>

        <div class="details-notes">
          <h2>Product notes</h2>
          <ul>
            <li v-for="detail in product.details" :key="detail">
              {{ detail }}
            </li>
          </ul>
        </div>
      </div>
    </section>

    <teleport to="body">
      <div
        v-if="imageViewerOpen"
        class="details-viewer"
        role="dialog"
        aria-modal="true"
        aria-label="Product image viewer"
        @click.self="closeImageViewer"
      >
        <button
          type="button"
          class="details-viewer-close"
          aria-label="Close image viewer"
          @click="closeImageViewer"
        >
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 6 18 18" />
            <path d="M18 6 6 18" />
          </svg>
        </button>
        <div class="details-viewer-scroll">
          <img
            :src="activeImage?.src"
            :alt="activeImage?.alt ?? product.name"
            class="details-viewer-image"
          />
        </div>
      </div>
    </teleport>
  </main>

  <main v-else class="details-shell details-shell--empty">
    <div class="details-missing">
      <p>Product not found.</p>
      <button type="button" class="details-action details-action--primary" @click="goHome">
        Back Home
      </button>
    </div>
  </main>
</template>

<style scoped>
.details-shell {
  background: #050505;
  color: #fff;
  min-height: 100vh;
  width: 100%;
  overflow-x: hidden;
  display: flex;
  flex-direction: column;
  font-family: "Cormorant Garamond", Georgia, serif;
}

.details-layout {
  display: grid;
  grid-template-columns: minmax(320px, 1.05fr) minmax(320px, 0.95fr);
  gap: clamp(2rem, 4vw, 4rem);
  width: 100%;
  max-width: 100%;
  margin: 0;
  align-self: stretch;
  box-sizing: border-box;
  padding: clamp(3rem, 6vw, 6rem) clamp(1.25rem, 7vw, 16rem);
  background:
    radial-gradient(circle at 12% 14%, rgba(123, 163, 181, 0.22), transparent 24%),
    radial-gradient(circle at 88% 86%, rgba(123, 163, 181, 0.1), transparent 28%),
    linear-gradient(180deg, #efe5d6 0%, #e8dcc8 100%);
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  align-items: start;
  min-height: auto;
}

.details-gallery {
  display: flex;
  align-items: center;
  justify-content: center;
}

.details-image-frame {
  position: relative;
  width: min(100%, 660px);
  aspect-ratio: 4 / 5;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  overflow: hidden;
  background: transparent;
  border: 0;
  box-shadow: none;
  cursor: zoom-in;
}

.details-image-frame--touch {
  cursor: pointer;
}

.details-image {
  width: 100%;
  max-width: 560px;
  max-height: 100%;
  object-fit: contain;
  transition:
    transform 0.32s cubic-bezier(0.22, 1, 0.36, 1),
    filter 0.32s ease;
  will-change: transform;
}

.details-image--zoomed {
  filter: saturate(1.02) contrast(1.01);
}

.details-image-switcher {
  display: inline-grid;
  grid-template-columns: repeat(2, minmax(0, auto));
  gap: 0.65rem;
  margin-top: 1.25rem;
}

.details-image-tab {
  min-height: 2.7rem;
  min-width: 6rem;
  padding: 0.7rem 1rem;
  border: 1px solid rgba(23, 33, 38, 0.16);
  background: rgba(255, 255, 255, 0.28);
  color: #172126;
  font-family: "Helvetica Neue", sans-serif;
  font-size: 0.66rem;
  font-weight: 800;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
  transition:
    background 0.2s ease,
    color 0.2s ease,
    border-color 0.2s ease;
}

.details-image-tab--active,
.details-image-tab:hover {
  background: #7ba3b5;
  border-color: #7ba3b5;
  color: #f7efe3;
}

.details-copy {
  position: relative;
  max-width: 36rem;
  padding: clamp(1.4rem, 2.4vw, 2rem);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0.14)),
    linear-gradient(180deg, rgba(244, 234, 214, 0.64), rgba(232, 220, 200, 0.82));
  border: 1px solid rgba(23, 33, 38, 0.12);
  box-shadow: 0 24px 50px rgba(39, 52, 59, 0.08);
  font-family: "Sora", sans-serif;
}

.details-eyebrow {
  margin: 0 0 1rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: rgba(79, 105, 115, 0.9);
}

.details-copy h1 {
  margin: 0;
  font-family: "Helvetica Neue", sans-serif;
  font-size: clamp(2.8rem, 5vw, 4.8rem);
  font-weight: 900;
  line-height: 0.9;
  letter-spacing: -0.07em;
  text-transform: uppercase;
  color: #172126;
  max-width: 10ch;
}

.details-meta-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  margin: 1.2rem 0 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(23, 33, 38, 0.12);
}

.details-price {
  margin: 0;
  font-size: 1.18rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #172126;
}

.details-fit {
  margin: 0;
  font-size: 0.8rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: rgba(79, 105, 115, 0.9);
}

.details-description {
  margin: 0 0 2rem;
  max-width: 34rem;
  font-size: 0.98rem;
  line-height: 1.9;
  color: rgba(23, 33, 38, 0.74);
}

.details-facts {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin: 0 0 2rem;
}

.details-fact {
  padding: 1rem 0 0.15rem;
  border-top: 1px solid rgba(23, 33, 38, 0.12);
}

.details-fact dt {
  margin-bottom: 0.45rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: rgba(79, 105, 115, 0.9);
}

.details-fact dd {
  margin: 0;
  font-size: 0.95rem;
  color: #172126;
}

.details-color-options {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.details-color-btn {
  min-height: 2.4rem;
  padding: 0.55rem 0.95rem;
  border: 1px solid rgba(23, 33, 38, 0.16);
  background: rgba(255, 255, 255, 0.28);
  color: #172126;
  font-family: "Helvetica Neue", sans-serif;
  font-size: 0.64rem;
  font-weight: 800;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
  transition:
    background 0.2s ease,
    color 0.2s ease,
    border-color 0.2s ease;
}

.details-color-btn--active,
.details-color-btn:hover {
  background: #172126;
  border-color: #172126;
  color: #f4ead6;
}

.details-sizes {
  margin-bottom: 2rem;
}

.details-sizes-label {
  display: block;
  margin-bottom: 0.85rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: rgba(79, 105, 115, 0.9);
}

.details-sizes-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.7rem;
}

.details-size-btn,
.details-action {
  min-height: 3.3rem;
  border-radius: 0;
  font-family: inherit;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
  transition:
    background 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease,
    border-color 0.2s ease;
}

.details-size-btn {
  border: 1px solid rgba(23, 33, 38, 0.18);
  background: rgba(255, 255, 255, 0.34);
  color: #172126;
}

.details-size-btn--active,
.details-size-btn:hover {
  background: #7ba3b5;
  color: #f7efe3;
  border-color: #7ba3b5;
}

.details-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.8rem;
  margin-bottom: 2rem;
}

.details-action {
  border: 1px solid #172126;
}

.details-action--primary {
  background: #172126;
  color: #f4eee6;
  box-shadow: 0 0.45rem 0 rgba(123, 163, 181, 0.72);
}

.details-action--added {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
}

.details-action-check {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 2.5;
}

.details-action--secondary {
  background: rgba(255, 255, 255, 0.28);
  color: #172126;
}

.details-action:hover {
  transform: translateY(-1px);
}

.details-action--primary:hover {
  background: #7ba3b5;
  border-color: #7ba3b5;
  color: #fff;
}

.details-action--primary:active {
  transform: translateY(2px);
  box-shadow: 0 0.18rem 0 rgba(123, 163, 181, 0.72);
}

.details-checkout-note {
  margin: -0.8rem 0 1.6rem;
  color: rgba(79, 105, 115, 0.92);
  font-family: "Sora", sans-serif;
  font-size: 0.78rem;
  line-height: 1.6;
}

.details-notes {
  padding-top: 1.6rem;
  border-top: 1px solid rgba(23, 33, 38, 0.12);
}

.details-notes h2 {
  margin: 0 0 1rem;
  font-size: 0.82rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.details-notes ul {
  margin: 0;
  padding-left: 0;
  list-style: none;
}

.details-notes li {
  position: relative;
  margin-bottom: 0.8rem;
  padding-left: 1.2rem;
  line-height: 1.7;
  color: rgba(23, 33, 38, 0.78);
}

.details-notes li::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0.72rem;
  width: 0.48rem;
  height: 0.48rem;
  border-radius: 50%;
  background: #7ba3b5;
}

.details-viewer {
  position: fixed;
  inset: 0;
  z-index: 300;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(5, 5, 5, 0.82);
  backdrop-filter: blur(10px);
}

.details-viewer-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 3rem;
  height: 3rem;
  border: 0;
  background: transparent;
  color: #f4ead6;
  cursor: pointer;
  display: inline-grid;
  place-items: center;
}

.details-viewer-close svg {
  width: 1.1rem;
  height: 1.1rem;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 2;
}

.details-viewer-scroll {
  max-width: 100%;
  max-height: 100%;
  overflow: auto;
  -webkit-overflow-scrolling: touch;
  touch-action: pan-x pan-y pinch-zoom;
}

.details-viewer-image {
  display: block;
  width: min(92vw, 900px);
  height: auto;
  object-fit: contain;
  margin: 0 auto;
}

.details-shell--empty {
  display: grid;
  place-items: center;
}

.details-missing {
  text-align: center;
}

@media (max-width: 960px) {
  .details-layout {
    grid-template-columns: 1fr;
    padding: 3rem 1rem;
  }

  .details-copy {
    max-width: none;
    padding-top: 0;
  }

  .details-image-frame {
    aspect-ratio: 4 / 4.6;
  }

  .details-image-switcher {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 640px) {
  .details-layout {
    gap: 1.25rem;
    padding: 1rem 0.7rem 2rem;
  }

  .details-copy {
    padding: 1.1rem 0.95rem 1.35rem;
  }

  .details-image-frame {
    padding: 0;
    aspect-ratio: auto;
  }

  .details-image {
    max-width: min(84vw, 320px);
    max-height: 360px;
  }

  .details-copy h1 {
    max-width: none;
    font-size: clamp(2.05rem, 10.2vw, 2.75rem);
    line-height: 0.92;
    letter-spacing: -0.06em;
  }

  .details-eyebrow {
    margin-bottom: 0.7rem;
    font-size: 0.65rem;
    letter-spacing: 0.18em;
  }

  .details-meta-row {
    margin: 0.95rem 0 1rem;
    padding-bottom: 0.8rem;
    align-items: center;
    gap: 0.75rem;
  }

  .details-price {
    font-size: 0.95rem;
    letter-spacing: 0.05em;
  }

  .details-fit {
    font-size: 0.68rem;
    letter-spacing: 0.14em;
  }

  .details-description {
    margin-bottom: 1.4rem;
    font-size: 0.84rem;
    line-height: 1.7;
  }

  .details-facts {
    gap: 0.75rem;
    margin-bottom: 1.35rem;
  }

  .details-fact {
    padding-top: 0.8rem;
  }

  .details-fact dt,
  .details-sizes-label,
  .details-notes h2 {
    font-size: 0.66rem;
    letter-spacing: 0.16em;
  }

  .details-fact dd {
    font-size: 0.9rem;
  }

  .details-color-options {
    gap: 0.45rem;
  }

  .details-color-btn {
    min-height: 2.1rem;
    padding: 0.45rem 0.8rem;
    font-size: 0.58rem;
  }

  .details-sizes {
    margin-bottom: 1.35rem;
  }

  .details-image-switcher {
    grid-template-columns: 1fr 1fr;
    margin-top: 0.8rem;
  }

  .details-image-tab {
    min-width: 0;
    min-height: 2.3rem;
    padding: 0.55rem 0.7rem;
    font-size: 0.58rem;
  }

  .details-facts,
  .details-actions {
    grid-template-columns: 1fr;
  }

  .details-sizes-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.55rem;
  }

  .details-size-btn,
  .details-action {
    min-height: 3rem;
    font-size: 0.64rem;
    letter-spacing: 0.14em;
  }

  .details-actions {
    gap: 0.65rem;
    margin-bottom: 1.4rem;
  }

  .details-action--primary {
    box-shadow: 0 0.3rem 0 rgba(123, 163, 181, 0.72);
  }

  .details-notes {
    padding-top: 1.15rem;
  }

  .details-checkout-note {
    margin: -0.45rem 0 1.15rem;
    font-size: 0.72rem;
  }

  .details-notes li {
    margin-bottom: 0.7rem;
    padding-left: 1rem;
    font-size: 0.84rem;
    line-height: 1.55;
  }

  .details-notes li::before {
    top: 0.6rem;
    width: 0.42rem;
    height: 0.42rem;
  }

  .details-viewer {
    padding: 0.75rem;
  }

  .details-viewer-close {
    top: 0.6rem;
    right: 0.6rem;
    width: 2.4rem;
    height: 2.4rem;
  }

  .details-viewer-image {
    width: 100%;
  }
}
</style>
