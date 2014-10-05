PHP Quandl
==========

This library provides easy access to the 
[Quandl API](https://www.quandl.com/help/api) 
using PHP.


Geting Started
--------------

Include the `Quandl.php` class in your code, and run one of 
the examples. 


Examples
--------

This is used in all subsequent examples.

	$api_key = "YOUR_KEY_HERE";
	$symbol  = "GOOG/NASDAQ_AAPL";

### Example 1: Hello Quandl

The simplest request form, sent without any API key.

	$quandl = new Quandl();
	$data = $quandl->getCsv($symbol);

### Example 2: API Key + JSON

	$quandl = new Quandl($api_key);
	$data = $quandl->getJson($symbol);

### Example 3: Decoded JSON + Date Range

Using the `trim_start` and `trim_end` properties, you may input any
string that can be understood by PHP's `strtotime`. 

	$quandl = new Quandl($api_key);
	$quandl->trim_start = "today-30 days";
	$quandl->trim_end   = "today";
	$data = $quandl->getObject($symbol);

### Example 4: XML + More parameters

You may assign any parameter in the Quandl documentation, using the 
syntax `$quandl->param_name = value`.

	$quandl = new Quandl($api_key);
	$quandl->sort_order = "desc"; // asc|desc
	$quandl->exclude_headers = true;
	$quandl->rows = 10;
	$quandl->column = 4; // 4 = close price
	$data = $quandl->getXml($symbol);

### Example 5: Frequency

	$quandl = new Quandl($api_key);
	$quandl->collapse = "weekly"; // none|daily|weekly|monthly|quarterly|annual
	$data = $quandl->getCsv($symbol);

### Example 6: Transformation

	$quandl = new Quandl($api_key);
	$quandl->transformation = "diff"; // none|diff|rdiff|cumul|normalize
	$data = $quandl->getCsv($symbol);

### Example 7: Constructor Options + Multiple Symbols

The second parameter of the contructor is an optional array of options.
Using this array is the equivalent of using the 
`$quandl->parameter = value` syntax.

In addition, all the methods that accept a symbol, also accept an array
of symbols. Each symbol in the array may be written in the usual 
slash notation (GOOG/NASDAQ_AAPL) or the dot notation (GOOG.NASDAQ_AAPL).

	$quandl = new Quandl($api_key, ["rows"=>30]);
	$data = $quandl->getData(["GOOG/NASDAQ_AAPL", "GOOG/NASDAQ_CSCO"]);

### Example 8: Multiple Symbols with Column Selector

When using multiple symbols, you may append a column selector (exactly
as described in the Quandl documentation) to select specific columns.

	$quandl = new Quandl($api_key, ["rows"=>30]);
	$data = $quandl->getData(["GOOG/NASDAQ_AAPL.4", "GOOG/NASDAQ_CSCO.4"]);

### Example 9: Search

The search method receives a query, and two additional optional 
parameters: Results per page, and page number.

	$quandl = new Quandl($api_key);
	$data = $quandl->search("crude oil", 10, 2);

### Example 10: Metadata

	$quandl = new Quandl($api_key);
	$quandl->exclude_data = true;
	$data = $quandl->getObject($symbol);


Caching
-------

You may provide the `quandl` object with a cache handler function.
This function should be responsible for both reading from your cache and storing to it. 

See the `example_cache.php` file.

