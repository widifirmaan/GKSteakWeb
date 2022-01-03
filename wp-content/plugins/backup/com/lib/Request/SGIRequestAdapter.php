<?php

interface SGIRequestAdapter
{
	public function sendPostRequest();
	public function sendGetRequest();
	public function sendRequest($type);
	public function setHeaders($headers);
	public function setUrl($url);
	public function setParams($params);
	public function addHeader($header);
	public function setGetWithQueryParams($getWithQueryParams);
	public function parseResponse();
}
