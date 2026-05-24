<template>
  <footer class="footer">
    <div class="container footer-grid">
      <div class="footer-section">
        <h3 class="footer-logo">YE</h3>
        <p class="footer-about">
          Curated collection of premium apparel and accessories. Designed for the modern individual.
        </p>
      </div>

      <div class="footer-section">
        <h4>Shop</h4>
        <ul>
          <li><a href="#">New Arrivals</a></li>
          <li><a href="#">Best Sellers</a></li>
          <li><a href="#">T-Shirts</a></li>
          <li><a href="#">Accessories</a></li>
        </ul>
      </div>

      <div class="footer-section">
        <h4>Info</h4>
        <ul>
          <li><a href="#">About Us</a></li>
          <li><a href="#">Shipping</a></li>
          <li><a href="#">Returns</a></li>
          <li><a href="#">Contact</a></li>
        </ul>
      </div>

      <div class="footer-section">
        <h4>Newsletter</h4>
        <p>Subscribe to receive updates and exclusive offers.</p>
        <form @submit.prevent class="newsletter-form">
          <input type="email" placeholder="Your email" />
          <button type="submit" class="btn">Join</button>
        </form>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="container">
        <p>&copy; 2024 YE. All rights reserved.</p>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.footer {
  background: var(--bg-secondary);
  border-top: 1px solid var(--border);
  padding: var(--spacing-xl) 0 0;
}

.footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 2fr;
  gap: var(--spacing-xl);
  margin-bottom: var(--spacing-xl);
}

.footer-logo {
  font-size: 1.5rem;
  font-weight: 900;
  text-transform: uppercase;
  margin-bottom: var(--spacing-md);
}

.footer-about {
  color: var(--text-secondary);
  max-width: 300px;
}

.footer-section h4 {
  font-size: 1rem;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: var(--spacing-md);
}

.footer-section ul {
  list-style: none;
}

.footer-section li {
  margin-bottom: var(--spacing-xs);
}

.footer-section a {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.footer-section a:hover {
  color: var(--text-primary);
}

.newsletter-form {
  display: flex;
  margin-top: var(--spacing-md);
  gap: var(--spacing-xs);
}

.newsletter-form input {
  flex: 1;
  padding: 10px 15px;
  border: 1px solid var(--border);
  font-family: inherit;
}

.footer-bottom {
  border-top: 1px solid var(--border);
  padding: var(--spacing-md) 0;
  text-align: center;
  color: var(--text-secondary);
  font-size: 0.8rem;
}

@media (max-width: 992px) {
  .footer-grid {
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-lg);
  }
}

@media (max-width: 576px) {
  .footer-grid {
    grid-template-columns: 1fr;
  }
}
</style>
