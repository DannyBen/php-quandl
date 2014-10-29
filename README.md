PHP Quandl
==========

This library provides easy access to the 
[Quandl API](https://www.quandl.com/help/api) 
using PHP.


Geting Started
--------------

Include the `Quandl.php` class in your code, and run one of 
the examples. 

You may also install with composer:

    "require": {
        "dannyben/php-quandl": "*"
    }


Examples
--------

This is a basic call. It will return a PHP object with price
data for AAPL:

	$api_key = "YOUR_KEY_HERE";
	$quandl = new Quandl($api_key);
	$data = $quandl->getSymbol("GOOG/NASDAQ_AAPL");

You may pass any parameter that is mentioned in the Quandl
documentation:

	$quandl = new Quandl($api_key);
	$data = $quandl->getSymbol($symbol, [
		"sort_order"      => "desc",
		"exclude_headers" => true,
		"rows"            => 10,
		"column"          => 4, 
	]);


The date range options get a special treatment. You may use
any date string that PHP's `strtotime()` understands.

	$quandl = new Quandl($api_key, "csv");
	$data = $quandl->getSymbol($symbol, [
		"trim_start" => "today-30 days",
		"trim_end"   => "today",
	]);

Multiple symbols, supported symbols and search methods are also 
available:

	$quandl = new Quandl($api_key);
	$data = $quandl->getSymbols(["WIKI/AAPL", "WIKI/CSCO"]);
	$data = $quandl->getSearch("crude oil");
	$data = $quandl->getList("WIKI", 1, 10);


More examples can be found in the [examples.php](https://github.com/DannyBen/php-quandl/blob/master/examples.php) file 

Caching
-------

You may provide the `quandl` object with a cache handler function.
This function should be responsible for both reading from your cache and storing to it. 

See the [example_cache.php](https://github.com/DannyBen/php-quandl/blob/master/example_cache.php) file.


Reference
---------

### Constructor and public properties

The constructor accepts two optional parameters: `$api_key` and `$format`:

	$quandl = new Quandl("YOUR KEY", "csv");

You may also set these properties later:

	$quandl->api_key = "YOUR KEY";
	$quandl->format  = "json";

`$format` can be one of `csv`, `xml`, `json`, and `object` (which will return a php object obtained with `json_decode()`).

After each call to Quandl, the property `$last_url` will be set 
for debugging and other purposes. In case there was an error getting
the data from Quandl, the result will be `false` and the property 
`$error` will contain the error message.


### getSymbol

`mixed getSymbol( string $symbol [, array $params ] )`

Returns an object containing data for a given symbol. The format
of the result depends on the value of `$quandl->format`.

The optional parameters array is an associative `key => value`
array with any of the parameters supported by Quandl.

You do not need to pass `auth_token` in the array, it will be 
automatically appended.


### getSymbols

`mixed getSymbols( array $symbols [, array $params ] )`

Same as `getSymbol()` only instead of a single symbol, it receives
an array of multiple symbols. Each symbol in the array may be 
listed using the slash notation (`WIKI/AAPL`) or dot notation 
(`WIKI.AAPL`).

In addition, you may append the column selector to each symbol in 
order to get only selected columns. For example, `WIKI/AAPL.4` will 
return only the close prices (column 4) of AAPL.


### getSearch

`mixed getSearch( string $query [, int $page, int $per_page] )`

Returns a search result object. Number of results per page is 
limited to 300 by default.

Note that currently Quandl does not support CSV response for this 
node so if `$quandl->format` is "csv", this call will return a JSON
string instead.


### getList

`mixed getList( string $source [, int $page, int $per_page] )`

Returns a list of symbols in a given source. Number of results per page
is limited to 300 by default.

