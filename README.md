PHP Quandl
==========

[![Packagist](https://img.shields.io/packagist/v/dannyben/php-quandl.svg?maxAge=2592000&style=flat-square)](https://packagist.org/packages/dannyben/php-quandl)
[![Build Status](https://img.shields.io/travis/DannyBen/php-quandl.svg?maxAge=2592000&style=flat-square)](https://travis-ci.org/DannyBen/php-quandl)
[![Code Climate](https://img.shields.io/codeclimate/github/DannyBen/php-quandl.svg?maxAge=2592000&style=flat-square)](https://codeclimate.com/github/DannyBen/php-quandl)

This library provides easy access to the 
[Quandl API](https://www.quandl.com/help/api) 
using PHP.


Geting Started
--------------

Include the `Quandl.php` class in your code, and run one of the examples. 

To install with composer:

```sh
$ composer require dannyben/php-quandl
```

Examples
--------

This is a basic call. It will return a PHP object with price
data for AAPL:

```php
$api_key = "YOUR_KEY_HERE";
$quandl = new Quandl($api_key);
$data = $quandl->getSymbol("GOOG/NASDAQ_AAPL");
```

You may pass any parameter that is mentioned in the Quandl
documentation:

```php
$quandl = new Quandl($api_key);
$data = $quandl->getSymbol($symbol, [
	"sort_order"      => "desc",
	"exclude_headers" => true,
	"rows"            => 10,
	"column"          => 4, 
]);
```

The date range options get a special treatment. You may use
any date string that PHP's `strtotime()` understands.

```php
$quandl = new Quandl($api_key, "csv");
$data = $quandl->getSymbol($symbol, [
	"trim_start" => "today-30 days",
	"trim_end"   => "today",
]);
```

You can also search the entire Quandl database and get a list of
supported symbols in a data source:

```php
$quandl = new Quandl($api_key);
$data = $quandl->getSearch("crude oil");
$data = $quandl->getList("WIKI", 1, 10);
```

More examples can be found in the [examples.php](https://github.com/DannyBen/php-quandl/blob/master/examples.php) file 

Caching
-------

You may provide the `quandl` object with a cache handler function.
This function should be responsible for both reading from your cache and storing to it. 

See the [example_cache.php](https://github.com/DannyBen/php-quandl/blob/master/example_cache.php) file.


Reference
---------

### Constructor

The constructor accepts two optional parameters: `$api_key` and `$format`:

```php
$quandl = new Quandl("YOUR KEY", "csv");
```

You may also set these properties later (see below);






### Public Properties


#### `$api_key`

```php
$quandl->api_key = "YOUR KEY";
```
Set your API key

#### `$format`

```php
$quandl->format = 'csv';
```

Set the output format. Can be: `csv`, `xml`, `json`, and `object` 
(which will return a php object obtained with `json_decode()`).


#### `$force_curl`

```php
$quandl->force_curl = true;
```

Force download using curl. By default, we will try to download with 
`file_get_contents` if available, and fall back to `curl` only as a last 
resort.


#### `$no_ssl_verify`

```php
$quandl->no_ssl_verify = true;
```

Disables curl SSL verification. Set to true if you get an error saying 
"SSL certificate problem".


#### `$timeout`

```php
$quandl->timeout = 60;
```

Set the timeout for the download operations.


#### `$last_url`

```php
print $quandl->last_url;
```

Holds the last API URL as requested from Quandl, for debugging.


#### `$error`

```php
print $quandl->error;
```

In case there was an error getting the data from Quandl, the request response
will be `false` and this property will contain the error message.

#### `$was_cached`

```php
print $quandl->was_cached;
```

When using a cache handler, this property will be set to `true` if the 
response came from the cache.




### Methods

#### `getSymbol`

```php
mixed getSymbol( string $symbol [, array $params ] )
```

Returns an object containing data for a given symbol. The format
of the result depends on the value of `$quandl->format`.

The optional parameters array is an associative `key => value`
array with any of the parameters supported by Quandl.

You do not need to pass `auth_token` in the array, it will be 
automatically appended.


#### `getSearch`

```php
mixed getSearch( string $query [, int $page, int $per_page] )
```

Returns a search result object. Number of results per page is 
limited to 300 by default.

Note that currently Quandl does not support CSV response for this 
node so if `$quandl->format` is "csv", this call will return a JSON
string instead.


#### `getList`

```php
mixed getList( string $source [, int $page, int $per_page] )
```

Returns a list of symbols in a given source. Number of results per page is limited to 300 by default.
