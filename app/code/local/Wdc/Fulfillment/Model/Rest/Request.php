<?php
class Wdc_Fulfillment_Model_Rest_Request extends Wdc_Fulfillment_Model_Rest_Utils
{
	private $request_vars;
	private $http_accept;
	private $method;
	private $function;
	private $parameters;

	public function __construct()
	{
		$this->request_vars		= array();
		$http_accept_array = explode(",", $_SERVER['HTTP_ACCEPT']);
		$this->http_accept = $http_accept_array[0];
		//$this->http_accept		=(strpos($_SERVER['HTTP_ACCEPT'], 'json')) ? 'json' : 'xml';
		$this->method			= 'get';
		$urlArray				= parse_url($_SERVER['REQUEST_URI']);
		$this->function			= basename($urlArray['path']);
		$this->parameters		= $urlArray['query'];
	}

	public function setMethod($method)
	{
		$this->method = $method;
	}

	public function setRequestVars($request_vars)
	{
		$this->request_vars = $request_vars;
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function getHttpAccept()
	{
		return $this->http_accept;
	}

	public function getRequestVars()
	{
		return $this->request_vars;
	}

	public function getFunction()
	{
		return $this->function;
	}

	public function getParameters()
	{
		return $this->parameters;
	}
}
?>