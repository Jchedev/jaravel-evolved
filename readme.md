# Laravel Evolved

Laravel is a really cool framework. But there is always some improvements that can be made. This repository is like beta sandbox for it, before a potential pull requests on the official laravel git.

Note: The following examples are based on a system which has a model `User` (sql table "users") who has many `Post` (sql table "posts" with a "user_id" column), with 0-X `Comment` attached to each of them (sql table "comments" with a "post_id" column).


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

- #### `handleJobOrDefer(ShouldQueue $job, $deferred = false)`
(todo...)

### Modified methods

- #### `info($message, $verbosity = null, $tab = 0)`
(todo...)

- #### `comment($message, $verbosity = null, $tab = 0)`
(todo...)

- #### `error($message, $verbosity = null, $tab = 0)`
(todo...)



## Jchedev\Laravel\Eloquent\Models\Model

This class inherits directly from [Illuminate\Database\Eloquent\Model](https://laravel.com/api/5.7/Illuminate/Database/Eloquent/Model.html) but add some features, specifically about relations management.

Implements the `CollectionOrModel` interface.

### New methods

- #### _static_ `table()`
(todo...)

- #### _static_ `tableColumn($column)`
(todo...)

- #### `getTableColumn($column)`
(todo...)

### Modified methods

- #### `newEloquentBuilder($query)`
This method has been overwritten to return a `Jchedev\Laravel\Eloquent\Builders\Builder` instead. If you decide to return your own builder model, you should make it inherits from `Jchedev\Laravel\Eloquent\Builders\Builder` first. 

- #### `newCollection(array $models = [])`
This method has been overwritten to return a `Jchedev\Laravel\Eloquent\Collections\Collection` instead. If you decide to return your own collection model, you should make it inherits from `Jchedev\Laravel\Eloquent\Collections\Collection` first. 



## Jchedev\Laravel\Eloquent\Collections\Collection

This class inherits directly from [Illuminate\Database\Eloquent\Collection](https://laravel.com/api/5.7/Illuminate/Database/Eloquent/Collection.html) but add new methods.

Implements the `CollectionOrModel` interface.

### New methods

- #### `builder()`
Returns a builder targeting only the items of the collection (using `whereIn(primary_key, [...])`). This method expects all the items of the collection to be from the same Model (Mixing some "Users" to some "Comments" will create unwanted behavior).  

- #### `update(array $attributes = [], array $options = [])`
(todo...)

- #### `update(array $attributes = [], array $options = [])`
(todo...)



## Jchedev\Laravel\Eloquent\Builders\Builder

This class inherits directly from [Illuminate\Database\Eloquent\Builder](https://laravel.com/api/5.7/Illuminate/Database/Eloquent/Builder.html) but add new methods.

### New methods

- #### `getModelTableColumn($column)`
(todo...)

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
(todo...)

- #### `addSelect($column, $addSelectAll = true)`
(todo...)

- #### `where($column, $operator = null, $value = null, $boolean = 'and')`
(todo...)

- #### `whereNull($column, $boolean = 'and', $not = false)`
(todo...)

- #### `whereIn($column, $values, $boolean = 'and', $not = false)`
(todo...)

- #### `whereIs($value, $boolean = 'and', $not = false)`
(todo...)

- #### `whereBetween($column, array $values, $boolean = 'and', $not = false)`
(todo...)



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
(todo...)



## Jchedev\Laravel\Http\Resources\Resource
(todo...)



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
(todo...)

- `get_class_basename($object)`: 
Accept a string or an object and return only the basename part of a `get_class()` return on it. (Example: `get_class_basename('Jchedev\Laravel\Console\Commands')` will return "Commands").

- `get_class_namespace($object)`: 
Accept a string or an object and return only the namespace part of a `get_class()` return on it. (Example: `get_class_basename('Jchedev\Laravel\Console\Commands')` will return "Jchedev\Laravel\Console").

#### strings.php

- `sanitize_string($value)`
(todo...)

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

