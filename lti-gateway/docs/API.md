# LTI Gateway API 文档

## 目录

- [概述](#概述)
- [认证](#认证)
- [管理后台 API](#管理后台-api)
  - [工具管理](#工具管理)
  - [系统状态](#系统状态)
- [LTI 核心 API](#lti-核心-api)
- [日志 API](#日志-api)
- [指标 API](#指标-api)
- [错误处理](#错误处理)

---

## 概述

LTI Gateway 提供了一套完整的 RESTful API 用于管理 LTI 工具配置、监控系统状态、查看操作日志等。

**基础 URL**: `http://localhost:8081`

**响应格式**: 所有 API 返回 JSON 格式数据

```json
{
  "success": true,
  "data": { ... },
  "message": "操作成功"
}
```

---

## 认证

管理后台 API 使用 Laravel 的 Session 认证。访问管理页面时会自动设置 Cookie。

对于 POST/PUT/DELETE 请求，需要在请求头中包含 CSRF Token：

```http
X-CSRF-TOKEN: {csrf_token}
```

---

## 管理后台 API

### 工具管理

#### 1. 获取所有工具配置

**GET** `/admin/api/tools`

获取系统中所有已配置的 LTI 工具列表。

**响应示例**:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Test Tool",
      "type": "lti13",
      "platform_issuer": "https://test-platform.edu",
      "client_id": "test-client-id",
      "api_base_url": "http://moodle:8080",
      "jwks_url": "http://localhost:8081/lti/jwks/1",
      "is_active": true,
      "created_at": "2024-01-15T10:30:00+08:00",
      "updated_at": "2024-01-15T10:30:00+08:00"
    }
  ]
}
```

---

#### 2. 获取单个工具配置

**GET** `/admin/api/tools/{id}`

获取指定工具的详细配置信息。

**路径参数**:

| 参数 | 类型 | 说明 |
|------|------|------|
| id | integer | 工具 ID |

**响应示例**:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Test Tool",
    "type": "lti13",
    "platform_issuer": "https://test-platform.edu",
    "client_id": "test-client-id",
    "api_base_url": "http://moodle:8080",
    "jwks_url": "http://localhost:8081/lti/jwks/1",
    "is_active": true,
    "has_auth_token": true,
    "has_public_key": true,
    "has_private_key": true
  }
}
```

---

#### 3. 创建新工具

**POST** `/admin/api/tools`

创建一个新的 LTI 工具配置。

**请求体**:

```json
{
  "name": "Moodle Production",
  "type": "lti13",
  "platform_issuer": "https://moodle.example.com",
  "client_id": "my-client-id",
  "api_base_url": "http://moodle:8080",
  "auth_token": "your-webservice-token",
  "jwks_url": null
}
```

**参数说明**:

| 参数 | 类型 | 必需 | 说明 |
|------|------|------|------|
| name | string | ✓ | 工具名称 |
| type | string | ✓ | LTI 版本: `lti13` 或 `lti11` |
| platform_issuer | string | ✓ | 平台 Issuer URL |
| client_id | string | ✓ | 客户端 ID |
| api_base_url | string | ✓ | Moodle Web Service API 地址 |
| auth_token | string | ✓ | Web Service Token |
| jwks_url | string | ✗ | JWKS 公钥地址（留空自动生成） |

**响应示例**:

```json
{
  "success": true,
  "message": "工具创建成功",
  "data": {
    "id": 2,
    "name": "Moodle Production",
    "jwks_url": "http://localhost:8081/lti/jwks/2",
    "launch_url": "http://localhost:8081/lti/launch/2"
  }
}
```

---

#### 4. 更新工具配置

**PUT** `/admin/api/tools/{id}`

更新指定工具的配置信息。

**路径参数**:

| 参数 | 类型 | 说明 |
|------|------|------|
| id | integer | 工具 ID |

**请求体**:

```json
{
  "name": "Updated Tool Name",
  "platform_issuer": "https://new-platform.edu",
  "client_id": "new-client-id",
  "api_base_url": "http://new-moodle:8080",
  "jwks_url": "https://example.com/jwks",
  "auth_token": "new-token",
  "is_active": true
}
```

**参数说明**:

| 参数 | 类型 | 必需 | 说明 |
|------|------|------|------|
| name | string | ✗ | 工具名称 |
| platform_issuer | string | ✗ | 平台 Issuer URL |
| client_id | string | ✗ | 客户端 ID |
| api_base_url | string | ✗ | API 基础地址 |
| jwks_url | string | ✗ | JWKS 地址 |
| auth_token | string | ✗ | Web Service Token（留空不修改） |
| is_active | boolean | ✗ | 是否启用 |

**响应示例**:

```json
{
  "success": true,
  "message": "配置更新成功",
  "data": {
    "id": 1,
    "name": "Updated Tool Name",
    "updated_at": "2024-01-15T12:00:00+08:00"
  }
}
```

---

#### 5. 删除工具

**DELETE** `/admin/api/tools/{id}`

删除指定的工具配置。

**路径参数**:

| 参数 | 类型 | 说明 |
|------|------|------|
| id | integer | 工具 ID |

**响应示例**:

```json
{
  "success": true,
  "message": "工具已删除",
  "data": {
    "id": 2,
    "name": "Moodle Production"
  }
}
```

---

#### 6. 切换工具状态

**POST** `/admin/api/tools/{id}/toggle-status`

切换工具的启用/停用状态。

**路径参数**:

| 参数 | 类型 | 说明 |
|------|------|------|
| id | integer | 工具 ID |

**响应示例**:

```json
{
  "success": true,
  "message": "工具已停用",
  "data": {
    "id": 1,
    "is_active": false,
    "status_text": "停用"
  }
}
```

---

#### 7. 测试工具连接

**POST** `/admin/api/tools/{id}/test-connection`

测试指定工具与下游服务的连接状态。

**路径参数**:

| 参数 | 类型 | 说明 |
|------|------|------|
| id | integer | 工具 ID |

**响应示例**:

```json
{
  "success": true,
  "data": {
    "connected": true,
    "message": "连接成功"
  }
}
```

---

### 系统状态

#### 获取系统状态

**GET** `/admin/api/system-status`

获取系统整体状态，包括数据库、Redis 和所有工具的状态。

**响应示例**:

```json
{
  "success": true,
  "data": {
    "database": {
      "status": "ok",
      "message": "连接正常"
    },
    "redis": {
      "status": "ok",
      "message": "连接正常"
    },
    "tools": {
      "status": "ok",
      "message": "2/2 个工具连接正常",
      "total": 2,
      "active": 2,
      "connected": 2,
      "tools": [
        {
          "id": 1,
          "name": "Test Tool",
          "type": "lti13",
          "is_active": true,
          "status": "ok",
          "message": "连接正常",
          "api_base_url": "http://moodle:8080"
        }
      ]
    },
    "timestamp": "2024-01-15T10:30:00+08:00"
  }
}
```

---

## LTI 核心 API

### 1. LTI 启动

**POST** `/lti/launch/{toolId}`

启动 LTI 工具会话。此端点接收 LTI 平台的请求，验证后自动创建/同步用户并重定向到下游 Moodle。

**路径参数**:

| 参数 | 类型 | 说明 |
|------|------|------|
| toolId | integer | 工具配置 ID |

**请求体** (LTI 1.3):

```json
{
  "id_token": "eyJhbGciOiJSUzI1NiIs...",
  "state": "random-state-string"
}
```

**请求体** (LTI 1.1):

```
oauth_consumer_key=...&
oauth_signature=...&
resource_link_id=...&
user_id=...&
roles=Student&
lis_person_sourcedid=2024001
```

**响应**:

- 成功: 返回自动提交的 HTML 表单，自动重定向到 Moodle
- 错误: 返回错误页面或 JSON 错误信息

---

### 2. JWKS 公钥

**GET** `/lti/jwks/{toolId}`

获取工具的 JWKS (JSON Web Key Set) 公钥集合，用于 LTI 平台验证 Gateway 的签名。

**路径参数**:

| 参数 | 类型 | 说明 |
|------|------|------|
| toolId | integer | 工具配置 ID |

**响应示例**:

```json
{
  "keys": [
    {
      "kty": "RSA",
      "kid": "gateway-key-1",
      "use": "sig",
      "alg": "RS256",
      "n": "base64url-encoded-modulus",
      "e": "base64url-encoded-exponent"
    }
  ]
}
```

---

### 3. 健康检查

**GET** `/lti/health`

检查 LTI Gateway 服务的健康状态。

**响应示例**:

```json
{
  "status": "ok",
  "timestamp": "2024-01-15T10:30:00+08:00",
  "version": "1.0.0",
  "services": {
    "database": true,
    "redis": true
  }
}
```

---

## 日志 API

### 1. 获取日志统计

**GET** `/logs/stats`

获取操作日志的统计信息。

**响应示例**:

```json
{
  "data": {
    "total": 150,
    "success": 145,
    "fail": 5,
    "success_rate": 96.67,
    "today": {
      "success": 20,
      "fail": 1
    },
    "avg_processing_time_ms": 125.50
  },
  "timestamp": "2024-01-15T10:30:00+08:00"
}
```

---

### 2. 获取最近日志

**GET** `/logs/recent?limit=10`

获取最近的操作日志。

**查询参数**:

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| limit | integer | 10 | 返回数量（最大 50） |

**响应示例**:

```json
{
  "data": [
    {
      "id": 1,
      "status": "success",
      "tool_name": "Test Tool",
      "tool_type": "lti13",
      "student_id": "2024001",
      "processing_time_ms": 120,
      "error_code": null,
      "created_at": "2024-01-15T10:30:00+08:00",
      "ip_address": "192.168.1.1"
    }
  ],
  "timestamp": "2024-01-15T10:30:00+08:00"
}
```

---

### 3. 获取日志列表

**GET** `/logs?status=success&tool_id=1&per_page=20`

获取分页的操作日志列表，支持筛选。

**查询参数**:

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| status | string | - | 筛选状态: `success` 或 `fail` |
| tool_id | integer | - | 筛选工具 ID |
| date_from | date | - | 起始日期 (YYYY-MM-DD) |
| date_to | date | - | 结束日期 (YYYY-MM-DD) |
| per_page | integer | 20 | 每页数量（最大 100） |

**响应示例**:

```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  },
  "timestamp": "2024-01-15T10:30:00+08:00"
}
```

---

### 4. 获取单个日志详情

**GET** `/logs/{id}`

获取指定日志的详细信息。

**路径参数**:

| 参数 | 类型 | 说明 |
|------|------|------|
| id | integer | 日志 ID |

---

## 指标 API

### 1. 系统指标

**GET** `/metrics/system`

获取系统级指标数据。

### 2. 工具指标

**GET** `/metrics/tool/{toolId}`

获取指定工具的指标数据。

### 3. 实时指标

**GET** `/metrics/realtime`

获取实时指标数据流（SSE）。

### 4. Prometheus 指标

**GET** `/metrics/prometheus`

获取 Prometheus 格式的指标数据。

---

## 错误处理

### 错误响应格式

```json
{
  "success": false,
  "message": "错误描述",
  "error": {
    "code": "ERROR_CODE",
    "details": "详细错误信息"
  }
}
```

### HTTP 状态码

| 状态码 | 说明 |
|--------|------|
| 200 | 请求成功 |
| 201 | 创建成功 |
| 400 | 请求参数错误 |
| 401 | 未认证 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 422 | 验证失败 |
| 500 | 服务器内部错误 |

### 错误代码

| 代码 | 说明 | 场景 |
|------|------|------|
| `MISSING_STUDENT_ID` | 缺少学号信息 | LTI 请求中无法提取学号 |
| `INVALID_LTI_REQUEST` | 无效的 LTI 请求 | JWT 验证失败或参数缺失 |
| `DOWNSTREAM_API_ERROR` | 下游服务错误 | Moodle API 调用失败 |
| `USER_MAPPING_ERROR` | 用户映射错误 | 用户创建或查询失败 |
| `TOOL_NOT_FOUND` | 工具不存在 | 请求的工具 ID 不存在 |
| `TOOL_DISABLED` | 工具已停用 | 尝试使用已停用的工具 |

---

## 学号提取规则

LTI Gateway 按以下优先级从 LTI 请求中提取学号：

1. `custom_student_id` - 最高优先级（推荐）
2. `lis_person_sourcedid` - 次优先级
3. `sub` (JWT subject) - 最低优先级

**配置建议**:

在 LTI 平台中配置自定义参数：

```
custom_student_id=$User.username
```

---

## 虚拟邮箱格式

LTI Gateway 为每个学生生成虚拟邮箱用于下游系统：

```
{student_id}@{virtual_email_domain}
```

**示例**: `2024001@proxy.university.edu`

虚拟邮箱域名可在工具配置中设置，默认为 `proxy.local`。

---

## 管理后台界面

LTI Gateway 提供 Web 管理界面：

**URL**: `http://localhost:8081/admin`

**功能**:
- 系统状态监控（数据库、Redis、所有工具）
- 工具配置管理（添加、编辑、删除、停用/启用）
- 连接测试
- 操作日志查看

---

## 版本历史

### v1.0.0
- 初始版本
- 支持 LTI 1.3 和 LTI 1.1
- 管理后台功能
- 多工具支持
