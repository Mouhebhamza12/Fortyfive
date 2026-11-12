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

const variantLabel = (item) =>
  [item.color, item.size && item.size !== "One size" ? item.size : null]
    .filter(Boolean)
    .join(", ");

const shippingLabel = computed(() => {
  if (props.loadingShipping) return "Calculating…";
  if (props.showShipping) return formatPrice(props.shippingFee);
  return "Choose a wilaya";
});
</script>

<template>
  <aside class="summary" aria-label="Order summary">
    <h2 class="summary__title">
      Your order
      <span v-if="itemCount">({{ itemCount }})</span>
    </h2>

    <ul v-if="items.length" class="summary__list">
      <li v-for="item in items" :key="item.id" class="summary__row">
        <span class="summary__thumb"><img :src="item.image" alt="" /></span>
        <span class="summary__product">
          <span class="summary__name">{{ item.name }}</span>
          <span class="summary__meta">{{ variantLabel(item) }}</span>
          <span class="summary__meta">Qty {{ item.quantity }}</span>
        </span>
        <span class="summary__price">{{ formatPrice(item.unitPrice * item.quantity) }}</span>
      </li>
    </ul>
    <p v-else class="summary__empty">Your cart is empty.</p>

    <dl class="summary__totals">
      <div>
        <dt>Subtotal</dt>
        <dd>{{ formatPrice(subtotal) }}</dd>
      </div>
      <div>
        <dt>Delivery</dt>
        <dd :class="{ 'summary__muted': !showShipping }">{{ shippingLabel }}</dd>
      </div>
      <div class="summary__total">
        <dt>Total to pay on delivery</dt>
        <dd>{{ formatPrice(showShipping ? total : subtotal) }}</dd>
      </div>
    </dl>
  </aside>
</template>

<style scoped>
.summary {
  position: sticky;
  top: calc(var(--header-h) + 1.5rem);
  padding: clamp(1.25rem, 2.5vw, 2rem);
  border-radius: var(--radius);
  background: var(--mist);
}

.summary__title {
  font-size: 1rem;
  font-weight: 600;
}

.summary__title span {
  color: var(--ink-soft);
  font-weight: 400;
}

.summary__list {
  list-style: none;
  margin: 1.25rem 0 0;
  padding: 0;
}

.summary__row {
  display: grid;
  grid-template-columns: 4.5rem 1fr auto;
  gap: 1rem;
  padding: 1rem 0;
  border-top: 1px solid var(--line);
}

.summary__thumb {
  aspect-ratio: 1;
  border-radius: 4px;
  background: var(--paper);
  overflow: hidden;
}

.summary__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.summary__product {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.summary__name {
  font-weight: 600;
}

.summary__meta,
.summary__empty,
.summary__muted {
  color: var(--ink-soft);
  font-size: 0.875rem;
}

.summary__empty {
  margin-top: 1rem;
}

.summary__price {
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.summary__totals {
  margin: 0;
  padding-top: 0.5rem;
  border-top: 1px solid var(--line-strong);
}

.summary__totals div {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.45rem 0;
}

.summary__totals dd {
  margin: 0;
  font-variant-numeric: tabular-nums;
}

.summary__totals .summary__total {
  margin-top: 0.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--line);
  font-size: 1rem;
  font-weight: 600;
}

@media (max-width: 900px) {
  .summary {
    position: static;
    grid-row: 1;
  }
}
</style>
