<script setup>
import { computed, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import SiteHeader from "../components/SiteHeader.vue";
import { useCart } from "../composables/useCart";

const router = useRouter();
const { cartItems, itemCount, subtotalLabel, clearCart, formatPrice } = useCart();

const navLinks = [
  { label: "Home", route: { name: "home" } },
  { label: "Shop", route: { name: "home", hash: "#shop" } },
];

const deliveryForm = reactive({
  fullName: "",
  phone: "",
  city: "",
  address: "",
  note: "",
});

const orderPlaced = ref(false);

const checkoutItems = computed(() => cartItems.items);
const canSubmit = computed(
  () =>
    checkoutItems.value.length > 0 &&
    deliveryForm.fullName.trim() &&
    deliveryForm.phone.trim() &&
    deliveryForm.city.trim() &&
    deliveryForm.address.trim()
);

const placeOrder = () => {
  if (!canSubmit.value) {
    return;
  }

  clearCart();
  orderPlaced.value = true;
  window.scrollTo({ top: 0, behavior: "smooth" });
};

const continueShopping = () => {
  router.push({ name: "home", hash: "#shop" });
};
</script>

<template>
  <main class="checkout-shell">
    <SiteHeader :nav-links="navLinks" :visible="true" />

    <section class="checkout-layout">
      <div class="checkout-main">
        <p class="checkout-eyebrow">Checkout</p>
        <h1>Delivery details</h1>
        <p class="checkout-intro">
          There is no online payment on the website. You pay when the order arrives.
        </p>

        <div v-if="orderPlaced" class="checkout-success">
          <h2>Order request sent</h2>
          <p>
            We saved your delivery details. Payment happens when you receive the product.
          </p>
          <button type="button" class="checkout-btn checkout-btn--primary" @click="continueShopping">
            Continue Shopping
          </button>
        </div>

        <form v-else class="checkout-form" @submit.prevent="placeOrder">
          <label class="checkout-field">
            <span>Full name</span>
            <input v-model="deliveryForm.fullName" type="text" autocomplete="name" />
          </label>
          <label class="checkout-field">
            <span>Phone number</span>
            <input v-model="deliveryForm.phone" type="tel" autocomplete="tel" />
          </label>
          <label class="checkout-field">
            <span>City</span>
            <input v-model="deliveryForm.city" type="text" autocomplete="address-level2" />
          </label>
          <label class="checkout-field">
            <span>Address</span>
            <textarea v-model="deliveryForm.address" rows="4" autocomplete="street-address"></textarea>
          </label>
          <label class="checkout-field">
            <span>Order note</span>
            <textarea v-model="deliveryForm.note" rows="3" placeholder="Building, floor, landmark, sizing note..." />
          </label>

          <div class="checkout-notice">
            <strong>Cash on delivery only.</strong>
            <p>No card payment is required on the site. We confirm the order, ship it, then you pay on delivery.</p>
          </div>

          <button
            type="submit"
            class="checkout-btn checkout-btn--primary"
            :disabled="!canSubmit"
          >
            Confirm Order
          </button>
        </form>
      </div>

      <aside class="checkout-summary">
        <div class="checkout-summary-card">
          <div class="checkout-summary-head">
            <p>Order summary</p>
            <span>{{ itemCount }} item<span v-if="itemCount !== 1">s</span></span>
          </div>

          <div v-if="checkoutItems.length" class="checkout-items">
            <article v-for="item in checkoutItems" :key="item.id" class="checkout-item">
              <img :src="item.image" :alt="item.name" class="checkout-item-image" />
              <div class="checkout-item-copy">
                <h2>{{ item.name }}</h2>
                <p>{{ item.color }} / {{ item.size }}</p>
                <p>Qty {{ item.quantity }}</p>
              </div>
              <strong>{{ formatPrice(item.unitPrice * item.quantity) }}</strong>
            </article>
          </div>

          <p v-else class="checkout-empty">Your cart is empty.</p>

          <div class="checkout-total">
            <span>Subtotal</span>
            <strong>{{ subtotalLabel }}</strong>
          </div>
          <p class="checkout-shipping">Shipping cost is confirmed with the order.</p>
        </div>
      </aside>
    </section>
  </main>
</template>

<style scoped>
.checkout-shell {
  min-height: 100vh;
  background: #050505;
}

.checkout-layout {
  min-height: calc(100vh - 5rem);
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
  gap: 2rem;
  padding: clamp(2rem, 4vw, 4rem);
  background:
    radial-gradient(circle at 12% 14%, rgba(123, 163, 181, 0.22), transparent 24%),
    linear-gradient(180deg, #efe5d6 0%, #e8dcc8 100%);
}

.checkout-main,
.checkout-summary-card {
  border: 1px solid rgba(23, 33, 38, 0.12);
  background: rgba(255, 252, 246, 0.58);
  box-shadow: 0 24px 50px rgba(39, 52, 59, 0.08);
}

.checkout-main {
  padding: clamp(1.4rem, 3vw, 2.4rem);
}

.checkout-eyebrow,
.checkout-field span,
.checkout-summary-head p,
.checkout-summary-head span {
  color: rgba(79, 105, 115, 0.92);
  font-family: "Sora", sans-serif;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.checkout-main h1,
.checkout-success h2 {
  margin: 0;
  color: #172126;
  font-family: "Helvetica Neue", sans-serif;
  font-size: clamp(2.4rem, 5vw, 4rem);
  font-weight: 900;
  line-height: 0.92;
  text-transform: uppercase;
}

.checkout-intro,
.checkout-success p,
.checkout-notice p,
.checkout-shipping,
.checkout-empty,
.checkout-item-copy p {
  margin: 0;
  color: rgba(23, 33, 38, 0.76);
  font-family: "Sora", sans-serif;
  line-height: 1.7;
}

.checkout-intro {
  margin-top: 1rem;
  max-width: 36rem;
}

.checkout-form {
  display: grid;
  gap: 1rem;
  margin-top: 2rem;
}

.checkout-field {
  display: grid;
  gap: 0.5rem;
}

.checkout-field input,
.checkout-field textarea {
  width: 100%;
  border: 1px solid rgba(23, 33, 38, 0.16);
  background: rgba(255, 255, 255, 0.52);
  color: #172126;
  padding: 0.95rem 1rem;
  font: 500 0.94rem/1.5 "Sora", sans-serif;
  resize: vertical;
  outline: none;
}

.checkout-field input:focus,
.checkout-field textarea:focus {
  border-color: #7ba3b5;
}

.checkout-notice {
  display: grid;
  gap: 0.5rem;
  padding: 1rem;
  border: 1px solid rgba(123, 163, 181, 0.35);
  background: rgba(123, 163, 181, 0.12);
  color: #172126;
  font-family: "Sora", sans-serif;
}

.checkout-btn {
  min-height: 3.35rem;
  border: 1px solid #172126;
  background: transparent;
  color: #172126;
  font: 800 0.72rem/1 "Helvetica Neue", sans-serif;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
}

.checkout-btn--primary {
  background: #172126;
  color: #f4ead6;
  box-shadow: 0 0.45rem 0 rgba(123, 163, 181, 0.72);
}

.checkout-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
  box-shadow: none;
}

.checkout-success {
  display: grid;
  gap: 1rem;
  margin-top: 2rem;
}

.checkout-summary-card {
  padding: 1.4rem;
  position: sticky;
  top: 7rem;
}

.checkout-summary-head,
.checkout-total,
.checkout-item {
  display: grid;
  gap: 0.5rem;
}

.checkout-summary-head,
.checkout-total {
  grid-template-columns: 1fr auto;
  align-items: center;
}

.checkout-items {
  display: grid;
  gap: 1rem;
  margin: 1.2rem 0;
}

.checkout-item {
  grid-template-columns: 4.75rem 1fr auto;
  align-items: center;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(23, 33, 38, 0.1);
}

.checkout-item-image {
  width: 4.75rem;
  height: 4.75rem;
  object-fit: contain;
}

.checkout-item-copy h2,
.checkout-total strong,
.checkout-item strong {
  margin: 0;
  color: #172126;
  font-family: "Sora", sans-serif;
}

.checkout-item-copy h2 {
  font-size: 0.92rem;
}

.checkout-total {
  padding-top: 0.8rem;
  border-top: 1px solid rgba(23, 33, 38, 0.12);
}

.checkout-shipping {
  margin-top: 0.65rem;
  font-size: 0.84rem;
}

@media (max-width: 900px) {
  .checkout-layout {
    grid-template-columns: 1fr;
    padding: 1rem 0.85rem 2rem;
  }

  .checkout-main,
  .checkout-summary-card {
    padding: 1rem;
  }

  .checkout-summary-card {
    position: static;
  }

  .checkout-item {
    grid-template-columns: 4rem 1fr;
  }

  .checkout-item strong {
    grid-column: 2;
  }
}
</style>
