<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { RouterLink, useRouter } from "vue-router";
import CheckoutOrderSummary from "../components/CheckoutOrderSummary.vue";
import SiteHeader from "../components/SiteHeader.vue";
import { apiFetch, authJsonHeaders } from "../lib/api";
import { useCart } from "../composables/useCart";
import { useCheckoutShipping } from "../composables/useCheckoutShipping";
import { useToast } from "../composables/useToast";

const router = useRouter();
const { cartItems, itemCount, subtotal, clearCart } = useCart();
const { error: toastError, success: toastSuccess } = useToast();

const {
  wilayas,
  bureaus,
  quote,
  loadingWilayas,
  loadingBureaus,
  loadingQuote,
  error: shippingError,
  loadWilayas,
  loadBureaus,
  fetchQuote,
} = useCheckoutShipping();

const navLinks = [
  { label: "Home", route: { name: "home" } },
  { label: "Shop", route: { name: "home", hash: "#shop" } },
];

const form = reactive({
  customer_name: "",
  customer_phone: "",
  customer_email: "",
  delivery_type: "home",
  wilaya_id: "",
  commune: "",
  shipping_address: "",
  delivery_bureau_id: "",
  notes: "",
});

const fieldErrors = reactive({});
const orderPlaced = ref(false);
const placedOrder = ref(null);
const isSubmitting = ref(false);

const checkoutItems = computed(() => cartItems.items);
const hasItems = computed(() => checkoutItems.value.length > 0);

const shippingFee = computed(() => Number(quote.value?.shipping_fee) || 0);
const showShipping = computed(() => Boolean(quote.value && form.wilaya_id));
const phonePattern = /^(\+213|0)(5|6|7)\d{8}$/;

const normalizePhone = (value) => value.replace(/[\s.-]/g, "");

const validate = () => {
  Object.keys(fieldErrors).forEach((key) => delete fieldErrors[key]);

  if (!form.customer_name.trim()) fieldErrors.customer_name = "Required";

  const phone = normalizePhone(form.customer_phone);
  if (!phone) fieldErrors.customer_phone = "Required";
  else if (!phonePattern.test(phone)) fieldErrors.customer_phone = "Invalid number";

  if (
    form.customer_email.trim() &&
    !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.customer_email.trim())
  ) {
    fieldErrors.customer_email = "Invalid email";
  }

  if (!form.wilaya_id) fieldErrors.wilaya_id = "Required";

  if (form.delivery_type === "home") {
    if (!form.commune.trim()) fieldErrors.commune = "Required";
    if (!form.shipping_address.trim()) fieldErrors.shipping_address = "Required";
  } else if (!form.delivery_bureau_id) {
    fieldErrors.delivery_bureau_id = "Required";
  }

  if (!quote.value) fieldErrors.shipping = "Select wilaya to continue";

  return Object.keys(fieldErrors).length === 0;
};

const canSubmit = computed(
  () =>
    hasItems.value &&
    form.customer_name.trim() &&
    form.customer_phone.trim() &&
    form.wilaya_id &&
    quote.value &&
    !isSubmitting.value &&
    (form.delivery_type === "home"
      ? form.commune.trim() && form.shipping_address.trim()
      : form.delivery_bureau_id)
);

const resetDeliveryFields = () => {
  form.commune = "";
  form.shipping_address = "";
  form.delivery_bureau_id = "";
};

watch(
  () => form.wilaya_id,
  async (wilayaId) => {
    resetDeliveryFields();
    if (!wilayaId) {
      quote.value = null;
      bureaus.value = [];
      return;
    }
    if (form.delivery_type === "bureau") await loadBureaus(wilayaId);
    await fetchQuote(wilayaId, form.delivery_type);
  }
);

watch(
  () => form.delivery_type,
  async (type) => {
    resetDeliveryFields();
    if (!form.wilaya_id) return;
    if (type === "bureau") await loadBureaus(form.wilaya_id);
    else bureaus.value = [];
    await fetchQuote(form.wilaya_id, type);
  }
);

const prefillProfile = async () => {
  try {
    const response = await apiFetch("/auth/me");
    if (!response.ok) return;
    const { user } = await response.json();
    if (!user) return;
    if (!form.customer_name.trim()) form.customer_name = user.name || "";
    if (!form.customer_phone.trim()) form.customer_phone = user.phone || "";
    if (!form.customer_email.trim()) form.customer_email = user.email || "";
    if (form.delivery_type === "home" && !form.shipping_address.trim()) {
      form.shipping_address = user.shipping_address || "";
      form.commune = user.city || "";
    }
    if (!form.notes.trim()) form.notes = user.delivery_note || "";
  } catch {
    /* guest */
  }
};

const placeOrder = async () => {
  if (!validate() || !canSubmit.value) return;

  isSubmitting.value = true;

  const payload = {
    customer_name: form.customer_name.trim(),
    customer_phone: normalizePhone(form.customer_phone),
    customer_email: form.customer_email.trim() || undefined,
    delivery_type: form.delivery_type,
    wilaya_id: Number(form.wilaya_id),
    notes: form.notes.trim() || undefined,
    items: checkoutItems.value.map((item) => ({
      slug: item.slug,
      name: item.name,
      price: item.unitPrice,
      quantity: item.quantity,
      size: item.size,
      color: item.color,
      image: item.image,
    })),
  };

  if (form.delivery_type === "home") {
    payload.commune = form.commune.trim();
    payload.shipping_address = form.shipping_address.trim();
  } else {
    payload.delivery_bureau_id = Number(form.delivery_bureau_id);
  }

  try {
    const response = await apiFetch("/orders", {
      method: "POST",
      headers: authJsonHeaders,
      body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      toastError(
        data.message ||
          (data.errors ? Object.values(data.errors).flat().join(" ") : null) ||
          "Could not place order."
      );
      return;
    }

    placedOrder.value = data.order;
    orderPlaced.value = true;
    clearCart();
    toastSuccess("Order confirmed.");
    window.scrollTo({ top: 0, behavior: "smooth" });
  } catch {
    toastError("Network error. Try again.");
  } finally {
    isSubmitting.value = false;
  }
};

const continueShopping = () => {
  router.push({ name: "home", hash: "#shop" });
};

onMounted(async () => {
  if (!hasItems.value) {
    router.replace({ name: "home", hash: "#shop" });
    return;
  }
  await Promise.all([loadWilayas(), prefillProfile()]);
});
</script>

<template>
  <main class="checkout">
    <SiteHeader :nav-links="navLinks" :visible="true" />

    <div v-if="orderPlaced && placedOrder" class="checkout__success-wrap">
      <div class="checkout__success">
        <p class="checkout__success-label">Order confirmed</p>
        <h1>{{ placedOrder.order_number }}</h1>
        <p class="checkout__success-total">
          {{ placedOrder.total_amount?.toLocaleString("fr-DZ") }} DA
        </p>
        <button type="button" class="checkout__cta" @click="continueShopping">
          Continue shopping
        </button>
      </div>
    </div>

    <div v-else class="checkout__body">
      <div class="checkout__container">
        <div class="checkout__grid">
          <section class="checkout__form-panel">
            <h1 class="checkout__title">Checkout</h1>

            <form class="checkout__form" novalidate @submit.prevent="placeOrder">
              <div class="checkout__section">
                <h2>Contact</h2>
                <div class="checkout__fields">
                  <label class="field" :class="{ 'field--err': fieldErrors.customer_name }">
                    <span>Full name *</span>
                    <input v-model="form.customer_name" type="text" autocomplete="name" />
                    <em v-if="fieldErrors.customer_name">{{ fieldErrors.customer_name }}</em>
                  </label>

                  <div class="field-row">
                    <label class="field" :class="{ 'field--err': fieldErrors.customer_phone }">
                      <span>Phone *</span>
                      <input
                        v-model="form.customer_phone"
                        type="tel"
                        inputmode="tel"
                        autocomplete="tel"
                        placeholder="05XX XX XX XX"
                      />
                      <em v-if="fieldErrors.customer_phone">{{ fieldErrors.customer_phone }}</em>
                    </label>

                    <label class="field" :class="{ 'field--err': fieldErrors.customer_email }">
                      <span>Email</span>
                      <input v-model="form.customer_email" type="email" autocomplete="email" />
                      <em v-if="fieldErrors.customer_email">{{ fieldErrors.customer_email }}</em>
                    </label>
                  </div>
                </div>
              </div>

              <div class="checkout__section">
                <h2>Delivery</h2>

                <div class="delivery-toggle" role="radiogroup" aria-label="Delivery type">
                  <label
                    class="delivery-toggle__btn"
                    :class="{ 'delivery-toggle__btn--on': form.delivery_type === 'home' }"
                  >
                    <input v-model="form.delivery_type" type="radio" value="home" />
                    Home
                  </label>
                  <label
                    class="delivery-toggle__btn"
                    :class="{ 'delivery-toggle__btn--on': form.delivery_type === 'bureau' }"
                  >
                    <input v-model="form.delivery_type" type="radio" value="bureau" />
                    Pickup point
                  </label>
                </div>

                <div class="checkout__fields">
                  <label class="field" :class="{ 'field--err': fieldErrors.wilaya_id }">
                    <span>Wilaya *</span>
                    <select v-model="form.wilaya_id" :disabled="loadingWilayas">
                      <option value="">Select wilaya</option>
                      <option v-for="w in wilayas" :key="w.id" :value="w.id">
                        {{ String(w.code).padStart(2, "0") }} - {{ w.name }}
                      </option>
                    </select>
                    <em v-if="fieldErrors.wilaya_id">{{ fieldErrors.wilaya_id }}</em>
                  </label>

                  <template v-if="form.delivery_type === 'home'">
                    <label class="field" :class="{ 'field--err': fieldErrors.commune }">
                      <span>Commune *</span>
                      <input v-model="form.commune" type="text" autocomplete="address-level2" />
                      <em v-if="fieldErrors.commune">{{ fieldErrors.commune }}</em>
                    </label>

                    <label class="field" :class="{ 'field--err': fieldErrors.shipping_address }">
                      <span>Address *</span>
                      <textarea
                        v-model="form.shipping_address"
                        rows="3"
                        autocomplete="street-address"
                      />
                      <em v-if="fieldErrors.shipping_address">{{ fieldErrors.shipping_address }}</em>
                    </label>
                  </template>

                  <template v-else>
                    <label class="field" :class="{ 'field--err': fieldErrors.delivery_bureau_id }">
                      <span>Pickup point *</span>
                      <select
                        v-model="form.delivery_bureau_id"
                        :disabled="!form.wilaya_id || loadingBureaus"
                      >
                        <option value="">
                          {{ loadingBureaus ? "Loading..." : "Select pickup point" }}
                        </option>
                        <option v-for="b in bureaus" :key="b.id" :value="b.id">
                          {{ b.name }} - {{ b.commune }}
                        </option>
                      </select>
                      <em v-if="fieldErrors.delivery_bureau_id">{{
                        fieldErrors.delivery_bureau_id
                      }}</em>
                    </label>
                  </template>

                  <label class="field">
                    <span>Note</span>
                    <input v-model="form.notes" type="text" placeholder="Optional" />
                  </label>
                </div>

                <p v-if="shippingError || fieldErrors.shipping" class="checkout__error">
                  {{ shippingError || fieldErrors.shipping }}
                </p>
              </div>

              <div class="checkout__actions">
                <button type="submit" class="checkout__cta" :disabled="!canSubmit">
                  {{ isSubmitting ? "Placing order..." : "Place order" }}
                </button>
                <RouterLink class="checkout__back" :to="{ name: 'home', hash: '#shop' }">
                  Return to shop
                </RouterLink>
              </div>
            </form>
          </section>

          <CheckoutOrderSummary
            :items="checkoutItems"
            :item-count="itemCount"
            :subtotal="subtotal"
            :shipping-fee="shippingFee"
            :show-shipping="showShipping"
            :loading-shipping="loadingQuote"
          />
        </div>
      </div>
    </div>
  </main>
</template>

<style scoped>
.checkout {
  min-height: 100vh;
  background: #050505;
}

.checkout__body {
  background:
    radial-gradient(circle at 12% 10%, rgba(123, 163, 181, 0.22), transparent 26%),
    linear-gradient(180deg, #efe5d6 0%, #e8dcc8 100%);
  min-height: calc(100vh - 5rem);
}

.checkout__container {
  width: min(1120px, 100%);
  margin: 0 auto;
  padding: clamp(1.5rem, 3vw, 2.5rem) clamp(1rem, 3vw, 2rem) 3rem;
}

.checkout__grid {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(280px, 360px);
  gap: 0;
  align-items: stretch;
  border: 1px solid rgba(23, 33, 38, 0.1);
  box-shadow: 0 28px 60px rgba(39, 52, 59, 0.1);
  overflow: hidden;
}

.checkout__form-panel {
  padding: clamp(1.5rem, 3vw, 2.25rem);
  background: rgba(255, 252, 246, 0.92);
}

.checkout__title {
  margin: 0 0 1.5rem;
  color: #172126;
  font: 800 clamp(1.75rem, 4vw, 2.5rem) / 0.95 "Sora", sans-serif;
  letter-spacing: -0.03em;
}

.checkout__form {
  display: grid;
  gap: 0.25rem;
}

.checkout__section {
  margin-bottom: 1.5rem;
}

.checkout__section h2 {
  margin: 0 0 0.85rem;
  color: #172126;
  font: 700 0.78rem/1 "Sora", sans-serif;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.checkout__fields {
  display: grid;
  gap: 0.75rem;
}

.field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.field {
  display: grid;
  gap: 0.35rem;
}

.field span {
  color: rgba(23, 33, 38, 0.72);
  font: 600 0.76rem/1.3 "Sora", sans-serif;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.field input,
.field select,
.field textarea {
  width: 100%;
  padding: 0.82rem 0.95rem;
  border: 1px solid rgba(23, 33, 38, 0.14);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.72);
  color: #172126;
  font: 500 0.92rem/1.45 "Sora", sans-serif;
  outline: none;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease;
}

.field select {
  appearance: none;
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%234f6973' d='M1 1l4 4 4-4'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.95rem center;
  padding-right: 2.25rem;
}

.field textarea {
  resize: vertical;
  min-height: 5rem;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
  border-color: #7ba3b5;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(123, 163, 181, 0.2);
}

.field--err input,
.field--err select,
.field--err textarea {
  border-color: #b85c5c;
}

.field em {
  color: #b85c5c;
  font: 500 0.74rem/1.3 "Sora", sans-serif;
  font-style: normal;
  text-transform: none;
  letter-spacing: 0;
}

.delivery-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  margin-bottom: 0.85rem;
  padding: 0.28rem;
  border-radius: 10px;
  background: rgba(123, 163, 181, 0.14);
}

.delivery-toggle__btn {
  position: relative;
  padding: 0.7rem 0.75rem;
  border-radius: 8px;
  text-align: center;
  color: rgba(23, 33, 38, 0.55);
  font: 700 0.72rem/1 "Sora", sans-serif;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  cursor: pointer;
  transition:
    background 0.2s ease,
    color 0.2s ease,
    box-shadow 0.2s ease;
}

.delivery-toggle__btn input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.delivery-toggle__btn--on {
  background: rgba(255, 252, 246, 0.98);
  color: #172126;
  box-shadow: 0 4px 14px rgba(23, 33, 38, 0.08);
}

.checkout__error {
  margin: 0.5rem 0 0;
  color: #b85c5c;
  font: 500 0.8rem/1.4 "Sora", sans-serif;
}

.checkout__actions {
  margin-top: 0.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(23, 33, 38, 0.08);
}

.checkout__cta {
  width: 100%;
  min-height: 3.2rem;
  border: 1px solid #172126;
  border-radius: 8px;
  background: #172126;
  color: #f4ead6;
  cursor: pointer;
  font: 800 0.74rem/1 "Sora", sans-serif;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  box-shadow: 0 0.4rem 0 rgba(123, 163, 181, 0.72);
  transition:
    background 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease;
}

.checkout__cta:hover:not(:disabled) {
  background: #7ba3b5;
  border-color: #7ba3b5;
  transform: translateY(-1px);
}

.checkout__cta:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.checkout__back {
  display: inline-block;
  margin-top: 0.85rem;
  color: rgba(79, 105, 115, 0.95);
  font: 600 0.74rem/1 "Sora", sans-serif;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  text-decoration: none;
}

.checkout__back:hover {
  color: #172126;
}

.checkout__success-wrap {
  display: grid;
  place-items: center;
  min-height: calc(100vh - 5rem);
  padding: 2rem;
  background:
    radial-gradient(circle at 12% 10%, rgba(123, 163, 181, 0.22), transparent 26%),
    linear-gradient(180deg, #efe5d6 0%, #e8dcc8 100%);
}

.checkout__success {
  width: min(24rem, 100%);
  padding: 2rem;
  text-align: center;
  border: 1px solid rgba(23, 33, 38, 0.1);
  background: rgba(255, 252, 246, 0.92);
  box-shadow: 0 24px 50px rgba(39, 52, 59, 0.1);
}

.checkout__success-label {
  margin: 0 0 0.5rem;
  color: rgba(79, 105, 115, 0.9);
  font: 700 0.72rem/1 "Sora", sans-serif;
  letter-spacing: 0.16em;
  text-transform: uppercase;
}

.checkout__success h1 {
  margin: 0 0 0.35rem;
  color: #172126;
  font: 800 1.6rem/1.1 "Sora", sans-serif;
}

.checkout__success-total {
  margin: 0 0 1.5rem;
  color: rgba(23, 33, 38, 0.7);
  font: 600 1rem/1.4 "Sora", sans-serif;
}

@media (max-width: 900px) {
  .checkout__grid {
    grid-template-columns: 1fr;
  }

  .field-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 520px) {
  .checkout__container {
    padding: 1rem 0.75rem 2rem;
  }

  .checkout__form-panel {
    padding: 1.15rem;
  }

  .delivery-toggle {
    grid-template-columns: 1fr;
  }
}
</style>
