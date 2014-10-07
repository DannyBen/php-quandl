<?php
	//--------------------------------------------------------------
	// Example: Quandl API with Cache
	//--------------------------------------------------------------
	require_once "Quandl.php";

	$api_key = "YOUR_KEY_HERE";

	$quandl = new Quandl($api_key, "csv");
	$quandl->cache_handler = 'cacheHandler';
	$data = $quandl->getSymbol("GOOG/NASDAQ_AAPL");
	
	// A simple example of a cache handler.
	// This function will be called by the Quandl class.
	// When action == "get", you should return a cached
	// object or false.
	// When action == "set", you should perform the save 
	// operation to your cache.
	function cacheHandler($action, $url, $data=null) {
		$cache_key = md5("quandl:$url");
		$cache_file = __DIR__ . "/$cache_key";

		if($action == "get" and file_exists($cache_file)) 
			return file_get_contents($cache_file);
		else if($action == "set") 
			file_put_contents($cache_file, $data);
		
		return false;
	}
?>