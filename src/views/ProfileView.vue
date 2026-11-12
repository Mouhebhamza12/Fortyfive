<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import SiteFooter from "../components/SiteFooter.vue";
import SiteHeader from "../components/SiteHeader.vue";
import { apiFetch, authJsonHeaders } from "../lib/api";

const router = useRouter();

const profileForm = reactive({
  name: "",
  email: "",
  phone: "",
  city: "",
  shipping_address: "",
  delivery_note: "",
});

const isLoading = ref(true);
const isSaving = ref(false);
const saveMessage = ref("");
const saveError = ref("");

const loadProfile = async () => {
  try {
    const response = await apiFetch("/auth/me");

    if (response.status === 401) {
      router.push({ name: "sign-in", query: { redirect: "/profile" } });
      return;
    }

    if (!response.ok) {
      saveError.value = "Could not load your profile.";
      return;
    }

    const payload = await response.json();
    const user = payload.user;

    profileForm.name = user.name || "";
    profileForm.email = user.email || "";
    profileForm.phone = user.phone || "";
    profileForm.city = user.city || "";
    profileForm.shipping_address = user.shipping_address || "";
    profileForm.delivery_note = user.delivery_note || "";
  } catch {
    saveError.value = "Network error while loading profile.";
  } finally {
    isLoading.value = false;
  }
};

const saveProfile = async () => {
  isSaving.value = true;
  saveMessage.value = "";
  saveError.value = "";

  try {
    const response = await apiFetch("/auth/profile", {
      method: "PUT",
      headers: authJsonHeaders,
      body: JSON.stringify({
        name: profileForm.name.trim(),
        email: profileForm.email.trim(),
        phone: profileForm.phone.trim(),
        city: profileForm.city.trim(),
        shipping_address: profileForm.shipping_address.trim(),
        delivery_note: profileForm.delivery_note.trim() || null,
      }),
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      saveError.value =
        payload.message ||
        Object.values(payload.errors || {})
          .flat()
          .join(" ") ||
        "Could not save profile.";
      return;
    }

    saveMessage.value = payload.message || "Profile saved.";
    window.dispatchEvent(new Event("auth-changed"));
  } catch {
    saveError.value = "Network error. Please try again.";
  } finally {
    isSaving.value = false;
  }
};

onMounted(loadProfile);
</script>

<template>
  <div class="ff-page">
    <SiteHeader />

    <main class="ff-page__main">
      <div class="ff-narrow">
        <h1 class="ff-page-title">Delivery details</h1>
        <p class="ff-page-intro">
          Save your delivery details once. They will be filled in automatically at checkout.
        </p>

        <div v-if="isLoading" class="profile-state">Loading your details…</div>

        <form v-else class="profile-form" @submit.prevent="saveProfile">
          <label class="ff-field">
            <span>Full name</span>
            <input v-model="profileForm.name" type="text" autocomplete="name" required />
          </label>

          <label class="ff-field">
            <span>Email</span>
            <input v-model="profileForm.email" type="email" autocomplete="email" required />
          </label>

          <label class="ff-field">
            <span>Phone number</span>
            <input v-model="profileForm.phone" type="tel" autocomplete="tel" required />
          </label>

          <label class="ff-field">
            <span>City</span>
            <input v-model="profileForm.city" type="text" autocomplete="address-level2" required />
          </label>

          <label class="ff-field">
            <span>Address</span>
            <textarea
              v-model="profileForm.shipping_address"
              rows="4"
              autocomplete="street-address"
              required
            ></textarea>
          </label>

          <label class="ff-field">
            <span>Default order note (optional)</span>
            <textarea
              v-model="profileForm.delivery_note"
              rows="3"
              placeholder="Building, floor, nearby landmark"
            />
          </label>

          <p v-if="saveMessage" class="profile-success">{{ saveMessage }}</p>
          <p v-if="saveError" class="profile-error">{{ saveError }}</p>

          <button type="submit" class="ff-btn" :disabled="isSaving">
            {{ isSaving ? "Saving…" : "Save details" }}
          </button>
        </form>
      </div>
    </main>

    <SiteFooter />
  </div>
</template>

<style scoped>
.profile-form {
  display: grid;
  gap: 1.1rem;
  margin-top: 2.25rem;
}

.profile-form .ff-btn {
  justify-self: start;
  margin-top: 0.75rem;
}

.profile-state {
  margin-top: 2rem;
  color: var(--ink-soft);
}

.profile-success {
  color: var(--ok);
}

.profile-error {
  color: var(--alert);
}
</style>
