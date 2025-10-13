import { request } from './index'

export interface Company {
  id: number
  name: string
  code: string
  description?: string
  contact_person?: string
  contact_phone?: string
  contact_email?: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface CompanyCreateRequest {
  name: string
  code: string
  description?: string
  contact_person?: string
  contact_phone?: string
  contact_email?: string
  is_active?: boolean
}

export interface CompanyUpdateRequest extends CompanyCreateRequest {}

export interface CompanyListResponse {
  success: boolean
  data: {
    data: Company[]
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface CompanyResponse {
  success: boolean
  data: Company
  message?: string
}

export interface CompanyListParams {
  page?: number
  per_page?: number
  search?: string
  is_active?: boolean
}

/**
 * 获取公司列表
 */
export const getCompanies = (params?: CompanyListParams) => {
  return request<CompanyListResponse>({
    url: '/companies',
    method: 'GET',
    params
  })
}

/**
 * 获取启用的公司列表（用于下拉选择）
 */
export const getActiveCompanies = () => {
  return request<{ success: boolean; data: Company[] }>({
    url: '/companies/active',
    method: 'GET'
  })
}

/**
 * 创建公司
 */
export const createCompany = (data: CompanyCreateRequest) => {
  return request<CompanyResponse>({
    url: '/companies',
    method: 'POST',
    data
  })
}

/**
 * 获取公司详情
 */
export const getCompany = (id: number) => {
  return request<CompanyResponse>({
    url: `/companies/${id}`,
    method: 'GET'
  })
}

/**
 * 更新公司信息
 */
export const updateCompany = (id: number, data: CompanyUpdateRequest) => {
  return request<CompanyResponse>({
    url: `/companies/${id}`,
    method: 'PUT',
    data
  })
}

/**
 * 删除公司
 */
export const deleteCompany = (id: number) => {
  return request<{ success: boolean; message: string }>({
    url: `/companies/${id}`,
    method: 'DELETE'
  })
}

/**
 * 切换公司状态
 */
export const toggleCompanyStatus = (id: number) => {
  return request<CompanyResponse>({
    url: `/companies/${id}/toggle-status`,
    method: 'PUT'
  })
}