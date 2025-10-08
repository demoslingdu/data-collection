# 数据采集平台 API 接口文档

## 目录

1. [概述](#1-概述)
2. [用户注册接口](#2-用户注册接口)
3. [用户登录接口](#3-用户登录接口)
4. [用户退出登录接口](#4-用户退出登录接口)
5. [获取当前用户信息接口](#5-获取当前用户信息接口)
6. [创建数据记录接口](#6-创建数据记录接口)
7. [获取数据记录列表接口](#7-获取数据记录列表接口)
8. [获取数据记录详情接口](#8-获取数据记录详情接口)
9. [更新数据记录接口](#9-更新数据记录接口)
10. [删除数据记录接口](#10-删除数据记录接口)
11. [批量删除数据记录接口](#11-批量删除数据记录接口)
12. [领取数据记录接口](#12-领取数据记录接口)
13. [完成数据记录接口](#13-完成数据记录接口)
14. [标记重复记录接口](#14-标记重复记录接口)
15. [获取数据统计信息接口](#15-获取数据统计信息接口)
16. [图片上传接口](#16-图片上传接口)
17. [获取图片列表接口](#17-获取图片列表接口)
18. [删除图片接口](#18-删除图片接口)
19. [获取用户列表接口（管理员）](#19-获取用户列表接口管理员)
20. [创建用户接口（管理员）](#20-创建用户接口管理员)
21. [获取用户统计接口（管理员）](#21-获取用户统计接口管理员)
22. [获取用户详情接口（管理员）](#22-获取用户详情接口管理员)
23. [更新用户接口（管理员）](#23-更新用户接口管理员)
24. [删除用户接口（管理员）](#24-删除用户接口管理员)
25. [批量删除用户接口（管理员）](#25-批量删除用户接口管理员)
26. [重置用户密码接口（管理员）](#26-重置用户密码接口管理员)
27. [切换用户角色接口（管理员）](#27-切换用户角色接口管理员)
28. [错误处理](#28-错误处理)
29. [开发指南](#29-开发指南)
30. [测试工具推荐](#30-测试工具推荐)
31. [更新日志](#31-更新日志)
32. [联系方式](#32-联系方式)

---

## 1. 概述

本文档描述了数据采集平台的完整API接口，包括用户认证、数据记录管理、图片管理和用户管理功能。所有API接口均采用RESTful设计风格，使用JSON格式进行数据交换。

### 1.1 基础信息

- **基础URL**: `http://localhost:8000/api`
- **数据格式**: JSON
- **字符编码**: UTF-8
- **认证方式**: Bearer Token (Laravel Sanctum)

### 1.2 通用响应格式

所有API接口均采用统一的响应格式：

```json
{
  "success": boolean,      // 请求是否成功
  "message": "string",     // 响应消息
  "data": object|null,     // 响应数据
  "errors": object|null    // 错误详情（仅在验证失败时存在）
}
```

### 1.3 HTTP状态码说明

| 状态码 | 说明 |
|--------|------|
| 200 | 请求成功 |
| 201 | 创建成功 |
| 401 | 未认证或认证失败 |
| 403 | 权限不足 |
| 404 | 资源不存在 |
| 422 | 请求参数验证失败 |
| 500 | 服务器内部错误 |

---

## 2. 用户注册接口

### 2.1 接口信息

- **接口路径**: `POST /api/register`
- **接口描述**: 用户注册，创建新用户账号并获取访问令牌
- **认证要求**: 无需认证（公开接口）
- **请求方式**: POST
- **Content-Type**: `application/json`

### 3.2 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| name | string | 是 | 用户姓名，最大255字符 |
| account | string | 是 | 用户账号，最大100字符，必须唯一 |
| password | string | 是 | 用户密码，最少6位 |
| password_confirmation | string | 是 | 确认密码，必须与password一致 |

#### 参数验证规则

- `name`: 必填，字符串类型，最大长度255字符
- `account`: 必填，字符串类型，最大长度100字符，必须在系统中唯一
- `password`: 必填，字符串类型，最少6位
- `password_confirmation`: 必填，必须与password字段值一致

### 3.3 请求示例

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "张三",
    "account": "zhangsan",
    "password": "123456",
    "password_confirmation": "123456"
  }'
```

```json
{
  "name": "张三",
  "account": "zhangsan",
  "password": "123456",
  "password_confirmation": "123456"
}
```

### 3.4 响应示例

#### 3.4.1 登录成功 (200)

```json
{
  "success": true,
  "message": "注册成功",
  "data": {
    "user": {
      "id": 2,
      "name": "张三",
      "account": "zhangsan",
      "role": "user"
    },
    "token": "2|abcdefghijklmnopqrstuvwxyz123456789"
  }
}
```

#### 3.4.2 参数验证失败 (422)

```json
{
  "success": false,
  "message": "验证失败",
  "errors": {
    "name": ["姓名不能为空"],
    "account": ["账户名已被注册"],
    "password": ["密码至少6位"],
    "password_confirmation": ["两次密码不一致"]
  }
}
```

#### 3.4.3 登录失败 (401)

```json
{
  "success": false,
  "message": "注册失败",
  "error": "具体错误信息"
}
```

### 3.5 业务逻辑说明

1. **注册流程**:
   - 验证请求参数的完整性和格式
   - 检查账号是否已存在
   - 验证密码确认
   - 创建新用户（默认角色为user）
   - 生成访问令牌并返回

2. **用户角色**:
   - 新注册用户默认角色为 `user`（普通用户）
   - 管理员账号需要通过其他方式创建

3. **令牌管理**:
   - 注册成功后自动生成Bearer Token
   - 用户可直接使用返回的token进行后续操作

### 3.6 使用说明

1. 用户填写注册信息
2. 系统验证信息有效性
3. 创建用户账号
4. 返回用户信息和访问令牌
5. 客户端保存token用于后续请求

---

## 3. 用户登录接口

### 3.1 接口信息

- **接口路径**: `POST /api/login`
- **接口描述**: 用户登录认证，获取访问令牌
- **认证要求**: 无需认证（公开接口）
- **请求方式**: POST
- **Content-Type**: `application/json`

### 2.2 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| account | string | 是 | 用户账号 |
| password | string | 是 | 用户密码 |

#### 参数验证规则

- `account`: 必填，字符串类型，不能为空
- `password`: 必填，字符串类型，不能为空

### 2.3 请求示例

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "account": "admin",
    "password": "123456"
  }'
```

```json
{
  "account": "admin",
  "password": "123456"
}
```

### 2.4 响应示例

#### 2.4.1 登录成功 (200)

```json
{
  "success": true,
  "message": "登录成功",
  "data": {
    "user": {
      "id": 1,
      "name": "系统管理员",
      "account": "admin",
      "role": "admin"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz123456789"
  }
}
```

#### 2.4.2 参数验证失败 (422)

```json
{
  "success": false,
  "message": "验证失败",
  "errors": {
    "account": ["账户名不能为空"],
    "password": ["密码不能为空"]
  }
}
```

#### 2.4.3 认证失败 (401)

```json
{
  "success": false,
  "message": "账户名或密码错误"
}
```

#### 2.4.4 服务器错误 (500)

```json
{
  "success": false,
  "message": "登录失败",
  "error": "具体错误信息"
}
```

### 2.5 业务逻辑说明

1. **认证流程**:
   - 验证请求参数的完整性和格式
   - 使用账号和密码进行身份验证
   - 验证成功后生成访问令牌（Token）
   - 返回用户信息和令牌

2. **令牌管理**:
   - 使用Laravel Sanctum生成Bearer Token
   - 令牌用于后续API请求的身份验证
   - 令牌格式：`{token_id}|{plain_text_token}`

3. **用户角色**:
   - `admin`: 管理员，拥有所有权限
   - `user`: 普通用户，拥有基础功能权限

### 2.6 使用说明

1. 客户端发送登录请求
2. 服务器验证用户凭据
3. 登录成功后，客户端保存返回的token
4. 后续请求在Header中携带token：`Authorization: Bearer {token}`

---

## 4. 用户退出登录接口

### 4.1 接口信息

- **接口路径**: `POST /api/auth/logout`
- **接口描述**: 用户退出登录，撤销当前访问令牌
- **认证要求**: 需要Bearer Token认证
- **请求方式**: POST
- **Content-Type**: `application/json`

### 4.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 4.3 请求参数

无需请求参数

### 4.4 请求示例

```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json"
```

### 4.5 响应示例

#### 4.5.1 退出成功 (200)

```json
{
  "success": true,
  "message": "退出登录成功"
}
```

#### 4.5.2 认证失败 (401)

```json
{
  "success": false,
  "message": "未认证或令牌无效"
}
```

### 4.6 业务逻辑说明

1. **退出流程**:
   - 验证请求头中的Bearer Token
   - 撤销当前用户的访问令牌
   - 返回退出成功消息

2. **令牌管理**:
   - 退出后当前令牌立即失效
   - 用户需要重新登录获取新令牌

### 4.7 使用说明

1. 用户点击退出登录
2. 客户端发送退出请求（携带当前token）
3. 服务器撤销令牌
4. 客户端清除本地保存的token
5. 跳转到登录页面

---

## 5. 获取当前用户信息接口

### 5.1 接口信息

- **接口路径**: `GET /api/auth/user`
- **接口描述**: 获取当前登录用户的详细信息
- **认证要求**: 需要Bearer Token认证
- **请求方式**: GET

### 5.2 请求头

```
Authorization: Bearer {token}
```

### 5.3 请求参数

无需请求参数

### 5.4 请求示例

```bash
curl -X GET http://localhost:8000/api/auth/user \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 5.5 响应示例

#### 5.5.1 获取成功 (200)

```json
{
  "success": true,
  "message": "获取用户信息成功",
  "data": {
    "id": 1,
    "name": "管理员",
    "account": "admin",
    "role": "admin",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### 5.5.2 认证失败 (401)

```json
{
  "success": false,
  "message": "未认证或令牌无效"
}
```

### 5.6 业务逻辑说明

1. **获取流程**:
   - 验证请求头中的Bearer Token
   - 根据令牌获取对应的用户信息
   - 返回用户详细信息

2. **用户信息**:
   - 包含用户ID、姓名、账号、角色等基本信息
   - 包含创建时间和更新时间
   - 不包含敏感信息（如密码）

### 5.7 使用说明

1. 用户登录后获取用户信息
2. 用于显示用户资料
3. 验证用户权限和角色
4. 个人中心页面数据展示

---

## 6. 创建数据记录接口

### 6.1 接口信息

- **接口路径**: `POST /api/data-records`
- **接口描述**: 创建新的数据记录
- **认证要求**: 需要Bearer Token认证
- **请求方式**: POST
- **Content-Type**: `application/json`

### 6.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 6.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| platform | string | 是 | 平台名称，最大255字符 |
| platform_id | string | 是 | 平台ID，最大255字符 |
| content | string | 否 | 记录内容，支持长文本 |
| status | string | 否 | 记录状态：`pending`（待处理）、`claimed`（已领取）、`completed`（已完成）、`duplicate`（重复） |
| metadata | object | 否 | 元数据对象，用于存储额外信息 |

#### 参数验证规则

- `platform`: 必填，字符串类型，最大长度255字符
- `platform_id`: 必填，字符串类型，最大长度255字符
- `content`: 可选，字符串类型，无长度限制
- `status`: 可选，枚举值，默认为 `pending`
- `metadata`: 可选，对象/数组类型，用于存储结构化数据

#### 去重逻辑

- 系统会检查相同平台和平台ID的记录
- 3天内的重复记录会被标记为重复（duplicate）
- 超过3天的记录算作新客资

### 6.4 请求示例

```bash
curl -X POST http://localhost:8000/api/data-records \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{
    "platform": "淘宝",
    "platform_id": "TB123456789",
    "content": "某品牌手机的价格和销量数据",
    "status": "pending",
    "metadata": {
      "product_type": "electronics",
      "target_count": 1000,
      "source_url": "https://item.taobao.com/item.htm?id=123456789"
    }
  }'
```

```json
{
  "platform": "淘宝",
  "platform_id": "TB123456789",
  "content": "某品牌手机的价格和销量数据",
  "status": "pending",
  "metadata": {
    "product_type": "electronics",
    "target_count": 1000,
    "source_url": "https://item.taobao.com/item.htm?id=123456789"
  }
}
```

### 6.5 响应示例

#### 6.5.1 创建成功 (201)

```json
{
  "success": true,
  "message": "数据记录创建成功",
  "data": {
    "id": 1,
    "platform": "淘宝",
    "platform_id": "TB123456789",
    "content": "某品牌手机的价格和销量数据",
    "status": "pending",
    "metadata": {
      "product_type": "electronics",
      "target_count": 1000,
      "source_url": "https://item.taobao.com/item.htm?id=123456789"
    },
    "user_id": 1,
    "claimed_by": null,
    "claimed_at": null,
    "completed_at": null,
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T10:30:00.000000Z",
    "user": {
      "id": 1,
      "name": "张三",
      "account": "zhangsan",
      "role": "user"
    }
  }
}
```

#### 6.5.2 重复记录 (201)

```json
{
  "success": true,
  "message": "检测到重复记录，已标记为重复",
  "data": {
    "id": 2,
    "platform": "淘宝",
    "platform_id": "TB123456789",
    "content": "某品牌手机的价格和销量数据",
    "status": "duplicate",
    "metadata": {
      "product_type": "electronics",
      "target_count": 1000,
      "source_url": "https://item.taobao.com/item.htm?id=123456789"
    },
    "user_id": 1,
    "claimed_by": null,
    "claimed_at": null,
    "completed_at": null,
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T10:30:00.000000Z",
    "user": {
      "id": 1,
      "name": "张三",
      "account": "zhangsan",
      "role": "user"
    }
  }
}
```

#### 6.5.3 参数验证失败 (422)

```json
{
  "success": false,
  "message": "验证失败",
  "errors": {
    "platform": ["平台名称不能为空"],
    "platform_id": ["平台ID不能为空"],
    "status": ["状态值无效"],
    "metadata": ["元数据必须是对象格式"]
  }
}
```

#### 6.5.4 认证失败 (401)

```json
{
  "success": false,
  "message": "未认证或令牌无效"
}
```

#### 6.5.5 服务器错误 (500)

```json
{
  "success": false,
  "message": "创建数据记录失败",
  "error": "具体错误信息"
}
```

### 6.6 业务逻辑说明

1. **自动字段填充**:
   - `user_id`: 自动设置为当前认证用户的ID
   - `status`: 如果未提供，默认设置为 `pending`
   - `created_at` 和 `updated_at`: 自动设置为当前时间戳

2. **去重机制**:
   - 系统会检查相同平台和平台ID的记录
   - 3天内的重复记录会被标记为 `duplicate`
   - 超过3天的记录算作新客资，状态为 `pending`

3. **关联数据**:
   - 每个数据记录都会关联到创建者
   - 响应中包含创建者的用户信息（通过 `user` 关联）

4. **权限控制**:
   - 只有认证用户才能创建数据记录
   - 记录会自动关联到创建者，确保数据归属明确

5. **元数据支持**:
   - `metadata` 字段支持存储任意结构化数据
   - 常用于存储平台特定信息、配置参数等

### 6.7 使用场景

1. **数据采集任务创建**: 用户创建新的数据采集任务
2. **客资管理**: 记录客户资源信息和状态
3. **平台数据管理**: 通过platform字段对不同平台数据进行管理
4. **状态跟踪**: 通过status字段跟踪记录的生命周期

---

## 7. 获取数据记录列表接口

### 7.1 接口信息

- **接口路径**: `GET /api/data-records`
- **接口描述**: 获取数据记录列表，支持搜索、筛选、排序和分页
- **认证要求**: 需要Bearer Token认证
- **请求方式**: GET

### 7.2 请求头

```
Authorization: Bearer {token}
```

### 7.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| search | string | 否 | 搜索关键词，搜索平台名称、平台ID和内容 |
| status | string | 否 | 状态筛选：`pending`、`claimed`、`completed`、`duplicate` |
| platform | string | 否 | 平台筛选 |
| user_id | integer | 否 | 创建者ID筛选 |
| claimed_by | integer | 否 | 领取者ID筛选 |
| sort_by | string | 否 | 排序字段：`created_at`、`updated_at`、`platform`、`status` |
| sort_order | string | 否 | 排序方向：`asc`（升序）、`desc`（降序），默认`desc` |
| page | integer | 否 | 页码，默认1 |
| per_page | integer | 否 | 每页数量，默认15，最大100 |

### 7.4 请求示例

```bash
curl -X GET "http://localhost:8000/api/data-records?search=淘宝&status=pending&page=1&per_page=10" \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 7.5 响应示例

#### 7.5.1 获取成功 (200)

```json
{
  "success": true,
  "message": "获取数据记录列表成功",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "platform": "淘宝",
        "platform_id": "TB123456789",
        "content": "某品牌手机的价格和销量数据",
        "status": "pending",
        "metadata": {
          "product_type": "electronics",
          "target_count": 1000
        },
        "user_id": 1,
        "claimed_by": null,
        "claimed_at": null,
        "completed_at": null,
        "created_at": "2024-01-20T10:30:00.000000Z",
        "updated_at": "2024-01-20T10:30:00.000000Z",
        "user": {
          "id": 1,
          "name": "张三",
          "account": "zhangsan",
          "role": "user"
        },
        "claimer": null
      }
    ],
    "first_page_url": "http://localhost:8000/api/data-records?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://localhost:8000/api/data-records?page=5",
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://localhost:8000/api/data-records?page=1",
        "label": "1",
        "active": true
      }
    ],
    "next_page_url": "http://localhost:8000/api/data-records?page=2",
    "path": "http://localhost:8000/api/data-records",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 67
  }
}
```

#### 7.5.2 认证失败 (401)

```json
{
  "success": false,
  "message": "未认证或令牌无效"
}
```

### 7.6 业务逻辑说明

1. **搜索功能**:
   - 支持按平台名称、平台ID、内容进行模糊搜索
   - 搜索不区分大小写

2. **筛选功能**:
   - 支持按状态、平台、创建者、领取者进行筛选
   - 多个筛选条件可以组合使用

3. **排序功能**:
   - 支持按创建时间、更新时间、平台、状态排序
   - 默认按创建时间降序排列

4. **分页功能**:
   - 使用Laravel标准分页格式
   - 包含完整的分页信息和链接

5. **关联数据**:
   - 包含创建者信息（user）
   - 包含领取者信息（claimer）

### 7.7 使用说明

1. 获取所有数据记录
2. 搜索特定平台或内容的记录
3. 筛选特定状态的记录
4. 分页浏览大量数据

---

## 8. 获取数据记录详情接口

### 8.1 接口信息

- **接口路径**: `GET /api/data-records/{id}`
- **接口描述**: 获取指定ID的数据记录详细信息
- **认证要求**: 需要Bearer Token认证
- **请求方式**: GET

### 8.2 请求头

```
Authorization: Bearer {token}
```

### 8.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据记录ID（路径参数） |

### 8.4 请求示例

```bash
curl -X GET http://localhost:8000/api/data-records/1 \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 8.5 响应示例

#### 8.5.1 获取成功 (200)

```json
{
  "success": true,
  "message": "获取数据记录详情成功",
  "data": {
    "id": 1,
    "platform": "淘宝",
    "platform_id": "TB123456789",
    "content": "某品牌手机的价格和销量数据",
    "status": "claimed",
    "metadata": {
      "product_type": "electronics",
      "target_count": 1000,
      "source_url": "https://item.taobao.com/item.htm?id=123456789"
    },
    "user_id": 1,
    "claimed_by": 2,
    "claimed_at": "2024-01-20T11:00:00.000000Z",
    "completed_at": null,
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T11:00:00.000000Z",
    "user": {
      "id": 1,
      "name": "张三",
      "account": "zhangsan",
      "role": "user"
    },
    "claimer": {
      "id": 2,
      "name": "李四",
      "account": "lisi",
      "role": "user"
    }
  }
}
```

#### 8.5.2 记录不存在 (404)

```json
{
  "success": false,
  "message": "数据记录不存在"
}
```

#### 8.5.3 认证失败 (401)

```json
{
  "success": false,
  "message": "未认证或令牌无效"
}
```

### 8.6 业务逻辑说明

1. **详情展示**:
   - 返回完整的记录信息
   - 包含所有时间戳信息

2. **关联数据**:
   - 包含创建者详细信息
   - 包含领取者详细信息（如果已被领取）

3. **权限控制**:
   - 所有认证用户都可以查看记录详情
   - 不限制查看权限

### 8.7 使用说明

1. 查看记录完整信息
2. 确认记录状态和处理进度
3. 获取创建者和领取者信息

---

## 9. 更新数据记录接口

### 9.1 接口信息

- **接口路径**: `PUT /api/data-records/{id}`
- **接口描述**: 更新指定ID的数据记录信息
- **认证要求**: 需要Bearer Token认证
- **请求方式**: PUT
- **Content-Type**: `application/json`
- **权限要求**: 只有记录的创建者可以编辑

### 9.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 9.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据记录ID（路径参数） |
| platform | string | 否 | 平台名称，最大255字符 |
| platform_id | string | 否 | 平台ID，最大255字符 |
| content | string | 否 | 记录内容 |
| status | string | 否 | 记录状态 |
| metadata | object | 否 | 元数据对象 |

### 9.4 请求示例

```bash
curl -X PUT http://localhost:8000/api/data-records/1 \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{
    "platform": "京东",
    "platform_id": "JD123456789",
    "content": "更新后的商品数据",
    "metadata": {
      "updated_reason": "平台变更"
    }
  }'
```

### 9.5 响应示例

#### 9.5.1 更新成功 (200)

```json
{
  "success": true,
  "message": "数据记录更新成功",
  "data": {
    "id": 1,
    "platform": "京东",
    "platform_id": "JD123456789",
    "content": "更新后的商品数据",
    "status": "pending",
    "metadata": {
      "updated_reason": "平台变更"
    },
    "user_id": 1,
    "claimed_by": null,
    "claimed_at": null,
    "completed_at": null,
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T12:00:00.000000Z",
    "user": {
      "id": 1,
      "name": "张三",
      "account": "zhangsan",
      "role": "user"
    }
  }
}
```

#### 9.5.2 权限不足 (403)

```json
{
  "success": false,
  "message": "无权限编辑此记录，只有提交者可以编辑"
}
```

#### 9.5.3 记录不存在 (404)

```json
{
  "success": false,
  "message": "数据记录不存在"
}
```

### 9.6 业务逻辑说明

1. **权限控制**: 只有记录的创建者可以编辑
2. **唯一性检查**: 更新时会检查平台和平台ID的唯一性
3. **部分更新**: 支持只更新部分字段

### 9.7 使用说明

1. 修正记录信息
2. 更新记录状态
3. 添加或修改元数据

---

## 10. 删除数据记录接口

### 10.1 接口信息

- **接口路径**: `DELETE /api/data-records/{id}`
- **接口描述**: 删除指定ID的数据记录
- **认证要求**: 需要Bearer Token认证
- **请求方式**: DELETE
- **权限要求**: 只有记录的创建者可以删除

### 10.2 请求头

```
Authorization: Bearer {token}
```

### 10.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据记录ID（路径参数） |

### 10.4 请求示例

```bash
curl -X DELETE http://localhost:8000/api/data-records/1 \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 10.5 响应示例

#### 10.5.1 删除成功 (200)

```json
{
  "success": true,
  "message": "数据记录删除成功"
}
```

#### 10.5.2 权限不足 (403)

```json
{
  "success": false,
  "message": "无权限删除此记录，只有提交者可以删除"
}
```

#### 10.5.3 记录不存在 (404)

```json
{
  "success": false,
  "message": "数据记录不存在"
}
```

### 10.6 业务逻辑说明

1. **权限控制**: 只有记录的创建者可以删除
2. **软删除**: 记录被物理删除，不可恢复

### 10.7 使用说明

1. 删除错误的记录
2. 清理无效数据

---

## 11. 批量删除数据记录接口

### 11.1 接口信息

- **接口路径**: `DELETE /api/data-records/batch`
- **接口描述**: 批量删除多个数据记录
- **认证要求**: 需要Bearer Token认证
- **请求方式**: DELETE
- **Content-Type**: `application/json`
- **权限要求**: 只能删除自己创建的记录

### 11.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 11.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| ids | array | 是 | 要删除的记录ID数组 |

### 11.4 请求示例

```bash
curl -X DELETE http://localhost:8000/api/data-records/batch \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{
    "ids": [1, 2, 3, 4, 5]
  }'
```

### 11.5 响应示例

#### 11.5.1 批量删除成功 (200)

```json
{
  "success": true,
  "message": "成功删除 3 条记录",
  "data": {
    "deleted_count": 3,
    "failed_count": 2,
    "failed_ids": [4, 5],
    "failed_reasons": {
      "4": "记录不存在",
      "5": "无权限删除此记录"
    }
  }
}
```

#### 11.5.2 参数验证失败 (422)

```json
{
  "success": false,
  "message": "验证失败",
  "errors": {
    "ids": ["ids字段必须是数组"]
  }
}
```

### 11.6 业务逻辑说明

1. **权限控制**: 只能删除自己创建的记录
2. **部分成功**: 支持部分删除成功，返回详细结果

### 11.7 使用说明

1. 批量清理数据
2. 提高删除效率

---

## 12. 领取数据记录接口

### 12.1 接口信息

- **接口路径**: `POST /api/data-records/{id}/claim`
- **接口描述**: 领取指定的数据记录
- **认证要求**: 需要Bearer Token认证
- **请求方式**: POST
- **Content-Type**: `application/json`

### 12.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 12.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据记录ID（路径参数） |

### 12.4 请求示例

```bash
curl -X POST http://localhost:8000/api/data-records/1/claim \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json"
```

### 12.5 响应示例

#### 12.5.1 领取成功 (200)

```json
{
  "success": true,
  "message": "记录领取成功",
  "data": {
    "id": 1,
    "platform": "淘宝",
    "platform_id": "TB123456789",
    "content": "某品牌手机的价格和销量数据",
    "status": "claimed",
    "user_id": 2,
    "claimed_by": 1,
    "claimed_at": "2024-01-20T12:00:00.000000Z",
    "completed_at": null,
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T12:00:00.000000Z"
  }
}
```

#### 12.5.2 记录已被领取 (400)

```json
{
  "success": false,
  "message": "记录已被领取"
}
```

#### 12.5.3 不能领取自己的记录 (400)

```json
{
  "success": false,
  "message": "不能领取自己提交的记录"
}
```

#### 12.5.4 记录不存在 (404)

```json
{
  "success": false,
  "message": "记录不存在"
}
```

### 12.6 业务逻辑说明

1. **领取限制**: 不能领取自己提交的记录
2. **状态更新**: 领取后状态变为 `claimed`
3. **时间记录**: 记录领取时间和领取者

### 12.7 使用说明

1. 领取待处理的记录
2. 分配工作任务

---

## 13. 完成数据记录接口

### 13.1 接口信息

- **接口路径**: `POST /api/data-records/{id}/complete`
- **接口描述**: 标记数据记录为已完成
- **认证要求**: 需要Bearer Token认证
- **请求方式**: POST
- **Content-Type**: `application/json`
- **权限要求**: 只有领取者可以完成记录

### 13.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 13.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据记录ID（路径参数） |

### 13.4 请求示例

```bash
curl -X POST http://localhost:8000/api/data-records/1/complete \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json"
```

### 13.5 响应示例

#### 13.5.1 完成成功 (200)

```json
{
  "success": true,
  "message": "记录已标记为完成",
  "data": {
    "id": 1,
    "platform": "淘宝",
    "platform_id": "TB123456789",
    "content": "某品牌手机的价格和销量数据",
    "status": "completed",
    "user_id": 2,
    "claimed_by": 1,
    "claimed_at": "2024-01-20T12:00:00.000000Z",
    "completed_at": "2024-01-20T14:00:00.000000Z",
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T14:00:00.000000Z"
  }
}
```

#### 13.5.2 权限不足 (403)

```json
{
  "success": false,
  "message": "只有领取者可以完成记录"
}
```

#### 13.5.3 记录未被领取 (400)

```json
{
  "success": false,
  "message": "记录尚未被领取"
}
```

### 13.6 业务逻辑说明

1. **权限控制**: 只有领取者可以完成记录
2. **状态更新**: 完成后状态变为 `completed`
3. **时间记录**: 记录完成时间

### 13.7 使用说明

1. 标记任务完成
2. 更新工作进度

---

## 14. 标记重复记录接口

### 14.1 接口信息

- **接口路径**: `POST /api/data-records/{id}/mark-duplicate`
- **接口描述**: 标记数据记录为重复
- **认证要求**: 需要Bearer Token认证
- **请求方式**: POST
- **Content-Type**: `application/json`
- **权限要求**: 只有领取者可以标记重复

### 14.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 14.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据记录ID（路径参数） |

### 14.4 请求示例

```bash
curl -X POST http://localhost:8000/api/data-records/1/mark-duplicate \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json"
```

### 14.5 响应示例

#### 14.5.1 标记成功 (200)

```json
{
  "success": true,
  "message": "记录已标记为重复",
  "data": {
    "id": 1,
    "platform": "淘宝",
    "platform_id": "TB123456789",
    "content": "某品牌手机的价格和销量数据",
    "status": "duplicate",
    "user_id": 2,
    "claimed_by": 1,
    "claimed_at": "2024-01-20T12:00:00.000000Z",
    "completed_at": "2024-01-20T14:00:00.000000Z",
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T14:00:00.000000Z"
  }
}
```

#### 14.5.2 权限不足 (403)

```json
{
  "success": false,
  "message": "只有领取者可以标记重复"
}
```

### 14.6 业务逻辑说明

1. **权限控制**: 只有领取者可以标记重复
2. **状态更新**: 标记后状态变为 `duplicate`
3. **数据清理**: 帮助识别和处理重复数据

### 14.7 使用说明

1. 标记重复的客资
2. 数据质量管理

---

## 15. 获取数据统计信息接口

### 15.1 接口信息

- **接口路径**: `GET /api/data-records/statistics`
- **接口描述**: 获取数据记录的统计信息
- **认证要求**: 需要Bearer Token认证
- **请求方式**: GET

### 15.2 请求头

```
Authorization: Bearer {token}
```

### 15.3 请求参数

无需请求参数

### 15.4 请求示例

```bash
curl -X GET http://localhost:8000/api/data-records/statistics \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 15.5 响应示例

#### 15.5.1 获取成功 (200)

```json
{
  "success": true,
  "message": "获取统计信息成功",
  "data": {
    "total_records": 150,
    "pending_records": 45,
    "claimed_records": 30,
    "completed_records": 60,
    "duplicate_records": 15,
    "today_created": 12,
    "this_week_created": 35,
    "this_month_created": 89,
    "my_submitted": 25,
    "my_claimed": 8,
    "my_completed": 15
  }
}
```

### 15.6 业务逻辑说明

1. **全局统计**: 包含所有记录的状态统计
2. **时间统计**: 包含今日、本周、本月的新增记录数
3. **个人统计**: 包含当前用户的提交、领取、完成记录数

### 15.7 使用说明

1. 仪表板数据展示
2. 工作量统计
3. 系统运营数据分析

---

## 16. 图片上传接口

### 16.1 接口信息

- **接口路径**: `POST /api/images/upload`
- **接口描述**: 上传图片文件到服务器，支持多种图片格式
- **认证要求**: 需要Bearer Token认证
- **请求方式**: POST
- **Content-Type**: `multipart/form-data`

### 4.2 请求头

```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

### 4.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| image | file | 是 | 图片文件 |

#### 参数验证规则

- `image`: 必填，文件类型，必须是图片格式
- **支持格式**: jpeg, jpg, png, gif, webp
- **文件大小**: 最大5MB (5120KB)

### 4.4 请求示例

#### 4.4.1 cURL 示例

```bash
curl -X POST http://localhost:8000/api/images/upload \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -F "image=@/path/to/your/image.jpg"
```

#### 4.4.2 JavaScript 示例

```javascript
// 使用 FormData 上传图片
const formData = new FormData();
const fileInput = document.getElementById('imageInput');
formData.append('image', fileInput.files[0]);

fetch('http://localhost:8000/api/images/upload', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer 1|abcdefghijklmnopqrstuvwxyz123456789'
  },
  body: formData
})
.then(response => response.json())
.then(data => {
  console.log('上传成功:', data);
})
.catch(error => {
  console.error('上传失败:', error);
});
```

#### 4.4.3 Vue 3 示例

```vue
<template>
  <div>
    <input 
      type="file" 
      ref="fileInput" 
      @change="handleFileSelect"
      accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
    />
    <button @click="uploadImage" :disabled="!selectedFile">上传图片</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const fileInput = ref(null)
const selectedFile = ref(null)

const handleFileSelect = (event) => {
  selectedFile.value = event.target.files[0]
}

const uploadImage = async () => {
  if (!selectedFile.value) return
  
  const formData = new FormData()
  formData.append('image', selectedFile.value)
  
  try {
    const response = await axios.post('/api/images/upload', formData, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'multipart/form-data'
      }
    })
    
    console.log('上传成功:', response.data)
    // 处理上传成功逻辑
  } catch (error) {
    console.error('上传失败:', error.response.data)
    // 处理上传失败逻辑
  }
}
</script>
```

### 4.5 响应示例

#### 4.5.1 上传成功 (200)

```json
{
  "success": true,
  "message": "图片上传成功",
  "data": {
    "filename": "550e8400-e29b-41d4-a716-446655440000.jpg",
    "url": "http://localhost:8000/images/550e8400-e29b-41d4-a716-446655440000.jpg",
    "size": 1024000,
    "original_name": "my-photo.jpg",
    "mime_type": "image/jpeg"
  }
}
```

#### 4.5.2 参数验证失败 (422)

```json
{
  "success": false,
  "message": "图片上传失败",
  "errors": {
    "image": ["请选择要上传的图片"]
  }
}
```

**文件格式错误示例**:
```json
{
  "success": false,
  "message": "图片上传失败",
  "errors": {
    "image": ["图片格式必须是：jpeg, jpg, png, gif, webp"]
  }
}
```

**文件大小超限示例**:
```json
{
  "success": false,
  "message": "图片上传失败",
  "errors": {
    "image": ["图片大小不能超过5MB"]
  }
}
```

#### 4.5.3 认证失败 (401)

```json
{
  "success": false,
  "message": "未认证"
}
```

#### 4.5.4 服务器错误 (500)

```json
{
  "success": false,
  "message": "图片上传失败：存储空间不足"
}
```

### 4.6 业务逻辑说明

1. **文件处理流程**:
   - 验证用户认证状态
   - 检查文件格式和大小限制
   - 生成唯一文件名（使用UUID）
   - 将文件保存到 `public/images` 目录
   - 返回文件访问URL和相关信息

2. **文件命名规则**:
   - 使用UUID生成唯一文件名
   - 保留原始文件扩展名
   - 格式：`{uuid}.{extension}`
   - 示例：`550e8400-e29b-41d4-a716-446655440000.jpg`

3. **存储位置**:
   - 服务器路径：`backend/public/images/`
   - 访问URL：`http://localhost:8000/images/{filename}`

4. **安全措施**:
   - 严格的文件类型验证
   - 文件大小限制
   - 用户认证要求
   - 唯一文件名防止冲突

### 4.7 使用说明

1. **前端集成**:
   - 使用 `<input type="file">` 选择图片
   - 通过 FormData 构建请求体
   - 设置正确的 Content-Type 和 Authorization 头

2. **错误处理**:
   - 检查响应状态码
   - 根据错误类型显示相应提示
   - 实现重试机制（网络错误时）

3. **用户体验优化**:
   - 显示上传进度
   - 图片预览功能
   - 拖拽上传支持

---

## 17. 获取图片列表接口

### 17.1 接口信息

- **接口路径**: `GET /api/images`
- **接口描述**: 获取已上传的图片列表，支持分页和搜索
- **认证要求**: 需要Bearer Token认证
- **请求方式**: GET

### 17.2 请求头

```
Authorization: Bearer {token}
```

### 17.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | integer | 否 | 页码，默认为1 |
| per_page | integer | 否 | 每页数量，默认为15，最大100 |
| search | string | 否 | 搜索关键词（按原始文件名搜索） |
| sort | string | 否 | 排序字段，可选值：created_at, size, original_name |
| order | string | 否 | 排序方向，可选值：asc, desc，默认desc |

### 17.4 请求示例

```bash
# 获取第一页图片列表
curl -X GET "http://localhost:8000/api/images?page=1&per_page=10" \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"

# 搜索图片
curl -X GET "http://localhost:8000/api/images?search=photo&sort=size&order=desc" \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 17.5 响应示例

#### 17.5.1 获取成功 (200)

```json
{
  "success": true,
  "message": "获取图片列表成功",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "filename": "550e8400-e29b-41d4-a716-446655440000.jpg",
        "original_name": "my-photo.jpg",
        "url": "http://localhost:8000/images/550e8400-e29b-41d4-a716-446655440000.jpg",
        "size": 1024000,
        "mime_type": "image/jpeg",
        "user_id": 1,
        "created_at": "2024-01-20T10:30:00.000000Z",
        "updated_at": "2024-01-20T10:30:00.000000Z"
      },
      {
        "id": 2,
        "filename": "660f9511-f39c-52e5-b827-557766551111.png",
        "original_name": "screenshot.png",
        "url": "http://localhost:8000/images/660f9511-f39c-52e5-b827-557766551111.png",
        "size": 2048000,
        "mime_type": "image/png",
        "user_id": 2,
        "created_at": "2024-01-20T11:15:00.000000Z",
        "updated_at": "2024-01-20T11:15:00.000000Z"
      }
    ],
    "first_page_url": "http://localhost:8000/api/images?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://localhost:8000/api/images?page=5",
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://localhost:8000/api/images?page=1",
        "label": "1",
        "active": true
      },
      {
        "url": "http://localhost:8000/api/images?page=2",
        "label": "2",
        "active": false
      }
    ],
    "next_page_url": "http://localhost:8000/api/images?page=2",
    "path": "http://localhost:8000/api/images",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 67
  }
}
```

#### 17.5.2 认证失败 (401)

```json
{
  "success": false,
  "message": "未认证"
}
```

### 17.6 业务逻辑说明

1. **分页处理**: 支持标准的Laravel分页格式
2. **搜索功能**: 按原始文件名进行模糊搜索
3. **排序功能**: 支持按创建时间、文件大小、文件名排序
4. **权限控制**: 用户只能查看自己上传的图片

### 17.7 使用说明

1. 图片管理界面展示
2. 图片选择和引用
3. 存储空间管理

---

## 18. 删除图片接口

### 18.1 接口信息

- **接口路径**: `DELETE /api/images`
- **接口描述**: 删除指定的图片文件，支持批量删除
- **认证要求**: 需要Bearer Token认证
- **请求方式**: DELETE
- **Content-Type**: `application/json`
- **权限要求**: 只能删除自己上传的图片

### 18.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 18.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| ids | array | 是 | 要删除的图片ID数组 |

#### 参数验证规则

- `ids`: 必填，数组类型，至少包含一个图片ID
- `ids.*`: 必须是存在的图片ID，且属于当前用户

### 18.4 请求示例

```bash
# 删除单个图片
curl -X DELETE http://localhost:8000/api/images \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{"ids": [1]}'

# 批量删除图片
curl -X DELETE http://localhost:8000/api/images \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{"ids": [1, 2, 3]}'
```

### 18.5 响应示例

#### 18.5.1 删除成功 (200)

```json
{
  "success": true,
  "message": "图片删除成功",
  "data": {
    "deleted_count": 2,
    "deleted_ids": [1, 2],
    "failed_ids": [],
    "details": [
      {
        "id": 1,
        "filename": "550e8400-e29b-41d4-a716-446655440000.jpg",
        "status": "deleted"
      },
      {
        "id": 2,
        "filename": "660f9511-f39c-52e5-b827-557766551111.png",
        "status": "deleted"
      }
    ]
  }
}
```

#### 18.5.2 部分删除成功 (200)

```json
{
  "success": true,
  "message": "部分图片删除成功",
  "data": {
    "deleted_count": 1,
    "deleted_ids": [1],
    "failed_ids": [999],
    "details": [
      {
        "id": 1,
        "filename": "550e8400-e29b-41d4-a716-446655440000.jpg",
        "status": "deleted"
      },
      {
        "id": 999,
        "error": "图片不存在或无权限删除"
      }
    ]
  }
}
```

#### 18.5.3 参数验证失败 (422)

```json
{
  "success": false,
  "message": "参数验证失败",
  "errors": {
    "ids": ["ids字段是必填的"],
    "ids.0": ["选择的图片不存在或无权限删除"]
  }
}
```

#### 18.5.4 认证失败 (401)

```json
{
  "success": false,
  "message": "未认证"
}
```

### 18.6 业务逻辑说明

1. **权限控制**: 用户只能删除自己上传的图片
2. **文件删除**: 同时删除数据库记录和物理文件
3. **批量处理**: 支持一次删除多个图片
4. **错误处理**: 部分删除失败时返回详细信息
5. **安全检查**: 验证文件所有权和存在性

### 18.7 使用说明

1. 图片管理界面的删除功能
2. 清理无用图片释放存储空间
3. 批量管理图片文件

---

## 19. 获取用户列表接口（管理员）

### 19.1 接口信息

- **接口路径**: `GET /api/users`
- **接口描述**: 获取系统用户列表，支持搜索、筛选和分页
- **认证要求**: 需要Bearer Token认证
- **请求方式**: GET
- **权限要求**: 仅管理员可访问

### 19.2 请求头

```
Authorization: Bearer {token}
```

### 19.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | integer | 否 | 页码，默认为1 |
| per_page | integer | 否 | 每页数量，默认为15，最大100 |
| search | string | 否 | 搜索关键词（按用户名、邮箱、真实姓名搜索） |
| role | string | 否 | 角色筛选，可选值：admin, user |
| status | string | 否 | 状态筛选，可选值：active, inactive |
| sort | string | 否 | 排序字段，可选值：created_at, username, email, last_login_at |
| order | string | 否 | 排序方向，可选值：asc, desc，默认desc |

### 19.4 请求示例

```bash
# 获取用户列表
curl -X GET "http://localhost:8000/api/users?page=1&per_page=10" \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"

# 搜索用户
curl -X GET "http://localhost:8000/api/users?search=admin&role=admin" \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 19.5 响应示例

#### 19.5.1 获取成功 (200)

```json
{
  "success": true,
  "message": "获取用户列表成功",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "username": "admin",
        "email": "admin@example.com",
        "real_name": "系统管理员",
        "role": "admin",
        "status": "active",
        "last_login_at": "2024-01-20T14:30:00.000000Z",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-20T14:30:00.000000Z",
        "statistics": {
          "total_records": 25,
          "completed_records": 20,
          "claimed_records": 3
        }
      },
      {
        "id": 2,
        "username": "user001",
        "email": "user001@example.com",
        "real_name": "张三",
        "role": "user",
        "status": "active",
        "last_login_at": "2024-01-20T12:15:00.000000Z",
        "created_at": "2024-01-10T10:00:00.000000Z",
        "updated_at": "2024-01-20T12:15:00.000000Z",
        "statistics": {
          "total_records": 15,
          "completed_records": 12,
          "claimed_records": 2
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/users?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://localhost:8000/api/users?page=3",
    "next_page_url": "http://localhost:8000/api/users?page=2",
    "path": "http://localhost:8000/api/users",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 42
  }
}
```

#### 19.5.2 权限不足 (403)

```json
{
  "success": false,
  "message": "权限不足，仅管理员可访问"
}
```

#### 19.5.3 认证失败 (401)

```json
{
  "success": false,
  "message": "未认证"
}
```

### 19.6 业务逻辑说明

1. **权限控制**: 仅管理员角色可以访问
2. **搜索功能**: 支持按用户名、邮箱、真实姓名搜索
3. **筛选功能**: 支持按角色和状态筛选
4. **统计信息**: 包含每个用户的数据记录统计
5. **分页处理**: 标准Laravel分页格式

### 19.7 使用说明

1. 用户管理界面展示
2. 用户搜索和筛选
3. 系统用户统计分析

---

## 20. 创建用户接口（管理员）

### 20.1 接口信息

- **接口路径**: `POST /api/users`
- **接口描述**: 创建新用户账户
- **认证要求**: 需要Bearer Token认证
- **请求方式**: POST
- **Content-Type**: `application/json`
- **权限要求**: 仅管理员可访问

### 20.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 20.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| username | string | 是 | 用户名，3-20个字符，只能包含字母、数字、下划线 |
| email | string | 是 | 邮箱地址，必须是有效的邮箱格式 |
| password | string | 是 | 密码，至少6个字符 |
| real_name | string | 是 | 真实姓名，2-50个字符 |
| role | string | 否 | 用户角色，可选值：admin, user，默认user |
| status | string | 否 | 用户状态，可选值：active, inactive，默认active |

#### 参数验证规则

- `username`: 必填，3-20个字符，唯一性，只能包含字母、数字、下划线
- `email`: 必填，有效邮箱格式，唯一性
- `password`: 必填，至少6个字符
- `real_name`: 必填，2-50个字符
- `role`: 可选，枚举值：admin, user
- `status`: 可选，枚举值：active, inactive

### 20.4 请求示例

```bash
curl -X POST http://localhost:8000/api/users \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "newuser",
    "email": "newuser@example.com",
    "password": "password123",
    "real_name": "新用户",
    "role": "user",
    "status": "active"
  }'
```

### 20.5 响应示例

#### 20.5.1 创建成功 (201)

```json
{
  "success": true,
  "message": "用户创建成功",
  "data": {
    "id": 10,
    "username": "newuser",
    "email": "newuser@example.com",
    "real_name": "新用户",
    "role": "user",
    "status": "active",
    "last_login_at": null,
    "created_at": "2024-01-20T15:30:00.000000Z",
    "updated_at": "2024-01-20T15:30:00.000000Z"
  }
}
```

#### 20.5.2 参数验证失败 (422)

```json
{
  "success": false,
  "message": "参数验证失败",
  "errors": {
    "username": ["用户名已存在"],
    "email": ["邮箱已存在"],
    "password": ["密码至少需要6个字符"]
  }
}
```

#### 20.5.3 权限不足 (403)

```json
{
  "success": false,
  "message": "权限不足，仅管理员可访问"
}
```

### 20.6 业务逻辑说明

1. **权限控制**: 仅管理员可以创建用户
2. **唯一性检查**: 用户名和邮箱必须唯一
3. **密码加密**: 自动对密码进行哈希加密
4. **默认值**: 角色默认为user，状态默认为active
5. **数据验证**: 严格的参数格式和长度验证

### 20.7 使用说明

1. 管理员批量创建用户账户
2. 系统初始化用户数据
3. 用户账户管理

---

## 21. 获取用户统计接口（管理员）

### 21.1 接口信息

- **接口路径**: `GET /api/users/statistics`
- **接口描述**: 获取用户相关的统计信息
- **认证要求**: 需要Bearer Token认证
- **请求方式**: GET
- **权限要求**: 仅管理员可访问

### 21.2 请求头

```
Authorization: Bearer {token}
```

### 21.3 请求参数

无需请求参数

### 21.4 请求示例

```bash
curl -X GET http://localhost:8000/api/users/statistics \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 21.5 响应示例

#### 21.5.1 获取成功 (200)

```json
{
  "success": true,
  "message": "获取用户统计成功",
  "data": {
    "total_users": 42,
    "active_users": 38,
    "inactive_users": 4,
    "admin_users": 3,
    "regular_users": 39,
    "today_registered": 2,
    "this_week_registered": 8,
    "this_month_registered": 15,
    "today_active": 25,
    "this_week_active": 35,
    "user_activity": [
      {
        "date": "2024-01-20",
        "active_count": 25,
        "login_count": 45
      },
      {
        "date": "2024-01-19",
        "active_count": 22,
        "login_count": 38
      }
    ],
    "top_contributors": [
      {
        "user_id": 2,
        "username": "user001",
        "real_name": "张三",
        "total_records": 25,
        "completed_records": 20
      },
      {
        "user_id": 5,
        "username": "user004",
        "real_name": "李四",
        "total_records": 18,
        "completed_records": 15
      }
    ]
  }
}
```

### 21.6 业务逻辑说明

1. **用户统计**: 包含总用户数、活跃用户数、角色分布等
2. **时间统计**: 包含今日、本周、本月的新注册用户数
3. **活跃度统计**: 包含用户活跃度和登录统计
4. **贡献排行**: 显示数据记录贡献最多的用户

### 21.7 使用说明

1. 管理员仪表板数据展示
2. 用户活跃度分析
3. 系统运营数据统计

---

## 22. 获取用户详情接口（管理员）

### 22.1 接口信息

- **接口路径**: `GET /api/users/{id}`
- **接口描述**: 获取指定用户的详细信息
- **认证要求**: 需要Bearer Token认证
- **请求方式**: GET
- **权限要求**: 仅管理员可访问

### 22.2 请求头

```
Authorization: Bearer {token}
```

### 22.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 用户ID（路径参数） |

### 22.4 请求示例

```bash
curl -X GET http://localhost:8000/api/users/2 \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 22.5 响应示例

#### 22.5.1 获取成功 (200)

```json
{
  "success": true,
  "message": "获取用户详情成功",
  "data": {
    "id": 2,
    "username": "user001",
    "email": "user001@example.com",
    "real_name": "张三",
    "role": "user",
    "status": "active",
    "last_login_at": "2024-01-20T12:15:00.000000Z",
    "created_at": "2024-01-10T10:00:00.000000Z",
    "updated_at": "2024-01-20T12:15:00.000000Z",
    "statistics": {
      "total_records": 25,
      "pending_records": 3,
      "claimed_records": 2,
      "completed_records": 18,
      "duplicate_records": 2,
      "total_images": 12,
      "login_count": 45,
      "last_7_days_activity": [
        {
          "date": "2024-01-20",
          "records_created": 2,
          "records_completed": 3
        },
        {
          "date": "2024-01-19",
          "records_created": 1,
          "records_completed": 2
        }
      ]
    },
    "recent_records": [
      {
        "id": 15,
        "platform": "淘宝",
        "platform_id": "TB123456789",
        "status": "completed",
        "created_at": "2024-01-20T10:30:00.000000Z"
      },
      {
        "id": 14,
        "platform": "京东",
        "platform_id": "JD987654321",
        "status": "pending",
        "created_at": "2024-01-19T15:20:00.000000Z"
      }
    ]
  }
}
```

#### 22.5.2 用户不存在 (404)

```json
{
  "success": false,
  "message": "用户不存在"
}
```

### 22.6 业务逻辑说明

1. **详细信息**: 包含用户基本信息和扩展统计
2. **活动统计**: 包含用户的数据记录和活动统计
3. **最近记录**: 显示用户最近创建的数据记录
4. **权限控制**: 仅管理员可以查看任意用户详情

### 22.7 使用说明

1. 用户详情页面展示
2. 用户行为分析
3. 用户管理和监控

---

## 23. 更新用户接口（管理员）

### 23.1 接口信息

- **接口路径**: `PUT /api/users/{id}`
- **接口描述**: 更新用户信息
- **认证要求**: 需要Bearer Token认证
- **请求方式**: PUT
- **Content-Type**: `application/json`
- **权限要求**: 仅管理员可访问

### 23.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 23.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 用户ID（路径参数） |
| username | string | 否 | 用户名，3-20个字符 |
| email | string | 否 | 邮箱地址 |
| real_name | string | 否 | 真实姓名，2-50个字符 |
| role | string | 否 | 用户角色，可选值：admin, user |
| status | string | 否 | 用户状态，可选值：active, inactive |

#### 参数验证规则

- `username`: 可选，3-20个字符，唯一性（排除当前用户）
- `email`: 可选，有效邮箱格式，唯一性（排除当前用户）
- `real_name`: 可选，2-50个字符
- `role`: 可选，枚举值：admin, user
- `status`: 可选，枚举值：active, inactive

### 23.4 请求示例

```bash
curl -X PUT http://localhost:8000/api/users/2 \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{
    "real_name": "张三丰",
    "role": "admin",
    "status": "active"
  }'
```

### 23.5 响应示例

#### 23.5.1 更新成功 (200)

```json
{
  "success": true,
  "message": "用户信息更新成功",
  "data": {
    "id": 2,
    "username": "user001",
    "email": "user001@example.com",
    "real_name": "张三丰",
    "role": "admin",
    "status": "active",
    "last_login_at": "2024-01-20T12:15:00.000000Z",
    "created_at": "2024-01-10T10:00:00.000000Z",
    "updated_at": "2024-01-20T16:30:00.000000Z"
  }
}
```

#### 23.5.2 参数验证失败 (422)

```json
{
  "success": false,
  "message": "参数验证失败",
  "errors": {
    "username": ["用户名已存在"],
    "email": ["邮箱已存在"]
  }
}
```

#### 23.5.3 用户不存在 (404)

```json
{
  "success": false,
  "message": "用户不存在"
}
```

### 23.6 业务逻辑说明

1. **部分更新**: 支持只更新部分字段
2. **唯一性检查**: 用户名和邮箱唯一性验证（排除当前用户）
3. **权限控制**: 仅管理员可以更新用户信息
4. **数据验证**: 严格的参数格式验证

### 23.7 使用说明

1. 用户信息编辑
2. 用户角色和状态管理
3. 批量用户信息更新

---

## 24. 删除用户接口（管理员）

### 24.1 接口信息

- **接口路径**: `DELETE /api/users/{id}`
- **接口描述**: 删除指定用户
- **认证要求**: 需要Bearer Token认证
- **请求方式**: DELETE
- **权限要求**: 仅管理员可访问，不能删除自己

### 24.2 请求头

```
Authorization: Bearer {token}
```

### 24.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 用户ID（路径参数） |

### 24.4 请求示例

```bash
curl -X DELETE http://localhost:8000/api/users/2 \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 24.5 响应示例

#### 24.5.1 删除成功 (200)

```json
{
  "success": true,
  "message": "用户删除成功",
  "data": {
    "deleted_user": {
      "id": 2,
      "username": "user001",
      "email": "user001@example.com",
      "real_name": "张三"
    },
    "cleanup_info": {
      "deleted_records": 25,
      "deleted_images": 12
    }
  }
}
```

#### 24.5.2 不能删除自己 (400)

```json
{
  "success": false,
  "message": "不能删除自己的账户"
}
```

#### 24.5.3 用户不存在 (404)

```json
{
  "success": false,
  "message": "用户不存在"
}
```

### 24.6 业务逻辑说明

1. **安全限制**: 管理员不能删除自己的账户
2. **级联删除**: 删除用户时同时删除相关的数据记录和图片
3. **权限控制**: 仅管理员可以删除用户
4. **清理信息**: 返回清理的相关数据统计

### 24.7 使用说明

1. 用户账户管理
2. 清理无效用户数据
3. 系统维护操作

---

## 25. 批量删除用户接口（管理员）

### 25.1 接口信息

- **接口路径**: `DELETE /api/users/batch`
- **接口描述**: 批量删除用户
- **认证要求**: 需要Bearer Token认证
- **请求方式**: DELETE
- **Content-Type**: `application/json`
- **权限要求**: 仅管理员可访问

### 25.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 25.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| ids | array | 是 | 要删除的用户ID数组 |

#### 参数验证规则

- `ids`: 必填，数组类型，至少包含一个用户ID
- `ids.*`: 必须是存在的用户ID，不能包含当前管理员的ID

### 25.4 请求示例

```bash
curl -X DELETE http://localhost:8000/api/users/batch \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{"ids": [2, 3, 4]}'
```

### 25.5 响应示例

#### 25.5.1 删除成功 (200)

```json
{
  "success": true,
  "message": "批量删除用户成功",
  "data": {
    "deleted_count": 2,
    "deleted_ids": [2, 3],
    "failed_ids": [1],
    "details": [
      {
        "id": 2,
        "username": "user001",
        "status": "deleted",
        "cleanup_info": {
          "deleted_records": 25,
          "deleted_images": 12
        }
      },
      {
        "id": 3,
        "username": "user002",
        "status": "deleted",
        "cleanup_info": {
          "deleted_records": 18,
          "deleted_images": 8
        }
      },
      {
        "id": 1,
        "error": "不能删除自己的账户"
      }
    ]
  }
}
```

### 25.6 业务逻辑说明

1. **批量处理**: 支持一次删除多个用户
2. **安全检查**: 不能删除当前管理员账户
3. **错误处理**: 部分删除失败时返回详细信息
4. **级联删除**: 删除用户相关的所有数据

### 25.7 使用说明

1. 批量用户管理
2. 系统清理和维护
3. 用户数据迁移

---

## 26. 重置用户密码接口（管理员）

### 26.1 接口信息

- **接口路径**: `PUT /api/users/{id}/reset-password`
- **接口描述**: 重置用户密码
- **认证要求**: 需要Bearer Token认证
- **请求方式**: PUT
- **Content-Type**: `application/json`
- **权限要求**: 仅管理员可访问

### 26.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 26.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 用户ID（路径参数） |
| new_password | string | 是 | 新密码，至少6个字符 |

#### 参数验证规则

- `new_password`: 必填，至少6个字符

### 26.4 请求示例

```bash
curl -X PUT http://localhost:8000/api/users/2/reset-password \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{
    "new_password": "newpassword123"
  }'
```

### 26.5 响应示例

#### 26.5.1 重置成功 (200)

```json
{
  "success": true,
  "message": "密码重置成功",
  "data": {
    "user_id": 2,
    "username": "user001",
    "reset_at": "2024-01-20T16:45:00.000000Z"
  }
}
```

#### 26.5.2 参数验证失败 (422)

```json
{
  "success": false,
  "message": "参数验证失败",
  "errors": {
    "new_password": ["密码至少需要6个字符"]
  }
}
```

#### 26.5.3 用户不存在 (404)

```json
{
  "success": false,
  "message": "用户不存在"
}
```

### 26.6 业务逻辑说明

1. **密码加密**: 自动对新密码进行哈希加密
2. **权限控制**: 仅管理员可以重置用户密码
3. **安全记录**: 记录密码重置时间和操作者
4. **强制登出**: 重置密码后用户需要重新登录

### 26.7 使用说明

1. 用户忘记密码时的管理员操作
2. 安全事件处理
3. 用户账户维护

---

## 27. 切换用户角色接口（管理员）

### 27.1 接口信息

- **接口路径**: `PUT /api/users/{id}/toggle-role`
- **接口描述**: 切换用户角色（admin ↔ user）
- **认证要求**: 需要Bearer Token认证
- **请求方式**: PUT
- **权限要求**: 仅管理员可访问，不能修改自己的角色

### 27.2 请求头

```
Authorization: Bearer {token}
```

### 27.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 用户ID（路径参数） |

### 27.4 请求示例

```bash
curl -X PUT http://localhost:8000/api/users/2/toggle-role \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 27.5 响应示例

#### 27.5.1 切换成功 (200)

```json
{
  "success": true,
  "message": "用户角色切换成功",
  "data": {
    "user_id": 2,
    "username": "user001",
    "old_role": "user",
    "new_role": "admin",
    "updated_at": "2024-01-20T17:00:00.000000Z"
  }
}
```

#### 27.5.2 不能修改自己的角色 (400)

```json
{
  "success": false,
  "message": "不能修改自己的角色"
}
```

#### 27.5.3 用户不存在 (404)

```json
{
  "success": false,
  "message": "用户不存在"
}
```

### 27.6 业务逻辑说明

1. **角色切换**: 在admin和user之间切换
2. **安全限制**: 管理员不能修改自己的角色
3. **权限控制**: 仅管理员可以切换用户角色
4. **操作记录**: 记录角色变更历史

### 27.7 使用说明

1. 用户权限管理
2. 管理员权限分配
3. 系统角色维护

---

## 28. 错误处理

### 28.1 常见错误码

| 错误码 | HTTP状态码 | 说明 | 解决方案 |
|--------|------------|------|----------|
| VALIDATION_ERROR | 422 | 请求参数验证失败 | 检查请求参数格式和必填项 |
| UNAUTHORIZED | 401 | 未认证或认证失败 | 检查登录状态和Token有效性 |
| FORBIDDEN | 403 | 权限不足 | 确认用户角色和权限 |
| NOT_FOUND | 404 | 资源不存在 | 检查请求路径和资源ID |
| SERVER_ERROR | 500 | 服务器内部错误 | 联系技术支持 |

### 28.2 错误处理最佳实践

1. **客户端错误处理**:
   - 根据HTTP状态码进行不同的错误处理
   - 显示用户友好的错误信息
   - 对于401错误，引导用户重新登录

2. **重试机制**:
   - 对于5xx错误，可以实施重试机制
   - 建议使用指数退避算法

3. **日志记录**:
   - 记录所有API请求和响应
   - 特别关注错误请求的详细信息

---

## 29. 开发指南

### 29.1 认证流程

1. 调用登录接口获取Token
2. 在后续请求的Header中携带Token
3. Token过期时重新登录获取新Token

### 29.2 请求示例（JavaScript）

```javascript
// 登录
const login = async (account, password) => {
  const response = await fetch('/api/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ account, password }),
  });
  
  const data = await response.json();
  if (data.success) {
    localStorage.setItem('token', data.data.token);
    return data.data;
  }
  throw new Error(data.message);
};

// 创建数据记录
const createRecord = async (recordData) => {
  const token = localStorage.getItem('token');
  const response = await fetch('/api/data-records', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify(recordData),
  });
  
  const data = await response.json();
  if (data.success) {
    return data.data;
  }
  throw new Error(data.message);
};
```

### 29.3 测试建议

1. **单元测试**: 测试各种参数组合和边界情况
2. **集成测试**: 测试完整的业务流程
3. **性能测试**: 测试高并发场景下的接口性能
4. **安全测试**: 测试认证和授权机制

---

## 30. 测试工具推荐

### 30.1 推荐工具

1. **Postman**: 功能强大的API测试工具，支持环境变量、测试脚本等
2. **Insomnia**: 轻量级的REST客户端，界面简洁易用
3. **cURL**: 命令行工具，适合脚本化测试
4. **HTTPie**: 现代化的命令行HTTP客户端
5. **Swagger UI**: 基于OpenAPI规范的交互式API文档

### 30.2 测试环境配置

```javascript
// Postman 环境变量配置示例
{
  "base_url": "http://localhost:8000/api",
  "token": "{{auth_token}}",
  "user_id": "1"
}
```

---

## 31. 更新日志

| 版本 | 日期 | 更新内容 |
|------|------|----------|
| 1.0.0 | 2024-01-20 | 初始版本，包含登录和创建数据记录接口 |
| 1.1.0 | 2024-01-21 | 新增图片上传接口，支持多种图片格式上传 |
| 2.0.0 | 2024-01-22 | 完整版本，新增用户注册、认证管理、数据记录完整CRUD、图片管理、用户管理等27个接口 |

---

## 32. 联系方式

如有问题或建议，请联系开发团队：
- 邮箱: dev@datacollection.com
- 技术支持: support@datacollection.com

---

*本文档最后更新时间：2024-01-22*
*文档版本：v2.0.0*
*接口总数：27个*