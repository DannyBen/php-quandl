<?php
//--------------------------------------------------------------
// Tests: Quandl
//--------------------------------------------------------------
require_once __DIR__ . "/../Quandl.php";

if (!class_exists("PHPUnit_Framework_TestCase")) {
	class PHPUnit_Framework_TestCase extends PHPUnit\Framework\TestCase {}
}

class QuandlTest extends PHPUnit_Framework_TestCase {
	private $api_key  = "DEBUG_KEY";

	private $symbol     = "WIKI/AAPL";
	private $symbols    = ["WIKI/CSCO", "WIKI/AAPL"];
	private $dates      = ["trim_start" => "2014-01-01", "trim_end" => "2014-02-02"];
	private $cache_file = false;
	private $premium_database = null;

	protected function setup() {
		if (getenv('QUANDL_KEY')) {
			$this->api_key = getenv('QUANDL_KEY');
		}
		if (getenv('QUANDL_PREMIUM')) {
			$this->premium_database = getenv('QUANDL_PREMIUM');
		}
		if (!ini_get('allow_url_fopen')) {
			print("Aborted.\nThe tests require 'allow_url_fopen'.\nSet it in your php.ini.");
			exit(1);
		}
		if (!function_exists('curl_version')) {
			print("Aborted.\nThe tests require curl and PHP curl.\nMake sure it is installed and configured.");
			exit(1);
		}
	}

	protected function tearDown() {
		$this->cache_file and unlink($this->cache_file);
	}

	public function testGet() {
		$quandl = new Quandl($this->api_key);
		$r = $quandl->get("datasets/WIKI/AAPL", ['rows' => 5]);

		$this->assertEquals('WIKI', $r->dataset->database_code);
		$this->assertEquals(5, count($r->dataset->data));
	}

	public function testCsv() {
		$this->_testGetSymbol("csv", 2800);
	}

	public function testXml() {
		$this->_testGetSymbol("xml", 14000);
	}

	public function testJson() {
		$this->_testGetSymbol("json", 4200);
	}

	public function testObject() {
		$this->_testGetSymbol("object", 7400);
	}

	public function testCurl() {
		$this->_testGetSymbol("csv", 2800, true);
	}

	public function testBulk() {
		$this->_testBulk();
	}
	
	public function testBulkWithCurl() {
		$this->_testBulk(true);
	}

	public function testInvalidUrl() {
		$quandl = new Quandl($this->api_key, "json");
		$r = $quandl->getSymbol("INVALID/SYMBOL", $this->dates);
		$this->assertEquals($quandl->error, "Invalid URL");
	}

	public function testGetList() {
		$quandl = new Quandl($this->api_key);
		$r = $quandl->getList("WIKI", 1, 10);
		$this->assertEquals(10, count($r->datasets));
		$this->assertEquals("WIKI", $r->datasets[0]->database_code);
	}

	public function testGetSearch() {
		$quandl = new Quandl($this->api_key);
		$r = $quandl->getSearch("crud oil", 1, 10);
		$this->assertEquals(10, count($r->datasets));
	}

	public function testGetMeta() {
		$quandl = new Quandl($this->api_key);
		$r = $quandl->getMeta("WIKI/AAPL");
		$this->assertEquals('AAPL', $r->dataset->dataset_code);
		$this->assertEquals('WIKI', $r->dataset->database_code);
	}

	public function testGetDatabases() {
		$quandl = new Quandl($this->api_key);
		$r = $quandl->getDatabases(1, 5);
		$this->assertEquals(5, count($r->databases));
		$this->assertTrue(array_key_exists('database_code', $r->databases[0]));
	}

	public function testCache() {
		$quandl = new Quandl($this->api_key);
		$quandl->cache_handler = array($this, "cacheHandler");
		$r = $quandl->getSymbol($this->symbol, $this->dates);
		$count = count($r->dataset->data);
		$this->assertFalse($quandl->was_cached);

		$r = $quandl->getSymbol($this->symbol, $this->dates);
		$this->assertEquals($count, count($r->dataset->data));

		$this->assertTrue($quandl->was_cached);
	}

	// ---

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

	private function _testGetSymbol($format, $length, $force_curl=false) {
		$quandl = new Quandl($this->api_key, $format);
		$quandl->force_curl = $quandl->no_ssl_verify = $force_curl;
		$r = $quandl->getSymbol($this->symbol, $this->dates);
		$quandl_format = $format;
		if(is_object($r)) {
			$r = serialize($r);
			$quandl_format = "json";
		}

		$this->assertGreaterThan($length, strlen($r), "Length is shorter ($format)");

		$this->assertEquals(
			"https://www.quandl.com/api/v3/datasets/{$this->symbol}.{$quandl_format}?trim_start={$this->dates['trim_start']}&trim_end={$this->dates['trim_end']}&auth_token={$this->api_key}",
			$quandl->last_url, "URL Mismatch ($format)");
	}

	private function _testBulk($force_curl=false) {
		if (!$this->premium_database) {
			$this->markTestSkipped('Premium database is not available. Use QUANDL_PREMIUM environment variable to set a database for testing');
			return;
		}

		$quandl = new Quandl($this->api_key);
		$quandl->force_curl = $quandl->no_ssl_verify = $force_curl;

		$filename = "tmp.zip";

		@unlink($filename);
		$this->assertFileNotExists($filename);

		$r = $quandl->getBulk($this->premium_database, $filename);

		$this->assertFileExists($filename);
		$this->assertGreaterThan(800, filesize($filename));
	}

}
?>