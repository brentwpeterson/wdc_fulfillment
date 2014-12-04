<?php
class Wdc_Fulfillment_Model_Rest_Utils extends Wdc_Fulfillment_Model_Payment_Types
{
	public static function processRequest()
	{
		// get our verb
		$request_method = strtolower($_SERVER['REQUEST_METHOD']);
		$return_obj		= new RestRequest();
		// we'll store our data here
		$data			= array();

		switch($request_method)
		{
			// gets are easy...
			case 'get':
				$data = $_GET;
				break;
				// so are posts
			case 'post':
				$data = $_POST;
				break;
				// here's the tricky bit...
			case 'put':
				// basically, we read a string from PHP's special input location,
				// and then parse it out into an array via parse_str... per the PHP docs:
				// Parses str  as if it were the query string passed via a URL and sets
				// variables in the current scope.
				parse_str(file_get_contents('php://input'), $put_vars);
				$data = $put_vars;
				break;
		}

		// store the method
		$return_obj->setMethod($request_method);

		// set the raw data, so we can access it if needed(there may be
		// other pieces to your requests)
		if(is_array($data))
		$data = array_change_key_case($data); //make all keys lowercase
			
		$return_obj->setRequestVars($data);

		return $return_obj;
	}

	public static function sendResponse($status = 200, $body = '', $http_accept = '')
	{
		$status_header = 'HTTP/1.1 ' . $status . ' ' . RestUtils::getStatusCodeMessage($status);
		// set the status
		header($status_header);

		switch($http_accept) {
			case 'application/json':
				$formatted_body = json_encode($body);
				break;

			case 'application/xml':
				//This function is for writing xml
				function writeXML(XMLWriter $xml, $data, $parent='') {
					foreach($data as $key => $value){
						if(is_array($value)){
							$xml->startElement($key);
							writeXML($xml, $value, $key);
							$xml->endElement();
							continue;
						}
						if(is_numeric($key))
						$xml->writeElement($parent.$key, $value);
						else
						$xml->writeElement($key, $value);
					}
				}

				//Start creating the xml
				$xml = new XmlWriter();
				$xml->openMemory();
				$xml->startDocument('1.0', 'UTF-8');
				$xml->startElement('root');

				//This is where our body goes
				writeXML($xml, $body);

				//Now finish the xml
				$xml->endElement();
				$formatted_body = $xml->outputMemory(true);
				break;

			default:
				$http_accept = 'text/html';

				// this should be templatized in a real-world solution
				$formatted_body = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
							<html>
								<head>
									<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"></meta>
									<title>' . $status . ' ' . RestUtils::getStatusCodeMessage($status) . '</title>
								</head>
								<body>
									<h1>' . RestUtils::getStatusCodeMessage($status) . '</h1>';
				if(is_array($body)) {
					$formatted_body .= '<pre>'. print_r($body, true) . '</pre>';
				}else{
					$formatted_body .= '<p>' . $body . '</p>';
				}

				$formatted_body .= '</body></html>';

				break;
		}

		// set the content type
		header('Content-type: ' . $http_accept);

		// send the body
		echo $formatted_body;
		exit;

	}


	public static function getStatusCodeMessage($status)
	{
		// these could be stored in a .ini file and loaded
		// via parse_ini_file()... however, this will suffice
		// for an example
		$codes = Array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => '(Unused)',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
		);

		return(isset($codes[$status])) ? $codes[$status] : '';
	}
}

?>