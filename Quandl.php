<?php
//--------------------------------------------------------------
// Class: Quandl API
//--------------------------------------------------------------

class Quandl {

	public $api_key;
	public $format;
	public $cache_handler = null;
	public $was_cached    = false;
	public $force_curl    = false;
	public $no_ssl_verify = false; // disable ssl verification for curl
	public $timeout       = false;
	public $last_url;
	public $error;

	private static $url_templates = [
		"symbol"  => 'https://www.quandl.com/api/v1/datasets/%s.%s?%s',
		"search"  => 'https://www.quandl.com/api/v1/datasets.%s?%s',
		"list"    => 'https://www.quandl.com/api/v2/datasets.%s?%s',
	];

	public function __construct($api_key=null, $format="object") {
		$this->api_key = $api_key;
		$this->format = $format;
	}

	// getSymbol returns data for a given symbol.
	public function getSymbol($symbol, $params=null) {
		$url = $this->getUrl("symbol", 
			$symbol, $this->getFormat(), 
			$this->arrangeParams($params));

		return $this->getData($url);
	}

	// getSearch returns results for a search query.
	// CSV output is not supported with this node so if format
	// is set to CSV, the result will fall back to object mode.
	public function getSearch($query, $page=1, $per_page=300) {
		$params = [
			"per_page" => $per_page, 
			"page"     => $page, 
			"query"    => $query,
		];
		$url = $this->getUrl("search", 
			$this->getFormat(true), 
			$this->arrangeParams($params));

		return $this->getData($url);
	}

	// getList returns the list of symbols for a given source.
	public function getList($source, $page=1, $per_page=300) {
		$params = [
			"query"       => "*",
			"source_code" => $source, 
			"per_page"    => $per_page, 
			"page"        => $page, 
		];
		$url = $this->getUrl("list", 
			$this->getFormat(), 
			$this->arrangeParams($params));

		return $this->getData($url);
	}

	// getFormat returns one of the three formats supported by Quandl.
	// It is here for two reasons: 
	//  1) we also allow "object" format. this will be sent to Quandl
	//     as "json" but the getData method will return a json_decoded
	//     output.
	//  2) some Quandl nodes do not support CSV (namely search).
	private function getFormat($omit_csv=false) {
		if (($this->format == "csv" and $omit_csv) or $this->format == "object")
			return "json";

		return $this->format;
	}

	// getUrl receives a kind that points to a URL template and 
	// a variable number of parameters, which will be replaced
	// in the template.
	private function getUrl($kind) {
		$template = self::$url_templates[$kind];
		$args = array_slice(func_get_args(), 1);
		$this->last_url = trim(vsprintf($template, $args), "?&");
		return $this->last_url;
	}

	// getData executes the download operation and returns the result
	// as is, or json-decoded if "object" type was requested.
	private function getData($url) {
		$result = $this->executeDownload($url);
		return $this->format == "object" ? json_decode($result) : $result;
	}

	// executeDownload gets a URL, and returns the downloaded document
	// either from cache (if cache_handler is set) or from Quandl.
	private function executeDownload($url) {
		if ($this->cache_handler == null) 
			$data = $this->download($url);
		else 
			$data = $this->attemptGetFromCache($url);

		return $data;
	}

	// attemptGetFromCache is called if a cache_handler is available.
	// It will call the cache handler with a get request, return the 
	// document if found, and will ask it to store the downloaded 
	// object where applicable.
	private function attemptGetFromCache($url) {
		$this->was_cached = false;
		$data = call_user_func($this->cache_handler, "get", $url);
		if ($data) {
			$this->was_cached = true;
		}
		else {
			$data = $this->download($url);
			$data and call_user_func($this->cache_handler, "set", $url, $data);
		}

		return $data;
	}

	// arrangeParams converts a parameters array to a query string.
	// In addition, we add some patches:
	//  1) trim_start and trim_end are converted from any plain
	//     language syntax to Quandl format
	//  2) api_key is appended
	private function arrangeParams($params) {
		$this->api_key and $params['auth_token'] = $this->api_key;
		if (!$params) return $params;
		
		foreach(["trim_start", "trim_end"] as $v) {
			if (isset($params[$v]) )
				$params[$v] = self::convertToQuandlDate($params[$v]);
		}

		return http_build_query($params);
	}

	// convertToQuandlDate converts any time string supported by
	// PHP (e.g. "today-30 days") to the format needed by Quandl
	private static function convertToQuandlDate($time_str) {
		return date("Y-m-d", strtotime($time_str));
	}

	// download fetches $url with file_get_contents or curl fallback
	// You can force curl download by setting $force_curl to true.
	// You can disable SSL verification for curl by setting 
	// $no_ssl_verify to true (solves "SSL certificate problem")
	private function download($url) {
		if (ini_get('allow_url_fopen') and !$this->force_curl) {
			return $this->simpleDownload($url);
		}

		if (function_exists('curl_version')) {
			return $this->curlDownload($url);
		}

		$this->error = "Enable allow_url_fopen or curl";
		return false;
	}

	private function simpleDownload($url) {
		// Set timeout, doesnt seem to work with ini_set
		// $this->timeout and ini_set('default_socket_timeout', $this->timeout);
		if ($this->timeout) {
			$context = stream_context_create( ['http' => ['timeout' => $this->timeout]] );
			$data = @file_get_contents($url, false, $context);
		}
		else {
			$data = @file_get_contents($url);
		}

		$data or $this->error = ($this->timeout ? "Invalid URL or timed out" : "Invalid URL");
		return $data;
	}

	private function curlDownload($url) {
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$this->timeout       and curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
		$this->no_ssl_verify and curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

		$data  = curl_exec($curl);
		$error = curl_error($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		if ($http_code == "404") {
			$data = false;
			$this->error = "Invalid URL";
		}
		else if ($error) {
			$data = false;
			$this->error = $error;
		}
		return $data;
	}
}
