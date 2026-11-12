<script setup>
import { onMounted, ref } from "vue";
import SiteFooter from "../components/SiteFooter.vue";
import SiteHeader from "../components/SiteHeader.vue";
import { apiFetch, authJsonHeaders } from "../lib/api";
import { formatPrice } from "../lib/formatPrice";

const feedbackForm = ref({
  name: "",
  email: "",
  phone: "",
  rating: 5,
  message: "",
});

const isSubmitting = ref(false);
const feedbackSent = ref(false);
const submitError = ref("");

const submitFeedback = async () => {
  if (!feedbackForm.value.name.trim() || !feedbackForm.value.message.trim()) {
    submitError.value = "Please fill in your name and message.";
    return;
  }

  isSubmitting.value = true;
  submitError.value = "";

  try {
    const response = await apiFetch("/feedback", {
      method: "POST",
      headers: authJsonHeaders,
      body: JSON.stringify({
        name: feedbackForm.value.name.trim(),
        email: feedbackForm.value.email.trim() || null,
        phone: feedbackForm.value.phone.trim() || null,
        rating: feedbackForm.value.rating,
        message: feedbackForm.value.message.trim(),
      }),
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      submitError.value =
        payload.message ||
        Object.values(payload.errors || {})
          .flat()
          .join(" ") ||
        "Could not send feedback. Please try again.";
      return;
    }

    feedbackSent.value = true;
    feedbackForm.value = {
      name: "",
      email: "",
      phone: "",
      rating: 5,
      message: "",
    };
  } catch {
    submitError.value = "Network error. Please try again.";
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="ff-page">
    <SiteHeader />

    <main class="ff-page__main">
      <div class="ff-narrow">
        <h1 class="ff-page-title">Contact us</h1>
        <p class="ff-page-intro">
          Questions about a product, an order or a delivery go here.
        </p>

        <div v-if="feedbackSent" class="feedback-success">
          <h2>Message sent</h2>
          <p>Thank you. If you left a phone number or email, we will reply there.</p>
        </div>

        <form v-else class="feedback-form" @submit.prevent="submitFeedback">
          <label class="ff-field">
            <span>Name</span>
            <input v-model="feedbackForm.name" type="text" required />
          </label>

          <label class="ff-field">
            <span>Email (optional)</span>
            <input v-model="feedbackForm.email" type="email" />
          </label>

          <label class="ff-field">
            <span>Phone (optional)</span>
            <input v-model="feedbackForm.phone" type="tel" />
          </label>

          <label class="ff-field">
            <span>Rating</span>
            <select v-model.number="feedbackForm.rating">
              <option v-for="value in 5" :key="value" :value="value">{{ value }} / 5</option>
            </select>
          </label>

          <label class="ff-field">
            <span>Message</span>
            <textarea v-model="feedbackForm.message" rows="5" required minlength="10" />
          </label>

          <p v-if="submitError" class="feedback-error">{{ submitError }}</p>

          <button type="submit" class="ff-btn" :disabled="isSubmitting">
            {{ isSubmitting ? "Sending…" : "Send message" }}
          </button>
        </form>
      </div>
    </main>

    <SiteFooter />
  </div>
</template>

<style scoped>
.feedback-form {
  display: grid;
  gap: 1.1rem;
  margin-top: 2.25rem;
}

.feedback-form .ff-btn {
  justify-self: start;
  margin-top: 0.75rem;
}

.feedback-success {
  margin-top: 2.25rem;
  padding: 1.5rem;
  background: var(--mist);
}

.feedback-success h2 {
  font-size: 1.125rem;
  font-weight: 700;
}

.feedback-success p {
  margin-top: 0.4rem;
  color: var(--ink-soft);
}

.feedback-error {
  color: var(--alert);
}
</style>
