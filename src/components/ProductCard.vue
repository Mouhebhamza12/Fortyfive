<script setup>
import { computed } from "vue";

// One tile per colourway: pass `color` to show a specific one.
const props = defineProps({
  product: { type: Object, required: true },
  color: { type: String, default: "" },
});

const shownColor = computed(() => props.color || props.product.color || "");

const image = computed(() => {
  const images = props.product.images;
  if (images && !Array.isArray(images) && images[shownColor.value]?.[0]?.src) {
    return images[shownColor.value][0].src;
  }
  return props.product.image;
});

const link = computed(() => ({
  name: "product-details",
  params: { slug: props.product.slug },
  query: props.color ? { color: props.color } : undefined,
}));
</script>

<template>
  <router-link :to="link" class="tile">
    <span class="tile__media">
      <img :src="image" :alt="`${product.name} ${shownColor}`.trim()" loading="lazy" />
      <span v-if="product.in_stock === false" class="tile__flag">Sold out</span>
    </span>
    <span class="tile__info">
      <span class="tile__name">{{ product.name }}</span>
      <span class="tile__price">{{ product.price }}</span>
      <span v-if="shownColor" class="tile__color">{{ shownColor }}</span>
    </span>
  </router-link>
</template>

<style scoped>
.tile {
  display: block;
  min-width: 0;
  color: var(--ink);
  text-decoration: none;
}

.tile__media {
  position: relative;
  display: block;
  aspect-ratio: 1;
  border-radius: var(--radius);
  background: var(--mist);
  overflow: hidden;
}

.tile__media img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transform: scale(1.12);
  transition: transform 0.5s var(--ease);
}

.tile:hover .tile__media img {
  transform: scale(1.2);
}

.tile__flag {
  position: absolute;
  top: 0.6rem;
  left: 0.6rem;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  background: var(--paper);
  font-size: 0.75rem;
  font-weight: 500;
}

.tile__info {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0 0.75rem;
  padding: 0.7rem 0.1rem 0;
  font-size: 0.875rem;
  line-height: 1.4;
}

.tile__name {
  font-weight: 600;
}

.tile__price {
  font-variant-numeric: tabular-nums;
}

.tile__color {
  grid-column: 1 / -1;
  color: var(--ink-soft);
}
</style>
