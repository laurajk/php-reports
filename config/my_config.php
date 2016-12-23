<?php

# db di prova in locale
#psql -h localhost reports postgres
#user: postgres
#pwd: password
#dbName: reports

// progetto di cui è stato fatto il fork
#https://jdorn.github.io/php-reports/

#progetto GIT
#https://github.com/jdorn/php-reports.git

// versione API google scaricate da composer
// v 2.0.1

# i valori possono essere
# pgsql per i db posgres
# mysql per i db mysql
define('DB_TYPE', 'pgsql');
define('DB_HOST', '10.254.4.247');
define('DB_USER', 'postgres');
define('DB_PASSWORD', 'v6bRUMMhuT');
define('DB_NAME', 'geodb5');
define('DB_PORT', '5432');

define('SQL_QUERY', 'SELECT * FROM reti.vw_collegamenti limit 10 OFFSET 10');

define('GOOGLE_AUTH', 'googleSheetsReport-4210adb569b0.json');
define('GOOGLE_SPREADSHEET_ID' , '1tyfkZsZNMEVl3ejZeN1P49PzMM3iA-EmUFDQtJ-eMMM');
define('GOOGLE_GID', 2134174337);
// con questo range scrive le righe dalla 32 in poi
//$range = "A32:F";
define('GOOGLE_RANGE', 'A2:V');
# i parametri accettati sono
# OVERWRITE se quando vado a scrivere sovrascrivo il contenuto del folgio tranne la riga di intestazione
# APPEND se scrivo dalla prima riga libera del foglio
define('GOOGLE_SPREADSHEET_WRITE' , 'OVERWRITE');