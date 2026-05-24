<script setup>
import { onMounted, ref } from "vue";
import SiteHeader from "../components/SiteHeader.vue";
import { apiFetch, authJsonHeaders } from "../lib/api";
import { formatPrice } from "../lib/formatPrice";

const navLinks = [
  { label: "Home", route: { name: "home" } },
  { label: "Shop", route: { name: "home", hash: "#shop" } },
];

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
  <main class="feedback-shell">
    <SiteHeader :nav-links="navLinks" :visible="true" />

    <section class="feedback-layout">
      <div class="feedback-card">
        <p class="feedback-eyebrow">Contact</p>
        <h1>Send us feedback</h1>
        <p class="feedback-intro">
          Tell us about your order, sizing, or anything we can improve. We read every message.
        </p>

        <div v-if="feedbackSent" class="feedback-success">
          <h2>Thank you</h2>
          <p>Your feedback was received. We will get back to you if needed.</p>
        </div>

        <form v-else class="feedback-form" @submit.prevent="submitFeedback">
          <label class="feedback-field">
            <span>Name</span>
            <input v-model="feedbackForm.name" type="text" required />
          </label>

          <label class="feedback-field">
            <span>Email (optional)</span>
            <input v-model="feedbackForm.email" type="email" />
          </label>

          <label class="feedback-field">
            <span>Phone (optional)</span>
            <input v-model="feedbackForm.phone" type="tel" />
          </label>

          <label class="feedback-field">
            <span>Rating</span>
            <select v-model.number="feedbackForm.rating">
              <option v-for="value in 5" :key="value" :value="value">{{ value }} / 5</option>
            </select>
          </label>

          <label class="feedback-field">
            <span>Message</span>
            <textarea v-model="feedbackForm.message" rows="5" required minlength="10" />
          </label>

          <p v-if="submitError" class="feedback-error">{{ submitError }}</p>

          <button type="submit" class="feedback-btn" :disabled="isSubmitting">
            {{ isSubmitting ? "Sending..." : "Send Feedback" }}
          </button>
        </form>
      </div>
    </section>
  </main>
</template>

<style scoped>
.feedback-shell {
  min-height: 100vh;
  background: #050505;
}

.feedback-layout {
  min-height: calc(100vh - 5rem);
  display: flex;
  justify-content: center;
  padding: clamp(2rem, 4vw, 4rem);
  background:
    radial-gradient(circle at 12% 14%, rgba(123, 163, 181, 0.22), transparent 24%),
    linear-gradient(180deg, #efe5d6 0%, #e8dcc8 100%);
}

.feedback-card {
  width: min(640px, 100%);
  padding: clamp(1.4rem, 3vw, 2.4rem);
  border: 1px solid rgba(23, 33, 38, 0.12);
  background: rgba(255, 252, 246, 0.58);
  box-shadow: 0 24px 50px rgba(39, 52, 59, 0.08);
}

.feedback-eyebrow,
.feedback-field span {
  color: rgba(79, 105, 115, 0.92);
  font-family: "Sora", sans-serif;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.feedback-card h1,
.feedback-success h2 {
  margin: 0;
  color: #172126;
  font-family: "Helvetica Neue", sans-serif;
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 900;
  text-transform: uppercase;
}

.feedback-intro,
.feedback-success p {
  margin: 1rem 0 0;
  color: rgba(23, 33, 38, 0.76);
  font-family: "Sora", sans-serif;
  line-height: 1.7;
}

.feedback-form {
  display: grid;
  gap: 1rem;
  margin-top: 2rem;
}

.feedback-field {
  display: grid;
  gap: 0.5rem;
}

.feedback-field input,
.feedback-field textarea,
.feedback-field select {
  width: 100%;
  border: 1px solid rgba(23, 33, 38, 0.16);
  background: rgba(255, 255, 255, 0.52);
  color: #172126;
  padding: 0.95rem 1rem;
  font: 500 0.94rem/1.5 "Sora", sans-serif;
  outline: none;
}

.feedback-error {
  margin: 0;
  color: #8b2020;
  font-family: "Sora", sans-serif;
}

.feedback-btn {
  min-height: 3.35rem;
  border: 1px solid #172126;
  background: #172126;
  color: #f4ead6;
  font: 800 0.72rem/1 "Helvetica Neue", sans-serif;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
}

.feedback-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.feedback-success {
  margin-top: 2rem;
}
</style>
