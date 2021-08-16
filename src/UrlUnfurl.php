<?php
###################################################################################
##               Open Source Social Network (Component/Extension)                ##
##          ~ Unfurl URL's for a preview when posting links on a wall ~          ##
##                                                                               ##
##    @package   UrlUnfurl Component                                             ##
##    @author    AntzCode Ltd                                                    ##
##    @copyright (C) AntzCode Ltd                                                ##
##    @link      https://github.com/AntzCode/ossn-com-UrlUnfurl                  ##
##    @license   GPLv3 https://raw.githubusercontent.com/AntzCode/               ##
##                       ossn-com-UrlUnfurl/main/LICENSE                         ##
##                                                                               ##
###################################################################################

namespace UrlUnfurl;
use UrlUnfurl\Database;

class UrlUnfurl
{
	public static $COMPONENT_ID = 'UrlUnfurl';

	protected static $ossnComponentSettings;

	public static $dbTablePrefix = 'ossn_urlunfurl';

	protected static $instance = null;

	protected $db;

	public static function printHello(){
		echo 'Hello World!';

	}

	public static function getInstance(){

		if(! self::$instance instanceof self){
			self::$instance = new self();
		}

		return self::$instance;

	}

	public function __construct(){

		self::$ossnComponentSettings = (new \OssnComponents())->getComSettings(self::$COMPONENT_ID);

		// for debugging @TODO: remove this
		(new \OssnComponents())->setSettings('UrlUnfurl', ['http_user_agent_html' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.16 (KHTML, like Gecko) Chrome/24.0.1304.0 Safari/537.16']);
		(new \OssnComponents())->setSettings('UrlUnfurl', ['http_user_agent_images' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.16 (KHTML, like Gecko) Chrome/24.0.1304.0 Safari/537.16']);

		$this->objectTempPath = ossn_get_userdata('tmp/urlunfurl/');
		$this->objectStoragePath = ossn_get_userdata('components/urlunfurl/');

		if(!is_dir($this->objectTempPath)) {
			mkdir($this->objectTempPath, 0755, true);
		}

		if(!is_dir($this->objectStoragePath.'original')) {
			mkdir($this->objectStoragePath.'original', 0755, true);
		}

		if(!is_dir($this->objectStoragePath.'large')) {
			mkdir($this->objectStoragePath.'large', 0755, true);
		}

	}

	public static function getPdoHandle(){
		global $Ossn;
		return $Ossn->dbLINK;
	}

	public static function getOssnComponentSettings(){
		return self::$ossnComponentSettings;
	}

	public static function getSettings(string $name){
		$nameUnderscored = Database::stringFromCamelCase($name);
		return self::$ossnComponentSettings->{$nameUnderscored};
	}

	public static function getBaseUrl(){
		return ossn_siteUrl();
	}

	public static function getStorageRootPath(){
		return ossn_get_userdata('components/'.UrlUnfurl::$COMPONENT_ID.'/');
	}

	public static function getImagePath($imageFilename)
	{
		return ossn_get_userdata('components/'.self::$COMPONENT_ID.'/large/'.$imageFilename);
	}

	public static function getImageUrl($imageFilename){

		$url = ossn_site_url('action/urlunfurl/image', false);

		if(strstr($url, '?') !== false){
			$url .= '&img='.$imageFilename;
		}else{
			$url .= '?img='.$imageFilename;
		}

		return $url;

	}

}