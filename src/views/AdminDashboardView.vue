<script setup>
import { computed, onMounted, ref } from "vue";
import { apiFetch } from "../lib/api";
import { formatPrice } from "../lib/formatPrice";

const stats = ref({
  total_revenue: 0,
  total_orders: 0,
  total_products: 0,
  total_customers: 0,
});

const recentOrders = ref([]);
const salesChart = ref([]);
const isLoading = ref(true);

onMounted(async () => {
  try {
    const res = await apiFetch("/admin/dashboard");

    if (res.ok) {
      const data = await res.json();
      stats.value = data.stats;
      recentOrders.value = data.recent_orders;
      salesChart.value = data.sales_chart;
    } else {
      console.error("Failed to fetch dashboard data:", res.statusText);
    }
  } catch (error) {
    console.error("Error fetching dashboard data:", error);
  } finally {
    isLoading.value = false;
  }
});

const statusLabels = {
  pending: "Pending",
  processing: "Processing",
  shipped: "Shipped",
  delivered: "Delivered",
  cancelled: "Cancelled",
};

const statusClassMap = {
  pending: "status-pending",
  processing: "status-processing",
  shipped: "status-shipped",
  delivered: "status-delivered",
  cancelled: "status-cancelled",
};

const maxSalesValue = computed(() =>
  Math.max(...salesChart.value.map((day) => Number(day.total) || 0), 1),
);

const completionRate = computed(() => {
  const total = Number(stats.value.total_orders) || 0;
  if (!total) return 0;

  const delivered = recentOrders.value.filter(
    (order) => order.status === "delivered",
  ).length;
  return Math.round((delivered / total) * 100);
});

const averageOrderValue = computed(() => {
  const totalOrders = Number(stats.value.total_orders) || 0;
  if (!totalOrders) return 0;
  return (Number(stats.value.total_revenue) || 0) / totalOrders;
});
</script>

<template>
  <div class="dashboard-view">
    <section class="hero-panel">
      <div class="hero-copy">
        <p class="eyebrow">Daily snapshot</p>
        <h2 class="hero-title">Management Dashboard</h2>
        <p class="hero-text">
          Track revenue, monitor fulfillment, and keep the storefront moving
          without digging through separate screens.
        </p>
      </div>

      <div class="hero-metrics">
        <div class="hero-metric">
          <span class="hero-metric-label">Average order</span>
          <strong>{{ formatPrice(averageOrderValue) }}</strong>
        </div>
        <div class="hero-metric">
          <span class="hero-metric-label">Delivered rate</span>
          <strong>{{ completionRate }}%</strong>
        </div>
      </div>
    </section>

    <section class="stats-grid">
      <article class="stat-card">
        <div class="stat-copy">
          <span class="stat-label">Revenue</span>
          <strong class="stat-value">{{ formatPrice(stats.total_revenue) }}</strong>
          <span class="stat-note">Gross revenue collected</span>
        </div>
        <div class="stat-icon revenue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </div>
      </article>

      <article class="stat-card">
        <div class="stat-copy">
          <span class="stat-label">Orders</span>
          <strong class="stat-value">{{ stats.total_orders }}</strong>
          <span class="stat-note">Placed through the store</span>
        </div>
        <div class="stat-icon orders">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
            />
          </svg>
        </div>
      </article>

      <article class="stat-card">
        <div class="stat-copy">
          <span class="stat-label">Products</span>
          <strong class="stat-value">{{ stats.total_products }}</strong>
          <span class="stat-note">Active catalog items</span>
        </div>
        <div class="stat-icon products">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
            />
          </svg>
        </div>
      </article>

      <article class="stat-card">
        <div class="stat-copy">
          <span class="stat-label">Customers</span>
          <strong class="stat-value">{{ stats.total_customers }}</strong>
          <span class="stat-note">Unique buyers recorded</span>
        </div>
        <div class="stat-icon customers">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M17 20h5v-1a4 4 0 00-5.356-3.771M9 20H4v-1a4 4 0 015.356-3.771M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
            />
          </svg>
        </div>
      </article>
    </section>

    <section class="dashboard-grid">
      <article class="panel panel-chart">
        <div class="panel-header">
          <div>
            <p class="panel-eyebrow">Trend</p>
            <h3 class="panel-title">Sales overview</h3>
          </div>
        </div>

        <div class="chart-surface">
          <div v-if="salesChart.length" class="chart">
            <div
              v-for="day in salesChart"
              :key="day.date"
              class="chart-column"
            >
              <span class="chart-value">{{ formatPrice(day.total) }}</span>
              <div class="chart-track">
                <div
                  class="chart-bar"
                  :style="{
                    height: `${(Number(day.total || 0) / maxSalesValue) * 100}%`,
                  }"
                ></div>
              </div>
              <span class="chart-label">
                {{
                  new Date(day.date).toLocaleDateString("en-US", {
                    weekday: "short",
                  })
                }}
              </span>
            </div>
          </div>

          <div v-else class="empty-state chart-empty">
            <p>No sales data available for the last 7 days.</p>
          </div>
        </div>
      </article>

      <article class="panel panel-orders">
        <div class="panel-header">
          <div>
            <p class="panel-eyebrow">Queue</p>
            <h3 class="panel-title">Recent orders</h3>
          </div>
          <router-link to="/management-portal-45/orders" class="panel-link">
            View all
          </router-link>
        </div>

        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
        </div>

        <div v-else-if="recentOrders.length === 0" class="empty-state">
          <p>No orders yet.</p>
        </div>

        <div v-else class="order-list">
          <article
            v-for="order in recentOrders"
            :key="order.id"
            class="order-row"
          >
            <div>
              <p class="order-id">#{{ order.order_number || order.id }}</p>
              <p class="order-customer">{{ order.customer_name || "Guest" }}</p>
            </div>

            <div class="order-meta">
              <strong class="order-amount">
                {{ formatPrice(order.total_amount || order.total) }}
              </strong>
              <span
                class="status-badge"
                :class="statusClassMap[order.status] || 'status-processing'"
              >
                {{ statusLabels[order.status] || order.status }}
              </span>
            </div>
          </article>
        </div>
      </article>
    </section>
  </div>
</template>

<style scoped>
.dashboard-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  color: #f3ead8;
}

.hero-panel,
.panel,
.stat-card {
  border: 1px solid rgba(255, 255, 255, 0.07);
  background: linear-gradient(180deg, rgba(28, 28, 28, 0.98), rgba(20, 20, 20, 0.98));
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.16);
}

.hero-panel {
  border-radius: 1.2rem;
  padding: 1.6rem;
  display: flex;
  justify-content: space-between;
  gap: 1.5rem;
  align-items: flex-end;
}

.eyebrow,
.panel-eyebrow,
.stat-label,
.hero-metric-label {
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.eyebrow {
  margin: 0 0 0.55rem;
  font-size: 0.76rem;
  color: #90b6c6;
  font-weight: 700;
}

.hero-title {
  margin: 0;
  font-size: 2rem;
  line-height: 1.1;
}

.hero-text {
  margin: 0.85rem 0 0;
  max-width: 44rem;
  color: #b1ab9f;
  line-height: 1.55;
}

.hero-metrics {
  display: grid;
  grid-template-columns: repeat(2, minmax(140px, 1fr));
  gap: 0.85rem;
  width: min(100%, 22rem);
}

.hero-metric {
  padding: 1rem;
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.hero-metric-label {
  display: block;
  margin-bottom: 0.4rem;
  font-size: 0.72rem;
  color: #938f87;
}

.hero-metric strong {
  font-size: 1.25rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
}

.stat-card {
  border-radius: 1rem;
  padding: 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.stat-copy {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.stat-label {
  font-size: 0.74rem;
  color: #969086;
  font-weight: 700;
}

.stat-value {
  font-size: 1.85rem;
  line-height: 1;
}

.stat-note {
  font-size: 0.82rem;
  color: #7e7a73;
}

.stat-icon {
  width: 3.25rem;
  height: 3.25rem;
  border-radius: 0.95rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.stat-icon svg {
  width: 1.4rem;
  height: 1.4rem;
}

.revenue {
  color: #7fd59e;
  background: rgba(52, 168, 83, 0.12);
}

.orders {
  color: #88b9ea;
  background: rgba(68, 128, 220, 0.12);
}

.products {
  color: #efc36f;
  background: rgba(185, 129, 31, 0.14);
}

.customers {
  color: #cc98f8;
  background: rgba(140, 73, 207, 0.14);
}

.dashboard-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(0, 0.95fr);
  gap: 1rem;
}

.panel {
  border-radius: 1.1rem;
  padding: 1.35rem;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.2rem;
}

.panel-eyebrow {
  margin: 0 0 0.2rem;
  font-size: 0.72rem;
  color: #8e8b83;
  font-weight: 700;
}

.panel-title {
  margin: 0;
  font-size: 1.18rem;
}

.panel-link {
  color: #9fc6d6;
  text-decoration: none;
  font-size: 0.88rem;
  font-weight: 600;
}

.chart-surface {
  min-height: 22rem;
}

.chart {
  height: 100%;
  min-height: 22rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(64px, 1fr));
  gap: 0.9rem;
  align-items: end;
}

.chart-column {
  display: grid;
  grid-template-rows: auto 1fr auto;
  gap: 0.75rem;
  align-items: end;
}

.chart-value,
.chart-label {
  text-align: center;
  font-size: 0.78rem;
}

.chart-value {
  color: #b6b0a5;
}

.chart-track {
  height: 17rem;
  border-radius: 999px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
  position: relative;
  display: flex;
  align-items: flex-end;
  overflow: hidden;
}

.chart-bar {
  width: 100%;
  min-height: 6px;
  border-radius: 999px;
  background: linear-gradient(180deg, #9cc7d6 0%, #6c99ac 100%);
}

.chart-label {
  color: #7b776f;
  font-weight: 600;
}

.order-list {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.order-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.95rem 1rem;
  border-radius: 0.95rem;
  background: rgba(255, 255, 255, 0.025);
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.order-id {
  margin: 0;
  font-family: ui-monospace, monospace;
  color: #9dc3d3;
  font-weight: 700;
}

.order-customer {
  margin: 0.25rem 0 0;
  color: #d7cfbf;
}

.order-meta {
  text-align: right;
}

.order-amount {
  display: block;
  margin-bottom: 0.45rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 0.72rem;
  border-radius: 999px;
  font-size: 0.74rem;
  font-weight: 700;
}

.status-pending {
  background: rgba(212, 161, 63, 0.14);
  color: #edc97f;
}

.status-processing {
  background: rgba(68, 128, 220, 0.14);
  color: #98bff0;
}

.status-shipped {
  background: rgba(137, 103, 214, 0.16);
  color: #c0a6ff;
}

.status-delivered {
  background: rgba(52, 168, 83, 0.14);
  color: #9bd8a8;
}

.status-cancelled {
  background: rgba(180, 65, 54, 0.16);
  color: #f0a39c;
}

.loading-state,
.empty-state {
  min-height: 14rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #8f8a81;
}

.chart-empty {
  min-height: 22rem;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(255, 255, 255, 0.08);
  border-top-color: #8bb9cc;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .hero-panel {
    flex-direction: column;
    align-items: stretch;
  }

  .hero-title {
    font-size: 1.55rem;
  }

  .hero-metrics,
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .stat-card {
    flex-direction: column;
    align-items: flex-start;
  }

  .chart-surface,
  .chart,
  .chart-empty {
    min-height: 14rem;
  }

  .chart-track {
    height: 10rem;
  }

  .chart {
    grid-template-columns: repeat(auto-fit, minmax(48px, 1fr));
    gap: 0.55rem;
  }

  .chart-value,
  .chart-label {
    font-size: 0.7rem;
  }

  .order-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .order-meta {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-align: left;
  }

  .order-amount {
    margin-bottom: 0;
  }

  .panel-header {
    flex-wrap: wrap;
  }
}
</style>
