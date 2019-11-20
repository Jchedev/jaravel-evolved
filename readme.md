# Laravel Evolved

Laravel is a really cool framework. But there is always some improvements that can be made. This repository is like beta sandbox for it, before a potential pull requests on the official laravel git.

## Jchedev\Laravel\Classes

This library comes with some useful standalone classes:

- `Pagination\ByOffsetLengthAwarePaginator` 
(todo...)

- `GPS\GPSCoordinates`
(todo...)

- `Selectors\Selector`
(todo...)

- `Services\Service`
(todo...)

- `Validation\Validator`
(todo...)



## Jchedev\Laravel\Console\Commands\Command
(todo...)

### New methods

- #### `createProgressBar($nbLines)`
(todo...)

- #### `advanceProgressBar($nbLinesMoved = 1)`
(todo...)

- #### `hasActiveProgressBar()`
(todo...)

- #### `finishProgressBar()`
(todo...)

### Modified methods

- #### `info($message, $verbosity = null, $tab = 0)`
(todo...)

- #### `comment($message, $verbosity = null, $tab = 0)`
(todo...)

- #### `error($message, $verbosity = null, $tab = 0)`
(todo...)



## Jchedev\Laravel\Eloquent\Models\Model

This class inherits directly from `Illuminate\Database\Eloquent\Model` but add some features, specifically about relations management.

Implements the `CollectionOrModel` interface.

### New methods

- #### _static_ `table()`
Allows the usage of `getTable()` statically.

- #### _static_ `tableColumn($column)`
Allows the usage of `getTableColumn()` statically.

- #### `getTableColumn($column)`
Alias for `qualifyColumn($column)` which has been added since.

### Modified methods

- #### `newEloquentBuilder($query)`
This method has been overwritten to return a `Jchedev\Laravel\Eloquent\Builders\Builder` instead. 
If you decide to use your own Builder class, you should make it inherits from `Jchedev\Laravel\Eloquent\Builders\Builder` first. 

- #### `newCollection(array $models = [])`
This method has been overwritten to return a `Jchedev\Laravel\Eloquent\Collections\Collection` instead. 
If you decide to return your own Collection class, you should make it inherits from `Jchedev\Laravel\Eloquent\Collections\Collection` first. 



## Jchedev\Laravel\Eloquent\Collections\Collection

This class inherits directly from `Illuminate\Database\Eloquent\Collection` but add new methods.

Implements the `CollectionOrModel` interface.

### New methods

- #### `builder()`
Returns a builder targeting only the items of the collection (using `whereIn(primary_key, [...])`). This method expects all the items of the collection to be from the same Model (Mixing some "Users" to some "Comments" will create unwanted behavior).  

- #### `update(array $attributes = [], array $options = [])`
Run an update query on the builder returned by `builder()`.

- #### `delete(array $options = [])`
Run an delete query on the builder returned by `builder()`.



## Jchedev\Laravel\Eloquent\Builders\Builder

This class inherits directly from `Illuminate\Database\Eloquent\Builder` but add new methods.

### New methods

- #### `addSelectThroughRelation($relationName, $fields)`
(todo...)

- #### `joinThroughRelation($relationName, $type = 'inner')`
(todo...)

- #### `countWithLimit($columns = '*')`
With the existing implementation of the `count()` method, the `limit` parameters is ignored. This method changes that. 
```
Example with a total of 200 users:
User::take(10)->count() =  200
User::take(10)->countWithLimit() = 10
```

- #### `chunkWithLimit($count, callable $callback, $limit = null)`
(todo...)

### Modified methods

- #### `select($columns = ['*'])`
The default `select()` method is not qualifying the column name. This one is.

- #### `where($column, $operator = null, $value = null, $boolean = 'and')`
The default `where()` method is not qualifying the column name. This one is.

- #### `whereNull($column, $boolean = 'and', $not = false)`
The default `whereNull()` method is not qualifying the column name. This one is.

- #### `whereIn($column, $values, $boolean = 'and', $not = false)`
The default `whereIn()` method is not qualifying the column name. This one is.

- #### `whereIs($value, $boolean = 'and', $not = false)`
(todo...)

- #### `whereBetween($column, array $values, $boolean = 'and', $not = false)`
The default `whereBetween()` method is not qualifying the column name. This one is.



## Jchedev\Laravel\Exceptions

This library comes with some extra exceptions:

- `UnexpectedClassException`
(todo...)



## Jchedev\Laravel\Http\Middleware

This library comes with some extra middleware:

- `AuthNotRequired`
(todo...)

- `LogQueries`
(todo...)

- `MinifiedResponse`
(todo...)



## Jchedev\Laravel\Http\Resources\Collection

Calling a method that doesnt exist on the object will automatically forward the call to each Resource element.



## Jchedev\Laravel\Http\Resources\Resource

Calling `::collection()` will return a `Jchedev\Laravel\Http\Resources\Collection` object.



## Jchedev\Laravel\Traits

This library comes with some extra rules:

- `HandlesExceptions`

- `HasCompositePrimaryKey`

- `HasGPSCoordinates`

- `HasReference`



## Helpers

This library comes with some useful helpers:

#### arrays.php

- `array_get_any(array $array, array $keys, $default = null)`
(todo...)

#### objects.php

- `get_variable_type($variable)`
The default gettype() method doesnt work if a Closure. This method does.

- `get_class_basename($object)`: 
Accept a string or an object and return only the basename part of a `get_class()` return on it. (Example: `get_class_basename('Jchedev\Laravel\Console\Commands')` will return "Commands").

- `get_class_namespace($object)`: 
Accept a string or an object and return only the namespace part of a `get_class()` return on it. (Example: `get_class_basename('Jchedev\Laravel\Console\Commands')` will return "Jchedev\Laravel\Console").

#### strings.php

- `sanitize_string($value)`
Trim the string, except if value is NULL.

- `boolean_to_string($value, $true = 'true', $false = 'false')`: 
Converts a boolean true/false into a string (Default values: "true" or "false").

- `null_if_empty($value)`: 
Check if a string is empty and return NULL if so.

- `time_duration($string, $convert_in = 'second')`: 
Converts a readable string ("2 weeks", "1 month") into a number (based on $convert_in which accepts: "second", "minute", "hour", "day", "month", "year"). 

- `time_multiplier($from, $to)`: 
Return the time ratio between $from and $to. (Example: `time_multiplier('minute', 'second')` will returns 60 since there is 60 seconds in 1 minute.

- `table_column($table, $column)`: 
Appends a $table name to a $column. This helper is mainly used by the query builder.

- `minify_html($html)`: 
Minify as much as possible some html content by removing unecessary tabs, comments and such and return the optimized string.

