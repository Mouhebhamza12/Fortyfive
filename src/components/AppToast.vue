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
  left: 50%;
  bottom: 1.25rem;
  z-index: 200;
  display: grid;
  gap: 0.5rem;
  width: min(26rem, calc(100vw - 2rem));
  transform: translateX(-50%);
  pointer-events: none;
}

.toast {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.8rem 0.6rem 0.8rem 1rem;
  background: var(--ink);
  color: var(--paper);
  border-radius: var(--radius);
  border-left: 3px solid #8c8c8c;
  font-family: var(--font);
  font-size: 0.9375rem;
  pointer-events: auto;
}

.toast--error {
  border-left-color: #e2705c;
}

.toast--success {
  border-left-color: #6fbf97;
}

.toast-message {
  margin: 0;
}

.toast-close {
  flex: none;
  width: 2rem;
  height: 2rem;
  border: 0;
  background: none;
  color: inherit;
  font-size: 1.25rem;
  line-height: 1;
}

.toast-close:focus-visible {
  outline-color: var(--paper);
}

.toast-enter-active,
.toast-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.25s var(--ease);
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(0.5rem);
}
</style>
