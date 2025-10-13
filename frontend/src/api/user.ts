/**
 * 用户管理相关 API
 */
import apiClient from './index'
import type { ApiResponse } from './auth'
import type { Company } from './company'

// 用户信息接口
export interface User {
  id: number
  name: string
  account: string
  email?: string
  role: 'admin' | 'user'
  company_id?: number
  company?: Company
  created_at: string
  updated_at: string
}

// 用户创建数据
export interface CreateUserData {
  name: string
  account: string
  password: string
  password_confirmation: string
  role: 'admin' | 'user'
  company_id?: number
}

// 用户更新数据
export interface UpdateUserData {
  name?: string
  account?: string
  role?: 'admin' | 'user'
  company_id?: number
}

// 用户查询参数
export interface UserQuery {
  page?: number
  per_page?: number
  search?: string
  role?: string
}

// 分页信息
export interface PaginationInfo {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

// 用户列表响应格式（匹配后端实际返回结构）
export interface UserListResponse {
  users: User[]
  pagination: PaginationInfo
}

// 用户统计数据
export interface UserStatistics {
  total_users: number
  admin_users: number
  regular_users: number
  active_users: number
  today_new_users: number
}

/**
 * 获取用户列表
 */
export const getUserList = (params?: UserQuery): Promise<ApiResponse<UserListResponse>> => {
  return apiClient.get('/users', { params })
}

/**
 * 获取单个用户详情
 */
export const getUser = (id: number): Promise<ApiResponse<User>> => {
  return apiClient.get(`/users/${id}`)
}

/**
 * 创建用户
 */
export const createUser = (data: CreateUserData): Promise<ApiResponse<User>> => {
  return apiClient.post('/users', data)
}

/**
 * 更新用户
 */
export const updateUser = (id: number, data: UpdateUserData): Promise<ApiResponse<User>> => {
  return apiClient.put(`/users/${id}`, data)
}

/**
 * 删除用户
 */
export const deleteUser = (id: number): Promise<ApiResponse> => {
  return apiClient.delete(`/users/${id}`)
}

/**
 * 批量删除用户
 */
export const batchDeleteUsers = (ids: number[]): Promise<ApiResponse> => {
  return apiClient.delete('/users/batch', { data: { ids } })
}

/**
 * 重置用户密码
 */
export const resetUserPassword = (id: number, password: string): Promise<ApiResponse> => {
  return apiClient.put(`/users/${id}/reset-password`, { password })
}

/**
 * 切换用户角色
 */
export const toggleUserRole = (id: number): Promise<ApiResponse<User>> => {
  return apiClient.put(`/users/${id}/toggle-role`)
}

/**
 * 获取用户统计信息
 */
export const getUserStatistics = (): Promise<ApiResponse<UserStatistics>> => {
  return apiClient.get('/users/statistics')
}

/**
 * 用户管理 API 对象
 * 为了兼容现有代码，将所有 API 函数组织成一个对象
 */
export const userApi = {
  getUserList,
  getUser,
  createUser,
  updateUser,
  deleteUser,
  batchDeleteUsers,
  resetUserPassword,
  toggleUserRole,
  getUserStatistics
}