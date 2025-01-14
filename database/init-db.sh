#!/bin/bash
set -e

mariadb -u root -p"${MARIADB_ROOT_PASSWORD}" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS soc_pms;
    USE soc_pms;
    CREATE USER IF NOT EXISTS 'dbuser'@'%' IDENTIFIED BY 'dbpassword';
    GRANT ALL PRIVILEGES ON soc_pms.* TO 'dbuser'@'%';
    FLUSH PRIVILEGES;
    source /docker-entrypoint-initdb.d/soc_pms.sql;
EOSQL