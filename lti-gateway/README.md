<div align="center">

# 🚀 LTI Gateway

**企业级 LTI 1.3/1.1 代理网关 | 学号自动映射 | 多租户支持**

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com)
[![License](https://img.shields.io/badge/License-MIT-4CAF50?style=for-the-badge)](LICENSE)

<p align="center">
  <a href="#-快速开始">快速开始</a> •
  <a href="#-架构说明">架构说明</a> •
  <a href="#-功能特性">功能特性</a> •
  <a href="#-文档">文档</a> •
  <a href="#-部署">部署</a>
</p>

<img src="docs/images/architecture.png" alt="Architecture" width="800">

</div>

---

## 📋 目录

- [简介](#-简介)
- [快速开始](#-快速开始)
- [架构说明](#-架构说明)
- [功能特性](#-功能特性)
- [管理后台](#-管理后台)
- [文档](#-文档)
- [部署](#-部署)
- [开发](#-开发)
- [许可证](#-许可证)

---

## 🎯 简介

LTI Gateway 是一个**生产级 LTI 代理网关**，解决教育机构在使用 LTI 工具时的用户同步难题。

### 核心问题

```
学校 Moodle (LTI 平台) ──LTI──> 第三方工具
         │                            │
         │                            ▼
         │                    学生需要重新注册账号
         │                    (学号不同步)
         ▼
    学生账号: 2024001
```

### LTI Gateway 解决方案

```
学校 Moodle (LTI 平台) ──LTI──> LTI Gateway ──API──> 资源 Moodle
         │                              │                │
         │                              ▼                ▼
         │                    自动提取学号          自动创建用户
         │                    调用下游 API          自动登录
         ▼                              │                │
    学生账号: 2024001 ────────────────> 2024001 ──────> 2024001
    
    ✅ 一次点击，无缝跳转，无需重新登录
```

---

## 🚀 快速开始

### 环境要求

| 组件 | 版本 | 说明 |
|------|------|------|
| Docker | 20.10+ | 容器运行时 |
| Docker Compose | 2.0+ | 编排工具 |
| RAM | 4GB+ | 推荐 8GB |
| 磁盘 | 20GB+ | SSD 推荐 |

### 一键启动

```bash
# 克隆项目
git clone https://github.com/your-org/lti-gateway.git
cd lti-gateway

# 启动所有服务
docker-compose up -d

# 等待初始化完成（约30秒）
sleep 30

# 配置 Web Service Token
docker exec -it lti_gateway_app php artisan tinker --execute='
$config = App\Models\ToolConfig::first();
$config->update(["auth_token" => "your-moodle-token"]);
echo "配置完成\n";
'

# 访问管理后台
open http://localhost:8081/admin
```

### 验证安装

```bash
# 健康检查
curl http://localhost:8081/lti/health

# 测试 Moodle 连接
docker exec lti_gateway_app php artisan lti:test-moodle
```

---

## 🏗️ 架构说明

### 三层架构

```
┌─────────────────────────────────────────────────────────────────┐
│                        🔷 上层 (LTI 平台)                         │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐         │
│  │   Moodle    │    │   Canvas    │    │  Blackboard │         │
│  │  (主站)     │    │             │    │             │         │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘         │
└───────┼────────────────────┼────────────────────┼──────────────┘
        │                    │                    │
        └────────────────────┼────────────────────┘
                             │ LTI 1.3/1.1
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      🔶 中间层 (LTI Gateway)                      │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  🔐 JWT 验证        📋 学号提取        🔄 用户同步       │   │
│  │                                                          │   │
│  │  • LTI 1.3/1.1 协议支持                                  │   │
│  │  • 学号自动映射 (custom_student_id)                      │   │
│  │  • 并发安全的用户创建                                    │   │
│  │  • 虚拟邮箱生成                                          │   │
│  │  • 多租户支持                                            │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│  🌐 http://localhost:8081/admin                                 │
└─────────────────────────────────────────────────────────────────┘
                             │ Web Service API
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                        🔷 下层 (资源平台)                         │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              Moodle Web Service API                     │   │
│  │                                                          │   │
│  │  • core_user_create_users                               │   │
│  │  • core_user_get_users_by_field                         │   │
│  │  • core_webservice_get_site_info                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│  🌐 http://moodle:8080                                          │
└─────────────────────────────────────────────────────────────────┘
```

### 数据流

```
学生点击 LTI 活动
    ↓
上层平台生成 JWT (包含学号)
    ↓
发送 POST /lti/launch/{toolId}
    ↓
Gateway 验证 JWT → 提取学号 → 查询工具配置
    ↓
调用下游 Moodle API 创建/查询用户
    ↓
生成自动登录表单 → 重定向到下游 Moodle
    ↓
学生自动登录成功
```

---

## ✨ 功能特性

### 核心功能

| 功能 | 描述 | 状态 |
|------|------|------|
| 🔐 **双协议支持** | LTI 1.3 和 LTI 1.1 | ✅ |
| 🎯 **学号提取** | 支持 custom_student_id / lis_person_sourcedid / sub | ✅ |
| 🔄 **自动同步** | 自动创建下游用户，无需手动注册 | ✅ |
| 🏢 **多租户** | 支持多个上层平台和多个下层 Moodle | ✅ |
| 📧 **虚拟邮箱** | 自动生成 {student_id}@{domain} 格式邮箱 | ✅ |

### 安全特性

| 功能 | 描述 | 状态 |
|------|------|------|
| 🔒 **JWT 验证** | RSA 签名验证，防篡改 | ✅ |
| 🛡️ **重放攻击防护** | Redis nonce 缓存，防止请求重放 | ✅ |
| ⏱️ **速率限制** | 60次/分钟，防止暴力攻击 | ✅ |
| 🔐 **数据加密** | 敏感数据 AES-256 加密存储 | ✅ |
| 📝 **审计日志** | 完整的操作日志记录 | ✅ |

### 管理功能

| 功能 | 描述 | 状态 |
|------|------|------|
| 📊 **Web 管理后台** | 可视化配置工具、查看状态 | ✅ |
| 🔌 **连接测试** | 一键测试下游 Moodle 连接 | ✅ |
| 📈 **实时监控** | 系统状态、工具状态实时监控 | ✅ |
| 📝 **操作日志** | 查看所有 LTI 启动记录 | ✅ |
| ⚙️ **工具管理** | 添加、编辑、停用、删除工具 | ✅ |

---

## 🎛️ 管理后台

### 系统状态监控

<img src="docs/images/admin-dashboard.png" alt="Dashboard" width="800">

**功能**:
- 🗄️ 数据库状态
- ⚡ Redis 状态
- 📊 工具状态概览（总数/启用/连接正常）
- 🔧 各工具详细状态

### 工具管理

<img src="docs/images/admin-tools.png" alt="Tools" width="800">

**功能**:
- ➕ 添加新工具
- ✏️ 编辑配置
- 🔌 测试连接
- ⏸️ 启用/停用
- 🗑️ 删除工具

### 配置编辑

<img src="docs/images/admin-config.png" alt="Config" width="800">

**功能**:
- 工具选择器
- 实时配置更新
- 连接测试

---

## 📚 文档

### 用户文档

| 文档 | 描述 |
|------|------|
| [📖 架构说明](docs/ARCHITECTURE.md) | 系统架构与数据流详解 |
| [🔧 工具路由](docs/TOOL-ROUTING.md) | Gateway 如何识别工具 |
| [🎓 LMS 接入指南](docs/LMS-INTEGRATION.md) | Moodle/Canvas 接入教程 |
| [📡 API 文档](docs/API.md) | 完整的 API 接口文档 |

### 运维文档

| 文档 | 描述 |
|------|------|
| [🚀 快速开始](QUICKSTART.md) | 5分钟快速启动指南 |
| [📦 生产部署](docs/DEPLOYMENT.md) | 详细部署与配置 |
| [🔐 Moodle 配置](docs/MOODLE_SETUP.md) | Web Service 配置步骤 |
| [📝 变更日志](CHANGELOG.md) | 版本更新记录 |

---

## 🚢 部署

### Docker Compose（推荐）

```yaml
version: '3.8'

services:
  app:
    image: lti-gateway:latest
    environment:
      - APP_ENV=production
      - DB_HOST=postgres
      - REDIS_HOST=redis
    volumes:
      - ./storage:/var/www/html/storage
    depends_on:
      - postgres
      - redis

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/ssl:/etc/nginx/ssl

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: lti_gateway
      POSTGRES_USER: lti_user
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data

volumes:
  postgres_data:
  redis_data:
```

### 生产环境检查清单

- [ ] 修改默认密码
- [ ] 配置 HTTPS
- [ ] 设置防火墙规则
- [ ] 配置日志轮转
- [ ] 设置监控告警
- [ ] 备份策略
- [ ] 速率限制调优

---

## 💻 开发

### 技术栈

| 层级 | 技术 |
|------|------|
| 后端 | PHP 8.2, Laravel 11 |
| 数据库 | PostgreSQL 16 |
| 缓存 | Redis 7 |
| Web | Nginx |
| 容器 | Docker, Docker Compose |

### 常用命令

```bash
# 开发环境启动
make dev

# 运行测试
make test

# 代码格式化
make format

# 静态分析
make analyse

# 查看日志
docker-compose logs -f app
```

### 项目结构

```
lti-gateway/
├── 📁 app/
│   ├── Http/Controllers/     # 控制器
│   ├── Models/               # 数据模型
│   ├── Services/             # 业务服务
│   │   ├── Lti13Handler.php  # LTI 1.3 处理
│   │   ├── Lti11Handler.php  # LTI 1.1 处理
│   │   └── UserMappingService.php  # 用户同步
│   └── ...
├── 📁 config/                # 配置文件
├── 📁 database/
│   ├── migrations/           # 数据库迁移
│   └── seeders/              # 数据填充
├── 📁 docs/                  # 文档
├── 📁 docker/                # Docker 配置
├── 📁 resources/
│   └── views/                # Blade 视图
├── 📁 routes/
│   └── web.php               # 路由定义
└── 📁 tests/                 # 测试代码
```

---

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

### 贡献流程

1. Fork 项目
2. 创建分支 (`git checkout -b feature/amazing-feature`)
3. 提交更改 (`git commit -m 'Add amazing feature'`)
4. 推送分支 (`git push origin feature/amazing-feature`)
5. 创建 Pull Request

---

## 📄 许可证

[MIT License](LICENSE) © 2024 LTI Gateway Team

---

## 🙏 致谢

- [Laravel](https://laravel.com) - 优雅的 PHP 框架
- [phpseclib](https://phpseclib.com) - RSA 加密库
- [IMS Global](https://www.imsglobal.org) - LTI 标准

---

<div align="center">

**[⬆ 返回顶部](#-lti-gateway)**

Made with ❤️ by LTI Gateway Team

</div>
