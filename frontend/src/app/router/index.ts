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
      path: '/chat/:matchId',
      name: 'chat',
      component: () => import('@/pages/chat/ChatPage.vue'),
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
