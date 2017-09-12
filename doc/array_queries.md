# Writing query using function parameters that looks and feels like string concatenation

All functions that perform queries accept these argument styles:
```php
// 1. single argument string query
$countries = hip_rows("SELECT * from country");
// 2. variable number of arguments 
$user = hip_row("SELECT * from user WHERE id=" , $id);
$user = hip_row("SELECT * from user WHERE username=" , $username , " AND is_deleted=" , $deleted);
```

# The principle

__Odd elements are values that need quoting.__ _( value can be a query which will be later then expaneded before executing)_

The idea comes from writing a simple concatenation of query parts and values into a query string.

Example complex query across multiple lines using simple concatenation
```php
// each variable could be escaped beforehand, but it becomes tiresome, and is more prone to errors
$query = "SELECT * FROM bills WHERE bill_date >= '" . $from . 
    "' AND bill_date <= '" . $to . "' ORDER by bill_date";
```

Same query in `php_hipster_sql` . _No quotes are needed because a prepared statement is generated, and in case if PDO is not used they will be added automatically with values escaped_ 
```php
$query = hip_q("SELECT * FROM bills WHERE bill_date >= ", $from, 
    " AND bill_date <= ", $to, " ORDER by bill_date");
```

To see the query you can call `hip_build`
```php
echo hip_build($query);
// output: if $from = '2000-01-01' and $to = '2000-01-31'
// SELECT * FROM bills WHERE bill_date >= '2000-01-01' AND bill_date <= '2000-01-31' ORDER by bill_date
```

The PDO versions of the library do not build the query string, but prepare and execute prepared statements. 
It is highly recommended to stick to PDO versions as it removes any chance of sql injection inside parameter values (because of prepared statement).

To see the resulting prepared statement and the arguments, call `hip_prepare($query)`
```php
print_r(hip_prepare($query));
/* output when: $from = '2000-01-01' and $to = '2000-01-31'
Array
(
    [0] => SELECT * FROM bills WHERE bill_date >= ? AND bill_date <= ?  ORDER by bill_date
    [1] => Array
        (
            [0] => 2000-01-01
            [1] => 2000-01-31
        )
)
*/
```


