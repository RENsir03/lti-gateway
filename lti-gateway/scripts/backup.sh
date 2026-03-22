#!/bin/bash

# LTI Gateway 备份脚本

BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="lti_gateway_backup_${DATE}.sql"

mkdir -p ${BACKUP_DIR}

echo "开始备份 LTI Gateway 数据库..."

docker-compose -f ../docker-compose-lti.yml exec -T lti_gateway_db pg_dump \
    -U lti_gateway_user \
    -d lti_gateway \
    --clean \
    --if-exists \
    > ${BACKUP_DIR}/${BACKUP_FILE}

if [ $? -eq 0 ]; then
    echo "备份成功: ${BACKUP_DIR}/${BACKUP_FILE}"
    
    # 压缩备份
    gzip ${BACKUP_DIR}/${BACKUP_FILE}
    echo "备份已压缩: ${BACKUP_DIR}/${BACKUP_FILE}.gz"
    
    # 删除 30 天前的备份
    find ${BACKUP_DIR} -name "lti_gateway_backup_*.sql.gz" -mtime +30 -delete
    echo "已清理 30 天前的备份"
else
    echo "备份失败!"
    exit 1
fi
