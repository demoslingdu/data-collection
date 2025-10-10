<template>
  <a-layout class="main-layout">
    <!-- 侧边菜单栏 -->
    <a-layout-sider
      v-model:collapsed="collapsed"
      :width="240"
      :collapsed-width="64"
      :trigger="null"
      breakpoint="lg"
      class="layout-sider"
    >
      <!-- Logo 区域 -->
      <div class="logo-container">
        <div class="logo">
          <icon-storage v-if="collapsed" :size="24" />
          <span v-else>数据采集仪表板</span>
        </div>
      </div>

      <!-- 导航菜单 -->
      <a-menu
        v-model:selected-keys="selectedKeys"
        :default-open-keys="defaultOpenKeys"
        mode="vertical"
        theme="light"
        class="layout-menu"
        @menu-item-click="handleMenuClick"
      >
        <a-menu-item key="dashboard">
          <template #icon>
            <icon-dashboard />
          </template>
          仪表板
        </a-menu-item>

        <a-menu-item  v-if="isAdmin" key="data-records">
          <template #icon>
            <icon-file />
          </template>
          数据记录
        </a-menu-item>

        <a-menu-item v-if="isAdmin" key="profile">
          <template #icon>
            <icon-user />
          </template>
          个人资料
        </a-menu-item>

        <!-- 管理员菜单 -->
        <a-menu-item v-if="isAdmin" key="admin">
          <template #icon>
            <icon-settings />
          </template>
          管理员控制台
        </a-menu-item>
      </a-menu>
    </a-layout-sider>

    <a-layout class="layout-content-wrapper">
      <!-- 头部栏 -->
      <a-layout-header class="layout-header">
        <div class="header-left">
          <a-button
            type="text"
            size="large"
            @click="toggleCollapsed"
            class="collapse-btn"
          >
            <icon-menu-unfold v-if="collapsed" />
            <icon-menu-fold v-else />
          </a-button>
          
          <a-breadcrumb class="breadcrumb">
            <a-breadcrumb-item>{{ currentPageTitle }}</a-breadcrumb-item>
          </a-breadcrumb>
        </div>

        <div class="header-right">
          <!-- 用户信息下拉菜单 -->
          <a-dropdown>
            <div class="user-info">
              <a-avatar :size="32" class="user-avatar">
                <icon-user />
              </a-avatar>
              <span class="username">{{ userInfo?.name || '用户' }}</span>
              <icon-down />
            </div>
            
            <template #content>
              <a-doption @click="goToProfile">
                <template #icon>
                  <icon-user />
                </template>
                个人资料
              </a-doption>
              <a-doption @click="handleLogout">
                <template #icon>
                  <icon-export />
                </template>
                退出登录
              </a-doption>
            </template>
          </a-dropdown>
        </div>
      </a-layout-header>

      <!-- 内容区域 -->
      <a-layout-content class="layout-content">
        <RouterView />
      </a-layout-content>
    </a-layout>
  </a-layout>
</template>

<script setup lang="ts">
/**
 * 主布局组件
 * 提供应用的整体布局结构，包括侧边菜单、头部栏和内容区域
 */
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Message } from '@arco-design/web-vue'
import {
  IconStorage,
  IconDashboard,
  IconFile,
  IconUser,
  IconSettings,
  IconMenuFold,
  IconMenuUnfold,
  IconDown,
  IconExport
} from '@arco-design/web-vue/es/icon'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

// 响应式数据
const collapsed = ref(false)
const selectedKeys = ref<string[]>([])
const defaultOpenKeys = ref<string[]>([])

// 计算属性
const userInfo = computed(() => authStore.user)
const isAdmin = computed(() => authStore.isAdmin)

// 页面标题映射
const pageTitleMap: Record<string, string> = {
  dashboard: '仪表板',
  'data-records': '数据记录',
  'data-record-create': '新建数据记录',
  'data-record-detail': '数据记录详情',
  profile: '个人资料',
  admin: '管理员控制台'
}

// 当前页面标题
const currentPageTitle = computed(() => {
  const routeName = route.name as string
  return pageTitleMap[routeName] || '数据采集仪表板'
})

/**
 * 切换侧边栏折叠状态
 */
const toggleCollapsed = () => {
  collapsed.value = !collapsed.value
}

/**
 * 处理菜单点击
 */
const handleMenuClick = (key: string) => {
  const routeMap: Record<string, string> = {
    dashboard: '/dashboard',
    'data-records': '/data-records',
    profile: '/profile',
    admin: '/admin'
  }

  const path = routeMap[key]
  if (path && route.path !== path) {
    router.push(path)
  }
}

/**
 * 跳转到个人资料页面
 */
const goToProfile = () => {
  router.push('/profile')
}

/**
 * 处理退出登录
 */
const handleLogout = async () => {
  try {
    await authStore.userLogout()
    Message.success('退出登录成功')
    router.push('/login')
  } catch (error) {
    console.error('退出登录失败:', error)
    Message.error('退出登录失败')
  }
}

// 监听路由变化，更新选中的菜单项
watch(
  () => route.path,
  (newPath) => {
    const pathToKeyMap: Record<string, string> = {
      '/dashboard': 'dashboard',
      '/data-records': 'data-records',
      '/data': 'data-records',
      '/profile': 'profile',
      '/admin': 'admin'
    }

    // 查找匹配的菜单项
    let matchedKey = ''
    for (const [path, key] of Object.entries(pathToKeyMap)) {
      if (newPath.startsWith(path)) {
        matchedKey = key
        break
      }
    }

    if (matchedKey) {
      selectedKeys.value = [matchedKey]
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.main-layout {
  min-height: 100vh;
}

.layout-sider {
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  z-index: 100;
  box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
  background: #fff;
}

.logo-container {
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8f9fa;
  margin-bottom: 1px;
  border-bottom: 1px solid #e5e6eb;
}

.logo {
  color: #1d2129;
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.layout-menu {
  border-right: none;
}

.layout-content-wrapper {
  margin-left: 240px;
  transition: margin-left 0.2s;
}

.layout-content-wrapper.collapsed {
  margin-left: 64px;
}

.layout-header {
  background: #fff;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 99;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.collapse-btn {
  color: #666;
}

.breadcrumb {
  font-size: 16px;
  font-weight: 500;
}

.header-right {
  display: flex;
  align-items: center;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.user-info:hover {
  background-color: #f5f5f5;
}

.user-avatar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.username {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.layout-content {
  background: #f5f5f5;
  min-height: calc(100vh - 64px);
  padding: 24px;
}

/* 响应式设计 */
@media (max-width: 1024px) {
  .layout-sider {
    position: fixed;
    z-index: 1000;
  }
  
  .layout-content-wrapper {
    margin-left: 0;
  }
  
  .layout-content {
    padding: 16px;
  }
}

@media (max-width: 768px) {
  .header-left .breadcrumb {
    display: none;
  }
  
  .username {
    display: none;
  }
  
  .layout-content {
    padding: 12px;
  }
}

/* 当侧边栏折叠时的样式调整 */
:deep(.arco-layout-sider-collapsed) + .layout-content-wrapper {
  margin-left: 64px;
}
</style>