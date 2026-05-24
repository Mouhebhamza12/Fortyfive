<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import SiteHeader from "../components/SiteHeader.vue";
import { apiFetch, authJsonHeaders } from "../lib/api";

const router = useRouter();

const navLinks = [
  { label: "Home", route: { name: "home" } },
  { label: "Shop", route: { name: "home", hash: "#shop" } },
];

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
  <main class="profile-shell">
    <SiteHeader :nav-links="navLinks" :visible="true" />

    <section class="profile-layout">
      <div class="profile-card">
        <p class="profile-eyebrow">Account</p>
        <h1>Delivery profile</h1>
        <p class="profile-intro">
          Save your delivery details once. They will be filled in automatically at checkout.
        </p>

        <div v-if="isLoading" class="profile-state">Loading profile...</div>

        <form v-else class="profile-form" @submit.prevent="saveProfile">
          <label class="profile-field">
            <span>Full name</span>
            <input v-model="profileForm.name" type="text" autocomplete="name" required />
          </label>

          <label class="profile-field">
            <span>Email</span>
            <input v-model="profileForm.email" type="email" autocomplete="email" required />
          </label>

          <label class="profile-field">
            <span>Phone number</span>
            <input v-model="profileForm.phone" type="tel" autocomplete="tel" required />
          </label>

          <label class="profile-field">
            <span>City</span>
            <input v-model="profileForm.city" type="text" autocomplete="address-level2" required />
          </label>

          <label class="profile-field">
            <span>Address</span>
            <textarea
              v-model="profileForm.shipping_address"
              rows="4"
              autocomplete="street-address"
              required
            ></textarea>
          </label>

          <label class="profile-field">
            <span>Default order note (optional)</span>
            <textarea
              v-model="profileForm.delivery_note"
              rows="3"
              placeholder="Building, floor, landmark, sizing note..."
            />
          </label>

          <p v-if="saveMessage" class="profile-success">{{ saveMessage }}</p>
          <p v-if="saveError" class="profile-error">{{ saveError }}</p>

          <button type="submit" class="profile-btn" :disabled="isSaving">
            {{ isSaving ? "Saving..." : "Save Profile" }}
          </button>
        </form>
      </div>
    </section>
  </main>
</template>

<style scoped>
.profile-shell {
  min-height: 100vh;
  background: #050505;
}

.profile-layout {
  min-height: calc(100vh - 5rem);
  display: flex;
  justify-content: center;
  padding: clamp(2rem, 4vw, 4rem);
  background:
    radial-gradient(circle at 12% 14%, rgba(123, 163, 181, 0.22), transparent 24%),
    linear-gradient(180deg, #efe5d6 0%, #e8dcc8 100%);
}

.profile-card {
  width: min(640px, 100%);
  padding: clamp(1.4rem, 3vw, 2.4rem);
  border: 1px solid rgba(23, 33, 38, 0.12);
  background: rgba(255, 252, 246, 0.58);
  box-shadow: 0 24px 50px rgba(39, 52, 59, 0.08);
}

.profile-eyebrow,
.profile-field span {
  color: rgba(79, 105, 115, 0.92);
  font-family: "Sora", sans-serif;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.profile-card h1 {
  margin: 0;
  color: #172126;
  font-family: "Helvetica Neue", sans-serif;
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 900;
  text-transform: uppercase;
}

.profile-intro,
.profile-state {
  margin: 1rem 0 0;
  color: rgba(23, 33, 38, 0.76);
  font-family: "Sora", sans-serif;
  line-height: 1.7;
}

.profile-form {
  display: grid;
  gap: 1rem;
  margin-top: 2rem;
}

.profile-field {
  display: grid;
  gap: 0.5rem;
}

.profile-field input,
.profile-field textarea {
  width: 100%;
  border: 1px solid rgba(23, 33, 38, 0.16);
  background: rgba(255, 255, 255, 0.52);
  color: #172126;
  padding: 0.95rem 1rem;
  font: 500 0.94rem/1.5 "Sora", sans-serif;
  resize: vertical;
  outline: none;
}

.profile-field input:focus,
.profile-field textarea:focus {
  border-color: #7ba3b5;
}

.profile-success {
  margin: 0;
  color: #1f6b3f;
  font-family: "Sora", sans-serif;
}

.profile-error {
  margin: 0;
  color: #8b2020;
  font-family: "Sora", sans-serif;
}

.profile-btn {
  min-height: 3.35rem;
  border: 1px solid #172126;
  background: #172126;
  color: #f4ead6;
  font: 800 0.72rem/1 "Helvetica Neue", sans-serif;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
}

.profile-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
