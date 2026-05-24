<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { apiFetch } from "../lib/api";

const router = useRouter();
const route = useRoute();
const isSidebarCollapsed = ref(false);
const isMobileNavOpen = ref(false);

const isCompactSidebar = computed(
  () => isSidebarCollapsed.value && !isMobileNavOpen.value,
);

const closeMobileNav = () => {
  isMobileNavOpen.value = false;
};

watch(() => route.path, closeMobileNav);

const navItems = [
  {
    name: "Dashboard",
    path: "/management-portal-45",
    icon: "M3 12l2-2 7-7 7 7 2 2M5 10v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3m-6 0v-4a1 1 0 011-1h2a1 1 0 011 1v4m-6 0h6",
    description: "Store health and activity",
  },
  {
    name: "Products",
    path: "/management-portal-45/products",
    icon: "M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4",
    description: "Catalog and stock",
  },
  {
    name: "Orders",
    path: "/management-portal-45/orders",
    icon: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01",
    description: "Fulfillment and tracking",
  },
  {
    name: "Feedback",
    path: "/management-portal-45/feedback",
    icon: "M8 10h8M8 14h5m-9 4h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z",
    description: "Customer messages",
  },
];

const activeItem = computed(
  () =>
    navItems.find((item) =>
      item.path === "/management-portal-45"
        ? route.path === item.path
        : route.path.startsWith(item.path),
    ) || navItems[0],
);

const isActive = (path) => {
  if (path === "/management-portal-45") {
    return route.path === path;
  }

  return route.path.startsWith(path);
};

const handleLogout = async () => {
  try {
    await apiFetch("/auth/logout", { method: "POST" });
  } catch {}

  localStorage.removeItem("is_admin");
  window.dispatchEvent(new Event("auth-changed"));
  router.push("/sign-in");
};

onMounted(() => {
  document.body.classList.add("admin-route");
});

onUnmounted(() => {
  document.body.classList.remove("admin-route");
  document.body.classList.remove("admin-nav-open");
});

watch(isMobileNavOpen, (open) => {
  document.body.classList.toggle("admin-nav-open", open);
});
</script>

<template>
  <div
    class="admin-layout"
    :class="{
      'sidebar-collapsed': isCompactSidebar,
      'mobile-nav-open': isMobileNavOpen,
    }"
  >
    <button
      type="button"
      class="sidebar-backdrop"
      :class="{ visible: isMobileNavOpen }"
      aria-label="Close navigation"
      @click="closeMobileNav"
    />

    <aside
      class="admin-sidebar"
      :class="{
        collapsed: isCompactSidebar,
        'mobile-open': isMobileNavOpen,
      }"
    >
      <div class="sidebar-shell">
        <div class="sidebar-header">
          <router-link to="/management-portal-45" class="brand">
            <span class="brand-mark">
              <img
                src="../../assets/images/45svg.png"
                alt="45"
                class="brand-logo"
              />
            </span>
            <div v-if="!isCompactSidebar" class="brand-copy">
              <span class="brand-title">45 Admin</span>
              <span class="brand-subtitle">Operations Console</span>
            </div>
          </router-link>

          <button
            type="button"
            class="collapse-btn"
            @click="isSidebarCollapsed = !isSidebarCollapsed"
            :aria-label="
              isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'
            "
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M4 7h16M4 12h16M4 17h16"
              />
            </svg>
          </button>
        </div>

        <section v-if="!isCompactSidebar" class="sidebar-context">
          <p class="context-label">Current section</p>
          <h2 class="context-title">{{ activeItem.name }}</h2>
          <p class="context-description">{{ activeItem.description }}</p>
        </section>

        <nav
          id="admin-sidebar-nav"
          class="sidebar-nav"
          aria-label="Admin navigation"
        >
          <router-link
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            class="nav-item"
            :class="{ active: isActive(item.path) }"
            @click="closeMobileNav"
          >
            <span class="nav-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="nav-icon">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  :d="item.icon"
                />
              </svg>
            </span>
            <div v-if="!isCompactSidebar" class="nav-copy">
              <span class="nav-label">{{ item.name }}</span>
              <span class="nav-description">{{ item.description }}</span>
            </div>
          </router-link>
        </nav>

        <div class="sidebar-footer">
          <router-link to="/" class="footer-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
              />
            </svg>
            <span v-if="!isCompactSidebar">View storefront</span>
          </router-link>

          <button type="button" class="footer-link logout-link" @click="handleLogout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>
            <span v-if="!isCompactSidebar">Sign out</span>
          </button>
        </div>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div class="topbar-start">
          <button
            type="button"
            class="mobile-menu-btn"
            :aria-expanded="isMobileNavOpen"
            aria-controls="admin-sidebar-nav"
            @click="isMobileNavOpen = !isMobileNavOpen"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M4 7h16M4 12h16M4 17h16"
              />
            </svg>
          </button>
          <div>
            <p class="topbar-label">Management workspace</p>
            <h1 class="topbar-title">{{ activeItem.name }}</h1>
          </div>
        </div>
        <div class="topbar-badge">Live store data</div>
      </header>

      <div class="admin-content">
        <router-view />
      </div>
    </main>
  </div>
</template>

<style scoped>
.admin-layout {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 288px minmax(0, 1fr);
  background:
    radial-gradient(circle at top left, rgba(123, 163, 181, 0.12), transparent 28%),
    linear-gradient(180deg, #121212 0%, #0d0d0d 100%);
  color: #f3ead8;
  position: relative;
  isolation: isolate;
  z-index: 10001;
}

.admin-layout.sidebar-collapsed {
  grid-template-columns: 88px minmax(0, 1fr);
}

.admin-sidebar {
  border-right: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(15, 15, 15, 0.86);
  backdrop-filter: blur(12px);
  position: relative;
  z-index: 10002;
}

.sidebar-shell {
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 1.25rem;
  gap: 1.25rem;
}

.sidebar-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  text-decoration: none;
  color: inherit;
  min-width: 0;
}

.brand-mark {
  width: 3rem;
  height: 3rem;
  border-radius: 0.9rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(243, 234, 216, 0.06);
  border: 1px solid rgba(243, 234, 216, 0.08);
  flex-shrink: 0;
}

.brand-logo {
  width: 1.5rem;
  height: 1.5rem;
  object-fit: contain;
}

.brand-copy {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.brand-title {
  font-size: 1rem;
  font-weight: 700;
  color: #f7efdf;
}

.brand-subtitle {
  font-size: 0.78rem;
  color: #9f9a8f;
}

.collapse-btn {
  width: 2.5rem;
  height: 2.5rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 0.8rem;
  background: rgba(255, 255, 255, 0.03);
  color: #c6c0b4;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.collapse-btn svg,
.footer-link svg {
  width: 1.1rem;
  height: 1.1rem;
}

.sidebar-context {
  padding: 1rem;
  border-radius: 1rem;
  background: linear-gradient(180deg, rgba(123, 163, 181, 0.18), rgba(123, 163, 181, 0.06));
  border: 1px solid rgba(123, 163, 181, 0.16);
}

.context-label {
  margin: 0 0 0.35rem;
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #a7a9ab;
}

.context-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: #f7efdf;
}

.context-description {
  margin: 0.35rem 0 0;
  font-size: 0.86rem;
  line-height: 1.45;
  color: #c7c0b2;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  flex: 1;
  align-items: stretch;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.85rem 0.9rem;
  border-radius: 0.95rem;
  color: #a6a29a;
  text-decoration: none;
  border: 1px solid transparent;
  transition: 0.2s ease;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.03);
  border-color: rgba(255, 255, 255, 0.04);
  color: #f1e8d7;
}

.nav-item.active {
  background: rgba(243, 234, 216, 0.06);
  border-color: rgba(123, 163, 181, 0.22);
  color: #f7efdf;
}

.nav-icon-wrap {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.8rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.03);
  flex-shrink: 0;
}

.nav-item.active .nav-icon-wrap {
  background: rgba(123, 163, 181, 0.18);
  color: #8bb9cc;
}

.nav-icon {
  width: 1.15rem;
  height: 1.15rem;
}

.nav-copy {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.nav-label {
  font-size: 0.95rem;
  font-weight: 600;
}

.nav-description {
  font-size: 0.78rem;
  color: #7f7a71;
}

.sidebar-footer {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.footer-link {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  width: 100%;
  padding: 0.85rem 0.9rem;
  border-radius: 0.85rem;
  color: #b8b3a7;
  text-decoration: none;
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  font: inherit;
}

.footer-link:hover {
  background: rgba(255, 255, 255, 0.03);
  color: #f4ead6;
}

.logout-link {
  color: #e39f99;
}

.logout-link:hover {
  background: rgba(160, 56, 44, 0.12);
  color: #f1b1ab;
}

/* Collapsed: icon-only tiles — highlight stays on the icon, not the row */
.admin-sidebar.collapsed .sidebar-shell {
  padding-left: 0.9rem;
  padding-right: 0.9rem;
}

.admin-sidebar.collapsed .sidebar-header {
  flex-direction: column;
  align-items: center;
}

.admin-sidebar.collapsed .brand {
  justify-content: center;
}

.admin-sidebar.collapsed .sidebar-nav {
  align-items: center;
}

.admin-sidebar.collapsed .nav-item,
.admin-sidebar.collapsed .footer-link {
  width: 2.5rem;
  height: 2.5rem;
  padding: 0;
  justify-content: center;
  gap: 0;
}

.admin-sidebar.collapsed .nav-item {
  background: transparent;
  border-color: transparent;
}

.admin-sidebar.collapsed .nav-item:hover {
  background: transparent;
  border-color: transparent;
}

.admin-sidebar.collapsed .nav-item.active {
  background: transparent;
  border-color: transparent;
}

.admin-sidebar.collapsed .nav-item.active .nav-icon-wrap {
  background: rgba(123, 163, 181, 0.18);
  border: 1px solid rgba(123, 163, 181, 0.22);
  color: #8bb9cc;
}

.admin-sidebar.collapsed .footer-link {
  width: 2.5rem;
}

.admin-main {
  min-width: 0;
  position: relative;
  z-index: 10002;
}

.topbar-start {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  min-width: 0;
}

.mobile-menu-btn {
  display: none;
  width: 2.5rem;
  height: 2.5rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 0.8rem;
  background: rgba(255, 255, 255, 0.03);
  color: #c6c0b4;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.mobile-menu-btn svg {
  width: 1.1rem;
  height: 1.1rem;
}

.sidebar-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  border: 0;
  padding: 0;
  margin: 0;
  background: rgba(0, 0, 0, 0.55);
  z-index: 10009;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.24s ease;
  cursor: pointer;
}

.sidebar-backdrop.visible {
  opacity: 1;
  pointer-events: auto;
}

.admin-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.5rem 2rem 0;
}

.topbar-label {
  margin: 0 0 0.2rem;
  color: #8f8b82;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.topbar-title {
  margin: 0;
  font-size: 1.65rem;
  font-weight: 700;
  color: #f8efdf;
}

.topbar-badge {
  padding: 0.6rem 0.9rem;
  border-radius: 999px;
  border: 1px solid rgba(123, 163, 181, 0.22);
  background: rgba(123, 163, 181, 0.1);
  color: #9ec2d1;
  font-size: 0.82rem;
  font-weight: 600;
  white-space: nowrap;
}

.admin-content {
  padding: 1.5rem 2rem 2rem;
  max-width: 1480px;
  position: relative;
  z-index: 10003;
}

:global(body.admin-route > :not(#app)) {
  display: none !important;
}

@media (max-width: 768px) {
  .admin-layout {
    grid-template-columns: 1fr;
  }

  .admin-layout.sidebar-collapsed {
    grid-template-columns: 1fr;
  }

  .sidebar-backdrop {
    display: block;
  }

  .mobile-menu-btn {
    display: inline-flex;
  }

  .collapse-btn {
    display: none;
  }

  .admin-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: min(288px, 88vw);
    transform: translateX(-100%);
    transition: transform 0.24s ease;
    z-index: 10010;
  }

  .admin-sidebar.mobile-open {
    transform: translateX(0);
  }

  .admin-sidebar.collapsed {
    width: min(288px, 88vw);
  }

  .admin-sidebar.collapsed .sidebar-nav {
    align-items: stretch;
  }

  .admin-sidebar.collapsed .nav-item,
  .admin-sidebar.collapsed .footer-link {
    width: 100%;
    height: auto;
    padding: 0.85rem 0.9rem;
    gap: 0.85rem;
  }

  .admin-sidebar.collapsed .nav-item.active {
    background: rgba(243, 234, 216, 0.06);
    border-color: rgba(123, 163, 181, 0.22);
  }

  .admin-sidebar.collapsed .nav-item.active .nav-icon-wrap {
    border-color: transparent;
  }

  .sidebar-shell {
    padding: 1rem;
    overflow-y: auto;
  }

  .admin-topbar,
  .admin-content {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .topbar-title {
    font-size: 1.35rem;
  }

  .topbar-badge {
    display: none;
  }
}

:global(body.admin-nav-open) {
  overflow: hidden;
}
</style>
