<?php
//--------------------------------------------------------------
// Class: Quandl API
//--------------------------------------------------------------

class Quandl {

	public $api_key;
	public $default_format = "csv";
	public $cache_handler = null;
	public $was_cached = false;
	private $params;

	private static $base = "https://www.quandl.com/api/v1/datasets";
	private static $base_multi = "https://quandl.com/api/v1/multisets";
	private static $base_lists = "http://www.quandl.com/api/v2/datasets";
	
	// The constructor accepts an optional api_key and an 
	// array of params. The params array may contain any key=>value
	// pair that is supported by Quandl. All params will be appended
	// to the request URL.
	// If you prefer, you may add the params later, see __set below.
	// Example: $quandl = new Quandl("asd123", ["trim_end"=>"yesterday"])
	public function __construct($api_key=null, $params=[]) {
		$this->api_key = $api_key;
		$this->params = $params;
	}

	// Magic setter handles all calls to $quandl->unknown_var
	// This is used to set params outside of the constructor.
	// Example: $quandl->trim_start = "today-300 days";
	public function __set($key, $value) {
		if($key == "trim_start" or $key == "trim_end")
			$value = self::convertToQuandlDate($value);
		$this->params[$key] = $value;
	}

	// Magic getter, for completeness
	public function __get($key) {
		return $this->params[$key];
	}

	// getCsv returns CSV data for a quandl symbol
	public function getCsv($symbol=null) {
		return $this->getData($symbol, "csv");
	}

	// getJson returns JSON data for a quandl symbol
	public function getJson($symbol=null) {
		return $this->getData($symbol, "json");
	}

	// getObject returns a PHP object data for a quandl symbol
	public function getObject($symbol=null) {
		return json_decode($this->getJson($symbol));
	}

	// getXml returns XML data for a quandl symbol
	public function getXml($symbol=null) {
		return $this->getData($symbol, "xml");
	}

	// search returns a data object with Quandle document results
	public function search($query, $per_page=null, $page=null) {
		$this->query = $query;
		$per_page and $this->per_page = $per_page;
		$page and $this->page = $page;
		return $this->getObject();
	}

	// 
	public function getList($source, $per_page=300, $page=1, $format=null) {
		$url = $this->getListUrl($source, $per_page, $page, $format);
		return $this->executeDownload($url);
	}

	// getData returns data in any format for a given symbol.
	// Normally, you should use the getCsv, getJson or getXml
	// which will call getData.
	public function getData($symbol=null, $format=null) {
		$url = $this->getUrl($symbol, $format);
		return $this->executeDownload($url);
	}

	// getUrl returns the complete URL for making a request to Quandl.
	// Normally, you would not need to use it but it is publicly exposed
	// for convenience.
	// Note that $symbol may be an array of symbols and in this case, 
	// may be either the slash notation or dot notation, and may include
	// the column selector.
	public function getUrl($symbol=null, $format=null) {
		$is_multi = is_array($symbol);
		$format or $format = $this->default_format;
		$params = [];
		
		if($is_multi) {
			$base   = self::$base_multi;
			$result = "$base.$format";
			$params["columns"] = self::convertSymbolsToMulti($symbol);
		}
		else {
			$base   = self::$base;
			$result = $symbol === null 
				? "$base.$format" 
				: "$base/$symbol.$format";
		}

		foreach($this->params as $k=>$v)
			$params[$k] = $v;

		$this->api_key and $params['auth_token'] = $this->api_key;
		$params and $result .= "?" . http_build_query($params);

		return $result;
	}

	// getListUrl returns a URL to the list of symbols in a 
	// given source
	public function getListUrl($source, $per_page=300, $page=1, $format=null) {
		$format or $format = $this->default_format;
		$base = self::$base_lists;
		$params = [
			 "query"       => "*",
			 "source_code" => $source,
			 "per_page"    => $per_page,
			 "page"        => $page,
		];
		$this->api_key and $params['auth_token'] = $this->api_key;
		$params = http_build_query($params);
		return "$base.$format?$params";
	}

	// executeDownload gets a URL, and returns the downloaded document
	// If a cache_handler is set, it will call it to get a document 
	// from it, and ask it to store the downloaded object where applicable.
	private function executeDownload($url) {
		$this->was_cached = false;
		if($this->cache_handler != null) {
			$data = call_user_func($this->cache_handler, "get", $url);
			if($data) {
				$this->was_cached = true;
			}
			else {
				$data = file_get_contents($url);
				call_user_func($this->cache_handler, "set", $url, $data);
			}
		}
		else {
			$data = file_get_contents($url);
		}

		return $data;
	}

	// convertToQuandlDate converts any time string supported by
	// PHP (e.g. "today-30 days") to the format needed by Quandl
	private static function convertToQuandlDate($time_str) {
		return date("Y-m-d", strtotime($time_str));
	}

	// convertSymbolsToMulti converts an array of symbols to
	// the format neede dfor a multiset request. In essence, it
	// just replaces slashes with dots and returns the comma 
	// delimited list.
	private static function convertSymbolsToMulti($symbols_array) {
		$result = [];
		foreach($symbols_array as $symbol) {
			$result[] = str_replace("/", ".", $symbol);
		}
		return implode(",", $result);
	}
}
	
?>