<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import SiteFooter from "../components/SiteFooter.vue";
import SiteHeader from "../components/SiteHeader.vue";
import { apiFetch } from "../lib/api";
import { formatPrice } from "../lib/formatPrice";

const router = useRouter();
const orders = ref([]);
const isLoading = ref(true);
const errorMessage = ref("");

const statusLabels = {
  pending: "Pending",
  processing: "Processing",
  shipped: "Shipped",
  delivered: "Delivered",
  cancelled: "Cancelled",
};

onMounted(async () => {
  try {
    const response = await apiFetch("/orders");

    if (response.status === 401) {
      router.push({ name: "sign-in", query: { redirect: "/my-orders" } });
      return;
    }

    if (!response.ok) {
      errorMessage.value = "Could not load your orders.";
      return;
    }

    orders.value = await response.json();
  } catch {
    errorMessage.value = "Network error while loading orders.";
  } finally {
    isLoading.value = false;
  }
});

const formatDate = (value) =>
  new Date(value).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
</script>

<template>
  <div class="ff-page">
    <SiteHeader />

    <main class="ff-page__main">
      <div class="ff-narrow">
        <h1 class="ff-page-title">My orders</h1>
        <p class="ff-page-intro">Every order you have placed, newest first.</p>

        <div v-if="isLoading" class="orders-state">Loading your orders…</div>

        <p v-else-if="errorMessage" class="orders-error">{{ errorMessage }}</p>

        <p v-else-if="!orders.length" class="orders-state">
          You have not placed an order yet.
          <router-link :to="{ name: 'home', hash: '#shop' }" class="ff-link">Shop now</router-link>
        </p>

        <div v-else class="orders-list">
          <article v-for="order in orders" :key="order.id" class="order-item">
            <div class="order-head">
              <div>
                <h2>{{ order.order_number }}</h2>
                <p>{{ formatDate(order.created_at) }}</p>
              </div>
              <span class="order-status">{{ statusLabels[order.status] || order.status }}</span>
            </div>

            <ul class="order-lines">
              <li v-for="(item, index) in order.items" :key="index">
                {{ item.quantity }} × {{ item.name }}, {{ item.color }}<template v-if="item.size && item.size !== 'One size'">, {{ item.size }}</template>
              </li>
            </ul>

            <p class="order-total">Total {{ formatPrice(order.total_amount) }}</p>
          </article>
        </div>
      </div>
    </main>

    <SiteFooter />
  </div>
</template>

<style scoped>
.orders-state,
.orders-error {
  margin-top: 2rem;
  color: var(--ink-soft);
}

.orders-error {
  color: var(--alert);
}

.orders-list {
  margin-top: 2.25rem;
  border-top: 1px solid var(--line-strong);
}

.order-item {
  padding: 1.5rem 0;
  border-bottom: 1px solid var(--line);
}

.order-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.order-head h2 {
  font-size: 1.0625rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.order-head p {
  color: var(--ink-soft);
  font-size: 0.875rem;
}

.order-status {
  padding: 0.25rem 0.6rem;
  background: var(--mist);
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
}

.order-lines {
  list-style: none;
  margin: 1rem 0 0;
  padding: 0;
  display: grid;
  gap: 0.25rem;
  color: var(--ink-soft);
}

.order-total {
  margin-top: 1rem;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}
</style>
