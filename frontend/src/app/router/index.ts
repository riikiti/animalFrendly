import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/entities/user/model'
import { getToken } from '@/shared/lib/tokenStorage'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/register',
      name: 'register',
      component: () => import('@/pages/auth/RegisterPage.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/pages/auth/LoginPage.vue'),
    },
    {
      path: '/',
      name: 'home',
      component: () => import('@/pages/SwipePage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/pets/new',
      name: 'create-pet',
      component: () => import('@/pages/pets/CreatePetPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/shelters',
      name: 'shelter-animals',
      component: () => import('@/pages/shelter/ShelterAnimalsPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/adoption-requests',
      name: 'my-adoption-requests',
      component: () => import('@/pages/shelter/MyAdoptionRequestsPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      // kind: 'match' | 'adoption' — см. src/pages/chat/ChatPage.vue.
      path: '/chat/:kind/:id',
      name: 'chat',
      component: () => import('@/pages/chat/ChatPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/marketplace',
      name: 'marketplace',
      component: () => import('@/pages/marketplace/MarketplacePage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/marketplace/my-listings',
      name: 'my-listings',
      component: () => import('@/pages/marketplace/MyListingsPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/orders',
      name: 'my-orders',
      component: () => import('@/pages/marketplace/MyOrdersPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      // Совпадает с return_url платежа ЮKassa — см. src/pages/marketplace/OrderDetailPage.vue.
      path: '/orders/:id',
      name: 'order-detail',
      component: () => import('@/pages/marketplace/OrderDetailPage.vue'),
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach(async (to) => {
  if (!to.meta.requiresAuth) return true

  if (!getToken()) {
    return { name: 'login' }
  }

  const userStore = useUserStore()

  if (!userStore.currentUser) {
    try {
      await userStore.fetchCurrentUser()
    } catch {
      return { name: 'login' }
    }
  }

  return true
})

export default router
