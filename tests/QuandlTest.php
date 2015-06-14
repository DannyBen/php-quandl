<?php
//--------------------------------------------------------------
// Tests: Quandl
//--------------------------------------------------------------
require_once __DIR__ . "/../Quandl.php";

class QuandlTest extends PHPUnit_Framework_TestCase {
	private $api_key  = "DEBUG_KEY";

	private $symbol   = "WIKI/AAPL";
	private $symbols  = ["WIKI/CSCO", "WIKI/AAPL"];
	private $dates    = ["trim_start" => "2014-01-01", "trim_end" => "2014-02-02"];

	public function tearDown() {
		$this->cache_file and unlink($this->cache_file);
	}

	public function testCsv() {
		$this->_testGetSymbol("csv", 2800);
		$this->_testGetSymbol("csv", 2800, true);
	}

	public function testXml() {
		$this->_testGetSymbol("xml", 14000);
		$this->_testGetSymbol("xml", 14000, true);
	}

	public function testJson() {
		$this->_testGetSymbol("json", 4200);
		$this->_testGetSymbol("json", 4200, true);
	}

	public function testObject() {
		$this->_testGetSymbol("object", 12000);
		$this->_testGetSymbol("object", 12000, true);
	}

	public function testInvalidUrl() {
		$this->_testInvalidUrl();
		$this->_testInvalidUrl(true);
	}

	public function testGetList() {
		$this->_testGetList();
		$this->_testGetList(true);
	}

	public function testGetSearch() {
		$this->_testGetSearch();
		$this->_testGetSearch(true);
	}

	public function testCache() {
		$this->_testCache();
		$this->cache_file and unlink($this->cache_file);
		$this->_testCache(true);
	}

	public function cacheHandler($action, $url, $data=null) {
		$cache_key = md5("quandl:$url");
		$cache_file = __DIR__ . "/$cache_key";

		if($action == "get" and file_exists($cache_file)) 
			return file_get_contents($cache_file);
		else if($action == "set") 
			file_put_contents($cache_file, $data);

		$this->cache_file = $cache_file;
		
		return false;
	}

	private function _testInvalidUrl($force_curl=false) {
		$quandl = new Quandl($this->api_key, "json");
		$quandl->force_curl = $quandl->no_ssl_verify = $force_curl;
		$r = $quandl->getSymbol("INVALID/SYMBOL", $this->dates);
		$this->assertEquals($quandl->error, "Invalid URL", 
			"TEST invalidUrl response");
	}

	private function _testGetList($force_curl=false) {
		$quandl = new Quandl($this->api_key);
		$quandl->force_curl = $quandl->no_ssl_verify = $force_curl;
		$r = $quandl->getList("WIKI", 1, 10);
		$this->assertEquals(10, count($r->docs),
			"TEST getList count");
	}

	private function _testGetSearch($force_curl=false) {
		$quandl = new Quandl($this->api_key);
		$quandl->force_curl = $quandl->no_ssl_verify = $force_curl;
		$r = $quandl->getSearch("crud oil", 1, 10);
		$this->assertEquals(10, count($r->docs),
			"TEST getSearch count");
	}

	private function _testCache($force_curl=false) {
		$quandl = new Quandl($this->api_key);
		$quandl->force_curl = $quandl->no_ssl_verify = $force_curl;
		$quandl->cache_handler = array($this, "cacheHandler");
		$r = $quandl->getSymbol($this->symbol, $this->dates);
		$count = count($r->data);
		$this->assertFalse($quandl->was_cached, 
			"TEST was_cache should be false");

		$r = $quandl->getSymbol($this->symbol, $this->dates);
		$this->assertEquals($count, count($r->data), 
			"TEST count before and after cache should match");

		$this->assertTrue($quandl->was_cached, 
			"TEST was_cache should be true");
	}

	private function _testGetSymbol($format, $length, $force_curl=false) {
		$quandl = new Quandl($this->api_key, $format);
		$quandl->force_curl = $quandl->no_ssl_verify = $force_curl;
		$r = $quandl->getSymbol($this->symbol, $this->dates);
		$quandl_format = $format;
		if(is_object($r)) {
			$r = serialize($r);
			$quandl_format = "json";
		}

		$this->assertGreaterThan(
			$length,
			strlen($r), 
			"TEST $format length");
		
		$this->assertEquals(
			"https://www.quandl.com/api/v1/datasets/{$this->symbol}.{$quandl_format}?trim_start={$this->dates['trim_start']}&trim_end={$this->dates['trim_end']}&auth_token={$this->api_key}",
			$quandl->last_url,
			"TEST $format url");
	}
}
?>