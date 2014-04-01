<?php

/**
 * Load API-class if not definied autoload.
 */
require_once "APIMediaIO.php";

/**
 * Converter
 * This simple-class allow work with APIMediaIO
 * 
 * @author Владимир
 * @version 0.1.0.0-preview
 */
class Converter {
	
	/**
	 * @var object of APIMediaIO
	 */
	private static $MediaIO = NULL;
	
	/**
	 * @var string Default path to sound-files.
	 */
	const SOUNDS_DIR = "same default directory /var/www/html/... or c:\\windows\\temp\\...";
	
	/**
	 * Initializing converter
	 */
	function __construct()
	{
		if ( is_null(self::$MediaIO) )
			self::$MediaIO = APIMediaIO::Init();
	}
	
	/**
	 * This method add's file to list of files which be converted.
	 * 
	 * @param string $fileName Filename which be add's to list of files which be converted.
	 * @param string $usePrefix Tell method is absolute path to filename or no.
	 */
	public function AddFile($fileName = "", $usePrefix = true)
	{
		if ( !empty($fileName) )
			self::$MediaIO->SendFile($usePrefix ? self::SOUNDS_DIR.$fileName : $fileName);
	}
	
	/**
	 * This method submit of start conversion
	 * 
	 * @param string $filePath This path optional and indicates where you want to save your converted files.
	 */
	public function Submit($filePath = "")
	{
		self::$MediaIO->StartConvert();
		
		$path = empty($filePath) ? self::SOUNDS_DIR : $filePath;
		$path = rtrim($path, SEPARATOR).SEPARATOR;
		
		if ( is_dir($path) )
			if ( $this->IsConverted() )
			{
				$files = self::$MediaIO->GetConvertedFiles();
				foreach ($files as $file)
				{
					$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
					if ( !is_dir($path.$ext) )
						mkdir($path.$ext, NULL, true);
					file_put_contents($path.$ext.SEPARATOR.$file["name"], $file["source"]);
				}
			}
	}
	
	/**
	 * This method notify via return result when conversion completed.
	 *  
	 * @return boolean Result of conversion
	 */
	private function IsConverted()
	{
		try
		{
			do
			{
				$converted = true;
				$jsons = self::$MediaIO->StatusConvert();
				foreach($jsons as $json)
					if ( intval($json->progress)==0 )
					{
						$converted = false;
						break;
					}
			} while ( !$converted );
			return true;
		}
		catch (\Exception $e)
		{
			return false;
		}
	}
	
}
