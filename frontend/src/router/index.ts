import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      redirect: '/dashboard'
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/auth/RegisterView.vue'),
      meta: { requiresGuest: true }
    },
    // 使用布局的路由
    {
      path: '/',
      component: () => import('@/components/Layout/MainLayout.vue'),
      children: [
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/views/DashboardView.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'data-records',
          name: 'data-records',
          component: () => import('@/views/data/DataRecordListView.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'data-records/create',
          name: 'data-record-create',
          component: () => import('@/views/data/DataRecordCreateView.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'data-records/:id',
          name: 'data-record-detail',
          component: () => import('@/views/data/DataRecordDetailView.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'profile',
          name: 'profile',
          component: () => import('@/views/user/ProfileView.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'admin',
          name: 'admin',
          component: () => import('@/views/admin/AdminView.vue'),
          meta: { requiresAuth: true, requiresAdmin: true }
        }
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue')
    }
  ],
})

// 路由守卫
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // 如果还没有初始化认证状态，先初始化
  if (!authStore.user && authStore.token) {
    await authStore.initAuth()
  }

  // 等待初始化完成
  while (authStore.isInitializing) {
    await new Promise(resolve => setTimeout(resolve, 50))
  }

  // 检查是否需要认证
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
    return
  }

  // 检查是否需要管理员权限
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next('/dashboard')
    return
  }

  // 检查是否需要游客状态（已登录用户不能访问登录/注册页面）
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    // 根据用户角色跳转到对应页面
    if (authStore.isAdmin) {
      next('/admin')
    } else {
      next('/dashboard')
    }
    return
  }

  next()
})

export default router
