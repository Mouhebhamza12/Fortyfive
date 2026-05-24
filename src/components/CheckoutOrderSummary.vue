<script setup>
import { computed } from "vue";
import { formatPrice } from "../lib/formatPrice";

const props = defineProps({
  items: { type: Array, default: () => [] },
  itemCount: { type: Number, default: 0 },
  subtotal: { type: Number, default: 0 },
  shippingFee: { type: Number, default: 0 },
  deliveryLabel: { type: String, default: "" },
  showShipping: { type: Boolean, default: false },
});

const total = computed(() => props.subtotal + (props.showShipping ? props.shippingFee : 0));
</script>

<template>
  <aside class="summary">
    <div class="summary-card">
      <h2 class="summary-title">Summary</h2>

      <div v-if="items.length" class="summary-items">
        <article v-for="item in items" :key="item.id" class="summary-item">
          <img :src="item.image" :alt="item.name" class="summary-item-image" />
          <div class="summary-item-copy">
            <p class="summary-item-name">{{ item.name }}</p>
            <p class="summary-item-meta">{{ item.color }} / {{ item.size }} · x{{ item.quantity }}</p>
          </div>
          <strong>{{ formatPrice(item.unitPrice * item.quantity) }}</strong>
        </article>
      </div>

      <p v-else class="summary-empty">Cart is empty.</p>

      <div class="summary-lines">
        <div class="summary-line">
          <span>Subtotal</span>
          <strong>{{ formatPrice(subtotal) }}</strong>
        </div>
        <div v-if="showShipping" class="summary-line">
          <span>Shipping{{ deliveryLabel ? ` (${deliveryLabel})` : "" }}</span>
          <strong>{{ formatPrice(shippingFee) }}</strong>
        </div>
        <div v-if="showShipping" class="summary-line summary-line--total">
          <span>Total</span>
          <strong>{{ formatPrice(total) }}</strong>
        </div>
      </div>

      <p class="summary-note">You pay on delivery.</p>
    </div>
  </aside>
</template>

<style scoped>
.summary-card {
  padding: 1.25rem;
  border: 1px solid rgba(23, 33, 38, 0.1);
  background: rgba(255, 252, 246, 0.55);
  position: sticky;
  top: 7rem;
}

.summary-title {
  margin: 0 0 1rem;
  color: #172126;
  font: 700 0.8rem/1 "Sora", sans-serif;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.summary-items {
  display: grid;
  gap: 0.85rem;
  margin-bottom: 1rem;
}

.summary-item {
  display: grid;
  grid-template-columns: 3.75rem 1fr auto;
  gap: 0.65rem;
  align-items: center;
  padding-bottom: 0.85rem;
  border-bottom: 1px solid rgba(23, 33, 38, 0.08);
}

.summary-item-image {
  width: 3.75rem;
  height: 3.75rem;
  object-fit: contain;
}

.summary-item-name,
.summary-line strong,
.summary-item strong {
  margin: 0;
  color: #172126;
  font-family: "Sora", sans-serif;
}

.summary-item-name {
  font-size: 0.88rem;
  font-weight: 600;
}

.summary-item-meta {
  margin: 0.15rem 0 0;
  color: rgba(23, 33, 38, 0.6);
  font: 400 0.8rem/1.4 "Sora", sans-serif;
}

.summary-lines {
  display: grid;
  gap: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(23, 33, 38, 0.08);
}

.summary-line {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: rgba(23, 33, 38, 0.72);
  font: 400 0.88rem/1.5 "Sora", sans-serif;
}

.summary-line--total {
  padding-top: 0.65rem;
  margin-top: 0.25rem;
  border-top: 1px solid rgba(23, 33, 38, 0.08);
  color: #172126;
  font-size: 0.95rem;
}

.summary-empty,
.summary-note {
  margin: 0;
  color: rgba(23, 33, 38, 0.65);
  font: 400 0.84rem/1.5 "Sora", sans-serif;
}

.summary-note {
  margin-top: 0.85rem;
}

@media (max-width: 900px) {
  .summary-card {
    position: static;
  }

  .summary-item {
    grid-template-columns: 3.25rem 1fr;
  }

  .summary-item strong {
    grid-column: 2;
  }
}
</style>
