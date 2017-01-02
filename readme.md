# Laravel Evolved

Laravel is a really cool framework. But there is always some improvements that can be made. This repository is like beta sandbox for it, before a potential pull requests on the official laravel git.

Note: The following examples are based on a system which has a model `User` (sql table "users") who has many `Post` (sql table "posts" with a "user_id" column), with 0-X `Comment` attached to each of them (sql table "comments" with a "post_id" column).

## Jchedev\Laravel\Eloquent\Models\Model

This class inherits directly from [Illuminate\Database\Eloquent\Model](https://laravel.com/api/5.3/Illuminate/Database/Eloquent/Model.html) but add some features, specifically about relations management.

### Accessing relation values

(todo...)

### New Method (static) `table()`

(todo...)

### New Method (static) `tableColumn($column)`

(todo...)

### New Method `getTableColumn($column)`

(todo...)

### Updated Method `newEloquentBuilder($query)`

This method has been overwritten to return a `Jchedev\Laravel\Eloquent\Builders\Builder` instead. If you decide to return your own builder model, you should make it inherits from `Jchedev\Laravel\Eloquent\Builders\Builder` first. 

### Updated Method `newCollection(array $models = [])`

This method has been overwritten to return a `Jchedev\Laravel\Eloquent\Collections\Collection` instead. If you decide to return your own collection model, you should make it inherits from `Jchedev\Laravel\Eloquent\Collections\Collection` first. 

### Updated Method `relationLoaded($relations)`

This method overwrites the initial behavior of laravel but allows to check if nested relations are loaded `(Example: User.Posts.Comments`) where Laravel can't. 




## Jchedev\Laravel\Eloquent\Collections\Collection

This class inherits directly from [Illuminate\Database\Eloquent\Collection](https://laravel.com/api/5.3/Illuminate/Database/Eloquent/Collection.html) but add new methods.

### Using a scope method

(todo...)

### New Method `loadMissing($relations)`

The `load($relations)` method will always load the requested `$relations` for **all** the items of the collection even if they are already loaded. `loadMissing()` only load the necessary ones.

### New Method `builder()`

Returns a builder targeting only the items of the collection (using `whereIn(primary_key, [...])`). This method expects all the items of the collection to be from the same Model (Mixing some "Users" to some "Comments" will create unwanted behavior).  





## Jchedev\Laravel\Eloquent\Builders\Builder

This class inherits directly from [Illuminate\Database\Eloquent\Builder](https://laravel.com/api/5.3/Illuminate/Database/Eloquent/Builder.html) but add new methods.

### New Method `countWithLimit($columns = '*')`

With the existing implementation of the `count()` method, the `limit` parameters is ignored. This method changes that. (Example: If there is 200 users and the query is `User::take(10)->count()` returns 200, where `User::take(10)->countWithLimit()` returns 10.)

### New Method `forceFail()`

Sometimes, we want to make sure that a query builder will returns 0 results. This method can be combined to `get()` or `count()` to do that. (Example: 

### New Method `randomize()`

(todo...)

### New Method `getModelTableColumn($column)`

(todo...)

### Updated Method `setModel(Model $model)`

(todo...)

### Updated Method `select($columns = ['*'])`

(todo...)

### Updated Method `where($column, $operator = null, $value = null, $boolean = 'and')`

(todo...)

### Updated Method `whereNull($column, $boolean = 'and', $not = false)`

(todo...)

### Updated Method `whereIn($column, $values, $boolean = 'and', $not = false)`

(todo...)

### Updated Method `whereBetween($column, array $values, $boolean = 'and', $not = false)`





## Jchedev\Laravel\Console\Commands\Command

(todo...)


## Helpers

This library comes with some useful helpers:

- `boolean_to_string($value, $true = 'true', $false = 'false')`: Converts a boolean true/false into a string (Default values: "true" or "false").

- `null_if_empty($value)`: Check if a string is empty and return NULL if so.

- `time_duration($string, $convert_in = 'second')`: Converts a readable string ("2 weeks", "1 month") into a number (based on $convert_in which accepts: "second", "minute", "hour", "day", "month", "year"). 

- `time_multiplier($from, $to)`: Return the time ratio between $from and $to. (Example: `time_multiplier('minute', 'second')` will returns 60 since there is 60 seconds in 1 minute.

- `table_column($table, $column)`: Appends a $table name to a $column. This helper is mainly used by the query builder.

- `minify_html($html)`: Minify as much as possible some html content by removing unecessary tabs, comments and such and return the optimized string.

- `get_class_basename($object)`: Accept a string or an object and return only the basename part of a `get_class()` return on it. (Example: `get_class_basename('Jchedev\Laravel\Console\Commands')` will return "Commands").

- `get_class_namespace($object)`: Accept a string or an object and return only the namespace part of a `get_class()` return on it. (Example: `get_class_basename('Jchedev\Laravel\Console\Commands')` will return "Jchedev\Laravel\Console").
