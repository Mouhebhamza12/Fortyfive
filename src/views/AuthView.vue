<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import BrandMark from "../components/BrandMark.vue";
import { apiBaseUrl, appBaseUrl, authJsonHeaders, jsonHeaders } from "../lib/api";
import { useToast } from "../composables/useToast";

const { success: toastSuccess, error: toastError } = useToast();

const route = useRoute();
const router = useRouter();

const authContent = {
  "sign-in": {
    title: "Sign in",
    description:
      "See your orders and check out faster with your saved delivery details.",
    submitLabel: "Sign in",
    secondaryText: "Need an account?",
    secondaryLink: { label: "Create one", route: { name: "sign-up" } },
  },
  "sign-up": {
    title: "Create your account",
    description:
      "Track your orders and save your delivery details for next time.",
    submitLabel: "Create account",
    secondaryText: "Already have an account?",
    secondaryLink: { label: "Sign in", route: { name: "sign-in" } },
  },
  "auth-google-callback": {
    title: "Signing you in…",
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
const showPassword = ref(false);

const submitLabel = computed(() =>
  isSubmitting.value ? "One moment…" : page.value.submitLabel
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
  <main class="auth" :class="{ 'auth--sign-up': route.name === 'sign-up' }">
    <section class="auth__card" aria-labelledby="auth-title">
      <form class="auth__form" @submit.prevent="handleSubmit">
        <router-link to="/" class="auth__logo" aria-label="Fortyfive, back to the shop">
          <BrandMark class="auth__logo-mark" title="Fortyfive" />
          <span>FORTYFIVE</span>
        </router-link>

        <header class="auth__intro">
          <h1 id="auth-title">{{ page.title }}</h1>
          <p>{{ page.description }}</p>
        </header>

        <div v-if="showFormFields" class="auth__fields">
          <div v-if="route.name === 'sign-up'" class="auth__two">
            <label class="ff-field auth__field">
              <span>First name</span>
              <input
                v-model="form.firstName"
                type="text"
                autocomplete="given-name"
                placeholder="First name*"
                required
              />
            </label>
            <label class="ff-field auth__field">
              <span>Last name</span>
              <input
                v-model="form.lastName"
                type="text"
                autocomplete="family-name"
                placeholder="Last name*"
                required
              />
            </label>
          </div>

          <label class="ff-field auth__field">
            <span>Email address</span>
            <input
              v-model="form.email"
              type="email"
              autocomplete="email"
              placeholder="Email address*"
              required
            />
          </label>

          <label class="ff-field auth__field">
            <span>Password</span>
            <span class="auth__password">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                :autocomplete="route.name === 'sign-up' ? 'new-password' : 'current-password'"
                placeholder="Password*"
                required
              />
              <button
                type="button"
                class="auth__password-toggle"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                :aria-pressed="showPassword"
                @click="showPassword = !showPassword"
              >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M2.5 12s3.2-6 9.5-6 9.5 6 9.5 6-3.2 6-9.5 6-9.5-6-9.5-6Z" />
                  <circle cx="12" cy="12" r="2.5" />
                </svg>
              </button>
            </span>
          </label>

          <label v-if="route.name === 'sign-up'" class="ff-field auth__field">
            <span>Confirm password</span>
            <input
              v-model="form.confirmPassword"
              type="password"
              autocomplete="new-password"
              placeholder="Confirm password*"
              required
            />
          </label>

          <div v-if="route.name === 'sign-in'" class="auth__options">
            <label class="auth__check">
              <input v-model="form.remember" type="checkbox" />
              <span>Keep me signed in</span>
            </label>
          </div>

          <label v-if="route.name === 'sign-up'" class="auth__check">
            <input v-model="form.agree" type="checkbox" />
            <span>I agree to receive messages about my account and orders.</span>
          </label>

          <button type="submit" class="ff-btn ff-btn--block auth__submit" :disabled="isSubmitting">
            {{ submitLabel }}
          </button>
        </div>

        <p v-if="feedback" class="auth__feedback" role="status">{{ feedback }}</p>
        <p v-if="errorMessage" class="auth__error" role="alert">{{ errorMessage }}</p>

        <p class="auth__switch">
          {{ page.secondaryText }}
          <router-link :to="page.secondaryLink.route" class="ff-link">
            {{ page.secondaryLink.label }}
          </router-link>
        </p>

        <template v-if="showGoogleButton">
          <p class="auth__or"><span>or</span></p>
          <button
            type="button"
            class="ff-btn ff-btn--ghost ff-btn--block auth__google"
            @click="signInWithGoogle"
          >
            Continue with Google
          </button>
        </template>
      </form>
    </section>
  </main>
</template>

<style scoped>
.auth {
  min-height: 100dvh;
  display: grid;
  place-items: center;
  padding: clamp(0.75rem, 2vw, 1.5rem);
  background:
    linear-gradient(180deg, rgba(20, 32, 42, 0.12), rgba(13, 25, 35, 0.38)),
    url("/mountain-background.jpg") center / cover no-repeat;
}

.auth__card {
  width: min(100%, 28rem);
  padding: clamp(1.5rem, 3vw, 2.25rem);
  border: 1px solid rgba(255, 255, 255, 0.7);
  border-radius: 0.45rem;
  background: #fff;
  box-shadow: 0 1.5rem 5rem rgba(10, 23, 33, 0.2);
}

.auth__logo {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.45rem;
  width: fit-content;
  margin: 0 auto 1.25rem;
  color: var(--ink);
  text-decoration: none;
}

.auth__logo-mark {
  height: 2.15rem;
  width: auto;
}

.auth__logo span {
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.16em;
}

.auth__intro {
  text-align: center;
}

.auth__intro h1 {
  font-size: clamp(1.65rem, 4vw, 1.9rem);
  font-weight: 500;
  letter-spacing: -0.035em;
  line-height: 1.15;
}

.auth__intro p {
  max-width: 23rem;
  margin: 0.75rem auto 0;
  color: var(--ink-soft);
  font-size: 0.9375rem;
}

.auth__form {
  width: 100%;
  margin: 0 auto;
}

.auth__fields {
  display: grid;
  gap: 0.75rem;
  margin-top: 1.25rem;
}

.auth__two {
  display: flex;
  gap: 0.75rem;
}

.auth__two > * {
  min-width: 0;
  flex: 1;
}

.auth__field {
  gap: 0.35rem;
}

.auth__field > span:first-child {
  color: #222;
  font-size: 0.875rem;
  font-weight: 500;
}

.auth__field input {
  min-height: 3rem;
  padding: 0.7rem 0.875rem;
  border: 1px solid #d5d6d8;
  border-radius: 0.2rem;
  font-size: 0.975rem;
}

.auth__field input:focus {
  outline: 2px solid #222;
  outline-offset: -1px;
  border-color: #222;
}

.auth__password {
  position: relative;
  display: block;
}

.auth__password input {
  padding-right: 3rem;
}

.auth__password-toggle {
  position: absolute;
  top: 50%;
  right: 0.75rem;
  display: grid;
  width: 1.75rem;
  height: 1.75rem;
  place-items: center;
  padding: 0;
  transform: translateY(-50%);
  border: 0;
  background: transparent;
  color: #73777b;
}

.auth__password-toggle svg {
  width: 1.2rem;
  height: 1.2rem;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 1.6;
}

.auth__options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.auth__check {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  color: #36383a;
  font-size: 0.875rem;
  line-height: 1.4;
  cursor: pointer;
}

.auth__check input {
  flex: none;
  width: 1rem;
  height: 1rem;
  margin: 0.1rem 0 0;
  accent-color: var(--ink);
}

.auth__submit {
  min-height: 3rem;
  border-radius: 0.2rem;
  font-size: 1rem;
}

.auth__feedback,
.auth__error {
  margin-top: 1rem;
  font-size: 0.9375rem;
}

.auth__feedback {
  color: var(--ok);
}

.auth__error {
  color: var(--alert);
}

.auth__switch {
  margin-top: 0.85rem;
  color: #3f4245;
  font-size: 0.9rem;
  text-align: center;
}

.auth__switch .ff-link {
  margin-left: 0.3rem;
}

.auth__or {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin: 0.9rem 0 0.65rem;
  color: var(--ink-soft);
  font-size: 0.8125rem;
  text-transform: uppercase;
}

.auth__or::before,
.auth__or::after {
  content: "";
  flex: 1;
  height: 1px;
  background: var(--line);
}

.auth__google {
  min-height: 2.9rem;
  border-color: #d5d6d8;
  border-radius: 0.2rem;
  font-size: 0.9375rem;
}

.auth__google:hover {
  border-color: var(--ink);
}

.auth :deep(:focus-visible) {
  outline-color: #17191b;
}

.auth--sign-up .auth__card {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.auth--sign-up .auth__logo {
  gap: 0;
  margin-bottom: 0.65rem;
}

.auth--sign-up .auth__logo-mark {
  height: 1.65rem;
}

.auth--sign-up .auth__logo span,
.auth--sign-up .auth__intro p {
  display: none;
}

.auth--sign-up .auth__intro h1 {
  font-size: 1.5rem;
}

.auth--sign-up .auth__fields {
  gap: 0.55rem;
  margin-top: 0.85rem;
}

.auth--sign-up .auth__field {
  gap: 0;
}

.auth--sign-up .auth__field > span:first-child {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  clip-path: inset(50%);
}

.auth--sign-up .auth__field input {
  min-height: 2.75rem;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.auth--sign-up .auth__check {
  font-size: 0.8rem;
}

.auth--sign-up .auth__submit,
.auth--sign-up .auth__google {
  min-height: 2.7rem;
}

.auth--sign-up .auth__switch {
  margin-top: 0.65rem;
}

.auth--sign-up .auth__or {
  margin: 0.55rem 0 0.45rem;
}

@media (max-width: 980px) {
  .auth__card {
    padding-top: 1rem;
    padding-bottom: 1rem;
  }

  .auth--sign-up .auth__card {
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
  }

  .auth__intro p {
    display: none;
  }

  .auth__field > span:first-child {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    clip-path: inset(50%);
  }

  .auth__fields {
    gap: 0.55rem;
    margin-top: 0.85rem;
  }

  .auth__field {
    gap: 0;
  }

  .auth__field input {
    min-height: 2.75rem;
  }

  .auth__switch {
    margin-top: 0.65rem;
  }

  .auth__or {
    margin: 0.55rem 0 0.45rem;
  }
}

@media (max-width: 600px) {
  .auth {
    min-height: 100dvh;
    padding: 0.75rem 1.25rem;
    background: var(--paper);
  }

  .auth__card {
    width: 100%;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
  }

  .auth__logo span {
    display: none;
  }

  .auth__fields {
    gap: 0.6rem;
    margin-top: 1rem;
  }

  .auth__field {
    gap: 0;
  }

  .auth__field > span:first-child {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    clip-path: inset(50%);
  }

  .auth__field input {
    min-height: 2.8rem;
    padding: 0.65rem 0.8rem;
    font-size: 0.9375rem;
  }

  .auth__password input {
    padding-right: 2.8rem;
  }

  .auth--sign-up .auth__fields {
    gap: 0.45rem;
    margin-top: 0.7rem;
  }

  .auth--sign-up .auth__two {
    gap: 0.5rem;
  }

  .auth--sign-up .auth__two > * {
    flex-basis: 0;
  }

  .auth--sign-up .auth__two {
    gap: 0.5rem;
  }
}
</style>
