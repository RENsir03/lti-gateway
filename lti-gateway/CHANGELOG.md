# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2024-01-15

### Added
- LTI 1.3 Advantage 完整支持
- LTI 1.1 兼容模式
- 学号自动提取 (custom_student_id / lis_person_sourcedid / sub)
- 并发安全的用户映射 (SELECT ... FOR UPDATE)
- 自动创建下游用户 (Moodle Web Service)
- 虚拟邮箱生成
- Redis 缓存防重放攻击
- 队列异步处理
- 完整的日志审计 (launch_logs)
- 健康检查与监控
- 速率限制保护 (60次/分钟)
- Docker 容器化部署
- PHPUnit 测试覆盖
- 管理命令 (health-check, stats, tools, cleanup, test-moodle)
- 自动备份脚本
- RSA 密钥生成工具

### Security
- JWT 签名验证
- Nonce 重放攻击防护
- 速率限制
- 敏感数据加密存储
- SQL 注入防护

### DevOps
- Docker Compose 配置
- Makefile 快捷命令
- 自动安装脚本
- 健康检查端点
- 日志轮转

## [Unreleased]

### Planned
- LTI 1.3 Deep Linking 支持
- 多 Moodle 实例支持
- 用户数据同步
- 管理后台界面
- Prometheus 指标导出
- Grafana 监控面板
