<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import SiteHeader from "../components/SiteHeader.vue";
import { apiFetch } from "../lib/api";
import { formatPrice } from "../lib/formatPrice";

const router = useRouter();
const orders = ref([]);
const isLoading = ref(true);
const errorMessage = ref("");

const navLinks = [
  { label: "Home", route: { name: "home" } },
  { label: "Shop", route: { name: "home", hash: "#shop" } },
];

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
  <main class="orders-shell">
    <SiteHeader :nav-links="navLinks" :visible="true" />

    <section class="orders-layout">
      <div class="orders-card">
        <p class="orders-eyebrow">Account</p>
        <h1>My orders</h1>
        <p class="orders-intro">Track the status of your purchases.</p>

        <div v-if="isLoading" class="orders-state">Loading orders...</div>

        <p v-else-if="errorMessage" class="orders-error">{{ errorMessage }}</p>

        <p v-else-if="!orders.length" class="orders-state">
          You have not placed any orders yet.
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
                {{ item.name }} · {{ item.color }} / {{ item.size }} · x{{ item.quantity }}
              </li>
            </ul>

            <p class="order-total">Total: {{ formatPrice(order.total_amount) }}</p>
          </article>
        </div>
      </div>
    </section>
  </main>
</template>

<style scoped>
.orders-shell {
  min-height: 100vh;
  background: #050505;
}

.orders-layout {
  min-height: calc(100vh - 5rem);
  display: flex;
  justify-content: center;
  padding: clamp(2rem, 4vw, 4rem);
  background:
    radial-gradient(circle at 12% 14%, rgba(123, 163, 181, 0.22), transparent 24%),
    linear-gradient(180deg, #efe5d6 0%, #e8dcc8 100%);
}

.orders-card {
  width: min(760px, 100%);
  padding: clamp(1.4rem, 3vw, 2.4rem);
  border: 1px solid rgba(23, 33, 38, 0.12);
  background: rgba(255, 252, 246, 0.58);
}

.orders-eyebrow {
  margin: 0;
  color: rgba(79, 105, 115, 0.92);
  font-family: "Sora", sans-serif;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.orders-card h1 {
  margin: 0.5rem 0 0;
  color: #172126;
  font-family: "Helvetica Neue", sans-serif;
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 900;
  text-transform: uppercase;
}

.orders-intro,
.orders-state,
.orders-error,
.order-head p,
.order-lines,
.order-total {
  color: rgba(23, 33, 38, 0.76);
  font-family: "Sora", sans-serif;
}

.orders-list {
  display: grid;
  gap: 1rem;
  margin-top: 2rem;
}

.order-item {
  padding: 1rem;
  border: 1px solid rgba(23, 33, 38, 0.12);
  background: rgba(255, 255, 255, 0.45);
}

.order-head {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
}

.order-head h2 {
  margin: 0;
  font-size: 1rem;
}

.order-head p {
  margin: 0.25rem 0 0;
  font-size: 0.85rem;
}

.order-status {
  padding: 0.35rem 0.7rem;
  border-radius: 999px;
  background: rgba(23, 33, 38, 0.08);
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
}

.order-lines {
  margin: 0.85rem 0 0;
  padding-left: 1rem;
}

.order-total {
  margin: 0.75rem 0 0;
  font-weight: 700;
}
</style>
