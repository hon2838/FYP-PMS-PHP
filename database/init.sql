-- Create database if not exists
CREATE DATABASE IF NOT EXISTS soc_pms;
USE soc_pms;

-- Create user and grant privileges
CREATE USER IF NOT EXISTS 'dbuser'@'%' IDENTIFIED BY 'dbpassword';
GRANT ALL PRIVILEGES ON soc_pms.* TO 'dbuser'@'%';
FLUSH PRIVILEGES;

-- Import schema
SOURCE /docker-entrypoint-initdb.d/soc_pms.sql;