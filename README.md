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

	$api_key = "YOUR_KEY_HERE";
	$symbol  = "GOOG/NASDAQ_AAPL";

	// Example 1: Hello World
	$quandl = new Quandl();
	$data = $quandl->getCsv($symbol);

	// Example 2: API Key + JSON
	$quandl = new Quandl($api_key);
	$data = $quandl->getJson($symbol);

	// Example 3: Decoded JSON + Date Range
	$quandl = new Quandl($api_key);
	$quandl->trim_start = "today-30 days";
	$quandl->trim_end   = "today";
	$data = $quandl->getObject($symbol);

	// Example 4: More parameters
	$quandl = new Quandl($api_key);
	$quandl->sort_order = "desc"; // asc|desc
	$quandl->exclude_headers = true;
	$quandl->rows = 10;
	$quandl->column = 4; // 4 = close price
	$data = $quandl->getCsv($symbol);

	// Example 5: Frequency
	$quandl = new Quandl($api_key);
	$quandl->collapse = "weekly"; // none|daily|weekly|monthly|quarterly|annual
	$data = $quandl->getCsv($symbol);

	// Example 6: Transformation
	$quandl = new Quandl($api_key);
	$quandl->transformation = "diff"; // none|diff|rdiff|cumul|normalize
	$data = $quandl->getCsv($symbol);

	// Example 7: Constructor Options + Multiple Symbols
	$quandl = new Quandl($api_key, ["rows"=>30]);
	$data = $quandl->getData(["GOOG/NASDAQ_AAPL", "GOOG/NASDAQ_CSCO"]);

	// Example 8: Multiple Symbols with Column Selector
	$quandl = new Quandl($api_key, ["rows"=>30]);
	$data = $quandl->getData(["GOOG/NASDAQ_AAPL.4", "GOOG/NASDAQ_CSCO.4"]);

	// Example 9: Search
	$quandl = new Quandl($api_key);
	$data = $quandl->search("crude oil", 10, 2);

	// Example 10: Metadata
	$quandl = new Quandl($api_key);
	$quandl->exclude_data = true;
	$data = $quandl->getObject($symbol);
