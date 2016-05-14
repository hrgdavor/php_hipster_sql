# Writing queries as arrays

## multiple styles

All functions that perform queries accept these argument styles:
```php
// 1. string query
$countries = sql_rows("SELECT * from country");

// 2. array
$user = sql_row(array("SELECT * from user WHERE id=" , $id));
$user = sql_row(array("SELECT * from user WHERE username=" , $username , " AND is_deleted=" , $deleted));

// 3. variable number of arguments are treated same as array (`using func_get_args()``)
$user = sql_row("SELECT * from user WHERE id=" , $id);
$user = sql_row("SELECT * from user WHERE username=" , $username , " AND is_deleted=" , $deleted);
```

This is onyl syntax sugar to allow for cleaner code (les verbose when not necessary). The examples above are internaly all handled the same.
```php
// 1. string query
array("SELECT * from country");

// 2. array
array("SELECT * from user WHERE id=" , $id);
array("SELECT * from user WHERE username=" , $username , " AND is_deleted=" , $deleted);

// 3. variable number of arguments are treated same as array (`using func_get_args()``)
array("SELECT * from user WHERE id=" , $id);
array("SELECT * from user WHERE username=" , $username , " AND is_deleted=" , $deleted);
```

