<?php

class Wdc_Fulfillment_Model_Transport_Curl extends Mage_Core_Model_Abstract
{
	public function sendXml($xmldata, $username, $password, $URL)
	{		
	
		$data = array(
			'username'	=> 'pinkweb',
			'password'	=> 'pinkweb123',
			'token'		=> '7gkimdnf9ndfghkl'		
			);
		
		$ch = curl_init($URL);
		curl_setopt($ch, CURLOPT_MUTE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: x-form-encoded'));
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, array($data, $xmldata));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);
		
		return $output;	
	}
}

