PHP Quandl
==========

[![Packagist](https://img.shields.io/packagist/v/dannyben/php-quandl.svg?maxAge=14400&style=flat-square)](https://packagist.org/packages/dannyben/php-quandl)
[![Build Status](https://img.shields.io/travis/DannyBen/php-quandl.svg?maxAge=14400&style=flat-square)](https://travis-ci.org/DannyBen/php-quandl)
[![Maintainability](https://img.shields.io/codeclimate/maintainability/DannyBen/php-quandl.svg?style=flat-square)](https://codeclimate.com/github/DannyBen/php-quandl)
[![Issues](https://img.shields.io/codeclimate/issues/github/DannyBen/php-quandl.svg?style=flat-square)](https://codeclimate.com/github/DannyBen/php-quandl)


---

This library provides easy access to the [Quandl API][1] using PHP.

It provides several convenience methods to common Quandl API endpoints, as
well as a generic method to access any of Quandl's endpoints directly.


---

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
$data = $quandl->getSymbol("WIKI/AAPL");
```

You may pass any parameter that is mentioned in the Quandl
documentation:

```php
$quandl = new Quandl($api_key);
$data = $quandl->getSymbol($symbol, [
	"sort_order"      => "desc",
	"rows"            => 10,
	"column_index"    => 4, 
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

To access any Quandl API endpoint directly, use the `get` method

```php
$quandl = new Quandl($api_key);
$data = $quandl->get("databases/WIKI");
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

In case there was an error getting the data from Quandl, the request 
response will be `false` and this property will contain the error message.

#### `$was_cached`

```php
print $quandl->was_cached;
```

When using a cache handler, this property will be set to `true` if the 
response came from the cache.




### Methods

#### `get`

```php
mixed get( string $path [, array $params ] )

// Examples
$data = $quandl->get( 'datasets/EOD/QQQ' );
$data = $quandl->get( 'datasets/EOD/QQQ', ['rows' => 5] );
```

Returns an object containing the response from any of Quandl's API
endpoints. The format of the result depends on the value of 
`$quandl->format`.

The optional parameters array is an associative `key => value`
array with any of the parameters supported by Quandl.

You do not need to pass `auth_token` in the array, it will be 
automatically appended.


#### `getSymbol`

```php
mixed getSymbol( string $symbol [, array $params ] )

// Examples
$data = $quandl->getSymbol( 'WIKI/AAPL' );
$data = $quandl->getSymbol( 'WIKI/AAPL', ['rows' => 5] );
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

// Examples
$data = $quandl->getSearch( "gold" );
$data = $quandl->getSearch( "gold", 1, 10 );
```

Returns a search result object. Number of results per page is 
limited to 300 by default.

Note that currently Quandl does not support CSV response for this 
node so if `$quandl->format` is "csv", this call will return a JSON
string instead.


#### `getList`

```php
mixed getList( string $source [, int $page, int $per_page] )

// Examples
$data = $quandl->getList( 'WIKI' );
$data = $quandl->getList( 'WIKI', 1, 10 );
```

Returns a list of symbols in a given source. Number of results per page is 
limited to 300 by default.


#### `getMeta`

```php
mixed getMeta( string $source )

// Example
$data = $quandl->getMeta( 'WIKI' );
```

Returns metadata about a symbol.


#### `getDatabases`

```php
mixed getDatabases( [int $page, int $per_page] )

// Examples
$data = $quandl->getDatabases();
$data = $quandl->getDatabases( 1, 10 );
```

Returns a list of available databases. Number of results per page is 
limited to 100 by default.


#### `getBulk`

> This feature is only supported with premium databases.

```php
boolean getBulk( string $database, string $path [, boolean $complete] )

// Examples
boolean getBulk( 'EOD', 'eod-partial.zip' );
boolean getBulk( 'EOD', 'eod-full.zip', true );
```

Downloads the entire database and saves it to a ZIP file. If `$complete` 
is true (false by default), it will download the entire database, otherwise,
it will download the last day only.



[1]: https://www.quandl.com/help/api
