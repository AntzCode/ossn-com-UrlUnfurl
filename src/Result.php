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
use UrlUnfurl\Result\Type;
use UrlUnfurl\Url;
use UrlUnfurl\HtmlDocument;
use UrlUnfurl\Result\Type\Facebook;
use UrlUnfurl\Result\Type\Twitter;
use UrlUnfurl\Result\Type\OEmbed;
use UrlUnfurl\Result\Type\Derived;
use UrlUnfurl\Result\Type\Error;
use UrlUnfurl\Result\Type\Incomplete;

class Result
{
	protected static $db;
	public $url;
	public $derived;
	public $facebook;
	public $twitter;
	public $oembed;
	public $invalid;
	public $error;

	public function __construct(){
		self::$db = Database::getDb();
		$this->derived = new Derived();
		$this->facebook = new Facebook();
		$this->twitter = new Twitter();
		$this->oembed = new OEmbed();
	}

	public function isValidType($type){
		if(in_array($type, [
			Type::TYPE_FACEBOOK,
			Type::TYPE_TWITTER,
			//Type::TYPE_OEMBED,
			Type::TYPE_DERIVED,
			//Type::TYPE_COMBINED,
		])){
			if(!is_null($this->{$type}) && !is_null($this->{$type}->getTitle()) && !is_null($this->{$type}->getDescription()) && !is_null($this->{$type}->getImage())){
				return true;
			}
		}
		return false;
	}

	public function getType($type){

		switch($type){

			case Type::TYPE_FACEBOOK:
				return $this->facebook;

			case Type::TYPE_TWITTER:
				return $this->twitter;

			case Type::TYPE_DERIVED:
				return $this->derived;

			case Type::TYPE_INCOMPLETE:
				$incompleteType = new Incomplete();
				return $incompleteType;

			case Type::TYPE_ERROR:
				$errorType = new Error();
				return $errorType;

			case Type::TYPE_OEMBED:
			case Type::TYPE_COMBINED:
			default:
				return null;
		}
	}


	public function resetType($type){

		switch($type){

			case Type::TYPE_FACEBOOK:

				foreach($this->facebook->images as $image){
					HtmlDocument::deleteImage($image->filename);
				}
				break;

			case Type::TYPE_TWITTER:

				foreach($this->twitter->images as $image){
					HtmlDocument::deleteImage($image->filename);
				}
				break;

			case Type::TYPE_DERIVED:

				foreach($this->derived->images as $image){
					HtmlDocument::deleteImage($image->filename);
				}
				break;

			case Type::TYPE_OEMBED:
			case Type::TYPE_COMBINED:
			default:
				return null;
		}

	}

	public static function createFromHtml(HtmlDocument $htmlDocument, Url $url){
		$result = new self();
		$result->url = $url;

		$result->facebook->setData($htmlDocument->getTypeData(Type::TYPE_FACEBOOK, $url));
		$result->twitter->setData($htmlDocument->getTypeData(Type::TYPE_TWITTER, $url));
		$result->derived->setData($htmlDocument->getTypeData(Type::TYPE_DERIVED, $url));

		return $result;
	}

	public static function getById($id){
		return Type::loadOneBy(['id' => $id]);
	}

	public static function getByUrl(Url $url){

		$urlUnfurlResult = new self();

		$resultTypes = Type::loadAllBy([ 'path' => ltrim($url->urlDecoded->path, '/'), 'domain' => $url->urlDecoded->host ], ['time_created' => 'DESC'], 10);

		if($resultTypes){
			// return the record from the database

			$resultTypes = (array) $resultTypes;

			foreach($resultTypes as $resultType){
				if(!is_null($urlUnfurlResult->{$resultType->getType()})){
					$urlUnfurlResult->{$resultType->getType()}->setData($resultType);
				}
			}

			$urlUnfurlResult->setUrl($url);

			return $urlUnfurlResult;

		}else{

			return false;

		}

	}


	public function toJson($properties=null){

		$newObject = new \stdClass();

		if(is_null($properties)){
			// strip non-public columns

			$properties = [
				'title',
				'url',
				'objects',
				'data',
				'facebook',
				'twitter',
				'oembed'
			];

		}

		foreach($properties as $k){

			if(property_exists($this, $k)){

				if(method_exists($this->$k, 'toJson')){

					$output = $this->$k->toJson($properties);
					$newObject->$k = json_decode($output);

				}else{

					if(is_scalar($this->$k)){
						$newObject->$k = $this->$k;
					}

					if(is_null($this->$k)){
						$newObject->$k = null;
					}

				}
			}
		}

		$newObject->properties = $properties;

		return json_encode($newObject);
	}

	public function setUrl(Url $url){
		$this->url = $url;
	}

}
