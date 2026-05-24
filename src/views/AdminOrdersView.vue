<script setup>
import { computed, onMounted, ref } from "vue";
import { apiFetch, authJsonHeaders } from "../lib/api";
import { formatPrice } from "../lib/formatPrice";

const orders = ref([]);
const isLoading = ref(true);
const selectedOrder = ref(null);
const showOrderModal = ref(false);
const filterStatus = ref("all");
const searchQuery = ref("");
const currentPage = ref(1);
const itemsPerPage = 10;

const statuses = [
  "all",
  "pending",
  "processing",
  "shipped",
  "delivered",
  "cancelled",
];

onMounted(async () => {
  await fetchOrders();
});

const fetchOrders = async () => {
  try {
    const res = await apiFetch("/admin/orders");
    const data = await res.json();
    orders.value = Array.isArray(data) ? data : [];
  } catch (error) {
    console.error("Error fetching orders:", error);
    orders.value = [];
  } finally {
    isLoading.value = false;
  }
};

const filteredOrders = computed(() => {
  let result = orders.value;

  if (filterStatus.value !== "all") {
    result = result.filter((order) => order.status === filterStatus.value);
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(
      (order) =>
        `#${order.id}` === query ||
        (order.customer_name?.toLowerCase() || "").includes(query) ||
        (order.order_number?.toLowerCase() || "").includes(query) ||
        (order.customer_phone?.toLowerCase() || "").includes(query),
    );
  }

  return result;
});

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredOrders.value.slice(start, start + itemsPerPage);
});

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredOrders.value.length / itemsPerPage)),
);

const openOrderDetails = async (order) => {
  try {
    const res = await apiFetch(`/admin/orders/${order.id}`);
    const data = await res.json();
    selectedOrder.value = { ...order, ...data };
    showOrderModal.value = true;
  } catch (error) {
    console.error("Error fetching order details:", error);
    selectedOrder.value = order;
    showOrderModal.value = true;
  }
};

const closeOrderModal = () => {
  showOrderModal.value = false;
  selectedOrder.value = null;
};

const updateOrderStatus = async (orderId, newStatus) => {
  try {
    const res = await apiFetch(`/admin/orders/${orderId}`, {
      method: "PUT",
      headers: authJsonHeaders,
      body: JSON.stringify({
        status: newStatus,
        payment_status: selectedOrder.value.payment_status,
      }),
    });

    if (res.ok) {
      await fetchOrders();
      if (selectedOrder.value?.id === orderId) {
        selectedOrder.value.status = newStatus;
      }
    }
  } catch (error) {
    console.error("Error updating order status:", error);
  }
};

const formatDate = (date) =>
  new Date(date).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });

const paymentStatuses = ["unpaid", "paid", "refunded"];

const statusClassMap = {
  pending: "status-pending",
  processing: "status-processing",
  shipped: "status-shipped",
  delivered: "status-delivered",
  cancelled: "status-cancelled",
};

const updatePaymentStatus = async (orderId, paymentStatus) => {
  try {
    const res = await apiFetch(`/admin/orders/${orderId}`, {
      method: "PUT",
      headers: authJsonHeaders,
      body: JSON.stringify({
        status: selectedOrder.value.status,
        payment_status: paymentStatus,
      }),
    });

    if (res.ok) {
      await fetchOrders();
      if (selectedOrder.value?.id === orderId) {
        selectedOrder.value.payment_status = paymentStatus;
      }
    }
  } catch (error) {
    console.error("Error updating payment status:", error);
  }
};

const isGuestEmail = (email) =>
  !email || email.endsWith("@orders.local");

const getStatusLabel = (status) =>
  status.charAt(0).toUpperCase() + status.slice(1);

const stats = computed(() => ({
  total: orders.value.length,
  pending: orders.value.filter((order) => order.status === "pending").length,
  processing: orders.value.filter((order) => order.status === "processing").length,
  shipped: orders.value.filter((order) => order.status === "shipped").length,
  delivered: orders.value.filter((order) => order.status === "delivered").length,
}));
</script>

<template>
  <div class="orders-view">
    <section class="page-hero">
      <div>
        <p class="eyebrow">Fulfillment</p>
        <h2 class="page-title">Orders</h2>
        <p class="page-subtitle">
          Review new purchases, keep delivery statuses up to date, and answer
          customer questions faster.
        </p>
      </div>
    </section>

    <section class="filter-strip">
      <button
        v-for="status in statuses"
        :key="status"
        type="button"
        class="filter-chip"
        :class="[statusClassMap[status], { active: filterStatus === status }]"
        @click="filterStatus = status"
      >
        <span class="chip-count">
          {{
            status === "all"
              ? stats.total
              : status === "pending"
                ? stats.pending
                : status === "processing"
                  ? stats.processing
                  : status === "shipped"
                    ? stats.shipped
                    : status === "delivered"
                      ? stats.delivered
                      : orders.filter((order) => order.status === "cancelled").length
          }}
        </span>
        <span>{{ status === "all" ? "All" : getStatusLabel(status) }}</span>
      </button>
    </section>

    <section class="panel">
      <div class="toolbar">
        <div class="search-field">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by order ID, customer name, or email"
          />
        </div>

        <div class="toolbar-note">{{ filteredOrders.length }} visible orders</div>
      </div>

      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading orders...</p>
      </div>

      <div v-else-if="filteredOrders.length === 0" class="empty-state">
        <p>No orders found.</p>
      </div>

      <template v-else>
      <div class="mobile-card-list" aria-label="Orders list">
        <article
          v-for="order in paginatedOrders"
          :key="`card-${order.id}`"
          class="order-card"
        >
          <div class="order-card-head">
            <div>
              <p class="order-id">{{ order.order_number || `#${order.id}` }}</p>
              <p class="customer-name">{{ order.customer_name || "Guest" }}</p>
              <p class="customer-email">{{ order.customer_email || "-" }}</p>
            </div>
            <span class="status-badge" :class="statusClassMap[order.status]">
              {{ getStatusLabel(order.status) }}
            </span>
          </div>

          <div class="order-card-meta">
            <span class="muted-text">{{ order.items?.length || 1 }} items</span>
            <strong class="amount-text">{{ formatPrice(order.total_amount) }}</strong>
            <span class="muted-text">{{ formatDate(order.created_at) }}</span>
          </div>

          <button type="button" class="view-btn" @click="openOrderDetails(order)">
            Open order
          </button>
        </article>
      </div>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Order</th>
              <th>Customer</th>
              <th>Items</th>
              <th>Total</th>
              <th>Status</th>
              <th>Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in paginatedOrders" :key="order.id">
              <td>
                <div class="order-id-block">
                  <p class="order-id">{{ order.order_number || `#${order.id}` }}</p>
                  <p class="order-note">Store order</p>
                </div>
              </td>
              <td>
                <div class="customer-block">
                  <p class="customer-name">{{ order.customer_name || "Guest" }}</p>
                  <p class="customer-email">{{ order.customer_email || "-" }}</p>
                </div>
              </td>
              <td class="muted-text">{{ order.items?.length || 1 }}</td>
              <td class="amount-text">{{ formatPrice(order.total_amount) }}</td>
              <td>
                <span class="status-badge" :class="statusClassMap[order.status]">
                  {{ getStatusLabel(order.status) }}
                </span>
              </td>
              <td class="muted-text">{{ formatDate(order.created_at) }}</td>
              <td>
                <button type="button" class="view-btn" @click="openOrderDetails(order)">
                  Open
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      </template>

      <div v-if="totalPages > 1" class="pagination">
        <button
          type="button"
          class="pagination-btn"
          @click="currentPage--"
          :disabled="currentPage === 1"
        >
          Previous
        </button>
        <span class="pagination-info">Page {{ currentPage }} of {{ totalPages }}</span>
        <button
          type="button"
          class="pagination-btn"
          @click="currentPage++"
          :disabled="currentPage === totalPages"
        >
          Next
        </button>
      </div>
    </section>

    <div
      v-if="showOrderModal && selectedOrder"
      class="modal-overlay"
      @click.self="closeOrderModal"
    >
      <div class="modal">
        <div class="modal-header">
          <div>
            <p class="modal-eyebrow">Order details</p>
            <h3 class="modal-title">{{ selectedOrder.order_number || `Order #${selectedOrder.id}` }}</h3>
            <p class="modal-subtitle">{{ formatDate(selectedOrder.created_at) }}</p>
          </div>

          <button type="button" class="close-btn" @click="closeOrderModal" aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <section class="status-panel">
            <p class="section-label">Payment status</p>
            <div class="status-actions">
              <button
                v-for="paymentStatus in paymentStatuses"
                :key="paymentStatus"
                type="button"
                class="status-action"
                :class="{ active: selectedOrder.payment_status === paymentStatus }"
                :disabled="selectedOrder.payment_status === paymentStatus"
                @click="updatePaymentStatus(selectedOrder.id, paymentStatus)"
              >
                {{ getStatusLabel(paymentStatus) }}
              </button>
            </div>
          </section>

          <section class="status-panel">
            <p class="section-label">Update status</p>
            <div class="status-actions">
              <button
                v-for="status in ['pending', 'processing', 'shipped', 'delivered']"
                :key="status"
                type="button"
                class="status-action"
                :class="[statusClassMap[status], { active: selectedOrder.status === status }]"
                :disabled="selectedOrder.status === status"
                @click="updateOrderStatus(selectedOrder.id, status)"
              >
                {{ getStatusLabel(status) }}
              </button>
              <button
                type="button"
                class="status-action status-cancelled"
                :disabled="selectedOrder.status === 'cancelled'"
                @click="updateOrderStatus(selectedOrder.id, 'cancelled')"
              >
                Cancel order
              </button>
            </div>
          </section>

          <section class="info-grid">
            <article class="info-card">
              <p class="section-label">Customer</p>
              <div class="info-list">
                <div class="info-row">
                  <span>Name</span>
                  <strong>{{ selectedOrder.customer_name || "Guest" }}</strong>
                </div>
                <div class="info-row">
                  <span>Email</span>
                  <strong>{{ isGuestEmail(selectedOrder.customer_email) ? "-" : selectedOrder.customer_email }}</strong>
                </div>
                <div class="info-row">
                  <span>Phone</span>
                  <strong>{{ selectedOrder.customer_phone || "-" }}</strong>
                </div>
              </div>
            </article>

            <article class="info-card">
              <p class="section-label">Shipping</p>
              <div class="info-list">
                <div class="info-row">
                  <span>Method</span>
                  <strong>{{
                    selectedOrder.delivery_type === "bureau" ? "ZR Express pickup" : "Home delivery"
                  }}</strong>
                </div>
                <div class="info-row">
                  <span>Wilaya</span>
                  <strong>{{ selectedOrder.wilaya?.name || selectedOrder.city || "-" }}</strong>
                </div>
                <div v-if="selectedOrder.commune" class="info-row">
                  <span>Commune</span>
                  <strong>{{ selectedOrder.commune }}</strong>
                </div>
                <div v-if="selectedOrder.delivery_type === 'bureau'" class="info-row">
                  <span>Stop desk</span>
                  <strong>{{ selectedOrder.bureau_name || selectedOrder.delivery_bureau?.name || "-" }}</strong>
                </div>
                <p class="address-text">{{ selectedOrder.shipping_address || "-" }}</p>
              </div>
            </article>
          </section>

          <section class="items-panel">
            <div class="section-header">
              <p class="section-label">Items</p>
            </div>

            <div class="item-list">
              <div
                v-for="(item, index) in selectedOrder.items || []"
                :key="index"
                class="item-row"
              >
                <div>
                  <p class="item-name">{{ item.name || "Product" }}</p>
                  <p class="item-meta">
                    {{ item.size ? `Size: ${item.size}` : "" }}
                    {{ item.color ? ` | Color: ${item.color}` : "" }}
                  </p>
                </div>
                <span class="item-qty">x{{ item.quantity || 1 }}</span>
                <strong class="item-price">
                  {{ formatPrice(parseFloat(item.price) * (item.quantity || 1)) }}
                </strong>
              </div>

              <div v-if="!selectedOrder.items?.length" class="item-row">
                <div>
                  <p class="item-name">Order item</p>
                </div>
                <span class="item-qty">x1</span>
                <strong class="item-price">
                  {{ formatPrice(selectedOrder.total_amount) }}
                </strong>
              </div>
            </div>
          </section>

          <section class="summary-card">
            <div class="summary-row">
              <span>Subtotal</span>
              <strong>{{ formatPrice(selectedOrder.subtotal || selectedOrder.total_amount) }}</strong>
            </div>
            <div class="summary-row">
              <span>Shipping</span>
              <strong>{{ formatPrice(selectedOrder.shipping_fee || 0) }}</strong>
            </div>
            <div class="summary-row total">
              <span>Total</span>
              <strong>{{ formatPrice(selectedOrder.total_amount) }}</strong>
            </div>
            <div v-if="selectedOrder.notes" class="summary-row">
              <span>Notes</span>
              <strong>{{ selectedOrder.notes }}</strong>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.orders-view {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  color: #f3ead8;
}

.page-hero,
.panel,
.modal,
.info-card,
.summary-card {
  border: 1px solid rgba(255, 255, 255, 0.07);
  background: linear-gradient(180deg, rgba(28, 28, 28, 0.98), rgba(20, 20, 20, 0.98));
}

.page-hero {
  border-radius: 1.15rem;
  padding: 1.45rem 1.5rem;
}

.eyebrow,
.section-label,
.modal-eyebrow {
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.eyebrow {
  margin: 0 0 0.45rem;
  font-size: 0.76rem;
  color: #90b6c6;
  font-weight: 700;
}

.page-title {
  margin: 0;
  font-size: 1.95rem;
}

.page-subtitle {
  margin: 0.75rem 0 0;
  color: #aaa497;
  max-width: 38rem;
  line-height: 1.55;
}

.filter-strip {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.72rem 0.95rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.07);
  background: rgba(255, 255, 255, 0.03);
  color: #d6cdbb;
  cursor: pointer;
  font: inherit;
}

.filter-chip.active {
  border-color: rgba(255, 255, 255, 0.14);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
}

.chip-count {
  min-width: 1.5rem;
  height: 1.5rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.08);
  font-size: 0.78rem;
  font-weight: 700;
}

.status-pending {
  color: #edc97f;
}

.status-processing {
  color: #98bff0;
}

.status-shipped {
  color: #c0a6ff;
}

.status-delivered {
  color: #9bd8a8;
}

.status-cancelled {
  color: #f0a39c;
}

.panel {
  border-radius: 1.15rem;
  padding: 1.25rem;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1rem;
}

.search-field {
  position: relative;
  flex: 1;
}

.search-field svg {
  position: absolute;
  left: 0.95rem;
  top: 50%;
  transform: translateY(-50%);
  width: 1rem;
  height: 1rem;
  color: #888277;
}

.search-field input {
  width: 100%;
  padding: 0.85rem 1rem 0.85rem 2.6rem;
  border-radius: 0.9rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  color: #f4ead6;
  font: inherit;
}

.search-field input:focus {
  outline: none;
  border-color: rgba(123, 163, 181, 0.34);
  box-shadow: 0 0 0 3px rgba(123, 163, 181, 0.12);
}

.toolbar-note,
.muted-text,
.customer-email,
.order-note,
.pagination-info,
.modal-subtitle,
.item-meta,
.address-text {
  color: #8b867d;
}

.mobile-card-list {
  display: none;
}

.order-card {
  padding: 1rem;
  border-radius: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.025);
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.order-card-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.75rem;
}

.order-card-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem;
}

.order-card .view-btn {
  width: 100%;
}

.table-wrap {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 1rem 0.85rem;
  text-align: left;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  vertical-align: middle;
}

.data-table th {
  font-size: 0.74rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #908b81;
}

.order-id,
.customer-name,
.amount-text {
  margin: 0;
  font-weight: 600;
}

.order-id {
  font-family: ui-monospace, monospace;
  color: #9fc6d6;
}

.order-note,
.customer-email {
  margin: 0.25rem 0 0;
  font-size: 0.82rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.36rem 0.72rem;
  border-radius: 999px;
  font-size: 0.74rem;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.06);
}

.view-btn,
.pagination-btn,
.status-action,
.close-btn {
  border-radius: 0.82rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  color: #f4ead6;
  font: inherit;
  cursor: pointer;
}

.view-btn {
  padding: 0.7rem 0.95rem;
  font-weight: 600;
}

.pagination {
  margin-top: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.pagination-btn {
  padding: 0.78rem 1rem;
}

.pagination-btn:disabled,
.status-action:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.loading-state,
.empty-state {
  min-height: 14rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 0.8rem;
  color: #908b82;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(255, 255, 255, 0.08);
  border-top-color: #8bb9cc;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(5, 5, 5, 0.76);
  backdrop-filter: blur(8px);
  z-index: 10020;
}

.modal {
  width: min(860px, 100%);
  max-height: 90vh;
  overflow: auto;
  border-radius: 1.15rem;
}

.modal-header,
.modal-body {
  padding: 1.25rem 1.35rem;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.modal-eyebrow {
  margin: 0 0 0.4rem;
  font-size: 0.72rem;
  color: #8eb6c6;
  font-weight: 700;
}

.modal-title {
  margin: 0;
  font-size: 1.3rem;
}

.close-btn {
  width: 2.4rem;
  height: 2.4rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.close-btn svg {
  width: 1rem;
  height: 1rem;
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.section-label {
  margin: 0 0 0.75rem;
  font-size: 0.72rem;
  color: #9a9388;
  font-weight: 700;
}

.status-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.status-action {
  padding: 0.72rem 0.95rem;
  font-weight: 600;
}

.status-action.active {
  border-color: rgba(255, 255, 255, 0.14);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.info-card,
.summary-card {
  border-radius: 1rem;
  padding: 1rem;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.info-row,
.summary-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.info-row span,
.summary-row span {
  color: #8f8a82;
}

.items-panel {
  padding: 1rem;
  border-radius: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.07);
  background: rgba(255, 255, 255, 0.025);
}

.item-list {
  display: flex;
  flex-direction: column;
}

.item-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto auto;
  gap: 1rem;
  align-items: center;
  padding: 0.95rem 0;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.item-row:first-child {
  border-top: 0;
}

.item-name,
.item-price {
  margin: 0;
}

.item-qty {
  color: #a29b8e;
}

.summary-row.total {
  margin-top: 0.7rem;
  padding-top: 0.8rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  font-size: 1.05rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 900px) {
  .toolbar,
  .pagination {
    flex-direction: column;
    align-items: stretch;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .page-title {
    font-size: 1.55rem;
  }

  .table-wrap {
    display: none;
  }

  .mobile-card-list {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
  }

  .filter-strip {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .filter-chip {
    justify-content: center;
    width: 100%;
  }

  .info-row,
  .summary-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .modal {
    max-height: calc(100vh - 1rem);
  }
}

@media (max-width: 700px) {
  .item-row {
    grid-template-columns: 1fr;
  }
}
</style>
