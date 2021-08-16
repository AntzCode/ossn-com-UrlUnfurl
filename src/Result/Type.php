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

namespace UrlUnfurl\Result;

use UrlUnfurl\Url;
use UrlUnfurl\HtmlDocument;
use UrlUnfurl\Database;
use UrlUnfurl\Result\Type\Facebook;
use UrlUnfurl\Result\Type\Twitter;
use UrlUnfurl\Result\Type\Derived;
use UrlUnfurl\Result\Type\OEmbed;
use UrlUnfurl\Result\Type\Incomplete;
use UrlUnfurl\Result\Type\Error;
use UrlUnfurl\Database\PersistentObjectInterface;

abstract class Type implements PersistentObjectInterface
{
	const TYPE_FACEBOOK = 'facebook';
	const TYPE_TWITTER = 'twitter';
	const TYPE_OEMBED = 'oembed';

	// type not applicable, data derived from page content
	const TYPE_DERIVED = 'derived';

	const TYPE_INCOMPLETE = 'incomplete';
	const TYPE_ERROR = 'error';

	protected $id = null;
	protected $timeCreated = null;
	protected $title = null;
	protected $description = null;
	protected $image = null;
	protected $type = null;
	protected $domain = null;
	protected $path = null;
	protected $language = null;
	protected $data = null;
	protected $objects = [];

	public function __construct(){

	}

	public function getTitle(){
		return $this->title;
	}

	public function getTimeCreated(){
		return $this->timeCreated;
	}

	public function getDescription(){
		return $this->description;
	}

	public function getImage(){
		return $this->image;
	}

	public function getType(){
		return $this->type;
	}

	public function getDomain(){
		return $this->domain;
	}

	public function getPath(){
		return $this->path;
	}

	public function getLanguage(){
		return $this->language;
	}

	protected function getData(){
		return $this->data;
	}

	public function getHtmlDocument(){
		return $this->data->HtmlDocument;
	}

	public function getUrl(){
		return $this->data->Url;
	}

	public function setHtmlDocument(HtmlDocument $htmlDocument){
		$this->data->HtmlDocument = $htmlDocument;
	}

	public function setUrl(Url $url){
		$this->data->Url = $url;
	}

	public function setTitle($title){
		return $this->title = $title;
	}

	public function setTimeCreated($timeCreated){
		return $this->timeCreated = $timeCreated;
	}

	public function setDescription($description){
		return $this->description = $description;
	}

	public function setImage($image){
		return $this->image = $image;
	}

	public function setDomain($domain){
		return $this->domain = $domain;
	}

	public function setPath($path){
		return $this->path = ltrim($path, '/');
	}

	public function setLanguage($language){
		return $this->language = $language;
	}

	public function persistUnique($wheres)
	{
		Database::delete('_url', ['path' => $this->getPath(), 'domain' => $this->getDomain()], ['id' => 'ASC'], 10000);
		return $this->persist();
	}

	public function persist()
	{
		Database::insert('_url', [
			'title' => $this->title,
			'type' => $this->type,
			'http_status' => $this->getHtmlDocument()->getHttpCode(),
			'domain' => $this->domain,
			'path' => $this->path,
			'image' => $this->image,
			'description' => $this->description,
			'data' => serialize($this->data)
		]);
	}

	public function drop()
	{
		if(isset($this->id)){
			return Database::delete('_url', [ 'id' => $this->id ], ['id' => 'ASC'], 1);
		}
	}

	public function loadBy($wheres){

		if($row = Database::getOneBy('_url', $wheres)){
			$this->id = $row->id;
			$this->title = $row->title;
			$this->domain = $row->domain;
			$this->path = $row->path;
			$this->image = $row->image;
			$this->description = $row->description;
			$this->data = unserialize($row->data);

			return $this;

		}else{
			return false;
		}

	}

	public static function loadAllBy($wheres, $sorting, $limits)
	{
		$rows = Database::getBy('_url', $wheres, ['time_created' => 'DESC'], [0, 10]);

		if(is_array($rows) && count($rows) > 0) {

			$resultTypeItems = [];

			foreach($rows as $row){

				switch($row->type){

					case self::TYPE_FACEBOOK:
						$resultType = new Facebook();
						break;
					case self::TYPE_TWITTER:
						$resultType = new Twitter();
						break;
					case self::TYPE_DERIVED:
						$resultType = new Derived();
						break;
					case self::TYPE_INCOMPLETE:
						$resultType = new Incomplete();
						break;
					case self::TYPE_ERROR:
						$resultType = new Error();
						break;
					case self::TYPE_OEMBED:
						$resultType = new OEmbed();
						break;
				}

				$resultType->id = $row->id;
				$resultType->title = $row->title;
				$resultType->domain = $row->domain;
				$resultType->path = $row->path;
				$resultType->image = $row->image;
				$resultType->description = $row->description;
				$resultType->data = unserialize($row->data);
				$resultTypeItems[] = $resultType;

			}

			return $resultTypeItems;

		}else{
			return [];
		}

	}

	public static function loadOneBy($wheres)
	{
		$row = Database::getOneBy('_url', $wheres);

		if(is_object($row)){

			switch($row->type){

				case self::TYPE_FACEBOOK:
					$resultType = new Facebook();
					break;
				case self::TYPE_TWITTER:
					$resultType = new Twitter();
					break;
				case self::TYPE_DERIVED:
					$resultType = new Derived();
					break;
				case self::TYPE_INCOMPLETE:
					$resultType = new Incomplete();
					break;
				case self::TYPE_ERROR:
					$resultType = new Error();
					break;
				case self::TYPE_OEMBED:
					$resultType = new OEmbed();
					break;
			}

			$resultType->id = $row->id;
			$resultType->title = $row->title;
			$resultType->domain = $row->domain;
			$resultType->path = $row->path;
			$resultType->image = $row->image;
			$resultType->description = $row->description;
			$resultType->data = unserialize($row->data);

			return $resultType;

		}

		return null;

	}

	public function getImages(){

		$images = [];

		if(isset($this->data->images)){
			foreach($this->data->images as $image){
				$images[] = $image;
			}
		}

		return $images;

	}

	public function resetData(){
		$this->id = null;
		$this->httpStatus = null;
		$this->timeCreated = null;
		$this->title = null;
		$this->image = null;
		$this->description = null;
		$this->domain = null;
		$this->path = null;
		$this->language = null;
		$this->data = (object) [ 'Url' => null, 'HtmlDocument' => null ];
		$this->objects = [];
	}

	public function setData($data){

		if(is_object($data)){

			foreach(get_object_vars($data) as $k => $v){
				$k2 = Database::stringToCamelCase($k);
				if(property_exists($this, $k2)){
					$this->$k2 = $v;
				}
			}

		}else{
			$this->resetData();
		}

	}

	public function toJson($properties=null){

		$newObject = new \stdClass();

		if(is_null($properties)){
			// strip non-public columns
			$properties = [
				'id',
				'type',
				'title',
				'image',
				'description',
				'objects',
				'data'
			];
		}

		foreach($properties as $k){

			if(property_exists($this, $k)){

				if(method_exists($this->$k, 'toJson')){

					$newObject->$k = json_decode($this->$k->toJson($properties));

				}else{

					if(is_array($this->$k)){

						$newObject->$k = [];

						foreach($this->$k as $v){

							if(method_exists($v, 'toJson')){

								$newObject->$k[] = $v->toJson($properties);

							}else{

								if(is_scalar($v)){
									$newObject->$k[] = $v;
								}

								if(is_null($v)){
									$newObject->$k[] = null;
								}
							}
						}

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
		}

		return json_encode($newObject);

	}

}
