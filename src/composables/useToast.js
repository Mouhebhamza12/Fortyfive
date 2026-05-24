import { reactive } from "vue";

const DEFAULT_DURATION = 4200;
const MAX_TOASTS = 4;

const state = reactive({
  items: [],
});

let nextId = 1;

const removeToast = (id) => {
  const index = state.items.findIndex((toast) => toast.id === id);
  if (index === -1) {
    return;
  }

  state.items.splice(index, 1);
};

const push = ({ type = "info", message, duration = DEFAULT_DURATION }) => {
  const text = String(message || "").trim();
  if (!text) {
    return null;
  }

  const id = nextId++;
  const toast = { id, type, message: text };

  state.items.unshift(toast);

  if (state.items.length > MAX_TOASTS) {
    state.items.splice(MAX_TOASTS);
  }

  if (duration > 0) {
    window.setTimeout(() => removeToast(id), duration);
  }

  return id;
};

export const useToast = () => ({
  toasts: state.items,
  push,
  success: (message, duration) => push({ type: "success", message, duration }),
  error: (message, duration) => push({ type: "error", message, duration }),
  info: (message, duration) => push({ type: "info", message, duration }),
  dismiss: removeToast,
});
