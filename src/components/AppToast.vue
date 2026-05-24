<script setup>
import { useToast } from "../composables/useToast";

const { toasts, dismiss } = useToast();
</script>

<template>
  <div class="toast-stack" aria-live="polite" aria-relevant="additions text">
    <TransitionGroup name="toast">
      <article
        v-for="toast in toasts"
        :key="toast.id"
        class="toast"
        :class="`toast--${toast.type}`"
        role="status"
      >
        <p class="toast-message">{{ toast.message }}</p>
        <button
          type="button"
          class="toast-close"
          aria-label="Dismiss"
          @click="dismiss(toast.id)"
        >
          ×
        </button>
      </article>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-stack {
  position: fixed;
  top: 5.5rem;
  right: clamp(0.75rem, 2vw, 1.5rem);
  z-index: 12000;
  display: grid;
  gap: 0.55rem;
  width: min(22rem, calc(100vw - 1.5rem));
  pointer-events: none;
}

.toast {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.75rem;
  align-items: start;
  padding: 0.85rem 0.9rem;
  border: 1px solid rgba(23, 33, 38, 0.12);
  background: rgba(254, 251, 246, 0.97);
  color: #172126;
  box-shadow:
    0 12px 28px rgba(23, 33, 38, 0.12),
    0 1px 0 rgba(255, 255, 255, 0.85) inset;
  pointer-events: auto;
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}

.toast--success {
  border-left: 3px solid #4a7c59;
}

.toast--error {
  border-left: 3px solid #9b3b3b;
}

.toast--info {
  border-left: 3px solid #7ba3b5;
}

.toast-message {
  margin: 0;
  font: 500 0.88rem/1.45 "Sora", sans-serif;
}

.toast-close {
  border: 0;
  background: transparent;
  color: rgba(23, 33, 38, 0.55);
  font-size: 1.15rem;
  line-height: 1;
  cursor: pointer;
  padding: 0;
}

.toast-close:hover {
  color: #172126;
}

.toast-enter-active,
.toast-leave-active {
  transition:
    opacity 0.22s ease,
    transform 0.22s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

.toast-move {
  transition: transform 0.22s ease;
}

@media (max-width: 640px) {
  .toast-stack {
    top: auto;
    bottom: 1rem;
    right: 0.75rem;
    left: 0.75rem;
    width: auto;
  }
}
</style>
