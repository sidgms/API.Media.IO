<?php

/**
 * APIMediaIO
 * This simple class allow generate cURL request, for convertation your files.
 * 
 * @author Vladimir BUDYLO <webmaster@coolfox.net.ua>
 * @version 0.1.0.0-preview
 */
class APIMediaIO {
	
	/**
	 * @var string Version of class.
	 */
	const VERSION = "0.1.0.0-preview";
	
	/**
	 * @var APIMediaIO
	 */
	private static $instance;
	
	/**
	 * @var string Cookie string for work with converter.
	 */
	private static $cookie = "";
	
	/**
	 * @var array Array which contain two fields, which are required for conversion.
	 */
	private static $post_field = array( "format" => "", "quality" => "" );
	
	/**
	 * @var string Default name of session id name.
	 */
	const cookieName = "JSESSIONID";
	
	/**
	 * This var used as prefix for cURL request.
	 * 
	 * @var string Default URL
	 */
	const URL = "http://media.io";
	
	/**
	 * This var used as User-Agent for cURL request.
	 * 
	 * @var string User-Agent for cURL request. Without this var, media.io return error.
	 */
	const UserAgent = "Mozilla/4.0 (MSIE 6.0; Windows NT 5.0)";
	
	/**
	 * Initializing API and get session id for work with converter.
	 */
	private function __construct()
	{
		$request = self::PrepareRequest();
		$response = curl_exec($request);
		$header = substr($response, 0, curl_getinfo($request, CURLINFO_HEADER_SIZE));
		curl_close($request);
		self::GetCookie($header);
	}
	
	/**
	 * Initializing API
	 * 
	 * @return APIMediaIO
	 */
	public static function Init()
	{
		return (self::$instance === null) ? self::$instance = new self() : self::$instance;
	}
	
	/**
	 * Generate cURL request which will be sended to site media.io. 
	 * 
	 * @param string $fileName Full path to file which will be upload to site media.io
	 */
	public static function SendFile($fileName = "")
	{
		if ( file_exists($fileName) )
		{
			$postdata = array();
			$postdata["file"] = new \CurlFile($fileName, mime_content_type($fileName), basename($fileName));
				
			$request = self::PrepareRequest(self::URL.preg_replace("/^JSESSIONID=/", ":8080/plupload;jsessionid=", self::$cookie));
			curl_setopt($request, CURLOPT_HTTPHEADER, array( "Content-Type: multipart/form-data" ));
			curl_setopt($request, CURLOPT_POSTFIELDS, $postdata);
			$response = curl_exec($request);
			curl_close($request);
		}
	}
	
	/**
	 * This method generates cURL request which went to the site media.io for action start convert. 
	 */
	public static function StartConvert()
	{
		$request = self::PrepareRequest(self::URL.preg_replace("/^JSESSIONID=/", "/api/Conversion/start;jsessionid=", self::$cookie));
		curl_setopt($request, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded" ));
		curl_setopt($request, CURLOPT_POSTFIELDS, self::PreparePOST());
		$response = curl_exec($request);
		curl_close($request);
	}
	
	/**
	 * This method generates cURL request which get status about convertation.
	 * 
	 * @return array Array of JSON objects which contain status about item(sound) convertation.
	 */
	public static function StatusConvert()
	{
		$request = self::PrepareRequest(self::URL.preg_replace("/^JSESSIONID=/", "/api/Status/all;jsessionid=", self::$cookie)."?_=".time());
		curl_setopt($request, CURLOPT_HTTPHEADER, array( "Accept: application/json, text/javascript, *\/*; q=0.01" ));
		$response = curl_exec($request);
		$header_size = curl_getinfo($request, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		curl_close($request);
	
		$jsons = json_decode(substr($response, $header_size));
		return $jsons;
	}
	
	/**
	 * This method get converted files.
	 * 
	 * @return multitype:array Associative array which contain info about item(sound).
	 */
	public static function GetConvertedFiles()
	{
		$results = array();
		$jsons = self::StatusConvert();
		foreach($jsons as $json)
			array_push($results, array(
				"name"		=> $json->outputName
				,"size"		=> $json->outputSize
				,"original"	=> $json->name
				,"source"	=> self::GetFile($json->outputName)
			));
		return $results;
	}
	
	/**
	 * This method download file and return source.
	 * 
	 * @param string $outputName Name which be requested for download
	 * @return string Return file source
	 */
	private static function GetFile($outputName = "")
	{
		$request = self::PrepareRequest(self::URL."/x-accel-download/".$outputName);
		$response = curl_exec($request);
		$header_size = curl_getinfo($request, CURLINFO_HEADER_SIZE);
		curl_close($request);
		return substr($response, $header_size);
	}
	
	/**
	 * This method allow convert two vars to HTTP query-string.
	 * 
	 * @param string $format Format convertation. Etc. mp3, wav, ogg.
	 * @param string $quality Quality of converted files.
	 * @return string String which usable in cURL request for post field.
	 */
	private static function PreparePOST($format = "mp3", $quality = "high")
	{
		return http_build_query(array_merge(self::$post_field, array( "format" => $format, "quality" => $quality )));
	}
	
	/**
	 * This method return cookie form request. And set to private var self::cookie.
	 * 
	 * @param string $header Headers which contained "Set-Cookie" header.
	 * @return string Session name vs. session ID. Example, JSESSIONID=123321
	 */
	private static function GetCookie($header = "")
	{
		preg_match('/^Set-Cookie:\s*([^;]*)/mi', $header, $m);
		parse_str($m[1], $cookies);
		foreach ($cookies as $key => $val)
		if ( strtoupper($key) == self::cookieName )
			return self::$cookie = strtoupper($key)."=".$val;
	}
	
	/**
	 * This method prepared request to the site media.io with defaul(required) params.
	 * 
	 * @param string $url Request URL.
	 * @return resource Prepared cURL request, with default params.
	 */
	private static function PrepareRequest($url = "")
	{
		$request = curl_init();
		curl_setopt($request, CURLOPT_URL,				empty($url) ? self::URL : $url);
		curl_setopt($request, CURLOPT_HEADER,			true);
		curl_setopt($request, CURLOPT_RETURNTRANSFER,	true);
		curl_setopt($request, CURLOPT_ENCODING,			"gzip");
		curl_setopt($request, CURLOPT_USERAGENT,		self::UserAgent);
		if ( !empty(self::$cookie) )
			curl_setopt($request, CURLOPT_COOKIE,		self::$cookie);
		return $request;
	}
	
}
