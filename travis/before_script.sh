#!/usr/bin/env bash

# create database and move db config into place
mysql -uroot -e '
    SET @@global.sql_mode = NO_ENGINE_SUBSTITUTION;
    DROP DATABASE IF EXISTS magento_integration_tests;
    CREATE DATABASE magento_integration_tests;
' && echo "Created empty MySQL database"

cd $HOME/build/magento2ce/dev/tests/integration
cp etc/install-config-mysql.travis.php.dist etc/install-config-mysql.php && echo "Prepared Magento 2 MySQL config"
