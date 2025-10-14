/**
 * 用户认证相关 API
 */
import apiClient from './index'

// 用户注册接口
export interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
}

// 用户登录接口
export interface LoginData {
  account: string
  password: string
}

// 修改密码接口
export interface ChangePasswordData {
  current_password: string
  new_password: string
  new_password_confirmation: string
}

// 用户信息接口
export interface User {
  id: number
  name: string
  account: string
  role: 'admin' | 'user' | 'company_admin'
  company_id?: number
  email?: string
  created_at: string
  updated_at: string
}

// API 响应格式
export interface ApiResponse<T = any> {
  success: boolean
  message: string
  data?: T
}

// 认证响应格式
export interface AuthResponse {
  user: User
  token: string
}

/**
 * 用户注册
 */
export const register = (data: RegisterData): Promise<ApiResponse<AuthResponse>> => {
  return apiClient.post('/register', data)
}

/**
 * 用户登录
 */
export const login = (data: LoginData): Promise<ApiResponse<AuthResponse>> => {
  return apiClient.post('/login', data)
}

/**
 * 用户登出
 */
export const logout = (): Promise<ApiResponse> => {
  return apiClient.post('/auth/logout')
}

/**
 * 获取当前用户信息
 */
export const getCurrentUser = (): Promise<ApiResponse<User>> => {
  return apiClient.get('/auth/user')
}

/**
 * 修改密码
 */
export const changePassword = (data: ChangePasswordData): Promise<ApiResponse> => {
  return apiClient.put('/auth/change-password', data)
}