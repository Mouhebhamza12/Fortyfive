<script setup>
import BrandMark from "./BrandMark.vue";

const year = new Date().getFullYear();
</script>

<template>
  <footer class="footer">
    <div class="footer__inner">
      <div class="footer__brand">
        <BrandMark class="footer__mark" title="Fortyfive" />
      </div>

      <nav class="footer__cols" aria-label="Footer">
        <div class="footer__col">
          <h2>Shop</h2>
          <router-link :to="{ name: 'product-details', params: { slug: 'atlas' } }">Atlas</router-link>
          <router-link :to="{ name: 'home', hash: '#shop' }">All colours</router-link>
        </div>
        <div class="footer__col">
          <h2>Account</h2>
          <router-link :to="{ name: 'sign-in' }">Sign in</router-link>
          <router-link :to="{ name: 'my-orders' }">My orders</router-link>
          <router-link :to="{ name: 'profile' }">Delivery details</router-link>
        </div>
        <div class="footer__col">
          <h2>Help</h2>
          <router-link :to="{ name: 'home', hash: '#delivery' }">Delivery and payment</router-link>
          <router-link :to="{ name: 'feedback' }">Contact us</router-link>
          <a href="mailto:contact@fortyfive.com">contact@fortyfive.com</a>
        </div>
      </nav>
    </div>

    <p class="footer__legal">&copy; {{ year }} Fortyfive</p>
  </footer>
</template>

<style scoped>
.footer {
  background: #000;
  color: #fff;
  padding: clamp(3rem, 6vw, 5rem) var(--gutter) 1.75rem;
}

.footer__inner {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr);
  gap: 3rem clamp(2rem, 6vw, 6rem);
  max-width: var(--page);
  margin: 0 auto;
}

.footer__mark {
  height: 3.25rem;
  width: auto;
}

.footer__brand p {
  margin-top: 1.25rem;
  max-width: 16rem;
  color: rgba(255, 255, 255, 0.72);
}

.footer__cols {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 2rem;
}

.footer__col {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.7rem;
}

.footer__col h2 {
  margin-bottom: 0.35rem;
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.875rem;
  font-weight: 500;
}

.footer__col a {
  color: var(--bone);
  font-size: 0.9375rem;
  text-decoration: none;
  overflow-wrap: anywhere;
}

.footer__col a:hover {
  text-decoration: underline;
  text-underline-offset: 0.25em;
}

.footer__legal {
  max-width: var(--page);
  margin: clamp(2.5rem, 5vw, 4rem) auto 0;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.18);
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.8125rem;
}

.footer :focus-visible {
  outline-color: var(--bone);
}

@media (max-width: 760px) {
  .footer__inner {
    grid-template-columns: 1fr;
  }

  .footer__cols {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
