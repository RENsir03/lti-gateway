# LTI Gateway - 基于学号的通用 LTI 代理网关

[![PHP Version](https://img.shields.io/badge/PHP-8.2-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-11-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

生产级的 LTI 1.3/1.1 代理网关，支持学号映射和自动用户创建。

## 架构

```
上游 LMS (LTI 1.3) → LTI Gateway → Moodle (LTI 1.3/1.1)
                          ↓
                    自动创建用户 (Web Service)
```

## 快速开始

### 系统要求

- Docker >= 20.10
- Docker Compose >= 2.0
- 4GB+ RAM
- 20GB+ 磁盘空间

### 5 分钟快速启动

```bash
# 1. 检查系统要求
make check-requirements

# 2. 一键安装
make install

# 3. 配置 Moodle Web Service Token
make shell
php artisan tinker
>>> $config = App\Models\ToolConfig::first();
>>> $config->update(['auth_token' => 'your-moodle-token']);

# 4. 测试连接
make health
make test-moodle
```

### 访问地址

| 服务 | 地址 |
|------|------|
| LTI Gateway | http://localhost:8081 |
| 健康检查 | http://localhost:8081/lti/health |
| Moodle | http://localhost:8080 |

## 功能特性

- ✅ **LTI 1.3/1.1** 双协议支持
- ✅ **学号自动提取** (custom_student_id / lis_person_sourcedid / sub)
- ✅ **并发安全** (SELECT ... FOR UPDATE 行锁)
- ✅ **自动用户创建** (Moodle Web Service API)
- ✅ **虚拟邮箱** ({student_id}@{domain})
- ✅ **Redis 缓存** (防重放攻击 nonce 验证)
- ✅ **队列处理** (异步用户创建 Job)
- ✅ **完整日志** (launch_logs 审计表)
- ✅ **健康检查** (定时监控 + 命令)
- ✅ **速率限制** (60次/分钟)
- ✅ **Docker 部署** (5个服务容器)
- ✅ **PHPUnit 测试**

## 管理命令

```bash
# 查看帮助
make help

# 服务管理
make up              # 启动服务
make down            # 停止服务
make restart         # 重启服务
make logs            # 查看日志

# 数据库
make migrate         # 运行迁移
make seed            # 填充数据
make fresh           # 重置数据库
make backup          # 备份数据库

# 监控
make health          # 健康检查
make stats           # 查看统计
make tools           # 列出工具
make test-moodle     # 测试连接

# 维护
make cleanup         # 清理日志
make cache-clear     # 清除缓存
make update          # 更新版本
make reset           # ⚠️ 重置数据
```

## Artisan 命令

```bash
php artisan lti:tools              # 列出工具配置
php artisan lti:stats              # 显示统计信息
php artisan lti:test-moodle        # 测试 Moodle 连接
php artisan lti:health-check       # 健康检查
php artisan lti:cleanup            # 清理过期日志
```

## 文档

- [快速开始](QUICKSTART.md) - 5分钟快速开始
- [生产部署](docs/DEPLOYMENT.md) - 详细部署指南
- [Moodle 配置](docs/MOODLE_SETUP.md) - Web Service 配置
- [API 文档](docs/API.md) - API 接口文档
- [变更日志](CHANGELOG.md) - 版本更新记录

## 项目结构

```
lti-gateway/
├── app/                    # 应用程序代码
├── config/                 # 配置文件
├── database/               # 数据库迁移和填充
├── docker/                 # Docker 配置
│   ├── nginx/             # Nginx 配置
│   ├── php/               # PHP 配置
│   └── postgres/          # PostgreSQL 初始化
├── docs/                   # 文档
├── resources/views/        # 视图
├── routes/                 # 路由
├── scripts/                # 实用脚本
├── storage/                # 存储目录
└── tests/                  # 测试
```

## 安全

- JWT 签名验证
- Nonce 重放攻击防护
- 速率限制保护
- 敏感数据加密存储
- SQL 注入防护

## 贡献

欢迎提交 Issue 和 Pull Request！

## 许可证

[MIT License](LICENSE)

## 支持

如有问题，请查看 [文档](docs/) 或提交 Issue。
