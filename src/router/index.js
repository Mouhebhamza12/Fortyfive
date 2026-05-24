import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import ProductDetailsView from '../views/ProductDetailsView.vue'
import AuthView from '../views/AuthView.vue'
import CheckoutView from '../views/CheckoutView.vue'
import AdminLayout from '../components/AdminLayout.vue'
import AdminDashboardView from '../views/AdminDashboardView.vue'
import AdminProductsView from '../views/AdminProductsView.vue'
import AdminOrdersView from '../views/AdminOrdersView.vue'
import AdminFeedbackView from '../views/AdminFeedbackView.vue'
import FeedbackView from '../views/FeedbackView.vue'
import MyOrdersView from '../views/MyOrdersView.vue'
import ProfileView from '../views/ProfileView.vue'
import { apiFetch } from '../lib/api'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  {
    path: '/sign-in',
    name: 'sign-in',
    component: AuthView
  },
  {
    path: '/sign-up',
    name: 'sign-up',
    component: AuthView
  },
  {
    path: '/google-auth/callback',
    name: 'auth-google-callback',
    component: AuthView
  },
  {
    path: '/product/:slug',
    name: 'product-details',
    component: ProductDetailsView
  },
  {
    path: '/checkout',
    name: 'checkout',
    component: CheckoutView
  },
  {
    path: '/feedback',
    name: 'feedback',
    component: FeedbackView
  },
  {
    path: '/my-orders',
    name: 'my-orders',
    component: MyOrdersView
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView
  },
  {
    path: '/management-portal-45',
    component: AdminLayout,
    meta: { requiresAdmin: true },
    children: [
      {
        path: '',
        name: 'admin-dashboard',
        component: AdminDashboardView
      },
      {
        path: 'products',
        name: 'admin-products',
        component: AdminProductsView
      },
      {
        path: 'orders',
        name: 'admin-orders',
        component: AdminOrdersView
      },
      {
        path: 'feedback',
        name: 'admin-feedback',
        component: AdminFeedbackView
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

const fetchCurrentUser = async () => {
  const response = await apiFetch('/auth/me')

  if (!response.ok) {
    localStorage.removeItem('is_admin')
    return null
  }

  const payload = await response.json()
  const user = payload.user ?? null
  localStorage.setItem('is_admin', user?.is_admin ? 'true' : 'false')

  return user
}

// Navigation guard for admin routes
router.beforeEach(async (to, from, next) => {
  if (to.meta.requiresAdmin) {
    const user = await fetchCurrentUser()

    if (!user) {
      next({ name: 'sign-in', query: { redirect: to.fullPath } })
      return
    }

    if (!user.is_admin) {
      next({ name: 'home' })
      return
    }
  }
  next()
})

export default router
