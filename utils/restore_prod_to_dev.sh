#!/bin/bash

mysqldump --complete-insert --quote-names --skip-triggers --user=root --password=root lute > dbexport.sql

mysqladmin -u root -proot drop test_lute

mysqladmin -u root -proot create test_lute

mysql -u root -proot test_lute < dbexport.sql

composer db:migrate:test

rm dbexport.sql
