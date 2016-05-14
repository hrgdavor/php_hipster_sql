# php_hipster_sql - PHP Hipster SQL
PHP SQL helper library before ORM was cool.



## Getting the files
Download the source or check the available [releases](releases)

Choose one of pre-built bundles from a release or build the bundles yourself after downloading the source. 
To build the bundles from source just run build.php from CLI or from a web server.


## Connecting to database
```php
require_once "totally.HipsterPdoMysql.php";
$DB = new org\hrg\php_hipster_sql\HipsterPdoMysql();
$DB->connect('host','username','pwd','dbname');

function hip_get_db(){ global $DB; return $DB;} // this is required for procedural style (totally.* bundles)
```

## Getting some data

```php
$rows =    hip_rows("SELECT * from coutnry"); // two diemnsional array
$article = hip_row("SELECT * from article WHERE id=",$id);// single row, and safe from sql injection
$page =    hip_rows_limit($offset, $limit, "SELECT * FROM users");// db specific OFFSET and LIMIT
$count =   hip_one("SELECT count(*) from city");
```

Object oriented version, that also allows for more connections on top of the default one used in procedural style.

```php
$rows =    $DB->rows("SELECT * from coutnry"); // two diemnsional array
$article = $DB->row("SELECT * from article WHERE id=",$id);// single row, and safe from sql injection
$page =    $DB->rows_limit($offset, $limit, "SELECT * FROM users");// db specific OFFSET and LIMIT
$count =   $DB->one("SELECT count(*) from city");
```

# SQL injection and value quoting
The simplest way I made my pages vulnerable to SQL injections was this:
```php
// unsafe
$result = mysql_query("SELECT * from article WHERE id=" . $_GET['id']);
$row    = msql_fetch_assoc($result);

// still unsafe
$row = hip_row("SELECT * from article WHERE id=" . $_GET['id']);

// SAFE :) ... and the difference is just one character
$row = hip_row("SELECT * from article WHERE id=" , $_GET['id']);

```

## 99%+
 - 99% of times you only need one connection to the database. ( more: multiple connections )
 - 99% of times you want get all the results you queried for. ( more: just give me the data )
 - 100% of times you want to get a result form a simple count query in one line of code :)

