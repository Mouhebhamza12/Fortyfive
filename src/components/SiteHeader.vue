<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import brandMark from "../../assets/images/45svg.png";
import { useCart } from "../composables/useCart";
import { useProducts } from "../composables/useProducts";
import { useToast } from "../composables/useToast";

const props = defineProps({
  navLinks: {
    type: Array,
    default: () => [],
  },
  visible: {
    type: Boolean,
    default: true,
  },
});

const router = useRouter();
const route = useRoute();
const { cartItems, itemCount, subtotalLabel, removeItem, updateQuantity } = useCart();
const { products, loadProducts } = useProducts();
const { success: toastSuccess } = useToast();

const cartBadgeLabel = computed(() => {
  if (!itemCount.value) return "";
  return itemCount.value > 99 ? "99+" : String(itemCount.value);
});

const cartAriaLabel = computed(() =>
  itemCount.value
    ? `Cart, ${itemCount.value} item${itemCount.value === 1 ? "" : "s"}`
    : "Cart",
);

const apiBaseUrl =
  (import.meta.env.VITE_API_BASE_URL || "/api").replace(/\/$/, "");

const mobileMenuOpen = ref(false);
const activePanel = ref(null);
const authUser = ref(null);
const authReady = ref(false);

const searchQuery = ref("");
const searchInputRef = ref(null);

const filteredProducts = computed(() => {
  if (!searchQuery.value.trim()) return [];
  const query = searchQuery.value.toLowerCase().trim();
  return products.value.filter(
    (p) =>
      p.name.toLowerCase().includes(query) ||
      (p.color && p.color.toLowerCase().includes(query)) ||
      (p.description && p.description.toLowerCase().includes(query))
  );
});

const executeSearch = () => {
  if (!searchQuery.value.trim()) return;
  router.push({ name: "home", query: { q: searchQuery.value.trim() }, hash: "#shop" });
  closeActivePanel();
};

const utilityPanels = {
  search: {
    title: "Search",
    items: ["Latest drop", "Best sellers", "Size guide"],
  },
};

const syncAuthState = async () => {
  try {
    const response = await fetch(`${apiBaseUrl}/auth/me`, {
      credentials: "include",
      headers: {
        Accept: "application/json",
      },
    });

    if (!response.ok) {
      authUser.value = null;
      authReady.value = true;
      return;
    }

    const payload = await response.json();
    authUser.value = payload.user ?? null;
  } catch {
    authUser.value = null;
  } finally {
    authReady.value = true;
  }
};

const isAuthenticated = computed(() => Boolean(authUser.value));

const accountPanel = computed(() => {
  if (isAuthenticated.value) {
    return {
      title: "Account",
      subtitle: authUser.value?.name || authUser.value?.email || "Signed in",
      items: [
        { label: "Your info", route: { name: "profile" } },
        { label: "My orders", route: { name: "my-orders" } },
        { label: "Feedback", route: { name: "feedback" } },
        { label: "Sign out", action: "logout" },
      ],
    };
  }

  return {
    title: "Account",
    items: [
      { label: "Sign in", route: { name: "sign-in" } },
      { label: "Create account", route: { name: "sign-up" } },
      { label: "Feedback", route: { name: "feedback" } },
    ],
  };
});

const activePanelContent = computed(() => {
  if (!activePanel.value) {
    return null;
  }

  if (activePanel.value === "account") {
    return accountPanel.value;
  }

  if (activePanel.value === "cart") {
    return {
      title: "Cart",
      items: cartItems.items,
    };
  }

  return utilityPanels[activePanel.value];
});

const closeMobileMenu = () => {
  mobileMenuOpen.value = false;
};

const closeNavOverlays = () => {
  activePanel.value = null;
  closeMobileMenu();
};

const closeActivePanel = () => {
  activePanel.value = null;
};

const goHome = async () => {
  closeNavOverlays();

  if (route.name === "home") {
    document.getElementById("home")?.scrollIntoView({ behavior: "smooth", block: "start" });
    return;
  }

  await router.push({ name: "home" });
};

const navigateLink = async (link) => {
  closeNavOverlays();

  if (link.route) {
    await router.push(link.route);
    return;
  }

  if (!link.target) {
    return;
  }

  if (route.name !== "home") {
    await router.push({ name: "home", hash: `#${link.target}` });
    return;
  }

  document.getElementById(link.target)?.scrollIntoView({ behavior: "smooth", block: "start" });
};

const handlePanelItem = async (item) => {
  if (typeof item === "string") {
    closeNavOverlays();
    return;
  }

  if (item.action === "close") {
    closeNavOverlays();
    return;
  }

  if (item.action === "logout") {
    try {
      await fetch(`${apiBaseUrl}/auth/logout`, {
        method: "POST",
        credentials: "include",
        headers: {
          Accept: "application/json",
        },
      });
    } catch {}

    authUser.value = null;
    localStorage.removeItem("is_admin");
    closeNavOverlays();
    toastSuccess("Signed out.");

    if (route.name !== "home") {
      await router.push({ name: "home" });
    }

    return;
  }

  await navigateLink(item);
};

const openCheckout = async () => {
  closeNavOverlays();
  await router.push({ name: "checkout" });
};

const toggleMobileMenu = () => {
  activePanel.value = null;
  mobileMenuOpen.value = !mobileMenuOpen.value;
};

const togglePanel = (panelName) => {
  closeMobileMenu();
  activePanel.value = activePanel.value === panelName ? null : panelName;
};

const handleKeydown = (event) => {
  if (event.key === "Escape") {
    closeNavOverlays();
  }
};

onMounted(async () => {
  await loadProducts();
  await syncAuthState();
  window.addEventListener("keydown", handleKeydown);
  window.addEventListener("auth-changed", syncAuthState);
});

onUnmounted(() => {
  window.removeEventListener("keydown", handleKeydown);
  window.removeEventListener("auth-changed", syncAuthState);
});

watch(
  () => route.fullPath,
  () => {
    syncAuthState();
  }
);

watch(activePanel, (panel) => {
  document.body.style.overflow = (panel === "cart" || panel === "search") ? "hidden" : "";
  if (panel === "search") {
    setTimeout(() => {
      searchInputRef.value?.focus();
    }, 120);
  } else {
    searchQuery.value = "";
  }
});
</script>

<template>
  <header
    class="topbar"
    :class="{
      'topbar--visible': visible,
      'topbar--menu-open': mobileMenuOpen,
      'topbar--panel-open': activePanel,
    }"
  >
    <button class="brand" type="button" aria-label="Go to home" @click="goHome">
      <img :src="brandMark" alt="45 logo" width="44" height="44" />
    </button>

    <button
      class="mobile-menu-toggle"
      type="button"
      :aria-expanded="mobileMenuOpen"
      aria-controls="mobile-menu"
      aria-label="Toggle navigation"
      @click="toggleMobileMenu"
    >
      <span></span>
      <span></span>
      <span></span>
    </button>

    <nav class="primary-nav" aria-label="Primary navigation">
      <button
        v-for="link in navLinks"
        :key="link.label ?? link.target"
        type="button"
        class="nav-link"
        @click="navigateLink(link)"
      >
        {{ link.label }}
      </button>
    </nav>

    <div class="nav-tools" aria-label="Account and cart actions">
      <button class="icon-btn" aria-label="Account" @click="togglePanel('account')">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="12" cy="8" r="4" />
          <path d="M4 21a8 8 0 0 1 16 0" />
        </svg>
      </button>
      <button class="icon-btn" aria-label="Search" @click="togglePanel('search')">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="10.5" cy="10.5" r="6.5" />
          <path d="m16 16 5 5" />
        </svg>
      </button>
      <button
        class="icon-btn cart-btn"
        :aria-label="cartAriaLabel"
        @click="togglePanel('cart')"
      >
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M6 8h12l-1 13H7L6 8Z" />
          <path d="M9 8a3 3 0 0 1 6 0" />
        </svg>
        <span v-if="itemCount" class="cart-badge">{{ cartBadgeLabel }}</span>
      </button>
    </div>

    <transition name="nav-panel-fade">
      <section
        v-if="activePanelContent && activePanel !== 'cart' && activePanel !== 'search'"
        class="nav-panel"
        :class="`nav-panel--${activePanel}`"
        :aria-label="`${activePanelContent.title} panel`"
      >
        <div class="nav-panel-header">
          <div class="nav-panel-heading">
            <span>{{ activePanelContent.title }}</span>
            <p v-if="activePanel === 'account' && activePanelContent.subtitle" class="nav-panel-identity">
              {{ activePanelContent.subtitle }}
            </p>
          </div>
          <button type="button" class="nav-panel-close" aria-label="Close panel" @click="closeActivePanel">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6 6 18 18" />
              <path d="M18 6 6 18" />
            </svg>
          </button>
        </div>
        <div class="nav-panel-body">
          <button
            v-for="item in activePanelContent.items"
            :key="item.label ?? item"
            type="button"
            class="nav-panel-link"
            @click="handlePanelItem(item)"
          >
            <span>{{ item.label ?? item }}</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M9 6 15 12 9 18" />
            </svg>
          </button>
        </div>
      </section>
    </transition>

    <teleport to="body">
      <transition name="cart-overlay">
        <div
          v-if="activePanel === 'cart' && activePanelContent"
          class="cart-drawer-overlay"
          @click="closeActivePanel"
        >
          <transition name="cart-drawer">
            <aside
              v-if="activePanel === 'cart' && activePanelContent"
              class="cart-drawer"
              aria-label="Cart panel"
              @click.stop
            >
              <div class="cart-drawer-header">
                <div class="cart-drawer-heading">
                  <span>Cart</span>
                  <p>{{ itemCount }} item<span v-if="itemCount !== 1">s</span></p>
                </div>
                <button type="button" class="cart-drawer-close" aria-label="Close cart" @click="closeActivePanel">
                  <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 6 18 18" />
                    <path d="M18 6 6 18" />
                  </svg>
                </button>
              </div>

              <div class="cart-drawer-body">
                <template v-if="activePanelContent.items.length">
                  <div class="nav-cart-list">
                    <article
                      v-for="item in activePanelContent.items"
                      :key="item.id"
                      class="nav-cart-item"
                    >
                      <img :src="item.image" :alt="item.name" class="nav-cart-image" />
                      <div class="nav-cart-copy">
                        <strong>{{ item.name }}</strong>
                        <p>{{ item.color }} / {{ item.size }}</p>
                        <div class="nav-cart-meta">
                          <div class="nav-cart-controls">
                            <button type="button" @click="updateQuantity(item.id, item.quantity - 1)">-</button>
                            <span>{{ item.quantity }}</span>
                            <button type="button" @click="updateQuantity(item.id, item.quantity + 1)">+</button>
                          </div>
                          <button type="button" class="nav-cart-remove" @click="removeItem(item.id)">Remove</button>
                        </div>
                      </div>
                    </article>
                  </div>
                </template>
                <template v-else>
                  <div class="nav-empty-state">
                    <div class="nav-empty-mark">
                      <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6 8h12l-1 13H7L6 8Z" />
                        <path d="M9 8a3 3 0 0 1 6 0" />
                      </svg>
                    </div>
                    <p class="nav-panel-note">Your cart is empty.</p>
                    <p class="nav-panel-subnote">
                      Add products first, then complete checkout. Payment happens on delivery.
                    </p>
                  </div>
                </template>
              </div>

              <div class="cart-drawer-footer">
                <div class="nav-cart-total">
                  <span>Subtotal</span>
                  <strong>{{ subtotalLabel }}</strong>
                </div>
                <p class="nav-panel-subnote">
                  No online payment. Customers pay when the order is delivered.
                </p>
                <button
                  type="button"
                  class="nav-panel-link nav-panel-link--cta"
                  :disabled="!activePanelContent.items.length"
                  @click="openCheckout"
                >
                  Go to Checkout
                </button>
              </div>
            </aside>
          </transition>
        </div>
      </transition>

      <transition name="search-overlay">
        <div
          v-if="activePanel === 'search'"
          class="search-fullscreen-overlay"
          @click="closeActivePanel"
        >
          <div class="search-overlay-blur-bg"></div>
          
          <button
            type="button"
            class="search-close-btn"
            aria-label="Close search"
            @click.stop="closeActivePanel"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6 6 18 18" />
              <path d="M18 6 6 18" />
            </svg>
          </button>

          <div class="search-overlay-content" @click.stop>
            <h2 class="search-prompt-text">
              START TYPING AND PRESS ENTER TO SEARCH
            </h2>
            
            <div class="search-input-wrapper">
              <input
                ref="searchInputRef"
                v-model="searchQuery"
                type="text"
                class="search-input-field"
                placeholder="Type to search..."
                autofocus
                @keydown.enter="executeSearch"
              />
              <button class="search-submit-icon" @click="executeSearch" aria-label="Submit search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <circle cx="10.5" cy="10.5" r="6.5" />
                  <path d="m16 16 5 5" />
                </svg>
              </button>
            </div>

            <!-- Instant Search Results dropdown inside overlay -->
            <transition name="search-results-fade">
              <div v-if="searchQuery.trim()" class="search-results-container">
                <p class="search-results-meta">
                  {{ filteredProducts.length }} product{{ filteredProducts.length !== 1 ? 's' : '' }} matching
                </p>
                <div v-if="filteredProducts.length" class="search-results-grid">
                  <router-link
                    v-for="product in filteredProducts"
                    :key="product.id"
                    :to="{ name: 'product-details', params: { slug: product.slug } }"
                    class="search-result-card"
                    @click="closeActivePanel"
                  >
                    <img :src="product.image" :alt="product.name" class="search-result-image" />
                    <div class="search-result-info">
                      <span class="search-result-name">{{ product.name }}</span>
                      <span class="search-result-price">{{ product.price }}</span>
                    </div>
                  </router-link>
                </div>
                <div v-else class="search-no-results">
                  No matching products found. Try "College", "Late", "Graduation", "White", or "Black".
                </div>
              </div>
            </transition>
          </div>
        </div>
      </transition>
    </teleport>

    <div
      id="mobile-menu"
      class="mobile-menu"
      :class="{ 'mobile-menu--open': mobileMenuOpen }"
    >
      <nav class="mobile-menu-links" aria-label="Mobile navigation">
        <button
          v-for="link in navLinks"
          :key="link.label ?? link.target"
          type="button"
          @click="navigateLink(link)"
        >
          {{ link.label }}
        </button>
      </nav>
      <div class="mobile-menu-actions" aria-label="Mobile account and cart actions">
        <button type="button" @click="togglePanel('account')">Account</button>
        <button type="button" @click="togglePanel('search')">Search</button>
        <button type="button" @click="togglePanel('cart')">Cart [{{ itemCount }}]</button>
      </div>
    </div>
  </header>
</template>

<style scoped>
.topbar {
  box-sizing: border-box;
  align-items: center;
  background: #7ba3b5;
  display: flex;
  justify-content: space-between;
  height: clamp(5rem, 6vw, 6.3rem);
  padding: 0 clamp(1rem, 4vw, 2.5rem);
  position: sticky;
  top: 0;
  z-index: 100;
  gap: clamp(1rem, 2.2vw, 2rem);
  opacity: 0;
  transform: translateY(-100%);
  transition:
    opacity 0.6s ease,
    transform 0.6s cubic-bezier(0.22, 1, 0.36, 1),
    box-shadow 0.25s ease;
}

.topbar--menu-open,
.topbar--panel-open {
  box-shadow: 0 18px 40px rgba(34, 56, 66, 0.18);
}

.topbar--visible {
  opacity: 1;
  transform: translateY(0);
}

.topbar::after {
  content: "";
  position: absolute;
  inset: 0;
  backdrop-filter: blur(0px);
  transition: backdrop-filter 0.3s ease;
  pointer-events: none;
}

.brand {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  width: clamp(3rem, 5vw, 4rem);
  height: clamp(3rem, 5vw, 4rem);
  border: 0;
  background: transparent;
  padding: 0;
  cursor: pointer;
}

.brand img {
  display: block;
  filter: none;
  height: clamp(2.15rem, 3vw, 3rem);
  mix-blend-mode: screen;
  object-fit: contain;
  width: auto;
  max-width: 100%;
}

.primary-nav {
  align-items: center;
  display: flex;
  gap: 0.6rem;
  justify-content: center;
  flex: 1;
  min-width: 0;
}

.nav-link {
  align-items: center;
  color: #e8dcc8;
  display: inline-flex;
  background: transparent;
  cursor: pointer;
  font-family: "Helvetica Neue", sans-serif;
  font-size: clamp(0.72rem, 0.85vw, 0.9rem);
  font-weight: 800;
  letter-spacing: 0.18em;
  line-height: 1;
  text-decoration: none;
  text-transform: uppercase;
  white-space: nowrap;
  padding: 0.9rem 1.2rem;
  border-radius: 999px;
  border: 1px solid rgba(232, 220, 200, 0.14);
  transition:
    background 0.28s cubic-bezier(0.22, 1, 0.36, 1),
    border-color 0.28s cubic-bezier(0.22, 1, 0.36, 1),
    color 0.22s ease,
    transform 0.22s ease,
    box-shadow 0.22s ease;
}

.nav-link:hover {
  background: rgba(244, 234, 214, 0.12);
  border-color: rgba(244, 234, 214, 0.34);
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 10px 18px rgba(34, 56, 66, 0.12);
}

.nav-link:active {
  transform: translateY(0);
  background: rgba(244, 234, 214, 0.22);
}

.nav-tools {
  align-items: center;
  display: flex;
  flex: 0 0 auto;
  gap: 0.5rem;
  margin-left: 0;
  padding-left: 0;
}

.mobile-menu-toggle {
  display: none;
}

.mobile-menu {
  display: none;
}

.icon-btn {
  background: rgba(244, 234, 214, 0.08);
  border: 1px solid rgba(232, 220, 200, 0.14);
  border-radius: 999px;
  color: #e8dcc8;
  cursor: pointer;
  display: inline-grid;
  height: 2.9rem;
  padding: 0;
  place-items: center;
  position: relative;
  width: 2.9rem;
  transition:
    transform 0.2s ease,
    opacity 0.2s ease,
    background 0.2s ease,
    border-color 0.2s ease;
}

.icon-btn:hover {
  opacity: 1;
  transform: translateY(-1px);
  background: rgba(244, 234, 214, 0.14);
  border-color: rgba(244, 234, 214, 0.26);
}

.icon-btn svg {
  fill: none;
  height: 1.35rem;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 2;
  width: 1.35rem;
}

.cart-badge {
  position: absolute;
  top: 0.12rem;
  right: 0.05rem;
  min-width: 1.1rem;
  height: 1.1rem;
  padding: 0 0.22rem;
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: #172126;
  color: #f4ead6;
  border: 1.5px solid rgba(123, 163, 181, 0.95);
  font: 700 0.6rem/1 "Sora", sans-serif;
  font-variant-numeric: tabular-nums;
  pointer-events: none;
}

.nav-panel {
  position: absolute;
  top: calc(100% + 0.85rem);
  right: clamp(1rem, 4vw, 2.5rem);
  width: min(22rem, calc(100vw - 2rem));
  background: rgba(254, 251, 246, 0.98);
  border: 1px solid rgba(23, 33, 38, 0.08);
  border-radius: 0.8rem;
  box-shadow:
    0 20px 45px rgba(23, 33, 38, 0.1),
    0 1px 0 rgba(255, 255, 255, 0.9) inset;
  padding: 1.25rem;
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  overflow: hidden;
}

.cart-overlay-enter-active,
.cart-overlay-leave-active {
  transition: opacity 0.24s ease;
}

.cart-overlay-enter-from,
.cart-overlay-leave-to {
  opacity: 0;
}

.cart-drawer-enter-active,
.cart-drawer-leave-active {
  transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

.cart-drawer-enter-from,
.cart-drawer-leave-to {
  transform: translateX(100%);
}

.cart-panel-enter-active,
.cart-panel-leave-active {
  transition:
    opacity 0.28s ease,
    transform 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

.cart-panel-enter-from,
.cart-panel-leave-to {
  opacity: 0;
  transform: translateX(18px) translateY(-10px);
}

.nav-panel-fade-enter-active,
.nav-panel-fade-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.24s ease;
}

.nav-panel-fade-enter-from,
.nav-panel-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.cart-drawer-overlay {
  position: fixed;
  inset: 0;
  z-index: 220;
  background: rgba(5, 5, 5, 0.48);
  backdrop-filter: blur(4px);
}

.cart-drawer {
  position: absolute;
  top: 0;
  right: 0;
  width: min(28rem, 100vw);
  height: 100%;
  display: grid;
  grid-template-rows: auto 1fr auto;
  background:
    linear-gradient(180deg, rgba(254, 251, 246, 0.99), rgba(239, 230, 216, 0.99));
  border-left: 1px solid rgba(23, 33, 38, 0.08);
  box-shadow: -18px 0 40px rgba(34, 56, 66, 0.16);
}

.cart-drawer-header,
.cart-drawer-footer {
  padding: 1.2rem 1.2rem 1rem;
}

.cart-drawer-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  border-bottom: 1px solid rgba(23, 33, 38, 0.08);
}

.cart-drawer-heading {
  display: grid;
  gap: 0.35rem;
}

.cart-drawer-heading span {
  color: #172126;
  font-family: "Helvetica Neue", sans-serif;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.24em;
  text-transform: uppercase;
}

.cart-drawer-heading p {
  margin: 0;
  color: rgba(23, 33, 38, 0.48);
  font-family: "Sora", sans-serif;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.cart-drawer-close {
  width: 2.5rem;
  height: 2.5rem;
  border: 0;
  background: transparent;
  color: #172126;
  cursor: pointer;
  display: inline-grid;
  place-items: center;
}

.cart-drawer-close svg {
  width: 1.1rem;
  height: 1.1rem;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 2;
}

.cart-drawer-body {
  overflow: auto;
  padding: 1.2rem;
}

.cart-drawer-footer {
  display: grid;
  gap: 0.8rem;
  border-top: 1px solid rgba(23, 33, 38, 0.08);
  background: rgba(255, 255, 255, 0.32);
}

.nav-panel::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.4) 0%, transparent 100%);
  pointer-events: none;
}

.nav-panel-header {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.nav-panel-heading {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  position: relative;
  z-index: 1;
}

.nav-panel-header span,
.nav-panel-note,
.nav-panel-subnote,
.nav-panel-link,
.nav-panel-close {
  font-family: "Helvetica Neue", sans-serif;
}

.nav-panel-header span {
  color: #172126;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.24em;
  text-transform: uppercase;
}

.nav-panel-identity {
  margin: 0;
  color: rgba(23, 33, 38, 0.58);
  font-family: "Helvetica Neue", sans-serif;
  font-size: 0.78rem;
  letter-spacing: 0.03em;
}

.nav-panel-close {
  position: relative;
  z-index: 1;
  width: 2.2rem;
  height: 2.2rem;
  border: 0;
  border-radius: 50%;
  background: transparent;
  color: rgba(23, 33, 38, 0.4);
  cursor: pointer;
  display: inline-grid;
  place-items: center;
  transition: all 0.25s ease;
}

.nav-panel-close:hover {
  background: rgba(23, 33, 38, 0.05);
  color: #172126;
  transform: scale(1.05);
}

.nav-panel-close svg {
  width: 0.9rem;
  height: 0.9rem;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 1.5;
}

.nav-panel-body {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.nav-panel-link {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  text-align: left;
  border: 1px solid rgba(23, 33, 38, 0.04);
  background: rgba(255, 255, 255, 0.65);
  color: #172126;
  border-radius: 0.6rem;
  padding: 0.85rem 1.1rem;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s ease;
}

.nav-panel-link:hover {
  background: #fff;
  border-color: rgba(123, 163, 181, 0.3);
  color: #7ba3b5;
  transform: translateY(-1px);
  box-shadow: 0 6px 15px rgba(23, 33, 38, 0.04);
}

.nav-panel-link svg {
  width: 0.85rem;
  height: 0.85rem;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 1.8;
  flex: 0 0 auto;
  opacity: 0.6;
  transition: opacity 0.25s ease;
}

.nav-panel-link--cta {
  justify-content: center;
  text-align: center;
  background: #172126;
  color: #f4ead6;
  border-radius: 999px;
  padding-left: 1rem;
}

.nav-panel-link--cta:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.nav-panel-note {
  color: #172126;
  font-size: 0.98rem;
  font-weight: 800;
  margin: 0;
}

.nav-panel-subnote {
  color: rgba(23, 33, 38, 0.66);
  font-size: 0.8rem;
  line-height: 1.7;
  margin: 0;
}

.nav-cart-list {
  display: grid;
  gap: 0.9rem;
}

.nav-cart-item {
  display: grid;
  grid-template-columns: 4.25rem 1fr;
  gap: 0.9rem;
  align-items: start;
  padding: 0 0 1rem;
  border-bottom: 1px solid rgba(23, 33, 38, 0.08);
}

.nav-cart-image {
  width: 4.25rem;
  height: 4.25rem;
  object-fit: contain;
  border-radius: 0.9rem;
  background: rgba(255, 255, 255, 0.58);
  border: 1px solid rgba(23, 33, 38, 0.06);
}

.nav-cart-copy {
  display: grid;
  gap: 0.35rem;
  color: #172126;
  font-family: "Helvetica Neue", sans-serif;
}

.nav-cart-copy strong {
  font-size: 0.8rem;
  line-height: 1.3;
}

.nav-cart-copy p {
  margin: 0;
  font-size: 0.74rem;
  color: rgba(23, 33, 38, 0.62);
}

.nav-cart-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-top: 0.1rem;
}

.nav-cart-controls {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
  width: fit-content;
  padding: 0.22rem 0.3rem;
  border-radius: 999px;
  background: rgba(123, 163, 181, 0.12);
}

.nav-cart-controls button,
.nav-cart-remove {
  border: 0;
  background: transparent;
  color: #172126;
  cursor: pointer;
  font-family: "Helvetica Neue", sans-serif;
}

.nav-cart-controls button {
  width: 1.5rem;
  height: 1.5rem;
  border: 1px solid rgba(23, 33, 38, 0.1);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.66);
}

.nav-cart-controls span {
  min-width: 1rem;
  text-align: center;
  color: #172126;
  font-size: 0.78rem;
}

.nav-cart-remove {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  opacity: 0.72;
  text-transform: none;
}

.nav-cart-footer {
  display: grid;
  gap: 0.8rem;
  margin-top: 0.9rem;
  padding-top: 0.2rem;
}

.nav-cart-total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #172126;
  font-family: "Helvetica Neue", sans-serif;
  font-size: 0.9rem;
}

.nav-empty-state {
  display: grid;
  justify-items: start;
  gap: 0.8rem;
  padding: 0.35rem 0 0.2rem;
}

.nav-empty-mark {
  width: 3rem;
  height: 3rem;
  display: inline-grid;
  place-items: center;
  border-radius: 1rem 0.6rem 1rem 0.6rem;
  background: rgba(123, 163, 181, 0.16);
  color: #172126;
}

.nav-empty-mark svg {
  width: 1.3rem;
  height: 1.3rem;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 2;
}

@media (max-width: 1120px) {
  .topbar {
    padding: 0 1rem;
    gap: 1rem;
  }

  .primary-nav {
    gap: 0.4rem;
  }

  .nav-link {
    padding: 0.85rem 1rem;
  }
}

@media (max-width: 760px) {
  .topbar {
    gap: 1rem;
    padding: 0 0.9rem;
    position: relative;
  }

  .brand {
    flex-shrink: 0;
    width: 2.8rem;
    height: 2.8rem;
  }

  .brand img {
    height: 2rem;
  }

  .primary-nav {
    display: none;
  }

  .nav-tools {
    border-left: 0;
    gap: 0.9rem;
    margin-left: auto;
    padding-left: 0;
  }

  .mobile-menu-toggle {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.2rem;
    width: 2.9rem;
    height: 2.9rem;
    padding: 0;
    border: 1px solid rgba(232, 220, 200, 0.14);
    border-radius: 999px;
    background: rgba(244, 234, 214, 0.08);
    color: #e8dcc8;
    cursor: pointer;
  }

  .mobile-menu-toggle span {
    display: block;
    width: 1rem;
    height: 1.5px;
    background: currentColor;
  }

  .icon-btn {
    height: 2.75rem;
    width: 2.75rem;
  }

  .icon-btn svg {
    height: 1.18rem;
    width: 1.18rem;
  }

  .mobile-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    display: none;
    background: #7ba3b5;
    padding: 0 0.9rem 0.9rem;
    border-top: 1px solid rgba(232, 220, 200, 0.16);
    box-shadow: 0 14px 24px rgba(0, 0, 0, 0.08);
  }

  .mobile-menu--open {
    display: block;
  }

  .mobile-menu-links,
  .mobile-menu-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .mobile-menu-links {
    margin-bottom: 0.85rem;
    padding-top: 0.85rem;
  }

  .mobile-menu-links button,
  .mobile-menu-actions button {
    color: #e8dcc8;
    font-family: "Helvetica Neue", sans-serif;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: none;
    background: transparent;
    border: 0;
    padding: 0;
    text-align: left;
    cursor: pointer;
  }

  .nav-panel {
    top: calc(100% + 0.6rem);
    left: 0.75rem;
    right: 0.75rem;
    width: auto;
    padding: 0.95rem;
    border-radius: 1.1rem 0.8rem 1.1rem 0.8rem;
    box-shadow: 0 18px 32px rgba(34, 56, 66, 0.14);
  }

  .cart-drawer {
    width: min(100vw, 26rem);
  }

  .cart-drawer-header,
  .cart-drawer-body,
  .cart-drawer-footer {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .nav-panel-header span {
    font-size: 0.68rem;
  }

  .nav-panel-close {
    width: 2.15rem;
    height: 2.15rem;
  }

  .nav-panel-link {
    border-radius: 0.9rem 0.6rem 0.9rem 0.6rem;
    padding: 0.85rem 0.9rem;
    font-size: 0.8rem;
  }

  .nav-cart-item {
    grid-template-columns: 3.5rem 1fr;
    align-items: start;
  }

  .nav-cart-image {
    width: 3.5rem;
    height: 3.5rem;
  }

  .nav-cart-meta {
    align-items: flex-start;
    flex-direction: column;
    gap: 0.45rem;
  }

  .nav-cart-remove {
    padding: 0;
  }

  .nav-cart-total {
    font-size: 0.88rem;
  }
}

/* Fullscreen Search Overlay */
.search-fullscreen-overlay {
  position: fixed;
  inset: 0;
  z-index: 300;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at center, #172126 0%, #0d1214 100%);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  padding: 2rem;
}

.search-overlay-blur-bg {
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.02'/%3E%3C/svg%3E");
  background-size: 150px 150px;
  pointer-events: none;
  opacity: 0.3;
}

/* Close Button (Circle with X) - Ultra-elegant and delicate */
.search-close-btn {
  position: absolute;
  top: clamp(1.5rem, 3vw, 2.5rem);
  right: clamp(1.5rem, 3vw, 2.5rem);
  width: 2.6rem;
  height: 2.6rem;
  border: 1px solid rgba(232, 220, 200, 0.12);
  background: transparent;
  color: rgba(232, 220, 200, 0.6);
  border-radius: 50%;
  cursor: pointer;
  display: inline-grid;
  place-items: center;
  z-index: 310;
  transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
}

.search-close-btn:hover {
  background: rgba(232, 220, 200, 0.06);
  border-color: rgba(232, 220, 200, 0.6);
  color: #fff;
  transform: rotate(90deg) scale(1.03);
}

.search-close-btn svg {
  width: 0.95rem;
  height: 0.95rem;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.4;
  stroke-linecap: round;
  stroke-linejoin: round;
}

/* Search Content Box */
.search-overlay-content {
  position: relative;
  z-index: 305;
  width: min(580px, 85vw);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: clamp(1.2rem, 2.5vw, 2rem);
  margin-top: -8vh;
}

/* Prompt Text */
.search-prompt-text {
  font-family: "Helvetica Neue", sans-serif;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.28em;
  color: rgba(232, 220, 200, 0.5);
  text-align: center;
  text-transform: uppercase;
  margin: 0;
}

/* Input Wrapper & Field */
.search-input-wrapper {
  position: relative;
  width: 100%;
  display: flex;
  align-items: center;
}

.search-input-field {
  appearance: none;
  -webkit-appearance: none;
  width: 100%;
  background: transparent !important;
  background-color: transparent !important;
  border: none !important;
  border-bottom: 1px solid rgba(232, 220, 200, 0.18) !important;
  border-radius: 0 !important;
  box-shadow: none !important;
  outline: none !important;
  outline-width: 0 !important;
  padding: 0.6rem 3rem 0.7rem 0.5rem !important;
  color: #fff !important;
  font-family: "Sora", sans-serif;
  font-size: clamp(1.15rem, 2vw, 1.45rem);
  font-weight: 500;
  text-align: center;
  transition: all 0.35s ease;
}

.search-input-field:focus {
  border-bottom-color: #7ba3b5 !important;
  box-shadow: none !important;
}

.search-input-field::placeholder {
  color: rgba(232, 220, 200, 0.25);
  font-weight: 400;
  letter-spacing: 0.05em;
}

.search-submit-icon {
  position: absolute;
  right: 0.2rem;
  background: transparent !important;
  border: none !important;
  color: rgba(232, 220, 200, 0.5);
  cursor: pointer;
  padding: 0.4rem;
  display: grid;
  place-items: center;
  transition: all 0.25s ease;
}

.search-submit-icon:hover {
  color: #fff;
  transform: scale(1.05);
}

.search-submit-icon svg {
  width: 1.15rem;
  height: 1.15rem;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.4;
  stroke-linecap: round;
  stroke-linejoin: round;
}

/* Search Results Container */
.search-results-container {
  width: 100%;
  max-height: clamp(200px, 45vh, 600px);
  overflow-y: auto;
  padding: 0.5rem 0.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.search-results-container::-webkit-scrollbar {
  width: 5px;
}

.search-results-container::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.02);
  border-radius: 999px;
}

.search-results-container::-webkit-scrollbar-thumb {
  background: rgba(232, 220, 200, 0.15);
  border-radius: 999px;
}

.search-results-container::-webkit-scrollbar-thumb:hover {
  background: rgba(123, 163, 181, 0.4);
}

.search-results-meta {
  font-family: "Sora", sans-serif;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(232, 220, 200, 0.48);
  margin: 0;
  border-bottom: 1px solid rgba(232, 220, 200, 0.12);
  padding-bottom: 0.5rem;
}

.search-results-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}

.search-result-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(232, 220, 200, 0.08);
  border-radius: 1rem;
  padding: 0.75rem;
  text-decoration: none;
  transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.search-result-card:hover {
  background: rgba(123, 163, 181, 0.08);
  border-color: rgba(123, 163, 181, 0.35);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.search-result-image {
  width: 3.5rem;
  height: 3.5rem;
  object-fit: contain;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 0.6rem;
  border: 1px solid rgba(232, 220, 200, 0.1);
  padding: 0.25rem;
}

.search-result-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.search-result-name {
  font-family: "Sora", sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  color: #fff;
}

.search-result-price {
  font-family: "Sora", sans-serif;
  font-size: 0.76rem;
  font-weight: 600;
  color: #e8dcc8;
}

.search-no-results {
  font-family: "Sora", sans-serif;
  font-size: 0.88rem;
  font-weight: 500;
  color: rgba(232, 220, 200, 0.6);
  text-align: center;
  padding: 2rem 0;
  line-height: 1.6;
}

/* Animations */
.search-overlay-enter-active,
.search-overlay-leave-active {
  transition: opacity 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.search-overlay-enter-from,
.search-overlay-leave-to {
  opacity: 0;
}

.search-overlay-enter-active .search-overlay-content {
  animation: search-fade-in 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

@keyframes search-fade-in {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.search-results-fade-enter-active,
.search-results-fade-leave-active {
  transition: all 0.3s ease;
}

.search-results-fade-enter-from,
.search-results-fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
