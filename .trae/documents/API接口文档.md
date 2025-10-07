# 数据采集平台 API 接口文档

## 1. 概述

本文档描述了数据采集平台的核心API接口，包括用户认证和数据记录管理功能。所有API接口均采用RESTful设计风格，使用JSON格式进行数据交换。

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

## 2. 用户登录接口

### 2.1 接口信息

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

## 3. 创建数据记录接口

### 3.1 接口信息

- **接口路径**: `POST /api/data-records`
- **接口描述**: 创建新的数据记录
- **认证要求**: 需要Bearer Token认证
- **请求方式**: POST
- **Content-Type**: `application/json`

### 3.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 3.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| title | string | 是 | 记录标题，最大255字符 |
| content | string | 否 | 记录内容，支持长文本 |
| category | string | 否 | 记录分类，最大100字符 |
| status | string | 否 | 记录状态：`active`（活跃）或 `inactive`（非活跃） |
| metadata | object | 否 | 元数据对象，用于存储额外信息 |

#### 参数验证规则

- `title`: 必填，字符串类型，最大长度255字符
- `content`: 可选，字符串类型，无长度限制
- `category`: 可选，字符串类型，最大长度100字符
- `status`: 可选，枚举值，只能是 `active` 或 `inactive`
- `metadata`: 可选，对象/数组类型，用于存储结构化数据

### 3.4 请求示例

```bash
curl -X POST http://localhost:8000/api/data-records \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "淘宝商品数据采集",
    "content": "采集某品牌手机的价格和销量数据",
    "category": "电商数据",
    "status": "active",
    "metadata": {
      "platform": "taobao",
      "product_type": "electronics",
      "target_count": 1000
    }
  }'
```

```json
{
  "title": "淘宝商品数据采集",
  "content": "采集某品牌手机的价格和销量数据",
  "category": "电商数据",
  "status": "active",
  "metadata": {
    "platform": "taobao",
    "product_type": "electronics",
    "target_count": 1000
  }
}
```

### 3.5 响应示例

#### 3.5.1 创建成功 (201)

```json
{
  "success": true,
  "message": "创建数据记录成功",
  "data": {
    "id": 1,
    "title": "淘宝商品数据采集",
    "content": "采集某品牌手机的价格和销量数据",
    "category": "电商数据",
    "status": "active",
    "metadata": {
      "platform": "taobao",
      "product_type": "electronics",
      "target_count": 1000
    },
    "user_id": 1,
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

#### 3.5.2 参数验证失败 (422)

```json
{
  "success": false,
  "message": "验证失败",
  "errors": {
    "title": ["标题不能为空"],
    "category": ["分类不能超过100个字符"],
    "status": ["状态值无效"],
    "metadata": ["元数据必须是数组格式"]
  }
}
```

#### 3.5.3 认证失败 (401)

```json
{
  "success": false,
  "message": "未认证"
}
```

#### 3.5.4 服务器错误 (500)

```json
{
  "success": false,
  "message": "创建数据记录失败",
  "error": "具体错误信息"
}
```

### 3.6 业务逻辑说明

1. **自动字段填充**:
   - `user_id`: 自动设置为当前认证用户的ID
   - `status`: 如果未提供，默认设置为 `active`
   - `created_at` 和 `updated_at`: 自动设置为当前时间戳

2. **关联数据**:
   - 每个数据记录都会关联到创建者
   - 响应中包含创建者的用户信息（通过 `user` 关联）

3. **权限控制**:
   - 只有认证用户才能创建数据记录
   - 记录会自动关联到创建者，确保数据归属明确

4. **元数据支持**:
   - `metadata` 字段支持存储任意结构化数据
   - 常用于存储平台特定信息、配置参数等

### 3.7 使用场景

1. **数据采集任务创建**: 用户创建新的数据采集任务
2. **项目管理**: 记录项目相关信息和进度
3. **数据分类**: 通过category字段对数据进行分类管理
4. **状态跟踪**: 通过status字段跟踪记录的生命周期

---

## 4. 错误处理

### 4.1 常见错误码

| 错误码 | HTTP状态码 | 说明 | 解决方案 |
|--------|------------|------|----------|
| VALIDATION_ERROR | 422 | 请求参数验证失败 | 检查请求参数格式和必填项 |
| UNAUTHORIZED | 401 | 未认证或认证失败 | 检查登录状态和Token有效性 |
| FORBIDDEN | 403 | 权限不足 | 确认用户角色和权限 |
| NOT_FOUND | 404 | 资源不存在 | 检查请求路径和资源ID |
| SERVER_ERROR | 500 | 服务器内部错误 | 联系技术支持 |

### 4.2 错误处理最佳实践

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

## 5. 开发指南

### 5.1 认证流程

1. 调用登录接口获取Token
2. 在后续请求的Header中携带Token
3. Token过期时重新登录获取新Token

### 5.2 请求示例（JavaScript）

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

### 5.3 测试建议

1. **单元测试**: 测试各种参数组合和边界情况
2. **集成测试**: 测试完整的业务流程
3. **性能测试**: 测试高并发场景下的接口性能
4. **安全测试**: 测试认证和授权机制

---

## 6. 更新日志

| 版本 | 日期 | 更新内容 |
|------|------|----------|
| 1.0.0 | 2024-01-20 | 初始版本，包含登录和创建数据记录接口 |

---

## 7. 联系方式

如有问题或建议，请联系开发团队：
- 邮箱: dev@datacollection.com
- 技术支持: support@datacollection.com

---

*本文档最后更新时间：2024-01-20*