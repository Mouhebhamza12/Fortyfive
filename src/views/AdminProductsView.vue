<script setup>
import { computed, onMounted, ref } from "vue";
import { apiFetch, authJsonHeaders } from "../lib/api";

const products = ref([]);
const isLoading = ref(true);
const showModal = ref(false);
const editingProduct = ref(null);
const searchQuery = ref("");
const currentPage = ref(1);
const itemsPerPage = 10;

const productForm = ref({
  name: "",
  slug: "",
  description: "",
  price: "",
  compare_price: "",
  category: "",
  sizes: [],
  colors: [],
  images: [],
  featured: false,
  in_stock: true,
});

const categories = ["T-Shirts", "Hoodies", "Accessories", "Limited Edition"];

onMounted(async () => {
  await fetchProducts();
});

const fetchProducts = async () => {
  try {
    const res = await apiFetch("/admin/products");
    products.value = await res.json();
  } catch (error) {
    console.error("Error fetching products:", error);
  } finally {
    isLoading.value = false;
  }
};

const filteredProducts = computed(() => {
  if (!searchQuery.value) return products.value;
  const query = searchQuery.value.toLowerCase();
  return products.value.filter(
    (product) =>
      product.name.toLowerCase().includes(query) ||
      product.category?.toLowerCase().includes(query) ||
      product.slug?.toLowerCase().includes(query),
  );
});

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredProducts.value.slice(start, start + itemsPerPage);
});

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredProducts.value.length / itemsPerPage)),
);

const featuredCount = computed(
  () => products.value.filter((product) => product.featured).length,
);

const outOfStockCount = computed(
  () => products.value.filter((product) => product.in_stock === false).length,
);

const openAddModal = () => {
  editingProduct.value = null;
  productForm.value = {
    name: "",
    slug: "",
    description: "",
    price: "",
    compare_price: "",
    category: "",
    sizes: ["S", "M", "L", "XL"],
    colors: [],
    images: [],
    featured: false,
    in_stock: true,
  };
  showModal.value = true;
};

const openEditModal = (product) => {
  editingProduct.value = product;
  productForm.value = {
    name: product.name,
    slug: product.slug,
    description: product.description || "",
    price: product.price,
    compare_price: product.compare_price || "",
    category: product.category || "",
    sizes: product.sizes || [],
    colors: product.colors || [],
    images: product.images || [],
    featured: product.featured || false,
    in_stock: product.in_stock !== false,
  };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingProduct.value = null;
};

const generateSlug = () => {
  productForm.value.slug = productForm.value.name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)/g, "");
};

const saveProduct = async () => {
  try {
    const method = editingProduct.value ? "PUT" : "POST";
    const url = editingProduct.value
      ? `/admin/products/${editingProduct.value.id}`
      : "/admin/products";

    const res = await apiFetch(url, {
      method,
      headers: authJsonHeaders,
      body: JSON.stringify(productForm.value),
    });

    if (res.ok) {
      await fetchProducts();
      closeModal();
    }
  } catch (error) {
    console.error("Error saving product:", error);
  }
};

const deleteProduct = async (product) => {
  if (!confirm(`Are you sure you want to delete "${product.name}"?`)) return;

  try {
    const res = await apiFetch(`/admin/products/${product.id}`, {
      method: "DELETE",
    });

    if (res.ok) {
      await fetchProducts();
    }
  } catch (error) {
    console.error("Error deleting product:", error);
  }
};

const formatPrice = (price) =>
  new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(price || 0);

const getStockStatus = (product) =>
  product.in_stock === false
    ? { text: "Out of stock", class: "badge-red" }
    : { text: "In stock", class: "badge-green" };
</script>

<template>
  <div class="products-view">
    <section class="page-hero">
      <div>
        <p class="eyebrow">Catalog management</p>
        <h2 class="page-title">Products</h2>
        <p class="page-subtitle">
          Keep inventory tidy, pricing consistent, and featured drops easy to
          review.
        </p>
      </div>

      <button type="button" class="primary-btn" @click="openAddModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.8"
            d="M12 4v16m8-8H4"
          />
        </svg>
        <span>Add product</span>
      </button>
    </section>

    <section class="summary-grid">
      <article class="summary-card">
        <span class="summary-label">Catalog size</span>
        <strong class="summary-value">{{ products.length }}</strong>
      </article>
      <article class="summary-card">
        <span class="summary-label">Featured</span>
        <strong class="summary-value">{{ featuredCount }}</strong>
      </article>
      <article class="summary-card">
        <span class="summary-label">Out of stock</span>
        <strong class="summary-value">{{ outOfStockCount }}</strong>
      </article>
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
            placeholder="Search by name, category, or slug"
          />
        </div>

        <div class="toolbar-note">{{ filteredProducts.length }} matching products</div>
      </div>

      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading products...</p>
      </div>

      <div v-else-if="paginatedProducts.length === 0" class="empty-state">
        <p>No products found.</p>
      </div>

      <div v-else class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Category</th>
              <th>Pricing</th>
              <th>Availability</th>
              <th>Flag</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in paginatedProducts" :key="product.id">
              <td>
                <div class="product-cell">
                  <img
                    :src="product.images?.[0] || '/placeholder.png'"
                    :alt="product.name"
                    class="product-image"
                  />
                  <div class="product-copy">
                    <p class="product-name">{{ product.name }}</p>
                    <p class="product-slug">{{ product.slug }}</p>
                  </div>
                </div>
              </td>
              <td class="muted-text">{{ product.category || "-" }}</td>
              <td>
                <p class="price-main">{{ formatPrice(product.price) }}</p>
                <p v-if="product.compare_price" class="price-compare">
                  {{ formatPrice(product.compare_price) }}
                </p>
              </td>
              <td>
                <span class="badge" :class="getStockStatus(product).class">
                  {{ getStockStatus(product).text }}
                </span>
              </td>
              <td>
                <span v-if="product.featured" class="badge badge-gold">
                  Featured
                </span>
                <span v-else class="muted-text">Standard</span>
              </td>
              <td>
                <div class="row-actions">
                  <button
                    type="button"
                    class="icon-btn"
                    @click="openEditModal(product)"
                    aria-label="Edit product"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                      />
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="icon-btn danger"
                    @click="deleteProduct(product)"
                    aria-label="Delete product"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

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

    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <div>
            <p class="modal-eyebrow">
              {{ editingProduct ? "Update item" : "Create item" }}
            </p>
            <h3 class="modal-title">
              {{ editingProduct ? "Edit product" : "Add product" }}
            </h3>
          </div>

          <button type="button" class="close-btn" @click="closeModal" aria-label="Close">
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

        <form class="modal-form" @submit.prevent="saveProduct">
          <div class="form-grid">
            <label class="field">
              <span>Product name</span>
              <input
                v-model="productForm.name"
                type="text"
                required
                @blur="generateSlug"
              />
            </label>

            <label class="field">
              <span>Slug</span>
              <input v-model="productForm.slug" type="text" required />
            </label>

            <label class="field">
              <span>Price</span>
              <input v-model="productForm.price" type="number" step="0.01" required />
            </label>

            <label class="field">
              <span>Compare price</span>
              <input v-model="productForm.compare_price" type="number" step="0.01" />
            </label>

            <label class="field">
              <span>Category</span>
              <select v-model="productForm.category">
                <option value="">Select category</option>
                <option v-for="category in categories" :key="category" :value="category">
                  {{ category }}
                </option>
              </select>
            </label>

            <label class="field field-wide">
              <span>Description</span>
              <textarea v-model="productForm.description" rows="4"></textarea>
            </label>

            <label class="toggle-field">
              <input v-model="productForm.featured" type="checkbox" />
              <span>Featured product</span>
            </label>

            <label class="toggle-field">
              <input v-model="productForm.in_stock" type="checkbox" />
              <span>Available for sale</span>
            </label>
          </div>

          <div class="modal-footer">
            <button type="button" class="secondary-btn" @click="closeModal">
              Cancel
            </button>
            <button type="submit" class="primary-btn">
              {{ editingProduct ? "Save changes" : "Create product" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.products-view {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  color: #f3ead8;
}

.page-hero,
.summary-card,
.panel,
.modal {
  border: 1px solid rgba(255, 255, 255, 0.07);
  background: linear-gradient(180deg, rgba(28, 28, 28, 0.98), rgba(20, 20, 20, 0.98));
}

.page-hero {
  border-radius: 1.15rem;
  padding: 1.45rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 1rem;
}

.eyebrow,
.summary-label,
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

.primary-btn,
.secondary-btn,
.pagination-btn {
  border-radius: 0.85rem;
  padding: 0.8rem 1.1rem;
  font-weight: 600;
  font: inherit;
  cursor: pointer;
  transition: 0.2s ease;
}

.primary-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  border: 1px solid rgba(123, 163, 181, 0.2);
  background: #7ba3b5;
  color: #0f1112;
}

.primary-btn svg,
.close-btn svg,
.icon-btn svg {
  width: 1.05rem;
  height: 1.05rem;
}

.primary-btn:hover {
  background: #8db5c6;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.summary-card {
  border-radius: 1rem;
  padding: 1.15rem 1.2rem;
}

.summary-label {
  display: block;
  margin-bottom: 0.45rem;
  font-size: 0.75rem;
  color: #938f86;
  font-weight: 700;
}

.summary-value {
  font-size: 1.65rem;
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

.search-field input,
.field input,
.field select,
.field textarea {
  width: 100%;
  border-radius: 0.9rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  color: #f4ead6;
  font: inherit;
}

.search-field input {
  padding: 0.85rem 1rem 0.85rem 2.6rem;
}

.search-field input:focus,
.field input:focus,
.field select:focus,
.field textarea:focus {
  outline: none;
  border-color: rgba(123, 163, 181, 0.34);
  box-shadow: 0 0 0 3px rgba(123, 163, 181, 0.12);
}

.toolbar-note,
.muted-text,
.price-compare,
.pagination-info {
  color: #8b867d;
}

.table-wrap {
  overflow-x: auto;
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

.product-cell {
  display: flex;
  align-items: center;
  gap: 0.9rem;
}

.product-image {
  width: 56px;
  height: 56px;
  object-fit: cover;
  border-radius: 0.95rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.product-name,
.price-main {
  margin: 0;
  font-weight: 600;
}

.product-slug,
.price-compare {
  margin: 0.25rem 0 0;
  font-size: 0.82rem;
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.36rem 0.72rem;
  border-radius: 999px;
  font-size: 0.74rem;
  font-weight: 700;
}

.badge-green {
  background: rgba(52, 168, 83, 0.14);
  color: #9ad7a8;
}

.badge-red {
  background: rgba(180, 65, 54, 0.15);
  color: #f0a39c;
}

.badge-gold {
  background: rgba(185, 129, 31, 0.15);
  color: #efc36f;
}

.row-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.55rem;
}

.icon-btn,
.close-btn {
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 0.8rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  color: #ddd3c2;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.icon-btn:hover,
.close-btn:hover {
  background: rgba(255, 255, 255, 0.06);
}

.icon-btn.danger {
  color: #f0a39c;
}

.pagination {
  margin-top: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.pagination-btn,
.secondary-btn {
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  color: #f4ead6;
}

.pagination-btn:disabled {
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
  width: min(720px, 100%);
  border-radius: 1.15rem;
  overflow: hidden;
}

.modal-header,
.modal-form {
  padding: 1.25rem 1.35rem;
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
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

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.field,
.toggle-field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.field span {
  color: #a7a094;
  font-size: 0.88rem;
}

.field input,
.field select,
.field textarea {
  padding: 0.82rem 0.95rem;
}

.field-wide {
  grid-column: 1 / -1;
}

.toggle-field {
  flex-direction: row;
  align-items: center;
  color: #d6ccba;
}

.toggle-field input {
  width: 1rem;
  height: 1rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.25rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 900px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }

  .toolbar,
  .page-hero,
  .pagination {
    flex-direction: column;
    align-items: stretch;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
