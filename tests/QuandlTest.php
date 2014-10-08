<?php
//--------------------------------------------------------------
// Unit Tests: Quandl
//--------------------------------------------------------------
require_once "../Quandl.php";

class QuandlTest extends PHPUnit_Framework_TestCase {
	private $api_key  = "DEBUG_KEY";
	private $symbol   = "WIKI/AAPL";
	private $symbols  = ["WIKI/CSCO", "WIKI/AAPL"];
	private $dates    = ["trim_start" => "2014-01-01", "trim_end"   => "2014-02-02"];

	public function tearDown() {
		$this->cache_file and unlink($this->cache_file);
	}

	public function testCsv() {
		$this->helperGetSymbol("csv", "57eea221bafe8a360b54068f7e93a335");
	}

	public function testXml() {
		$this->helperGetSymbol("xml", "baf67cffc877a85304509513374a5a06");
	}

	public function testJson() {
		$this->helperGetSymbol("json", "e692240ceefd1bc9fde29117a6ed5d5f");
	}

	public function testObjecr() {
		$this->helperGetSymbol("object", "0123cfae5111cf1ea3ba93c20406c9bb");
	}

	public function testGetSymbols() {
		$quandl = new Quandl($this->api_key, "json");
		$r = $quandl->getSymbols($this->symbols, $this->dates);
		$sig = md5($r);
		$this->assertEquals("56fdde06b1cc699286b2e3bdaaf40761", $sig,
			"TEST getSymbols checksum");
	}

	public function testGetList() {
		$quandl = new Quandl($this->api_key);
		$r = $quandl->getList("WIKI", 1, 10);
		$this->assertEquals(10, count($r->docs),
			"TEST getList count");
	}

	public function testGetSearch() {
		$quandl = new Quandl($this->api_key);
		$r = $quandl->getSearch("crud oil", 1, 10);
		$this->assertEquals(10, count($r->docs),
			"TEST getSearch count");
	}

	public function testCache() {
		$quandl = new Quandl($this->api_key);
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

	private function helperGetSymbol($format, $checksum) {
		$quandl = new Quandl($this->api_key, $format);
		$r = $quandl->getSymbol($this->symbol, $this->dates);
		$quandl_format = $format;
		if(is_object($r)) {
			$r = serialize($r);
			$quandl_format = "json";
		}

		$sig = md5($r);
		$this->assertEquals(
			$checksum, 
			$sig,
			"TEST $format checksum");
		
		$this->assertEquals(
			"https://www.quandl.com/api/v1/datasets/{$this->symbol}.{$quandl_format}?trim_start={$this->dates['trim_start']}&trim_end={$this->dates['trim_end']}&auth_token={$this->api_key}",
			$quandl->last_url,
			"TEST $format url");
	}
}
?>