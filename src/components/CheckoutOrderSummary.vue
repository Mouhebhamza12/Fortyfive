<script setup>
import { computed } from "vue";
import { formatPrice } from "../lib/formatPrice";

const props = defineProps({
  items: { type: Array, default: () => [] },
  itemCount: { type: Number, default: 0 },
  subtotal: { type: Number, default: 0 },
  shippingFee: { type: Number, default: 0 },
  showShipping: { type: Boolean, default: false },
  loadingShipping: { type: Boolean, default: false },
});

const total = computed(() => props.subtotal + (props.showShipping ? props.shippingFee : 0));

const shippingLabel = computed(() => {
  if (props.loadingShipping) return "...";
  if (props.showShipping) return formatPrice(props.shippingFee);
  return "Select wilaya";
});
</script>

<template>
  <aside class="order-sum">
    <div class="order-sum__inner">
      <h2 class="order-sum__title">Order summary</h2>
      <p v-if="itemCount" class="order-sum__count">{{ itemCount }} item<span v-if="itemCount !== 1">s</span></p>

      <ul v-if="items.length" class="order-sum__list">
        <li v-for="item in items" :key="item.id" class="order-sum__row">
          <div class="order-sum__product">
            <span class="order-sum__name">{{ item.name }}</span>
            <span class="order-sum__meta">{{ item.size }}, {{ item.color }} &times;{{ item.quantity }}</span>
          </div>
          <span class="order-sum__line-price">{{ formatPrice(item.unitPrice * item.quantity) }}</span>
        </li>
      </ul>

      <div class="order-sum__totals">
        <div class="order-sum__total-row">
          <span>Subtotal</span>
          <span>{{ formatPrice(subtotal) }}</span>
        </div>
        <div class="order-sum__total-row">
          <span>Shipping</span>
          <span :class="{ 'order-sum__muted': !showShipping && !loadingShipping }">{{ shippingLabel }}</span>
        </div>
        <div class="order-sum__total-row order-sum__total-row--final">
          <span>Total</span>
          <strong>{{ formatPrice(showShipping ? total : subtotal) }}</strong>
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.order-sum {
  min-width: 0;
  padding: clamp(1.5rem, 3vw, 2.25rem);
  background: rgba(232, 220, 200, 0.65);
  border-left: 1px solid rgba(23, 33, 38, 0.08);
}

.order-sum__inner {
  position: sticky;
  top: 6rem;
  display: flex;
  flex-direction: column;
  min-height: 100%;
}

.order-sum__title {
  margin: 0;
  color: #172126;
  font: 700 0.78rem/1 "Sora", sans-serif;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.order-sum__count {
  margin: 0.35rem 0 1rem;
  color: rgba(79, 105, 115, 0.9);
  font: 500 0.8rem/1.4 "Sora", sans-serif;
}

.order-sum__list {
  list-style: none;
  margin: 0 0 auto;
  padding: 0 0 1.25rem;
  display: grid;
  gap: 0.65rem;
  border-bottom: 1px solid rgba(23, 33, 38, 0.1);
}

.order-sum__row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.order-sum__product {
  display: flex;
  flex-direction: column;
  gap: 0.12rem;
  min-width: 0;
}

.order-sum__name {
  color: #172126;
  font: 600 0.86rem/1.35 "Sora", sans-serif;
}

.order-sum__meta {
  color: rgba(79, 105, 115, 0.95);
  font: 500 0.72rem/1.4 "Sora", sans-serif;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.order-sum__line-price {
  flex-shrink: 0;
  color: #172126;
  font: 700 0.86rem/1.35 "Sora", sans-serif;
  white-space: nowrap;
}

.order-sum__totals {
  margin-top: 1.25rem;
  display: grid;
  gap: 0.55rem;
}

.order-sum__total-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  color: rgba(23, 33, 38, 0.72);
  font: 500 0.9rem/1.5 "Sora", sans-serif;
}

.order-sum__total-row span:last-child {
  color: #172126;
  font-weight: 600;
}

.order-sum__muted {
  color: rgba(79, 105, 115, 0.9) !important;
  font-weight: 500 !important;
  font-size: 0.84rem !important;
}

.order-sum__total-row--final {
  margin-top: 0.5rem;
  padding-top: 0.85rem;
  border-top: 1px solid rgba(23, 33, 38, 0.12);
  color: #172126;
  font-size: 1.05rem;
}

.order-sum__total-row--final strong {
  font-size: 1.15rem;
  font-weight: 800;
}

@media (max-width: 900px) {
  .order-sum {
    border-left: 0;
    border-top: 1px solid rgba(23, 33, 38, 0.08);
  }

  .order-sum__inner {
    position: static;
  }
}
</style>
