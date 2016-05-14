# Writing queries as arrays

## The multiple styles handled as one

All functions that perform queries accept these argument styles:
```php
// 1. string query
$countries = hip_rows("SELECT * from country");

// 2. array
$user = hip_row(array("SELECT * from user WHERE id=" , $id));
$user = hip_row(array("SELECT * from user WHERE username=" , $username , " AND is_deleted=" , $deleted));

// 3. variable number of arguments are treated same as array ( using func_get_args() )
$user = hip_row("SELECT * from user WHERE id=" , $id);
$user = hip_row("SELECT * from user WHERE username=" , $username , " AND is_deleted=" , $deleted);
```

This is only syntax sugar to allow for cleaner code (less verbose). The parameters from the examples above are internaly all handled the same.
```php
// 1. string query is treated as array with the string as only element
array("SELECT * from country");

// 2. array - that is what is actaually used internally
array("SELECT * from user WHERE id=" , $id);
array("SELECT * from user WHERE username=" , $username , " AND is_deleted=" , $deleted);

// 3. variable number of arguments are treated same as array ( using func_get_args() )
array("SELECT * from user WHERE id=" , $id);
array("SELECT * from user WHERE username=" , $username , " AND is_deleted=" , $deleted);
```

# The principle

__Odd elements are values that need quoting.__ _(if value is array, it is treated as a query recursively)_

The idea comes from making a classic case of concatenating static query parts and values into a query string.

Example complex query across multiple lines using simple concatenation
```php
// each variable could be escaped beforehand, but it becomes tiresome, and is more prone to errors
$sql = "SELECT * FROM bills WHERE bill_date >= '".$from."' AND bill_date <= '".$to."' ORDER by bill_date";
```

Same query in `php_hipster_sql` _(no quotes needed, they will be added automatically and values escaped)_ 
```php
$sql = array("SELECT * FROM bills WHERE bill_date >= ",$from," AND bill_date <= ",$to," ORDER by bill_date");
```

To see the query you can call `hip_build`
```php
echo hip_build($sql);
// output: if $from = '2000-01-01' and $to = '2000-01-31'
// SELECT * FROM bills WHERE bill_date >= '2000-01-01' AND bill_date <= '2000-01-31' ORDER by bill_date
```

The PDO versions of the library do not build the query string, but prepare and execute prepared statements. 

To see the prepared statement and the arguments, call `hip_prepare($sql)`
```php
print_r(hip_prepare($sql));
/* output: if $from = '2000-01-01' and $to = '2000-01-31'
Array
(
    [0] => SELECT * FROM bills WHERE bill_date >= ? AND bill_date <=  ORDER by bill_date
    [1] => Array
        (
            [0] => 2000-01-01
            [1] => 2000-01-31
        )
)
*/
```


