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
    {
      path: '/subscription/plans',
      name: 'subscription-plans',
      component: () => import('@/pages/subscription/PlansPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      // Совпадает с return_url платежа ЮKassa — см. src/pages/subscription/SubscriptionStatusPage.vue.
      path: '/subscription/status',
      name: 'subscription-status',
      component: () => import('@/pages/subscription/SubscriptionStatusPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/notifications',
      name: 'notifications',
      component: () => import('@/pages/notifications/NotificationsPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/search/pets',
      name: 'search-pets',
      component: () => import('@/pages/search/SearchPetsPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/settings/profile',
      name: 'profile-settings',
      component: () => import('@/pages/settings/ProfileSettingsPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/admin',
      name: 'admin-dashboard',
      component: () => import('@/pages/admin/AdminDashboardPage.vue'),
      meta: { requiresAuth: true, requiresStaff: true },
    },
    {
      path: '/admin/reports',
      name: 'admin-reports',
      component: () => import('@/pages/admin/AdminReportsPage.vue'),
      meta: { requiresAuth: true, requiresStaff: true },
    },
    {
      path: '/admin/audit-log',
      name: 'admin-audit-log',
      component: () => import('@/pages/admin/AdminAuditLogPage.vue'),
      meta: { requiresAuth: true, requiresStaff: true },
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

  if (to.meta.requiresStaff) {
    const accountType = userStore.currentUser?.account_type
    if (accountType !== 'admin' && accountType !== 'moderator') {
      return { name: 'home' }
    }
  }

  return true
})

export default router
