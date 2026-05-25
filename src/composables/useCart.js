import { computed, reactive, ref, watch } from "vue";
import { useToast } from "./useToast";

const { info: toastInfo } = useToast();

/** Incremented when add-to-cart should open the header cart drawer. */
const openCartRequest = ref(0);

const STORAGE_KEY = "ye-store-cart";

const state = reactive({
  items: [],
  ready: false,
});

const parsePrice = (price) => {
  const numeric = Number.parseInt(String(price).replace(/[^\d]/g, ""), 10);
  return Number.isFinite(numeric) ? numeric : 0;
};

const formatPrice = (value) => `${value} DA`;

const loadCart = () => {
  if (state.ready || typeof window === "undefined") {
    state.ready = true;
    return;
  }

  try {
    const saved = window.localStorage.getItem(STORAGE_KEY);
    state.items = saved ? JSON.parse(saved) : [];
  } catch {
    state.items = [];
  } finally {
    state.ready = true;
  }
};

if (typeof window !== "undefined") {
  loadCart();

  watch(
    () => state.items,
    (items) => {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    },
    { deep: true }
  );
}

const itemCount = computed(() =>
  state.items.reduce((total, item) => total + item.quantity, 0)
);

const subtotal = computed(() =>
  state.items.reduce((total, item) => total + item.unitPrice * item.quantity, 0)
);

const subtotalLabel = computed(() => formatPrice(subtotal.value));

const addItem = ({
  slug,
  name,
  price,
  image,
  size,
  color,
  quantity = 1,
  openDrawer = true,
}) => {
  loadCart();

  const id = `${slug}-${color}-${size}`;
  const existingItem = state.items.find((item) => item.id === id);

  if (existingItem) {
    existingItem.quantity += quantity;
    if (openDrawer) {
      openCartRequest.value += 1;
    }
    return;
  }

  state.items.push({
    id,
    slug,
    name,
    priceLabel: price,
    unitPrice: parsePrice(price),
    image,
    size,
    color,
    quantity,
  });

  if (openDrawer) {
    openCartRequest.value += 1;
  }
};

const removeItem = (id) => {
  const removed = state.items.find((item) => item.id === id);
  state.items = state.items.filter((item) => item.id !== id);

  if (removed) {
    toastInfo("Removed from cart");
  }
};

const updateQuantity = (id, quantity) => {
  if (quantity <= 0) {
    removeItem(id);
    return;
  }

  const item = state.items.find((entry) => entry.id === id);
  if (!item) {
    return;
  }

  item.quantity = quantity;
};

const clearCart = () => {
  state.items = [];
};

export const useCart = () => ({
  cartItems: state,
  itemCount,
  subtotal,
  subtotalLabel,
  openCartRequest,
  addItem,
  removeItem,
  updateQuantity,
  clearCart,
  formatPrice,
});
