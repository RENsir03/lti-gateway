#!/bin/bash

# LTI Gateway 重置脚本 (危险操作！)

set -e

echo "============================================"
echo "⚠️  LTI Gateway 重置脚本"
echo "============================================"
echo ""
echo "此操作将:"
echo "  - 删除所有 Docker 容器"
echo "  - 删除所有数据卷"
echo "  - 重置数据库"
echo ""

read -p "确定要继续吗? (输入 'yes' 确认): " confirm

if [ "$confirm" != "yes" ]; then
    echo "操作已取消"
    exit 0
fi

echo ""
echo "开始重置..."

# 停止并删除容器
echo "[1/4] 停止并删除容器..."
docker-compose -f ../docker-compose-lti.yml down -v

# 删除数据卷
echo "[2/4] 删除数据卷..."
docker volume rm -f moodle_ltigateway_db_data moodle_ltigateway_redis_data 2>/dev/null || true

# 清理存储目录
echo "[3/4] 清理存储目录..."
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*
rm -rf storage/logs/*
rm -rf bootstrap/cache/*.php

# 重新创建目录
echo "[4/4] 重新创建目录..."
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

echo ""
echo "============================================"
echo "✅ 重置完成！"
echo "============================================"
echo ""
echo "请重新运行安装脚本:"
echo "  make install"
echo ""
