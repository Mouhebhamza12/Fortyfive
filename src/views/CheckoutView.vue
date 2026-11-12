<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { RouterLink, useRouter } from "vue-router";
import CheckoutOrderSummary from "../components/CheckoutOrderSummary.vue";
import SiteFooter from "../components/SiteFooter.vue";
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
  <div class="ff-page">
    <SiteHeader />
    <main class="checkout">

    <div v-if="orderPlaced && placedOrder" class="checkout__success-wrap">
      <div class="checkout__success">
        <h1 class="ff-page-title">Order placed. Thank you.</h1>
        <p class="ff-page-intro">
          We will call you to confirm before it ships. Keep your order number in case you
          need to reach us.
        </p>
        <dl class="checkout__receipt">
          <div>
            <dt>Order number</dt>
            <dd>{{ placedOrder.order_number }}</dd>
          </div>
          <div>
            <dt>To pay on delivery</dt>
            <dd>{{ placedOrder.total_amount?.toLocaleString("fr-DZ") }} DA</dd>
          </div>
        </dl>
        <button type="button" class="ff-btn" @click="continueShopping">
          Continue shopping
        </button>
      </div>
    </div>

    <div v-else class="checkout__body">
      <div class="checkout__container">
        <div class="checkout__grid">
          <section class="checkout__form-panel">
            <h1 class="ff-page-title">Checkout</h1>

            <form class="checkout__form" novalidate @submit.prevent="placeOrder">
              <div class="checkout__section">
                <h2>Contact</h2>
                <div class="checkout__fields">
                  <label class="ff-field" :class="{ 'ff-field--err': fieldErrors.customer_name }">
                    <span>Full name</span>
                    <input v-model="form.customer_name" type="text" autocomplete="name" />
                    <em v-if="fieldErrors.customer_name">{{ fieldErrors.customer_name }}</em>
                  </label>

                  <div class="field-row">
                    <label class="ff-field" :class="{ 'ff-field--err': fieldErrors.customer_phone }">
                      <span>Phone</span>
                      <input
                        v-model="form.customer_phone"
                        type="tel"
                        inputmode="tel"
                        autocomplete="tel"
                        placeholder="05XX XX XX XX"
                      />
                      <em v-if="fieldErrors.customer_phone">{{ fieldErrors.customer_phone }}</em>
                    </label>

                    <label class="ff-field" :class="{ 'ff-field--err': fieldErrors.customer_email }">
                      <span>Email (optional)</span>
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
                    Home delivery
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
                  <label class="ff-field" :class="{ 'ff-field--err': fieldErrors.wilaya_id }">
                    <span>Wilaya</span>
                    <select v-model="form.wilaya_id" :disabled="loadingWilayas">
                      <option value="">Select wilaya</option>
                      <option v-for="w in wilayas" :key="w.id" :value="w.id">
                        {{ String(w.code).padStart(2, "0") }} - {{ w.name }}
                      </option>
                    </select>
                    <em v-if="fieldErrors.wilaya_id">{{ fieldErrors.wilaya_id }}</em>
                  </label>

                  <template v-if="form.delivery_type === 'home'">
                    <label class="ff-field" :class="{ 'ff-field--err': fieldErrors.commune }">
                      <span>Commune</span>
                      <input v-model="form.commune" type="text" autocomplete="address-level2" />
                      <em v-if="fieldErrors.commune">{{ fieldErrors.commune }}</em>
                    </label>

                    <label class="ff-field" :class="{ 'ff-field--err': fieldErrors.shipping_address }">
                      <span>Address</span>
                      <textarea
                        v-model="form.shipping_address"
                        rows="3"
                        autocomplete="street-address"
                      />
                      <em v-if="fieldErrors.shipping_address">{{ fieldErrors.shipping_address }}</em>
                    </label>
                  </template>

                  <template v-else>
                    <label class="ff-field" :class="{ 'ff-field--err': fieldErrors.delivery_bureau_id }">
                      <span>Pickup point</span>
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

                  <label class="ff-field">
                    <span>Note for the courier (optional)</span>
                    <input v-model="form.notes" type="text" />
                  </label>
                </div>

                <p v-if="shippingError || fieldErrors.shipping" class="checkout__error">
                  {{ shippingError || fieldErrors.shipping }}
                </p>
              </div>

              <div class="checkout__actions">
                <button type="submit" class="ff-btn ff-btn--block" :disabled="!canSubmit">
                  {{ isSubmitting ? "Placing order…" : "Place order" }}
                </button>
                <p class="checkout__terms">
                  You pay in cash when the order arrives. Nothing is charged now.
                </p>
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
    <SiteFooter />
  </div>
</template>

<style scoped>
.checkout {
  flex: 1;
  width: 100%;
  max-width: var(--page);
  margin: 0 auto;
  padding: clamp(2rem, 5vw, 4rem) var(--gutter) clamp(3rem, 7vw, 6rem);
}

.checkout__grid {
  display: grid;
  grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
  gap: 2.5rem clamp(2rem, 6vw, 6rem);
  align-items: start;
}

.checkout__form-panel {
  max-width: 38rem;
}

.checkout__section {
  margin-top: 2.5rem;
}

.checkout__section h2 {
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--line);
  font-size: 1rem;
  font-weight: 600;
}

.checkout__fields {
  display: grid;
  gap: 1.1rem;
}

.field-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.1rem;
}

.delivery-toggle {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.6rem;
  margin-bottom: 1.25rem;
}

.delivery-toggle__btn {
  position: relative;
  display: grid;
  place-items: center;
  min-height: 3rem;
  padding: 0 1rem;
  border: 1px solid var(--line-strong);
  border-radius: var(--radius);
  font-weight: 500;
  text-align: center;
  cursor: pointer;
}

.delivery-toggle__btn input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.delivery-toggle__btn:hover {
  border-color: var(--ink);
}

.delivery-toggle__btn--on {
  border-color: var(--ink);
  box-shadow: inset 0 0 0 1px var(--ink);
}

.delivery-toggle__btn:focus-within {
  outline: 2px solid var(--ink);
  outline-offset: 3px;
}

.checkout__error {
  margin-top: 1rem;
  color: var(--alert);
  font-size: 0.9375rem;
}

.checkout__actions {
  margin-top: 2.5rem;
}

.checkout__terms {
  margin-top: 0.875rem;
  color: var(--ink-soft);
  font-size: 0.9375rem;
}

.checkout__success {
  max-width: 36rem;
}

.checkout__receipt {
  margin: 2rem 0;
  border-top: 1px solid var(--line-strong);
}

.checkout__receipt div {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.9rem 0;
  border-bottom: 1px solid var(--line);
}

.checkout__receipt dt {
  color: var(--ink-soft);
}

.checkout__receipt dd {
  margin: 0;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

@media (max-width: 900px) {
  .checkout__grid {
    grid-template-columns: 1fr;
  }

  .checkout__form-panel {
    max-width: none;
  }
}

@media (max-width: 520px) {
  .field-row {
    grid-template-columns: 1fr;
  }
}
</style>
