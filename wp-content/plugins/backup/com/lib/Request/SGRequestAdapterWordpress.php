<?php

require_once(SG_REQUEST_PATH.'SGIRequestAdapter.php');
require_once(SG_REQUEST_PATH.'SGResponse.php');

class SGRequestAdapterWordpress implements SGIRequestAdapter
{
	private $headers = array();
	private $params = array();
	private $url;
	private $getWithQueryParams = true;

	private $body;
	private $httpCode;
	private $contentType;

	private $stream = false;

	public function __construct()
	{
		$reloadMethod = SGConfig::get('SG_BACKGROUND_RELOAD_METHOD');
		if ($reloadMethod == SG_RELOAD_METHOD_STREAM) {
			$this->stream = true;
		}
	}

	public function setGetWithQueryParams($getWithQueryParams)
	{
		$this->getWithQueryParams = $getWithQueryParams;
	}

	public function addHeader($header)
	{
		$this->headers[] = $header;
	}

	public function setHeaders($headers)
	{
		$this->headers = $headers;
	}

	public function setUrl($url)
	{
		$this->url = $url;
	}

	public function setParams($params)
	{
		$this->params = $params;
	}

	public function getRequestArgs()
	{
		$args = array(
			'headers'     => $this->headers,
			'sslverify'   => false,
			'stream'      => $this->stream,
			'timeout'     => 30
		);

		if (!function_exists("curl_init")) {
			$args['sslverify'] = true;
		}

		return $args;
	}

	public function sendPostRequest()
	{
		$body = null;

		if (count($this->params)) {
			// $body = http_build_query($this->params, '', '&');
			$body = $this->params;
		}

		$args = $this->getRequestArgs();
		$args['body'] = $body;

		$response = wp_remote_post($this->url, $args);
		if ($this->stream && !($response instanceof WP_Error)) {
			$this->body = file_get_contents($response['filename']);
		}
		else {
			$this->body = wp_remote_retrieve_body($response);
		}
		$this->httpCode = wp_remote_retrieve_response_code($response);

		$headers = wp_remote_retrieve_headers($response);
		if ($headers && $headers instanceof Requests_Utility_CaseInsensitiveDictionary) {
			$data = $headers->getAll();
			$this->contentType = $data['content-type'];
		}
		else if ($headers && is_array($headers)) {
			$this->contentType = $headers['content-type'];
		}
		else {
			$this->contentType = '';
		}

		return $this->parseResponse();
	}

	public function sendGetRequest($otherArgs = array())
	{
		$args = $this->getRequestArgs();
		$args = array_merge($args, $otherArgs);

		if (count($this->params)) {
			$this->url = rtrim($this->url, '/').'/';

			if ($this->getWithQueryParams) { //standard get url, with query params
				$this->url .= '?'.http_build_query($this->params, '', '&');
			}
			else { //mvs-styled get url
				$this->url .= implode('/', array_values($this->params));
			}
		}

		$response = wp_remote_get($this->url, $args);
		if ($this->stream && !($response instanceof WP_Error)) {
			$this->body = file_get_contents($response['filename']);
		}
		else {
			$this->body = wp_remote_retrieve_body($response);
		}
		$this->httpCode = wp_remote_retrieve_response_code($response);

		$headers = wp_remote_retrieve_headers($response);
		if ($headers && $headers instanceof Requests_Utility_CaseInsensitiveDictionary) {
			$data = $headers->getAll();
			$this->contentType = $data['content-type'];
		}
		else if ($headers && is_array($headers)) {
			$this->contentType = $headers['content-type'];
		}
		else {
			$this->contentType = '';
		}

		return $this->parseResponse();
	}

	public function sendRequest($type)
	{
		$body = null;

		if ($this->params) {
			// $body = http_build_query($this->params, '', '&');
			$body = $this->params;
		}

		$args = $this->getRequestArgs();
		$args['body'] = $body;
		$args['method'] = $type;

		$response = wp_remote_request($this->url, $args);
		if ($this->stream && !($response instanceof WP_Error)) {
			$this->body = file_get_contents($response['filename']);
		}
		else {
			$this->body = wp_remote_retrieve_body($response);
		}
		$this->httpCode = wp_remote_retrieve_response_code($response);

		$headers = wp_remote_retrieve_headers($response);
		if ($headers && $headers instanceof Requests_Utility_CaseInsensitiveDictionary) {
			$data = $headers->getAll();
			$this->contentType = $data['content-type'];
		}
		else if ($headers && is_array($headers)) {
			$this->contentType = $headers['content-type'];
		}
		else {
			$this->contentType = '';
		}

		return $this->parseResponse();
	}

	public function parseResponse()
	{
		$response = new SGResponse();
		$response->setBody($this->body);
		$response->setHttpStatus($this->httpCode);
		$response->setContentType($this->contentType);

		//if the response is in json format, decode it
		if ($this->contentType == 'application/json') {
			$response->parseJsonBody();
		}

		return $response;
	}
}
