<template>
  <div class="dashboard-container">
    <!-- 页面头部 -->
    <div class="dashboard-header">
      <h1>数据采集仪表板</h1>
      <p>欢迎回来，{{ userStore.user?.name || '用户' }}！</p>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * 仪表板页面组件
 * 简洁的欢迎页面
 */
import { useAuthStore } from '@/stores/auth'

// 状态管理
const userStore = useAuthStore()
</script>

<style scoped>
.dashboard-container {
  padding: 24px;
  background-color: #f5f5f5;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dashboard-header {
  text-align: center;
}

.dashboard-header h1 {
  margin: 0 0 16px 0;
  font-size: 32px;
  font-weight: 600;
  color: #1d2129;
}

.dashboard-header p {
  margin: 0;
  font-size: 18px;
  color: #86909c;
}
</style>