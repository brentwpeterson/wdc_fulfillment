<?php

class Wdc_Fulfillment_Model_Rest_Client extends Wdc_Fulfillment_Model_Rest_Request
{
	protected $url;
	protected $verb;
	protected $requestBody;
	protected $requestLength;
	protected $username;
	protected $password;
	protected $acceptType;
	protected $responseBody;
	protected $responseInfo;

	public function processClient($url = null, $verb = 'POST', $acceptType = 'application/json', $requestBody = null, $username = null, $password = null)
	{		
		$this->url			= $url;
		$this->verb			= $verb;
		$this->requestBody		= $requestBody;
		$this->requestLength            = 0;
		$this->username			= $username;
		$this->password			= $password;
		$this->acceptType		= $acceptType;
		//	other types include
		//		application/json
		//		application/xml
		// 		text/html
		//		text/xml
		$this->responseBody		= null;
		$this->responseInfo		= null;
		
		if($this->requestBody !== null)
		{			
			$this->buildPostBody();
		}
		
		return $this;
	}

	public function flush()
	{
		$this->requestBody		= null;
		$this->requestLength	= 0;
		$this->verb				= 'POST';
		$this->responseBody		= null;
		$this->responseInfo		= null;
		
		return $this;
	}

	public function execute()
	{
		$resp = '';
		$ch = curl_init();
		$this->setAuth($ch);

		try
		{
			switch(strtoupper($this->verb))
			{
				case 'GET':
					$resp = $this->executeGet($ch);
					break;
				case 'POST':				
					$resp = $this->executePost($ch);
					break;
				case 'PUT':
					$resp = $this->executePut($ch);
					break;
				default:
					throw new InvalidArgumentException('Current verb(' . $this->verb . ') is an invalid REST verb.');
			}
		}
		catch(InvalidArgumentException $e)
		{
			curl_close($ch);
			throw $e;
		}
		catch(Exception $e)
		{
			curl_close($ch);
			throw $e;
		}
		return $resp;
	}

	public function buildPostBody($data = null)
	{
		$data =($data !== null) ? $data : $this->requestBody;
		if( is_string($data) && substr($data,0,5) == '<?xml') {//Allow sending of XML
			$this->requestBody = $data;
			return;
		}

		switch($this->acceptType) {
			case "text/html":
			case "application/json":
				if(!is_array($data))
				{
					$this->requestBody = $data;
				} else {
					$data = http_build_query($data, '', '&');
					$this->requestBody = $data;
				}
				break;
			case "text/xml":
			case "application/xml":
				$this->requestBody = $data;
				break;
		}
	}

	protected function executeGet($ch)
	{
		$this->doExecute($ch);
	}

	protected function executePost($ch)
	{
		if(!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}
		$this->requestLength = strlen($this->requestBody);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
		curl_setopt($ch, CURLOPT_POST, 1);

		return $this->doExecute($ch);
	}

	protected function executePut($ch)
	{
		if(!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}
		$this->requestLength = strlen($this->requestBody);

		$fh = fopen('php://memory', 'rw');
		fwrite($fh, $this->requestBody);
		rewind($fh);

		curl_setopt($ch, CURLOPT_INFILE, $fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, $this->requestLength);
		curl_setopt($ch, CURLOPT_PUT, true);

		$this->doExecute($ch);

		fclose($fh);
	}

	protected function executeDelete($ch)
	{
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		$this->doExecute($ch);
	}

	protected function doExecute(&$curlHandle)
	{
		$this->setCurlOpts($curlHandle);

		//Log OUTGOING
		$outgoing = date("Y-m-d H:i:s").", OUTGOING, ".$this->requestBody."\n";
//		file_put_contents("/var/log/restclient.log",$outgoing, FILE_APPEND);
        //        Mage::log($outgoing);
	//	Mage::log($outgoing, null, 'fulfillment.log');
		$this->responseBody = curl_exec($curlHandle);
		$this->responseInfo	= curl_getinfo($curlHandle);
		$error = curl_error($curlHandle);
		if( !empty($error) ) {
			$contents = date('Y-m-d H:i:s').", CURL ERROR, {$error}\n";
		} else {
			if( is_array($this->responseBody) ) {
				$response = implode("::", $this->responseBody);
			} else {
				$response = $this->responseBody;
			}
			$contents = date('Y-m-d H:i:s').", RESPONSE, {$response}\n";
		}
		//Log all of these

//		Mage::log($contents, null, 'fulfillment.log');
		curl_close($curlHandle);
		return $response;
	}

	protected function setCurlOpts(&$curlHandle)
	{
		curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);//Not the 'proper' fix, but it works, unlike the 'proper' fix...
                curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
		curl_setopt($curlHandle, CURLOPT_URL, $this->url);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		switch($this->acceptType) {
			case "text/html":
			case "application/json":
				curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array('Accept: ' . $this->acceptType));
				break;
			case "text/xml":
			case "application/xml":
				$header[] = "Content-type: text/xml";
				$header[] = "Content-length: ". strlen($this->requestBody)."\r\n";
				$header[] = $this->requestBody;
				curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $header);//array("ContentType: $this->acceptType"));
				break;
		}

	}

	protected function setAuth(&$curlHandle)
	{
		if($this->username !== null && $this->password !== null)
		{
			curl_setopt($curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curlHandle, CURLOPT_USERPWD, $this->username . ':' . $this->password);
		}
	}

	public function getAcceptType()
	{
		return $this->acceptType;
	}

	public function setAcceptType($acceptType)
	{
		$this->acceptType = $acceptType;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}

	public function getResponseBody()
	{
		return $this->responseBody;
	}

	public function getResponseInfo()
	{
		return $this->responseInfo;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function setUrl($url)
	{
		$this->url = $url;
	}

	public function getUsername()
	{
		return $this->username;
	}

	public function setUsername($username)
	{
		$this->username = $username;
	}

	public function getVerb()
	{
		return $this->verb;
	}

	public function setVerb($verb)
	{
		$this->verb = $verb;
	}
}
?>