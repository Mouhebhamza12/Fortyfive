<script setup>
defineProps({
  product: {
    type: Object,
    required: true
  }
})
</script>

<template>
  <div class="product-card">
    <div class="product-image">
      <img :src="product.image" :alt="product.name" />
      <button class="add-to-cart">Add to Cart</button>
    </div>
    <div class="product-info">
      <h3 class="product-name">{{ product.name }}</h3>
      <p class="product-price">${{ product.price }}</p>
    </div>
  </div>
</template>

<style scoped>
.product-card {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  transition: transform 0.3s ease;
}

.product-image {
  position: relative;
  aspect-ratio: 3/4;
  background: var(--bg-secondary);
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.product-card:hover .product-image img {
  transform: scale(1.05);
}

.add-to-cart {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--accent);
  color: white;
  padding: 12px;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  transform: translateY(100%);
  transition: transform 0.3s ease;
}

.product-card:hover .add-to-cart {
  transform: translateY(0);
}

.product-name {
  font-size: 0.95rem;
  font-weight: 600;
  margin: 0;
}

.product-price {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin: 0;
}
</style>
