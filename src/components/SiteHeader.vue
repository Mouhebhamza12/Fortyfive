<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import BrandMark from "./BrandMark.vue";
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
const { cartItems, itemCount, subtotalLabel, removeItem, updateQuantity, openCartRequest, formatPrice } =
  useCart();
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
  return products.value.filter((p) =>
    [p.name, p.shape, p.category, p.description, ...(p.colors ?? [])]
      .filter(Boolean)
      .some((text) => String(text).toLowerCase().includes(query))
  );
});

const executeSearch = () => {
  if (!searchQuery.value.trim()) return;
  router.push({ name: "home", query: { q: searchQuery.value.trim() }, hash: "#shop" });
  closeActivePanel();
};

const utilityPanels = {
  search: { title: "Search", items: [] },
};

// The header owns the main navigation so it is the same on every page.
const mainNav = [
  { label: "Shop", target: "shop" },
  { label: "Delivery", target: "delivery" },
  { label: "Contact", route: { name: "feedback" } },
];

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
        { label: "Delivery details", route: { name: "profile" } },
        { label: "My orders", route: { name: "my-orders" } },
        { label: "Sign out", action: "logout" },
      ],
    };
  }

  return {
    title: "Account",
    items: [
      { label: "Sign in", route: { name: "sign-in" } },
      { label: "Create account", route: { name: "sign-up" } },
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

const variantLabel = (item) =>
  [item.color, item.size && item.size !== "One size" ? item.size : null]
    .filter(Boolean)
    .join(", ");

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
  document.body.style.overflow = "";
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
  document.body.style.overflow = panel === "cart" ? "hidden" : "";
  if (panel === "search") {
    setTimeout(() => {
      searchInputRef.value?.focus();
    }, 120);
  } else {
    searchQuery.value = "";
  }
});

watch(mobileMenuOpen, (open) => {
  if (!activePanel.value) document.body.style.overflow = open ? "hidden" : "";
});

watch(openCartRequest, () => {
  closeMobileMenu();
  activePanel.value = "cart";
});
</script>


<template>
  <p class="notice">Cash on delivery, anywhere in Algeria</p>

  <header class="header" :class="{ 'header--open': activePanel === 'search' || mobileMenuOpen }">
    <div class="header__bar">
      <button
        class="header__menu-btn"
        type="button"
        :aria-expanded="mobileMenuOpen"
        aria-controls="mobile-menu"
        :aria-label="mobileMenuOpen ? 'Close menu' : 'Open menu'"
        @click="toggleMobileMenu"
      >
        <svg v-if="!mobileMenuOpen" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M3 7h18M3 12h18M3 17h18" />
        </svg>
        <svg v-else viewBox="0 0 24 24" aria-hidden="true">
          <path d="M5 5l14 14M19 5 5 19" />
        </svg>
      </button>

      <button class="header__brand" type="button" aria-label="Fortyfive, go to home" @click="goHome">
        <BrandMark class="header__mark" />
        <span class="header__wordmark">Fortyfive</span>
      </button>

      <nav class="header__nav" aria-label="Main">
        <button
          v-for="link in mainNav"
          :key="link.label"
          type="button"
          class="header__nav-link"
          @click="navigateLink(link)"
        >
          {{ link.label }}
        </button>
      </nav>

      <div class="header__tools">
        <button
          class="header__tool"
          type="button"
          aria-label="Search"
          :aria-expanded="activePanel === 'search'"
          @click="togglePanel('search')"
        >
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="10.5" cy="10.5" r="6.5" />
            <path d="m15.5 15.5 5 5" />
          </svg>
        </button>
        <button
          class="header__tool header__tool--account"
          type="button"
          aria-label="Account"
          :aria-expanded="activePanel === 'account'"
          @click="togglePanel('account')"
        >
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
          </svg>
        </button>
        <button class="header__tool" type="button" :aria-label="cartAriaLabel" @click="togglePanel('cart')">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5 8h14l-1 13H6L5 8Z" />
            <path d="M9 8V7a3 3 0 0 1 6 0v1" />
          </svg>
          <span v-if="itemCount" class="header__count">{{ cartBadgeLabel }}</span>
        </button>
      </div>

      <transition name="pop">
        <section
          v-if="activePanel === 'account' && activePanelContent"
          class="account-menu"
          aria-label="Account menu"
        >
          <p v-if="activePanelContent.subtitle" class="account-menu__user">
            {{ activePanelContent.subtitle }}
          </p>
          <button
            v-for="item in activePanelContent.items"
            :key="item.label ?? item"
            type="button"
            class="account-menu__item"
            @click="handlePanelItem(item)"
          >
            {{ item.label ?? item }}
          </button>
        </section>
      </transition>
    </div>

    <transition name="sheet">
      <section v-if="activePanel === 'search'" class="search" aria-label="Search">
        <div class="search__inner">
          <form class="search__form" role="search" @submit.prevent="executeSearch">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="10.5" cy="10.5" r="6.5" />
              <path d="m15.5 15.5 5 5" />
            </svg>
            <input
              ref="searchInputRef"
              v-model="searchQuery"
              type="search"
              class="search__input"
              placeholder="Search"
              aria-label="Search products"
              autocomplete="off"
            />
            <button type="button" class="search__close" @click="closeActivePanel">Close</button>
          </form>

          <div v-if="searchQuery.trim()" class="search__results">
            <ul v-if="filteredProducts.length" class="search__list">
              <li v-for="product in filteredProducts" :key="product.id">
                <router-link
                  :to="{ name: 'product-details', params: { slug: product.slug } }"
                  class="search__hit"
                  @click="closeActivePanel"
                >
                  <span class="search__thumb"><img :src="product.image" alt="" /></span>
                  <span class="search__name">{{ product.name }}</span>
                  <span class="search__price">{{ product.price }}</span>
                </router-link>
              </li>
            </ul>
            <p v-else class="search__empty">No results for “{{ searchQuery.trim() }}”.</p>
          </div>
        </div>
      </section>
    </transition>

    <transition name="sheet">
      <nav v-if="mobileMenuOpen" id="mobile-menu" class="mobile-menu" aria-label="Menu">
        <div class="mobile-menu__main">
          <button v-for="link in mainNav" :key="link.label" type="button" @click="navigateLink(link)">
            {{ link.label }}
          </button>
        </div>
        <div class="mobile-menu__account">
          <button
            v-for="item in accountPanel.items"
            :key="item.label"
            type="button"
            @click="handlePanelItem(item)"
          >
            {{ item.label }}
          </button>
        </div>
      </nav>
    </transition>
  </header>

  <div
    v-if="activePanel === 'search' || activePanel === 'account' || mobileMenuOpen"
    class="header-scrim"
    :class="{ 'header-scrim--clear': activePanel === 'account' }"
    @click="closeNavOverlays"
  ></div>

  <teleport to="body">
    <transition name="fade">
      <div v-if="activePanel === 'cart'" class="cart-scrim" @click="closeActivePanel"></div>
    </transition>
    <transition name="drawer">
      <aside
        v-if="activePanel === 'cart' && activePanelContent"
        class="cart"
        role="dialog"
        aria-modal="true"
        aria-label="Cart"
      >
        <header class="cart__head">
          <h2 class="cart__title">
            Cart <span v-if="itemCount" class="cart__qty">({{ itemCount }})</span>
          </h2>
          <button type="button" class="cart__close" aria-label="Close cart" @click="closeActivePanel">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5l14 14M19 5 5 19" /></svg>
          </button>
        </header>

        <div class="cart__body">
          <ul v-if="activePanelContent.items.length" class="cart__list">
            <li v-for="item in activePanelContent.items" :key="item.id" class="line">
              <router-link
                :to="{ name: 'product-details', params: { slug: item.slug } }"
                class="line__media"
                @click="closeActivePanel"
              >
                <img :src="item.image" :alt="item.name" />
              </router-link>
              <div class="line__info">
                <div class="line__top">
                  <h3 class="line__name">{{ item.name }}</h3>
                  <span class="line__price">{{ formatPrice(item.unitPrice * item.quantity) }}</span>
                </div>
                <p class="line__variant">{{ variantLabel(item) }}</p>
                <div class="line__actions">
                  <div class="stepper" role="group" :aria-label="`Quantity of ${item.name}`">
                    <button type="button" aria-label="Decrease quantity" @click="updateQuantity(item.id, item.quantity - 1)">
                      −
                    </button>
                    <span aria-live="polite">{{ item.quantity }}</span>
                    <button type="button" aria-label="Increase quantity" @click="updateQuantity(item.id, item.quantity + 1)">
                      +
                    </button>
                  </div>
                  <button type="button" class="line__remove" @click="removeItem(item.id)">Remove</button>
                </div>
              </div>
            </li>
          </ul>

          <div v-else class="cart__empty">
            <p class="cart__empty-title">Your cart is empty</p>
                        <button type="button" class="ff-btn ff-btn--ghost" @click="navigateLink({ target: 'shop' })">
              Shop now
            </button>
          </div>
        </div>

        <footer v-if="activePanelContent.items.length" class="cart__foot">
          <div class="cart__subtotal">
            <span>Subtotal</span>
            <strong>{{ subtotalLabel }}</strong>
          </div>
          <p class="cart__note">Delivery is added at checkout. You pay when your order arrives.</p>
          <button type="button" class="ff-btn ff-btn--block" @click="openCheckout">Checkout</button>
        </footer>
      </aside>
    </transition>
  </teleport>
</template>

<style scoped>
.notice {
  margin: 0;
  padding: 0.5rem var(--gutter);
  background: #000;
  color: #fff;
  font-size: 0.8125rem;
  font-weight: 500;
  text-align: center;
}

/* The bar floats over the page as a white card. */
.header {
  position: sticky;
  top: 0.75rem;
  z-index: 60;
  margin: 0.75rem var(--gutter) 0;
  background: var(--paper);
  border-radius: var(--radius);
  box-shadow:
    0 0 0 1px rgba(0, 0, 0, 0.06),
    0 10px 30px -18px rgba(0, 0, 0, 0.35);
}

.header__bar {
  position: relative;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 1.5rem;
  height: var(--header-h);
  padding: 0 1.25rem;
}

.header__brand {
  justify-self: start;
}

.header__tools {
  justify-self: end;
}

.header__brand {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0;
  border: 0;
  background: none;
  color: var(--ink);
}

.header__mark {
  height: 1.75rem;
  width: auto;
}

.header__wordmark {
  font-weight: 600;
  font-size: 1.0625rem;
  letter-spacing: -0.03em;
}

.header__nav {
  display: flex;
  gap: clamp(1.25rem, 2.4vw, 2.25rem);
}

.header__nav-link {
  position: relative;
  padding: 0.5rem 0;
  border: 0;
  background: none;
  font-size: 0.9375rem;
  font-weight: 500;
}

.header__nav-link::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0.2rem;
  height: 1.5px;
  background: currentColor;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.25s var(--ease);
}

.header__nav-link:hover::after,
.header__nav-link:focus-visible::after {
  transform: scaleX(1);
}

.header__tools {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-right: -0.625rem;
}

.header__tool,
.header__menu-btn {
  position: relative;
  display: grid;
  place-items: center;
  width: 2.75rem;
  height: 2.75rem;
  padding: 0;
  border: 0;
  background: none;
  color: var(--ink);
}

.header__tool svg,
.header__menu-btn svg,
.cart__close svg,
.search__form svg {
  width: 1.375rem;
  height: 1.375rem;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.5;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.header__tool:hover,
.header__menu-btn:hover {
  color: var(--slate-deep);
}

.header__menu-btn {
  display: none;
  margin-left: -0.625rem;
}

.header__count {
  position: absolute;
  top: 0.3rem;
  right: 0.15rem;
  min-width: 1.125rem;
  height: 1.125rem;
  padding: 0 0.25rem;
  display: grid;
  place-items: center;
  background: var(--ink);
  color: var(--paper);
  border-radius: 999px;
  font-size: 0.6875rem;
  font-weight: 600;
  line-height: 1;
}

/* Account dropdown */
.account-menu {
  position: absolute;
  top: calc(100% + 1px);
  top: calc(100% + 0.5rem);
  right: 0;
  width: 15rem;
  padding: 0.5rem 0;
  background: var(--paper);
  border-radius: var(--radius);
  box-shadow:
    0 0 0 1px rgba(0, 0, 0, 0.06),
    0 18px 40px -18px rgba(0, 0, 0, 0.4);
  z-index: 2;
}

.account-menu__user {
  padding: 0.6rem 1.25rem 0.7rem;
  margin-bottom: 0.25rem;
  border-bottom: 1px solid var(--line);
  color: var(--ink-soft);
  font-size: 0.875rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.account-menu__item {
  display: block;
  width: 100%;
  padding: 0.65rem 1.25rem;
  border: 0;
  background: none;
  text-align: left;
  font-size: 0.9375rem;
}

.account-menu__item:hover {
  background: var(--mist);
}

/* Search sheet */
.search {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 0.5rem;
  background: var(--paper);
  border-radius: var(--radius);
  box-shadow:
    0 0 0 1px rgba(0, 0, 0, 0.06),
    0 18px 40px -18px rgba(0, 0, 0, 0.4);
  max-height: calc(100vh - var(--header-h) - 5rem);
  overflow-y: auto;
}

.search__inner {
  padding: 0 1.25rem;
}

.search__form {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  height: 4rem;
}

.search__form svg {
  flex: none;
  color: var(--ink-soft);
}

.search__input {
  flex: 1;
  min-width: 0;
  height: 100%;
  border: 0;
  background: none;
  font-size: 1.0625rem;
}

.search__input:focus {
  outline: none;
}

.search__input::placeholder {
  color: #8a989e;
}

.search__input::-webkit-search-cancel-button {
  display: none;
}

.search__close {
  border: 0;
  background: none;
  padding: 0.5rem 0;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: underline;
  text-underline-offset: 0.25em;
}

.search__results {
  padding: 0.25rem 0 1.5rem;
  border-top: 1px solid var(--line);
}

.search__list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.search__hit {
  display: grid;
  grid-template-columns: 3.5rem 1fr auto;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--line);
  text-decoration: none;
}

.search__hit:hover .search__name {
  text-decoration: underline;
  text-underline-offset: 0.25em;
}

.search__thumb {
  aspect-ratio: 1;
  border-radius: 4px;
  background: var(--mist);
  overflow: hidden;
}

.search__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.search__name {
  font-weight: 600;
}

.search__price {
  color: var(--ink-soft);
  font-variant-numeric: tabular-nums;
}

.search__empty {
  padding: 1.5rem 0 0.5rem;
  color: var(--ink-soft);
}

.header-scrim {
  position: fixed;
  inset: 0;
  z-index: 50;
  background: rgba(0, 0, 0, 0.35);
}

.header-scrim--clear {
  background: transparent;
}

/* Mobile menu */
.mobile-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 0.5rem;
  max-height: calc(100dvh - var(--header-h) - 5rem);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1rem 1.25rem 1.25rem;
  background: var(--paper);
  border-radius: var(--radius);
  box-shadow:
    0 0 0 1px rgba(0, 0, 0, 0.06),
    0 18px 40px -18px rgba(0, 0, 0, 0.4);
}

.mobile-menu button {
  display: block;
  width: 100%;
  border: 0;
  background: none;
  text-align: left;
}

.mobile-menu__main button {
  padding: 0.5rem 0;
  font-weight: 500;
  font-size: 1.5rem;
  letter-spacing: -0.03em;
  line-height: 1.15;
}

.mobile-menu__account {
  padding-top: 1rem;
  border-top: 1px solid var(--line);
}

.mobile-menu__account button {
  padding: 0.7rem 0;
  font-size: 1rem;
  font-weight: 500;
}

/* Cart drawer */
.cart-scrim {
  position: fixed;
  inset: 0;
  z-index: 90;
  background: rgba(0, 0, 0, 0.45);
}

.cart {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  z-index: 100;
  width: min(27rem, 100vw);
  display: flex;
  flex-direction: column;
  background: var(--paper);
  color: var(--ink);
  font-family: var(--font);
}

.cart__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: var(--header-h);
  padding: 0 0.75rem 0 1.5rem;
  border-bottom: 1px solid var(--line);
  flex: none;
}

.cart__title {
  font-weight: 600;
  font-size: 1.0625rem;
  letter-spacing: -0.02em;
}

.cart__qty {
  font-stretch: normal;
  font-weight: 400;
  color: var(--ink-soft);
}

.cart__close {
  display: grid;
  place-items: center;
  width: 2.75rem;
  height: 2.75rem;
  border: 0;
  background: none;
}

.cart__body {
  flex: 1;
  overflow-y: auto;
  padding: 0 1.5rem;
}

.cart__list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.line {
  display: grid;
  grid-template-columns: 5.5rem 1fr;
  gap: 1rem;
  padding: 1.25rem 0;
  border-bottom: 1px solid var(--line);
}

.line__media {
  aspect-ratio: 1;
  border-radius: var(--radius);
  background: var(--mist);
  overflow: hidden;
}

.line__media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.line__info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.line__top {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.line__name {
  font-size: 1rem;
  font-weight: 600;
}

.line__price {
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.line__variant {
  margin-top: 0.15rem;
  color: var(--ink-soft);
  font-size: 0.875rem;
}

.line__actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  padding-top: 0.75rem;
}

.stepper {
  display: inline-grid;
  grid-template-columns: 2.25rem 2rem 2.25rem;
  align-items: center;
  height: 2.25rem;
  border: 1px solid var(--line-strong);
  border-radius: 999px;
  overflow: hidden;
  text-align: center;
  font-variant-numeric: tabular-nums;
}

.stepper button {
  height: 100%;
  border: 0;
  background: none;
  font-size: 1.05rem;
  line-height: 1;
}

.stepper button:hover {
  background: var(--mist);
}

.line__remove {
  border: 0;
  background: none;
  padding: 0.4rem 0;
  color: var(--ink-soft);
  font-size: 0.875rem;
  text-decoration: underline;
  text-underline-offset: 0.25em;
}

.line__remove:hover {
  color: var(--ink);
}

.cart__empty {
  display: grid;
  justify-items: start;
  gap: 0.5rem;
  padding: 2.5rem 0;
  color: var(--ink-soft);
}

.cart__empty-title {
  color: var(--ink);
  font-size: 1.125rem;
  font-weight: 600;
}

.cart__empty .ff-btn {
  margin-top: 1rem;
}

.cart__foot {
  flex: none;
  padding: 1.25rem 1.5rem 1.5rem;
  border-top: 1px solid var(--line);
}

.cart__subtotal {
  display: flex;
  justify-content: space-between;
  font-size: 1.0625rem;
}

.cart__subtotal strong {
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.cart__note {
  margin: 0.4rem 0 1.1rem;
  color: var(--ink-soft);
  font-size: 0.875rem;
}

/* Transitions: each one answers something the shopper just did */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.drawer-enter-active,
.drawer-leave-active {
  transition: transform 0.35s var(--ease);
}

.drawer-enter-from,
.drawer-leave-to {
  transform: translateX(100%);
}

.sheet-enter-active,
.sheet-leave-active,
.pop-enter-active,
.pop-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.25s var(--ease);
}

.sheet-enter-from,
.sheet-leave-to,
.pop-enter-from,
.pop-leave-to {
  opacity: 0;
  transform: translateY(-0.5rem);
}

@media (max-width: 760px) {
  .header {
    top: 0.5rem;
    margin-top: 0.5rem;
  }

  .header__bar {
    gap: 0.5rem;
    padding: 0 0.75rem;
  }

  .header__brand {
    justify-self: center;
  }

  .header__menu-btn {
    justify-self: start;
  }

  .header__menu-btn {
    display: grid;
  }

  .header__nav,
  .header__tool--account,
  .account-menu {
    display: none;
  }

  .header__tools {
    justify-content: flex-end;
  }

  .header__mark {
    height: 1.5rem;
  }
}
</style>
