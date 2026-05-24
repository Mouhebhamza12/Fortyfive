<script setup>
import { useRouter } from "vue-router";
import { useCart } from "../composables/useCart";

const router = useRouter();
const { addItem } = useCart();

defineProps({
  product: {
    type: Object,
    required: true
  }
})

const openProduct = (slug) => {
  router.push({ name: "product-details", params: { slug } });
};

const addProductToCart = (product) => {
  addItem({
    slug: product.slug,
    name: product.name,
    price: product.price,
    image: product.image,
    color: product.color ?? product.colors?.[0] ?? "White",
    size: product.sizes?.[1] ?? product.sizes?.[0] ?? "M",
  });
};
</script>

<template>
  <div class="product-card">
    <button class="product-image" type="button" @click="openProduct(product.slug)">
      <img :src="product.image" :alt="product.name" />
    </button>
    <div class="product-info">
      <div class="product-meta">
        <h3 class="product-name">{{ product.name }}</h3>
        <p class="product-price">{{ product.price }}</p>
      </div>
      <div class="product-actions">
        <button class="product-btn product-btn--primary" type="button" @click="addProductToCart(product)">Add to Cart</button>
        <button class="product-btn product-btn--secondary" type="button" @click="openProduct(product.slug)">View Product</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100%;
  gap: 0.5rem;
  transition: transform 0.3s ease;
}

.product-image {
  position: relative;
  width: 100%;
  min-height: 385px;
  background: transparent;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.15rem 0 0;
  border: 0;
  cursor: pointer;
}

.product-image img {
  width: 100%;
  height: 100%;
  max-width: 286px;
  max-height: 372px;
  object-fit: contain;
  transition: transform 0.5s ease;
}

.product-card:hover .product-image img {
  transform: scale(1.03);
}

.product-info {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  padding: 0;
  border-top: 0;
  background: transparent;
}

.product-meta {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
}

.product-name {
  font-family: "Sora", sans-serif;
  font-size: 0.95rem;
  font-weight: 700;
  color: #1a2429;
  margin: 0;
}

.product-price {
  font-family: "Sora", sans-serif;
  font-size: 0.82rem;
  font-weight: 600;
  color: #4f6973;
  margin: 0;
}

.product-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.7rem;
}

.product-btn {
  min-height: 3rem;
  border: 1px solid #20343b;
  border-radius: 0;
  background: transparent;
  color: #20343b;
  font-family: "Sora", sans-serif;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  cursor: pointer;
  transition:
    background 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease;
}

.product-btn:hover {
  transform: translateY(-1px);
}

.product-btn--primary {
  background: #20343b;
  color: #f4ead6;
}

.product-btn--secondary:hover {
  background: rgba(32, 52, 59, 0.08);
}

@media (max-width: 760px) {
  .product-image {
    min-height: 350px;
    padding: 0.1rem 0 0;
  }

  .product-image img {
    max-width: 252px;
    max-height: 332px;
  }

  .product-actions {
    grid-template-columns: 1fr;
  }
}
</style>
