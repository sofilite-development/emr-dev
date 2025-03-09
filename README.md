### Reporting Issues and Bugs

Report these on the [Issue Tracker](https://github.com/openemr/openemr/issues). If you are unsure if it is an issue/bug, then always feel free to use the [Forum](https://community.open-emr.org/) and [Chat](https://www.open-emr.org/chat/) to discuss about the issue 🪲.

### Reporting Security Vulnerabilities

Check out [SECURITY.md](.github/SECURITY.md)

### API

Check out [API_README.md](API_README.md)

### Docker

Check out [DOCKER_README.md](DOCKER_README.md)

### FHIR

Check out [FHIR_README.md](FHIR_README.md)

### For Developers

If using OpenEMR directly from the code repository, then the following commands will build OpenEMR (Node.js version 20.* is required) :

```shell
composer install --no-dev
npm install
npm run build
composer dump-autoload -o
```
Add a file if not exist `sites/default/sqlconf.php` by database credentials
```shell

<?php
//  OpenEMR
//  MySQL Config

global $disable_utf8_flag;
$disable_utf8_flag = false;

$host    = 'localhost';
$port    = '3306';
$login    = 'root';
$pass    = '123456';
$dbase    = 'openemr';
$db_encoding    = 'utf8mb4';

$sqlconf = array();
global $sqlconf;
$sqlconf["host"] = $host;
$sqlconf["port"] = $port;
$sqlconf["login"] = $login;
$sqlconf["pass"] = $pass;
$sqlconf["dbase"] = $dbase;
$sqlconf["db_encoding"] = $db_encoding;

//////////////////////////
//////////////////////////
//////////////////////////
//////DO NOT TOUCH THIS///
$config = 1; /////////////
//////////////////////////
//////////////////////////
//////////////////////////


```

- https://chatgpt.com/share/67c36212-aadc-8012-b6cc-274fe844178a