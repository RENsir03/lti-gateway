# LTI Gateway Makefile

.PHONY: help install up down restart logs test shell migrate seed fresh cache-clear health cleanup stats tools test-moodle queue-restart backup reset check-requirements update key-generate analyze export-mappings import-mappings metrics report sync-users notify status

help: ## 显示帮助信息
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

check-requirements: ## 检查系统要求
	@chmod +x scripts/check-requirements.sh
	@./scripts/check-requirements.sh

install: ## 安装项目
	@echo "正在安装 LTI Gateway..."
	@chmod +x scripts/install.sh
	@./scripts/install.sh

up: ## 启动所有服务
	docker-compose -f ../docker-compose-lti.yml up -d

down: ## 停止所有服务
	docker-compose -f ../docker-compose-lti.yml down

restart: ## 重启服务
	docker-compose -f ../docker-compose-lti.yml restart

logs: ## 查看日志
	docker-compose -f ../docker-compose-lti.yml logs -f

test: ## 运行测试
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan test

shell: ## 进入应用容器
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app bash

migrate: ## 运行数据库迁移
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan migrate --force

seed: ## 填充测试数据
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan db:seed

fresh: ## 重置数据库
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan migrate:fresh --seed

cache-clear: ## 清除缓存
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan cache:clear
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan config:clear
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan route:clear

health: ## 运行健康检查
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:health-check

cleanup: ## 清理过期日志
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:cleanup

stats: ## 显示统计信息
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:stats

tools: ## 列出工具配置
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:tools

test-moodle: ## 测试 Moodle 连接
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:test-moodle

queue-restart: ## 重启队列
	docker-compose -f ../docker-compose-lti.yml restart lti_gateway_queue

backup: ## 备份数据库
	@chmod +x scripts/backup.sh
	@./scripts/backup.sh

reset: ## ⚠️ 重置所有数据 (危险操作！)
	@chmod +x scripts/reset.sh
	@./scripts/reset.sh

update: ## 更新到最新版本
	@chmod +x scripts/update.sh
	@./scripts/update.sh

key-generate: ## 生成 RSA 密钥对
	@php scripts/generate-keys.php

analyze: ## 分析日志
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:analyze

export-mappings: ## 导出用户映射
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:export-mappings

import-mappings: ## 导入用户映射
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:import-mappings

metrics: ## 查看系统指标
	@curl -s http://localhost:8081/metrics/system | jq .

report: ## 生成系统报告
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:report

sync-users: ## 同步用户数据
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:sync-users

notify: ## 发送管理员通知
	docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:notify

status: ## 查看系统整体状态
	@echo "========================================"
	@echo "     LTI Gateway 系统状态"
	@echo "========================================"
	@echo ""
	@echo "🐳 Docker 容器状态:"
	@docker-compose -f ../docker-compose-lti.yml ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || echo "   服务未运行"
	@echo ""
	@echo "📊 最近24小时统计:"
	@docker-compose -f ../docker-compose-lti.yml exec -T lti_gateway_app php artisan lti:stats --days=1 2>/dev/null || echo "   无法获取统计"
	@echo ""
	@echo "✅ 健康检查:"
	@docker-compose -f ../docker-compose-lti.yml exec -T lti_gateway_app php artisan lti:health-check 2>/dev/null || echo "   健康检查失败"
	@echo ""
	@echo "========================================"
