<?php
	//--------------------------------------------------------------
	// Example: Quandl API with Cache
	//--------------------------------------------------------------
	require_once "Quandl.php";

	$quandl = new Quandl();
	$quandl->cache_handler = 'cacheHandler';
	$quandl->rows=10;
	$d = $quandl->getCsv("GOOG/NASDAQ_AAPL");
	
	// A simple example of a cache handler.
	// This function will be called by the Quandle class.
	// When action == "get", you should return a cached
	// object or false.
	// When action == "set", you should perform the save 
	// operation to your cache.
	function cacheHandler($action, $url, $data=null) {
		$cache_key = md5("quandl:$url");
		if($action == "get" and file_exists($cache_key)) 
			return file_get_contents($cache_key);
		else if($action == "set") 
			file_put_contents($cache_key, $data);
		return false;
	}
?>