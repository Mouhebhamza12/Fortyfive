<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import layerTexture from "../../assets/images/bgls.png";
import { apiBaseUrl, appBaseUrl, authJsonHeaders, jsonHeaders } from "../lib/api";
import { useToast } from "../composables/useToast";

const { success: toastSuccess, error: toastError } = useToast();

const route = useRoute();
const router = useRouter();

const authContent = {
  "sign-in": {
    title: "Sign in to your account.",
    description:
      "Access your orders, saved details, and upcoming drops from one place.",
    submitLabel: "Sign In",
    secondaryText: "Need an account?",
    secondaryLink: { label: "Create one", route: { name: "sign-up" } },
  },
  "sign-up": {
    title: "Join the drop list.",
    description:
      "Create your account to shop, track orders, and check out faster.",
    submitLabel: "Create Account",
    secondaryText: "Already have an account?",
    secondaryLink: { label: "Sign in", route: { name: "sign-in" } },
  },
  "auth-google-callback": {
    title: "Signing you in.",
    description: "Finishing your Google authentication.",
    submitLabel: "Continue",
    secondaryText: "If this takes too long,",
    secondaryLink: { label: "go back to sign in", route: { name: "sign-in" } },
  },
};

const page = computed(() => authContent[route.name] ?? authContent["sign-in"]);
const showHomeBadge = computed(
  () => route.name === "sign-in" || route.name === "sign-up"
);
const showGoogleButton = computed(
  () => route.name === "sign-in" || route.name === "sign-up"
);
const showFormFields = computed(() => route.name !== "auth-google-callback");

const form = reactive({
  firstName: "",
  lastName: "",
  email: typeof route.query.email === "string" ? route.query.email : "",
  password: "",
  confirmPassword: "",
  remember: true,
  agree: false,
});

const feedback = ref("");
const errorMessage = ref("");
const isSubmitting = ref(false);

const submitLabel = computed(() =>
  isSubmitting.value ? "Please wait..." : page.value.submitLabel
);

const notifyAuthChanged = () => {
  window.dispatchEvent(new Event("auth-changed"));
};

const fetchProfile = async () => {
  const response = await fetch(`${apiBaseUrl}/auth/me`, {
    credentials: "include",
    headers: jsonHeaders,
  });

  if (!response.ok) {
    throw new Error(await parseError(response));
  }

  return response.json();
};

const parseError = async (response) => {
  const data = await response.json().catch(() => null);
  const firstValidationError = data?.errors
    ? Object.values(data.errors).flat()[0]
    : null;

  return firstValidationError || data?.message || "Something went wrong.";
};

const signInWithGoogle = () => {
  if (typeof route.query.redirect === "string") {
    sessionStorage.setItem("auth_redirect", route.query.redirect);
  }

  window.location.href = `${appBaseUrl}/auth/google/redirect`;
};

const getAuthRedirect = () => {
  const redirect =
    typeof route.query.redirect === "string"
      ? route.query.redirect
      : sessionStorage.getItem("auth_redirect");

  sessionStorage.removeItem("auth_redirect");

  return redirect && redirect.startsWith("/") ? redirect : null;
};

const finishAuthenticatedFlow = async (user, message) => {
  localStorage.setItem("is_admin", user?.is_admin ? "true" : "false");
  notifyAuthChanged();

  if (message) {
    toastSuccess(message);
  }

  const redirect = getAuthRedirect();
  await router.push(redirect || { name: "home" });
};

const oauthErrorMessages = {
  google_auth_failed: "Google authentication failed. Try again.",
  google_not_configured:
    "Google sign-in is not configured yet. Add GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI in the backend environment.",
};

onMounted(async () => {
  if (route.query.oauth_error) {
    const message =
      oauthErrorMessages[route.query.oauth_error] || "Google authentication failed. Try again.";
    errorMessage.value = message;
    toastError(message);
  }

  if (route.name !== "auth-google-callback") {
    return;
  }

  isSubmitting.value = true;

  try {
    const payload = await fetchProfile();
    window.history.replaceState({}, "", "/");
    await finishAuthenticatedFlow(payload.user, "Signed in with Google.");
  } catch (error) {
    const message = error instanceof Error ? error.message : "Something went wrong.";
    errorMessage.value = message;
    toastError(message);
  } finally {
    isSubmitting.value = false;
  }
});

const handleSubmit = async () => {
  feedback.value = "";
  errorMessage.value = "";
  isSubmitting.value = true;

  try {
    if (route.name === "sign-up") {
      const response = await fetch(`${apiBaseUrl}/auth/register`, {
        method: "POST",
        credentials: "include",
        headers: authJsonHeaders,
        body: JSON.stringify({
          name: `${form.firstName} ${form.lastName}`.trim(),
          email: form.email,
          password: form.password,
          password_confirmation: form.confirmPassword,
        }),
      });

      if (!response.ok) {
        throw new Error(await parseError(response));
      }

      const payload = await response.json();
      feedback.value = payload.message;
      await finishAuthenticatedFlow(payload.user, payload.message || "Account created.");
      return;
    }

    if (route.name === "sign-in") {
      const response = await fetch(`${apiBaseUrl}/auth/login`, {
        method: "POST",
        credentials: "include",
        headers: authJsonHeaders,
        body: JSON.stringify({
          email: form.email,
          password: form.password,
        }),
      });

      if (!response.ok) {
        throw new Error(await parseError(response));
      }

      const payload = await response.json();
      feedback.value = payload.message;
      await finishAuthenticatedFlow(payload.user, payload.message || "Welcome back.");
      return;
    }

  } catch (error) {
    const message = error instanceof Error ? error.message : "Something went wrong.";
    errorMessage.value = message;
    toastError(message);
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <main class="auth-shell">
    <section class="auth-stage">
      <div class="auth-background">
        <img :src="layerTexture" alt="" class="auth-background-mark" />
      </div>

      <div class="auth-panel auth-panel--form">
        <form class="auth-form" @submit.prevent="handleSubmit">
          <router-link
            v-if="showHomeBadge"
            to="/"
            class="home-badge"
            aria-label="Back to home"
          >
            <img src="/45svgblue.png" alt="" />
          </router-link>

          <div class="auth-form-copy">
            <h1>{{ page.title }}</h1>
            <p class="auth-copy">{{ page.description }}</p>
          </div>

          <button
            v-if="showGoogleButton"
            type="button"
            class="google-btn"
            @click="signInWithGoogle"
          >
            Continue with Google
          </button>

          <div v-if="showGoogleButton" class="divider">
            <span>or</span>
          </div>

          <template v-if="showFormFields">
          <div v-if="route.name === 'sign-up'" class="auth-grid auth-grid--two">
            <label class="field">
              <span>First name</span>
              <input v-model="form.firstName" type="text" placeholder="First name" />
            </label>
            <label class="field">
              <span>Last name</span>
              <input v-model="form.lastName" type="text" placeholder="Last name" />
            </label>
          </div>

          <label class="field">
            <span>Email</span>
            <input
              v-model="form.email"
              type="email"
              placeholder="you@example.com"
            />
          </label>

          <label class="field">
            <span>Password</span>
            <input v-model="form.password" type="password" placeholder="Enter password" />
          </label>

          <template v-if="route.name === 'sign-up'">
            <label class="field">
              <span>Confirm password</span>
              <input
                v-model="form.confirmPassword"
                type="password"
                placeholder="Confirm password"
              />
            </label>
          </template>

          <div v-if="route.name === 'sign-in'" class="auth-row">
            <label class="check">
              <input v-model="form.remember" type="checkbox" />
              <span>Keep me signed in</span>
            </label>
            <span class="auth-note">Google sign-in recommended</span>
          </div>

          <label v-if="route.name === 'sign-up'" class="check">
            <input v-model="form.agree" type="checkbox" />
            <span>I agree to account updates and order communication.</span>
          </label>
          </template>

          <button
            v-if="showFormFields"
            type="submit"
            class="submit-btn"
            :disabled="isSubmitting"
          >
            {{ submitLabel }}
          </button>

          <p v-if="feedback" class="feedback">{{ feedback }}</p>
          <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>

          <p class="switch-copy">
            {{ page.secondaryText }}
            <router-link :to="page.secondaryLink.route" class="text-link">
              {{ page.secondaryLink.label }}
            </router-link>
          </p>
        </form>
      </div>
    </section>
  </main>
</template>

<style scoped>
.auth-shell {
  --brand-blue: #7498a6;
  --brand-blue-deep: #4b6570;
  --brand-ink: #162127;
  --brand-sand: #f0dfcd;
  --brand-warm: #ead8b9;
  height: 100vh;
  background: #efe6da;
  color: #fff;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  font-family: "Cormorant Garamond", Georgia, serif;
}

.auth-stage {
  flex: 1 1 auto;
  position: relative;
  display: grid;
  place-items: center;
  min-height: 100vh;
  padding: clamp(1rem, 2vw, 1.5rem);
  background: transparent;
  overflow: hidden;
}

.auth-background {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.auth-background-mark {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.auth-background-mark {
  object-fit: cover;
  object-position: center;
  opacity: 0.32;
  mix-blend-mode: normal;
  transform: scale(1);
}

.auth-panel--form {
  position: relative;
  z-index: 1;
  display: flex;
  justify-content: center;
  width: 100%;
}

.field span,
.switch-copy,
.check,
.feedback {
  font-family: "Sora", sans-serif;
}

.auth-form {
  width: min(100%, 28rem);
  padding: clamp(1.2rem, 2vw, 1.45rem);
  background:
    linear-gradient(180deg, rgba(244, 234, 214, 0.96) 0%, rgba(240, 223, 205, 0.92) 100%);
  border: 1px solid rgba(116, 152, 166, 0.32);
  border-radius: 0;
  box-shadow:
    0 20px 40px rgba(39, 56, 63, 0.08),
    0 0 0 1px rgba(255, 255, 255, 0.22) inset;
  position: relative;
  overflow: hidden;
}

.home-badge {
  position: absolute;
  top: 0.8rem;
  right: 0.8rem;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 4rem;
  height: 4rem;
  text-decoration: none;
}

.home-badge img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.auth-form::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 0.22rem;
  background: linear-gradient(90deg, #7ba3b5 0%, #92b5c3 100%);
}

.auth-form-copy h1 {
  margin: 0;
  max-width: 8ch;
  font-family: "Helvetica Neue", sans-serif;
  font-size: clamp(2.25rem, 3.8vw, 3.15rem);
  line-height: 0.9;
  letter-spacing: -0.05em;
  text-transform: uppercase;
  color: var(--brand-ink);
}

.google-btn,
.resend-btn {
  width: 100%;
  min-height: 2.9rem;
  border: 1px solid rgba(79, 105, 115, 0.18);
  background: transparent;
  color: var(--brand-ink);
  font-family: "Sora", sans-serif;
  font-size: 0.66rem;
  font-weight: 800;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
}

.divider {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0.8rem 0 1rem;
  color: rgba(22, 33, 39, 0.48);
  font-family: "Sora", sans-serif;
  font-size: 0.62rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.auth-copy {
  margin: 0.95rem 0 1.35rem;
  max-width: 20rem;
  font-family: "Sora", sans-serif;
  font-size: 0.8rem;
  line-height: 1.6;
  color: rgba(22, 33, 39, 0.64);
}

.auth-grid {
  display: grid;
  gap: 0.75rem;
}

.auth-grid--two {
  grid-template-columns: 1fr 1fr;
}

.field {
  display: grid;
  gap: 0.45rem;
  margin-bottom: 0.7rem;
}

.field span {
  font-size: 0.58rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: rgba(79, 105, 115, 0.88);
}

.field input {
  min-height: 2.8rem;
  padding: 0 0.85rem;
  border: 1px solid rgba(79, 105, 115, 0.14);
  border-radius: 0;
  background: rgba(232, 220, 200, 0.34);
  color: var(--brand-ink);
  font-family: "Sora", sans-serif;
  font-size: 0.83rem;
}

.field input::placeholder {
  color: rgba(22, 33, 39, 0.38);
}

.field input:focus {
  outline: none;
  border-color: rgba(116, 152, 166, 0.62);
  box-shadow: inset 0 -2px 0 rgba(116, 152, 166, 0.62);
}

.auth-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin: 0.15rem 0 1rem;
}

.check {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  font-size: 0.71rem;
  color: rgba(22, 33, 39, 0.76);
  margin-bottom: 0.9rem;
}

.check input {
  width: 1rem;
  height: 1rem;
}

.text-link {
  color: var(--brand-blue-deep);
  text-decoration: none;
  font-family: "Sora", sans-serif;
  font-size: 0.71rem;
  font-weight: 700;
}

.auth-note {
  color: rgba(22, 33, 39, 0.62);
  font-family: "Sora", sans-serif;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.submit-btn {
  width: 100%;
  min-height: 2.9rem;
  border: 1px solid #7ba3b5;
  border-radius: 0;
  background: #7ba3b5;
  color: #102f36;
  font-family: "Sora", sans-serif;
  font-size: 0.66rem;
  font-weight: 800;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  cursor: pointer;
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.submit-btn:disabled {
  cursor: wait;
  opacity: 0.8;
}

.submit-btn:hover {
  transform: none;
  background: #86aab9;
}

.feedback {
  margin: 1rem 0 0;
  font-size: 0.74rem;
  color: var(--brand-blue-deep);
}

.error-message {
  margin: 1rem 0 0;
  font-family: "Sora", sans-serif;
  font-size: 0.74rem;
  color: #8d3030;
}

.switch-copy {
  margin: 1rem 0 0;
  font-size: 0.71rem;
  color: rgba(22, 33, 39, 0.62);
}

@media (max-width: 960px) {
  .auth-stage {
    padding: 1.25rem;
  }

  .auth-background-mark {
    transform: scale(0.98);
  }
}

@media (max-width: 640px) {
  .auth-grid--two,
  .auth-row {
    grid-template-columns: 1fr;
    display: grid;
  }

  .auth-form-copy h1 {
    font-size: clamp(1.8rem, 10vw, 2.45rem);
  }

  .auth-form {
    width: min(100%, 23rem);
    padding: 1rem;
  }

  .home-badge {
    top: 0.65rem;
    right: 0.65rem;
    width: 3.2rem;
    height: 3.2rem;
  }
}
</style>
