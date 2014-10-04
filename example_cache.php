<?php
	//--------------------------------------------------------------
	// Example: Quandl API with Cache
	//--------------------------------------------------------------
	require_once "Quandl.php";

	$symbol  = "GOOG/NASDAQ_AAPL";

	$quandl = new Quandl();
	$quandl->cache_handler = cacheHandler;
	$quandl->rows = 10;
	$data = $quandl->getCsv($symbol);
	
	// After calling any of the getData methods, the was_cached
	// property will be true if your cache handler returned an 
	// object. You may use this to store the returned object in
	// your cache.
	if(!$quandl->was_cached) {
		$cache_key = md5($quandl->getUrl($symbol));
		file_put_contents($cache_key, $data);
	}

	// Your cache handler should return an object or false
	// if not in your cache
	function cacheHandler($url) {
		$cache_key = md5($url);
		return file_exists($cache_key)
			? file_get_contents($cache_key)
			: false;
	}

?>