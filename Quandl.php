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
		"direct"  => 'https://www.quandl.com/api/v3/%s.%s?%s',
		"symbol"  => 'https://www.quandl.com/api/v3/datasets/%s.%s?%s',
		"search"  => 'https://www.quandl.com/api/v3/datasets.%s?%s',
		"list"    => 'https://www.quandl.com/api/v3/datasets.%s?%s',
		"meta"    => 'https://www.quandl.com/api/v3/datasets/%s/metadata.%s',
		"dbs"     => 'https://www.quandl.com/api/v3/databases.%s?%s',
		"bulk"    => 'https://www.quandl.com/api/v3/databases/%s/data?%s',
	];

	// --- API Methods

	public function __construct($api_key=null, $format="object") {
		$this->api_key = $api_key;
		$this->format = $format;
	}

	// get provides access to any Quandl API endpoint. There is no need
	// to include the format.
	public function get($path, $params=null) {
		$url = $this->getUrl("direct", $path, $this->getFormat(), 
			$this->arrangeParams($params));

		return $this->getData($url);
	}

	// getSymbol returns data for a given symbol.
	public function getSymbol($symbol, $params=null) {
		$url = $this->getUrl("symbol", $symbol, $this->getFormat(), $this->arrangeParams($params));
		return $this->getData($url);
	}

	// getBulk downloads an entire database to a ZIP file.
	public function getBulk($database, $filename, $complete=false) {
		$params = [];
		$params['download_type'] = $complete ? 'complete' : 'partial';
		$url = $this->getUrl("bulk", $database, $this->arrangeParams($params));
		return $this->downloadToFile($url, $filename);
	}

	// getMeta returns metadata for a given symbol.
	public function getMeta($symbol) {
		$url = $this->getUrl("meta", $symbol, $this->getFormat());
		return $this->getData($url);
	}

	// getDatabases returns the list of databases. Quandl limits it to 
	// 100 per page at most.
	public function getDatabases($page=1, $per_page=100) {
		$params = [
			"per_page"    => $per_page, 
			"page"        => $page, 
		];
		$url = $this->getUrl("dbs", $this->getFormat(), 
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
		$url = $this->getUrl("search", $this->getFormat(true), 
			$this->arrangeParams($params));

		return $this->getData($url);
	}

	// getList returns the list of symbols for a given source.
	public function getList($source, $page=1, $per_page=300) {
		$params = [
			"query"         => "*",
			"database_code" => $source, 
			"per_page"      => $per_page, 
			"page"          => $page, 
		];
		$url = $this->getUrl("list", $this->getFormat(), 
			$this->arrangeParams($params));

		return $this->getData($url);
	}

	// --- Private Methods

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
		$mode = $this->downloadMode();
		
		if ($mode == 'simple') return $this->simpleDownload($url);
		if ($mode == 'curl')   return $this->curlDownload($url);

		$this->error = "Cannot download. Please enable allow_url_fopen or curl.";
		return false;
	}

	// downloadToFile fetches $url with file_get_contents or curl fallback
	// You can force curl download by setting $force_curl to true.
	// You can disable SSL verification for curl by setting 
	// $no_ssl_verify to true (solves "SSL certificate problem")
	private function downloadToFile($url, $path) {
		$mode = $this->downloadMode();
		
		if ($mode == 'simple') return $this->simpleDownloadFile($url, $path);
		if ($mode == 'curl')   return $this->curlDownloadFile($url, $path);

		$this->error = "Cannot download. Please enable allow_url_fopen or curl.";
		return false;
	}

	// downloadMode determines if we can download with 
	// file_get_contents/fopen or curl.
	private function downloadMode() {
		if (ini_get('allow_url_fopen') and !$this->force_curl)
			return 'simple';

		if (function_exists('curl_version'))
			return 'curl';

		return 'unknown';
	}

	// simpleDownload gets a URL using file_get_contents and returns its content
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

	// simpleDownloadFile downloads a file with fopen and saves the content to 
	// disk.
	private function simpleDownloadFile($url, $path) {
		if ($this->timeout) {
			$context = stream_context_create( ['http' => ['timeout' => $this->timeout]] );
			$success = @file_put_contents($path, fopen($url, 'r', false, $context));
		}
		else {
			$success = @file_put_contents($path, fopen($url, 'r'));
		}

		$success or $this->error = ($this->timeout ? "Invalid URL or timed out" : "Invalid URL");
		return $success;
	}

	// curlDownload is the curl equivalent of simpleDownload. 
	private function curlDownload($url) {
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true
		];

		return $this->curlExecute($options);
	}

	// curlDownloadFile is the curl equivalent of simpleDownloadFile.
	private function curlDownloadFile($url, $path) {
		$fp = fopen($path, 'w+');

		$options = [
			CURLOPT_URL => $url,
			CURLOPT_FILE => $fp,
			CURLOPT_FOLLOWLOCATION => true
		];

		$response = $this->curlExecute($options);
		
		fclose($fp);

		return $response;
	}

	// curlExecute handles generic curl execution, for DRYing the two other
	// functions that rely on curl.
	private function curlExecute($options) {
		$curl = curl_init();

		curl_setopt_array($curl, $options);

		$this->timeout       and curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
		$this->no_ssl_verify and curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($curl);
		$error = curl_error($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		curl_close($curl);

		if ($http_code == "404") {
			$response = false;
			$this->error = "Invalid URL";
		}
		else if ($error) {
			$response = false;
			$this->error = $error;
		}

		return $response;
	}
}
