<template>
  <div class="not-found-container">
    <div class="not-found-content">
      <!-- 404 图标和数字 -->
      <div class="error-icon">
        <icon-exclamation-circle-fill :size="120" />
      </div>
      
      <div class="error-code">404</div>
      
      <!-- 错误信息 -->
      <div class="error-message">
        <h2>页面未找到</h2>
        <p>抱歉，您访问的页面不存在或已被移除。</p>
      </div>
      
      <!-- 操作按钮 -->
      <div class="error-actions">
        <a-space size="large">
          <a-button type="primary" size="large" @click="goHome">
            <template #icon>
              <icon-home />
            </template>
            返回首页
          </a-button>
          
          <a-button type="outline" size="large" @click="goBack">
            <template #icon>
              <icon-left />
            </template>
            返回上页
          </a-button>
          
          <a-button type="text" size="large" @click="refreshPage">
            <template #icon>
              <icon-refresh />
            </template>
            刷新页面
          </a-button>
        </a-space>
      </div>
      
      <!-- 建议链接 -->
      <div class="suggestions">
        <h3>您可能想要访问：</h3>
        <div class="suggestion-links">
          <a-link @click="goToDashboard">
            <icon-dashboard />
            仪表板
          </a-link>
          <a-link @click="goToRecords">
            <icon-file />
            数据记录
          </a-link>
          <a-link @click="goToProfile">
            <icon-user />
            个人资料
          </a-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * 404 错误页面组件
 * 当用户访问不存在的页面时显示
 */
import { useRouter } from 'vue-router'
import {
  IconExclamationCircleFill,
  IconHome,
  IconLeft,
  IconRefresh,
  IconDashboard,
  IconFile,
  IconUser
} from '@arco-design/web-vue/es/icon'

// 路由实例
const router = useRouter()

/**
 * 返回首页
 */
const goHome = () => {
  router.push('/')
}

/**
 * 返回上一页
 */
const goBack = () => {
  if (window.history.length > 1) {
    router.go(-1)
  } else {
    router.push('/')
  }
}

/**
 * 刷新当前页面
 */
const refreshPage = () => {
  window.location.reload()
}

/**
 * 跳转到仪表板
 */
const goToDashboard = () => {
  router.push('/dashboard')
}

/**
 * 跳转到数据记录页面
 */
const goToRecords = () => {
  router.push('/records')
}

/**
 * 跳转到个人资料页面
 */
const goToProfile = () => {
  router.push('/profile')
}
</script>

<style scoped>
.not-found-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.not-found-content {
  text-align: center;
  background: white;
  border-radius: 16px;
  padding: 60px 40px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  max-width: 600px;
  width: 100%;
}

.error-icon {
  margin-bottom: 20px;
  color: #f53f3f;
}

.error-code {
  font-size: 120px;
  font-weight: 700;
  color: #1d2129;
  line-height: 1;
  margin-bottom: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.error-message {
  margin-bottom: 40px;
}

.error-message h2 {
  font-size: 32px;
  font-weight: 600;
  color: #1d2129;
  margin: 0 0 16px 0;
}

.error-message p {
  font-size: 16px;
  color: #86909c;
  margin: 0;
  line-height: 1.6;
}

.error-actions {
  margin-bottom: 40px;
}

.error-actions .arco-btn {
  height: 48px;
  padding: 0 24px;
  font-size: 16px;
  font-weight: 500;
  border-radius: 8px;
}

.suggestions {
  border-top: 1px solid #e5e6eb;
  padding-top: 30px;
}

.suggestions h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1d2129;
  margin: 0 0 20px 0;
}

.suggestion-links {
  display: flex;
  justify-content: center;
  gap: 32px;
  flex-wrap: wrap;
}

.suggestion-links .arco-link {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 500;
  color: #165dff;
  text-decoration: none;
  padding: 8px 16px;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.suggestion-links .arco-link:hover {
  background-color: #f2f3ff;
  color: #0e42d2;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .not-found-content {
    padding: 40px 20px;
  }
  
  .error-code {
    font-size: 80px;
  }
  
  .error-message h2 {
    font-size: 24px;
  }
  
  .error-actions .arco-space {
    flex-direction: column;
    width: 100%;
  }
  
  .error-actions .arco-btn {
    width: 100%;
  }
  
  .suggestion-links {
    flex-direction: column;
    gap: 16px;
  }
}

@media (max-width: 480px) {
  .not-found-container {
    padding: 10px;
  }
  
  .not-found-content {
    padding: 30px 15px;
  }
  
  .error-code {
    font-size: 60px;
  }
  
  .error-message h2 {
    font-size: 20px;
  }
  
  .error-message p {
    font-size: 14px;
  }
}
</style>