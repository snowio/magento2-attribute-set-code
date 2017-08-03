#!/usr/bin/env bash

echo > ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini
echo 'memory_limit = -1' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
phpenv rehash

composer config --global repo.packagist false
composer config --global repo.package path `pwd`
