# LTI Gateway 架构与接入指南

## 一、LTI Gateway 是什么？

LTI Gateway 是一个**中间层服务**，它位于：
- **上层**：LTI 平台（如 Moodle、Canvas 等 LMS）
- **下层**：下游 Moodle 实例

它的作用是**桥接**上层 LTI 平台和下层 Moodle，实现用户自动同步。

---

## 二、架构图

```
┌─────────────────────────────────────────────────────────────────┐
│                         上层（LTI 平台）                          │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐         │
│  │   Moodle    │    │   Canvas    │    │  Blackboard │         │
│  │  (主站)     │    │             │    │             │         │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘         │
│         │                  │                  │                │
│         └──────────────────┼──────────────────┘                │
│                            │                                   │
│                            ▼                                   │
│              ┌─────────────────────────┐                       │
│              │   LTI 1.3 启动请求       │                       │
│              │   (id_token + state)    │                       │
│              └───────────┬─────────────┘                       │
└──────────────────────────┼─────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                      中间层（LTI Gateway）                        │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  1. 接收 LTI 启动请求                                    │   │
│  │  2. 验证 JWT (id_token)                                 │   │
│  │  3. 提取学号 (student_id)                               │   │
│  │  4. 调用下游 Moodle API 创建/查询用户                    │   │
│  │  5. 生成自动登录表单，重定向到下游 Moodle                │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           │                                     │
│              ┌────────────┴────────────┐                       │
│              │    http://localhost:8081  │                       │
│              │        /lti/launch/1      │                       │
│              └────────────┬────────────┘                       │
└───────────────────────────┼─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                      下层（下游 Moodle）                          │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Moodle Web Service API                                 │   │
│  │  - core_user_create_users                               │   │
│  │  - core_user_get_users_by_field                         │   │
│  │  - core_webservice_get_site_info                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           │                                     │
│              ┌────────────┴────────────┐                       │
│              │    http://moodle:8080     │                       │
│              │    /webservice/rest/...   │                       │
│              └───────────────────────────┘                       │
└─────────────────────────────────────────────────────────────────┘
```

---

## 三、上层怎么接？（LTI 平台 → LTI Gateway）

### 3.1 上层需要做什么？

上层 LTI 平台（如 Moodle、Canvas）需要配置一个**外部工具（External Tool）**，指向 LTI Gateway。

### 3.2 具体配置步骤

#### 在 LTI Gateway 中获取信息：

1. 访问 `http://localhost:8081/admin`
2. 创建一个工具，记录以下信息：

| 信息 | 示例值 | 用途 |
|------|--------|------|
| 工具 ID | `1` | 标识这个工具配置 |
| 启动 URL | `http://localhost:8081/lti/launch/1` | LTI 启动端点 |
| JWKS URL | `http://localhost:8081/lti/jwks/1` | 公钥验证地址 |
| Platform Issuer | `https://moodle.example.com` | 上层平台标识 |

#### 在上层 Moodle 中配置：

**路径**：网站管理 → 插件 → 活动模块 → 外部工具 → 管理工具

**填写内容**：

```
工具名称: LTI Gateway
工具 URL: http://localhost:8081/lti/launch/1
LTI 版本: LTI 1.3

公钥类型: 密钥集 URL
密钥集 URL: http://localhost:8081/lti/jwks/1

启动登录 URL: http://localhost:8081/lti/launch/1
重定向 URI: http://localhost:8081/lti/launch/1
```

**关键理解**：
- 上层 Moodle 把 LTI Gateway **当作一个外部工具**
- 学生点击活动 → 上层 Moodle 发送 LTI 请求 → LTI Gateway 接收

---

## 四、下层怎么接？（LTI Gateway → 下游 Moodle）

### 4.1 下层需要做什么？

下游 Moodle 需要开启 **Web Service**，让 LTI Gateway 可以调用 API 创建用户。

### 4.2 具体配置步骤

#### 步骤 1: 启用 Web Service

**路径**：网站管理 → 高级功能

```
☑️ 启用 Web 服务
```

#### 步骤 2: 创建外部服务

**路径**：网站管理 → 服务器 → Web 服务 → 外部服务

```
名称: LTI Gateway
启用: 是
允许的函数:
  - core_user_create_users      (创建用户)
  - core_user_get_users_by_field (查询用户)
  - core_webservice_get_site_info (站点信息)
```

#### 步骤 3: 创建令牌

**路径**：网站管理 → 服务器 → Web 服务 → 管理令牌

```
用户: admin (或专用服务账号)
服务: LTI Gateway
```

**生成的令牌**：`58d273e326a23f22f899f4c83837a917`

#### 步骤 4: 在 LTI Gateway 中配置

回到 LTI Gateway 管理后台：

```
Moodle API 地址: http://moodle:8080
Web Service Token: 58d273e326a23f22f899f4c83837a917
```

**关键理解**：
- LTI Gateway 把下游 Moodle **当作 API 服务**
- LTI Gateway 调用 Web Service 创建用户 → 生成登录链接 → 重定向学生

---

## 五、完整数据流

```
学生在上层 Moodle 点击 LTI 活动
         │
         ▼
上层 Moodle 生成 JWT (包含学生信息)
         │
         ▼
发送 POST 请求到 LTI Gateway
         │
         ▼
LTI Gateway 验证 JWT，提取学号 "2024001"
         │
         ▼
LTI Gateway 调用下游 Moodle API
POST http://moodle:8080/webservice/rest/server.php
参数: wstoken=xxx&wsfunction=core_user_create_users&users[0][username]=2024001
         │
         ▼
下游 Moodle 创建用户，返回用户 ID "123"
         │
         ▼
LTI Gateway 生成自动登录表单
<form action="http://moodle:8080/login/index.php" method="post">
  <input name="username" value="2024001">
  <input name="password" value="自动生成的密码">
</form>
<script>document.forms[0].submit();</script>
         │
         ▼
浏览器自动提交表单，学生登录到下游 Moodle
```

---

## 六、配置对照表

### 上层配置（LTI 平台）

| 配置项 | 值 | 说明 |
|--------|-----|------|
| 工具 URL | `http://localhost:8081/lti/launch/1` | LTI Gateway 启动端点 |
| JWKS URL | `http://localhost:8081/lti/jwks/1` | 验证 LTI Gateway 签名 |
| Client ID | 由上层生成 | 双方共享的客户端标识 |
| 自定义参数 | `custom_student_id=$User.username` | 传递学号 |

### 中间层配置（LTI Gateway）

| 配置项 | 值 | 说明 |
|--------|-----|------|
| Platform Issuer | `https://moodle.example.com` | 上层平台标识 |
| Client ID | 上层提供的 | 验证 LTI 请求来源 |
| API Base URL | `http://moodle:8080` | 下游 Moodle 地址 |
| Auth Token | 下游提供的 | 调用 Web Service |

### 下层配置（下游 Moodle）

| 配置项 | 值 | 说明 |
|--------|-----|------|
| Web Service | 启用 | 允许 API 调用 |
| 外部服务 | LTI Gateway | 授权的服务名称 |
| 令牌 | 生成的字符串 | LTI Gateway 使用 |

---

## 七、常见问题图解

### Q: 上层和下层可以是同一个 Moodle 吗？

```
可以，但不推荐：

上层 Moodle ──LTI──> LTI Gateway ──API──> 同一个 Moodle
                      (用户同步到自己)
                      
推荐架构：
上层 Moodle A ──LTI──> LTI Gateway ──API──> 下层 Moodle B
(认证中心)                              (资源平台)
```

### Q: 可以有多个上层吗？

```
可以：

Moodle A ──┐
           ├──> LTI Gateway ──> 下游 Moodle
Canvas B ──┘

每个上层在 LTI Gateway 中创建一个工具配置
```

### Q: 可以有多个下层吗？

```
可以：

上层 Moodle ──LTI──> LTI Gateway ──API──> Moodle A (工具1)
                    │
                    └──API──> Moodle B (工具2)
                    
每个下层对应 LTI Gateway 中的一个工具配置
```

---

## 八、总结

| 层级 | 角色 | 连接方式 |
|------|------|----------|
| 上层 | LTI 平台 | 通过 LTI 1.3 协议连接 Gateway |
| 中间层 | LTI Gateway | 接收 LTI 请求，调用下游 API |
| 下层 | Moodle | 通过 Web Service API 被 Gateway 调用 |

**记住**：
- 上层把 Gateway **当作工具**
- Gateway 把下层 **当作 API**
- Gateway 是**中间人**，负责翻译和同步
