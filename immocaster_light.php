<?php

/**
 *
 * @author Julian Wiegel
 * @copyright 2014 Julian Wiegel
 * @licence LGPL
 * @license http://opensource.org/licenses/LGPL-2.1 GNU Lesser General Public License
 *
 * @todo Nothing
 */

class IMMOCASTER_BASE
{
	private $oauth_uri = "rest.immobilienscout24.de/restapi/security/oauth/";
	private $consumer_key;
	private $consumer_secret;
	private $curl;
	
	private $timestamp;
	private $nonce;
	private $request_token;
	private $request_token_secret = "";
	
	private function parse_params($params)
	{
		$keys = array_keys($params);
		$vals = array_values($params);
		
		$url_params = "";
		
		if(count($keys) != count($vals))
		{
			return $url_params;
		}
		
		for($i = 0; $i < count($keys); $i++)
		{
			if ($i == 0)
			{
				$url_params = $url_params ."?" .$keys[$i] ."=" .$vals[$i];
			}
			else
			{
				$url_params = $url_params ."&" .$keys[$i] ."=" .$vals[$i];
			}
		}
		
		return $url_params;
	}
	
	private function get_request_token()
	{
		if(!isset($this->request_token))
		{	
			$this->prepare_curl($this->protocol .$this->oauth_uri ."request_token", "POST", array());
			$data = curl_exec($this->curl);
			$data = explode("&", $data);
			
			$tmp = explode("=", $data[0]);
			$this->request_token = $tmp[1];
			
			$tmp = explode("=", $data[1]);
			$this->request_token_secret = $tmp[1];
		}
	}
	
	private function get_access_token()
	{
		if(!isset($this->access_token))
		{
			if(!isset($this->request_token_verifier))
			{
				setcookie("IMMOCASTER_LIGHT_TMP", $this->request_token ."&" .$this->request_token_secret);
				
				echo "<b>Klicken Sie bitte auf folgenden Link, authorizieren Sie sich bei IS24 und speichern Sie den Code in der Variable 'request_token_verifier'. Dies muss innerhalb 30 Minuten geschehen und ihr Browser muss Cookies erlauben.</b><br><br>";
				echo "<a href='" .$this->protocol .$this->oauth_uri ."confirm_access?oauth_token=" .$this->request_token ."'>" .$this->protocol .$this->oauth_uri ."confirm_access?oauth_token=" .$this->request_token ."</a>";
				
			}
			else
			{
				$tmp = explode("&", $_COOKIE["IMMOCASTER_LIGHT_TMP"]);
				setcookie("IMMOCASTER_LIGHT_TMP", "", 0);
				$this->request_token = $tmp[0];
				$this->request_token_secret = $tmp[1];
				
				$this->prepare_curl($this->protocol .$this->oauth_uri ."access_token", "POST", array());
				$data = curl_exec($this->curl);
				
				echo "<b>Kopieren Sie nun die folgenden zwei Werte in die entsprechenden Variablen. Loeschen Sie den Wert aus 'request_token_verifier'. Fertig.</b><br><br>";
				$data = explode("&", $data);
				$token = explode("=", $data[0]);
				$secret = explode("=", $data[1]);
				echo "Access_Token: " .$token[1] ."<br>";
				echo "Access_Token_Secret: " .$secret[1];
			}
		}
	}
		
	private function create_time_and_nonce()
	{
		$mt = microtime();
		$time = time();
    	$rand = mt_rand();
		
    	$this->nonce = md5($mt . $rand);
    	$this->timestamp = $time;
	}
	
	private function create_signature($url, $method, $req_params)
	{
		if(isset($this->access_token_secret))
		{
			$key = $this->consumer_secret ."&" .$this->access_token_secret;
		}
		else
		{
			$key = $this->consumer_secret ."&" .$this->request_token_secret;
		}
		
		$params = array();
		
		if(!isset($this->request_token))
		{
			$params["oauth_callback"] = "oob";
		}
		
		$params["oauth_consumer_key"] = $this->consumer_key;
		$params["oauth_nonce"] = $this->nonce;
		$params["oauth_signature_method"] = "HMAC-SHA1";
		$params["oauth_timestamp"] = $this->timestamp;
		
		if(isset($this->access_token))
		{
			$params["oauth_token"] = $this->access_token;
		}
		elseif(isset($this->request_token))
		{
			$params["oauth_token"] = $this->request_token;
		}
		if(isset($this->request_token_verifier))
		{
			$params["oauth_verifier"] = $this->request_token_verifier;
		}

		$params["oauth_version"] = "1.0";
    
    	$params = array_merge($params, $req_params);
		ksort($params);
		
		$keys = array_keys($params);
		$values = array_values($params);
		$params_string = ""; 
		for($i = 0; $i < count($keys); $i++)
		{
			$params_string = $params_string .$keys[$i] ."=" .$values[$i] ."&"; 
		}
		$params_string = substr($params_string, 0, -1);

		//signature basestring
		$signature_base = $method ."&" .rawurlencode($url) ."&" .rawurlencode($params_string);
		
		return urlencode(base64_encode(hash_hmac('sha1', $signature_base, $key, true)));
	}
	
	private function create_http_header($url, $method, $params = array())
	{
		$this->create_time_and_nonce();
		
		$http_header = array();
		$http_header[0] = "Authorization: OAuth ";
		
		$header_array = array();
		
		if(!isset($this->request_token))
		{
			$header_array["oauth_callback"] = "oob";
		}
		
		$header_array["oauth_consumer_key"] = $this->consumer_key;
		$header_array["oauth_nonce"] = $this->nonce;
		$header_array["oauth_signature"] = $this->create_signature($url, $method, $params);
		$header_array["oauth_signature_method"] = "HMAC-SHA1";
		$header_array["oauth_timestamp"] = $this->timestamp;
		
		if(isset($this->access_token))
		{
			$header_array["oauth_token"] = $this->access_token;
		}
		elseif(isset($this->request_token))
		{
			$header_array["oauth_token"] = $this->request_token;
		}
		if(isset($this->request_token_verifier))
		{
			$header_array["oauth_verifier"] = $this->request_token_verifier;
		}
		$header_array["oauth_version"] = "1.0";
		
		$header_array = array_merge($header_array, $params);
		ksort($header_array);
		
		$keys = array_keys($header_array);
		$values = array_values($header_array);
		for($i = 0; $i < count($keys); $i++)
		{
			$http_header[0] = $http_header[0] .$keys[$i] ."=\"" .$values[$i] ."\","; 
		}
		$http_header[0] = substr($http_header[0], 0, -1);
		
		$http_header[1] = "User-Agent: IMMOCASTER_LIGHT";
		$http_header[2] = "Accept: application/" .$this->content_type .";strict=true;";
		$http_header[3] = "Content-Type: application/" .$this->content_type .";charset=utf-8";
		
		return $http_header;
	}
	
	public function prepare_curl($url = "", $method, $request)
	{
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($this->curl, CURLOPT_HEADER, 1);

		switch($method)
		{
			case "GET":
				$url_params = $this->parse_params($request);
				curl_setopt($this->curl, CURLOPT_URL, $url .$url_params);
				curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->create_http_header($url, $method, $request));
			break;
			
			case "POST":
				curl_setopt($this->curl, CURLOPT_URL, $url);
				curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->create_http_header($url, $method));
				curl_setopt($this->curl, CURLOPT_POST, TRUE);
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request);
			break;
		}
	}
	
	public function exec_curl()
	{
		return curl_exec($this->curl);
	}
	
	public function IMMOCASTER_BASE($ckey, $csecret, $getreqtoken = FALSE)
	{
		$this->consumer_key = $ckey;
		$this->consumer_secret = $csecret;
		
		$this->get_request_token();
		
		if($getreqtoken == TRUE)
		{
			$this->get_access_token();
		}
	}
}

class IMMOCASTER_LIGHT extends IMMOCASTER_BASE
{
	public $content_type = "json"; //set this to json or xml
	public $protocol = "https://"; //or http
	public $base_uri = "rest.immobilienscout24.de/restapi/api/";
	
	public $request_token_verifier;
	public $access_token;
	public $access_token_secret;	
	
	//Template methods
	public function search_region($params)
	{
		$url = $this->protocol .$this->base_uri ."search/v1.0/search/region";
		
		$this->prepare_curl($url, "GET", $params); //GET MUST be UPPERCASE
		return $this->exec_curl();
	}
	
	public function importexport_realestate_get($params)
	{
		$url = $this->protocol .$this->base_uri ."offer/v1.0/user/me/realestate";
		
		$this->prepare_curl($url, "GET", $params);
		return $this->exec_curl();
	}
}

?>

