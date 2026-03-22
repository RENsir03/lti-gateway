-- PostgreSQL 初始化脚本

-- 创建数据库
CREATE DATABASE lti_gateway;

-- 创建用户
CREATE USER lti_gateway_user WITH PASSWORD 'lti_gateway_password_123';

-- 授权
GRANT ALL PRIVILEGES ON DATABASE lti_gateway TO lti_gateway_user;

-- 切换到新数据库
\c lti_gateway;

-- 创建扩展
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- 设置时区
SET timezone = 'Asia/Shanghai';
