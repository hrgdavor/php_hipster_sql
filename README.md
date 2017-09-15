# php_hipster_sql - PHP Hipster SQL
PHP SQL helper library before ORM was cool.


## Getting the files
Choose one of pre-built bundles from a [release](https://github.com/hrgdavor/php_hipster_sql/releases) or build the bundles yourself after downloading the source. 

To build the bundles from source just run `build.php` from CLI or from a web server.


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

Object oriented version, that also allows for more connections _(on top of the one used in procedural style)_.

```php
$rows =    $DB->rows("SELECT * from coutnry"); // two diemnsional array
$article = $DB->row("SELECT * from article WHERE id=",$id);// single row, and safe from sql injection
$page =    $DB->rows_limit($offset, $limit, "SELECT * FROM users");// db specific OFFSET and LIMIT
$count =   $DB->one("SELECT count(*) from city");
```

## SQL injection safety and value quoting for FREE
__DO NOT concatenate user provided values to the query string yourself !__

You get automatic value quoting and safety from SQL injection for free. Read more details on the queries expressed as arrays 
[here](doc/array_queries.md).

To get a quick idea, read the rest of this section.

The simplest way one can make pages vulnerable to SQL injections is this (simple fix example included):
```php
// unsafe
$result = mysql_query("SELECT * from article WHERE id=" . $_GET['id']);
$row    = msql_fetch_assoc($result);

// still unsafe
$row = hip_row("SELECT * from article WHERE id=" . $_GET['id']);

// SAFE :) ... and the difference is just one character
$row = hip_row("SELECT * from article WHERE id=" , $_GET['id']);

```

As you can see, making the queries is very simple, but keep in mind that this subtle difference also
means it is easy to overlook the unsafe code, so be dilligent. [rad more](doc/array_queries.md) about variable parameter queries.

## migration
In case you are becoming a hipster gradually :) 

In an existing project you can skip the `$DB->connect(...)` and reuse an existing connection form the library that you are
currently using. Just call `$DB->set_connection($MyExistingConnection);` with your existing conenction reference and
make sure the type of the existing connection matches the hipster class flavor that you are using. 

There is no guarantee that this will not cause side effects, so it is up to you if you want to try the gradual transition instead of going full hipster.



## 99%+
 - 99% of times you only need one connection to the database. ( more: multiple connections )
 - 99% of times you want get all the results you queried for. ( more: just give me the data )
 - 100% of times you want to get a result form a simple count query in one line of code :)

