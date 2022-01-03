<?php

class SGRequest
{
	private static $instance = null;

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = self::createAdapterInstance();
		}

		return self::$instance;
	}

	private static function createAdapterInstance()
	{
		$className = 'SGRequestAdapter'.SG_ENV_ADAPTER;
		require_once(SG_REQUEST_PATH.$className.'.php');
		$adapter = new $className();
		return $adapter;
	}

	private function __construct()
	{

	}

	private function __clone()
	{

	}
}
