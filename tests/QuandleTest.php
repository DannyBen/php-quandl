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
		if($this->cache_file) unlink($this->cache_file);
	}

	public function testGetSymbolCsv() {
		$quandl = new Quandl($this->api_key, "csv");
		$r = $quandl->getSymbol($this->symbol, $this->dates);
		$sig = md5($r);
		$this->assertEquals("57eea221bafe8a360b54068f7e93a335", $sig);

		$expected_url = "https://www.quandl.com/api/v1/datasets/{$this->symbol}.csv?trim_start={$this->dates['trim_start']}&trim_end={$this->dates['trim_end']}&auth_token={$this->api_key}";
		$this->assertEquals($expected_url, $quandl->last_url);
	}

	public function testGetSymbolsJson() {
		$quandl = new Quandl($this->api_key, "json");
		$r = $quandl->getSymbols($this->symbols, $this->dates);
		$sig = md5($r);
		$this->assertEquals("56fdde06b1cc699286b2e3bdaaf40761", $sig);
	}

	public function testGetSymbolObject() {
		$quandl = new Quandl($this->api_key);
		$r = $quandl->getSymbol($this->symbol, $this->dates);
		$sig = md5(serialize($r));
		$this->assertEquals("9a342e768439e282b58e2db3226976fe", $sig);
	}

	public function testGetList() {
		$quandl = new Quandl($this->api_key);
		$r = $quandl->getList("WIKI", 1, 10);
		$this->assertEquals(10, count($r->docs));
	}

	public function testGetSearch() {
		$quandl = new Quandl($this->api_key);
		$r = $quandl->getSearch("crud oil", 1, 10);
		$this->assertEquals(10, count($r->docs));
	}

	public function testCache() {
		$quandl = new Quandl($this->api_key);
		$quandl->cache_handler = array($this, "cacheHandler");
		$r = $quandl->getSymbol($this->symbol, $this->dates);
		$count = count($r->data);
		$this->assertFalse($quandl->was_cached, "Expected was_cache to be false");

		$r = $quandl->getSymbol($this->symbol, $this->dates);
		$this->assertEquals($count, count($r->data), "Expected count before and after cache to match");
		$this->assertTrue($quandl->was_cached, "Expected was_cache to be true");
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
}
?>