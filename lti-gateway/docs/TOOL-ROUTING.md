# LTI Gateway 工具路由机制

## 问题：上层点击时，Gateway 怎么知道调用哪个工具？

**答案：通过 URL 中的 `toolId` 参数**

---

## 一、核心机制

### 1. URL 路径包含工具 ID

```
上层 Moodle 配置的工具 URL:
http://localhost:8081/lti/launch/1
                              ↑
                              这就是 toolId

上层 Canvas 配置的工具 URL:
http://localhost:8081/lti/launch/2
                              ↑
                              另一个 toolId
```

### 2. 代码中的处理

```php
// routes/web.php
Route::post('/lti/launch/{toolId}', [GatewayController::class, 'launch'])
    ->name('lti.launch');
          //   ↑
          //   从 URL 中提取 toolId

// GatewayController.php
public function launch(int $toolId, Request $request)
{
    // 根据 toolId 查询数据库
    $toolConfig = ToolConfig::find($toolId);
    
    // 找到配置后，使用它处理请求
    if ($toolConfig->type === 'lti13') {
        return $this->handleLti13Launch($toolConfig, $request);
    }
}
```

---

## 二、完整流程图解

### 场景：两个上层 Moodle 连接到同一个 Gateway

```
┌─────────────────────────────────────────────────────────────────┐
│                     上层 Moodle A (学校A)                         │
│                                                                  │
│  课程页面                                                          │
│  ┌─────────────────┐                                            │
│  │  LTI 活动        │  ← 学生点击                                 │
│  │  "数学资源"      │                                            │
│  │                 │                                            │
│  │  配置: URL =    │  http://localhost:8081/lti/launch/1        │
│  │         ↑       │                                            │
│  │         └── 工具ID = 1                                        │
│  └─────────────────┘                                            │
│                                                                  │
│  发送请求到: POST http://localhost:8081/lti/launch/1             │
│  携带: id_token (JWT)                                            │
└──────────────────────────────────┬───────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│                     LTI Gateway (中间层)                          │
│                                                                  │
│  1. 接收请求: POST /lti/launch/1                                 │
│                      ↑                                           │
│                      └── 从 URL 提取 toolId = 1                  │
│                                                                  │
│  2. 查询数据库: SELECT * FROM tool_configs WHERE id = 1          │
│                                                                  │
│  3. 获取配置:                                                    │
│     - name: "学校A-Moodle"                                       │
│     - platform_issuer: "https://school-a.edu"                    │
│     - api_base_url: "http://moodle-a:8080"                       │
│     - auth_token: "xxx"                                          │
│                                                                  │
│  4. 使用这个配置处理请求                                          │
│     - 验证 JWT (用 toolId=1 的公钥)                               │
│     - 调用 http://moodle-a:8080 创建用户                          │
│     - 重定向到 moodle-a                                          │
│                                                                  │
└──────────────────────────────────┬───────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│                     下层 Moodle A                                 │
│                                                                  │
│  学生自动登录成功，开始学习                                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

### 场景：另一个上层 Moodle B 也连接到 Gateway

```
┌─────────────────────────────────────────────────────────────────┐
│                     上层 Moodle B (学校B)                         │
│                                                                  │
│  课程页面                                                          │
│  ┌─────────────────┐                                            │
│  │  LTI 活动        │  ← 学生点击                                 │
│  │  "物理实验"      │                                            │
│  │                 │                                            │
│  │  配置: URL =    │  http://localhost:8081/lti/launch/2        │
│  │         ↑       │                                            │
│  │         └── 工具ID = 2                                        │
│  └─────────────────┘                                            │
│                                                                  │
│  发送请求到: POST http://localhost:8081/lti/launch/2             │
│  携带: id_token (JWT)                                            │
└──────────────────────────────────┬───────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│                     LTI Gateway (中间层)                          │
│                                                                  │
│  1. 接收请求: POST /lti/launch/2                                 │
│                      ↑                                           │
│                      └── 从 URL 提取 toolId = 2                  │
│                                                                  │
│  2. 查询数据库: SELECT * FROM tool_configs WHERE id = 2          │
│                                                                  │
│  3. 获取配置:                                                    │
│     - name: "学校B-Moodle"                                       │
│     - platform_issuer: "https://school-b.edu"                    │
│     - api_base_url: "http://moodle-b:8080"  ← 不同的下游!        │
│     - auth_token: "yyy"                                          │
│                                                                  │
│  4. 使用这个配置处理请求                                          │
│     - 验证 JWT (用 toolId=2 的公钥)                               │
│     - 调用 http://moodle-b:8080 创建用户                          │
│     - 重定向到 moodle-b                                          │
│                                                                  │
└──────────────────────────────────┬───────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│                     下层 Moodle B                                 │
│                                                                  │
│  学生自动登录成功，开始学习                                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 三、关键代码解析

### 1. 路由定义

```php
// routes/web.php

// 这个路由定义了 {toolId} 参数
Route::post('/lti/launch/{toolId}', [GatewayController::class, 'launch']);
//                         ↑
//                         Laravel 会自动提取这个值
//                         并传递给控制器的 $toolId 参数
```

### 2. 控制器接收

```php
// app/Http/Controllers/GatewayController.php

public function launch(int $toolId, Request $request)
{
    // Laravel 自动将 URL 中的 {toolId} 注入到 $toolId 参数
    // 例如: /lti/launch/1  →  $toolId = 1
    //       /lti/launch/2  →  $toolId = 2
    
    Log::info('LTI Launch', ['tool_id' => $toolId]);
    // 输出: ["tool_id" => 1]
    
    // 根据 toolId 查询对应的配置
    $toolConfig = ToolConfig::find($toolId);
    
    if (!$toolConfig) {
        abort(404, '工具不存在');
    }
    
    // 使用这个配置处理 LTI 请求
    return $this->processLtiLaunch($toolConfig, $request);
}
```

### 3. 数据库查询

```php
// 根据 toolId 查询对应的工具配置
$toolConfig = ToolConfig::find($toolId);

// 生成的 SQL:
// SELECT * FROM tool_configs WHERE id = ? LIMIT 1
// 参数: [$toolId]

// 返回的 $toolConfig 包含:
// - id: 1
// - name: "学校A-Moodle"
// - platform_issuer: "https://school-a.edu"
// - client_id: "xxx"
// - api_base_url: "http://moodle-a:8080"
// - auth_token: "加密存储的token"
// - public_key: "RSA公钥"
// - private_key: "加密存储的私钥"
// - is_active: true
```

---

## 四、为什么这样设计？

### 优点 1: 简单直接

```
不需要复杂的识别逻辑，URL 本身就包含了工具标识
```

### 优点 2: 支持多租户

```
每个上层平台使用不同的 toolId，自然隔离
- 学校A → toolId=1 → 下游 Moodle A
- 学校B → toolId=2 → 下游 Moodle B
- 学校C → toolId=3 → 下游 Moodle C
```

### 优点 3: 易于管理

```
在管理后台可以看到:
- 工具 1: 学校A-Moodle → http://moodle-a:8080
- 工具 2: 学校B-Moodle → http://moodle-b:8080
- 工具 3: Canvas-测试 → http://moodle-c:8080
```

---

## 五、配置对照

### 上层 Moodle 配置

```
工具名称: LTI Gateway - 学校A
工具 URL: http://localhost:8081/lti/launch/1
                              ↑
                              告诉 Gateway 使用工具ID=1的配置
```

### LTI Gateway 配置 (工具ID=1)

```
ID: 1
名称: 学校A-Moodle
Platform Issuer: https://school-a.edu
Client ID: abc123
API Base URL: http://moodle-a:8080
Auth Token: xxx
```

### 数据流向

```
学生点击上层 Moodle 的 LTI 活动
    ↓
上层 Moodle 发送请求到 /lti/launch/1
    ↓
Gateway 提取 toolId=1
    ↓
Gateway 查询数据库获取工具1的配置
    ↓
Gateway 使用工具1的配置:
    - 验证来自 school-a.edu 的 JWT
    - 调用 moodle-a:8080 的 API
    - 重定向到 moodle-a
```

---

## 六、总结

| 问题 | 答案 |
|------|------|
| Gateway 怎么知道用哪个工具？ | 从 URL 路径中的 `toolId` 参数 |
| toolId 在哪里配置？ | 在 LTI Gateway 管理后台创建工具时自动生成 |
| 上层怎么指定 toolId？ | 在工具 URL 中填写，如 `/lti/launch/1` |
| 一个 Gateway 能接多个上层吗？ | 能，每个上层用不同的 toolId |
| 一个上层能接多个下层吗？ | 能，创建多个工具配置，每个对应一个下层 |

**一句话总结**：
> **URL 中的数字就是工具ID，Gateway 根据这个数字查数据库找到对应的配置，然后使用那个配置处理请求。**
